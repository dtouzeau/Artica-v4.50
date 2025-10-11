<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
function OPENDKIM_VERSION():string{
	if(isset($GLOBALS["OPENDKIM_VERSION"])){return $GLOBALS["OPENDKIM_VERSION"];}

	$opendkim=$GLOBALS["CLASS_UNIX"]->find_program("opendkim");

	exec("$opendkim -V 2>&1",$results);
	foreach ($results as $line){
		if(!preg_match("#OpenDKIM Filter v([0-9\.]+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "VERSION NO MATCH: \"$line\"\n";}
			continue;}
		$GLOBALS["OPENDKIM_VERSION"]=$re[1];
		return $GLOBALS["OPENDKIM_VERSION"];

	}
return "";
}


function ARTICAREST_VERSION():string{
    if(isset($GLOBALS["ARTICAREST_VERSION"])){return $GLOBALS["ARTICAREST_VERSION"];}

    $opendkim="/usr/sbin/articarest";
    if(!is_file("$opendkim")){return "0.0.0.0";}
    exec("$opendkim -version 2>&1",$results);
    foreach ($results as $line){
        if(!preg_match("#Artica REST API version\s+([0-9\.]+)#", $line,$re)){
            if($GLOBALS["VERBOSE"]){echo "VERSION NO MATCH: \"$line\"\n";}
            continue;}
        $GLOBALS["ARTICAREST_VERSION"]=$re[1];
        return $GLOBALS["ARTICAREST_VERSION"];

    }
    return "0.0.0.0";
}


function SAFEGUARD_AUTH():string{
    if(isset($GLOBALS["SAFEGUARD_AUTH"])){return strval($GLOBALS["SAFEGUARD_AUTH"]);}

    $opendkim="/opt/quest/sbin/vasd";

    exec("$opendkim -v 2>&1",$results);
    foreach ($results as $line){
        if(!preg_match("#SAS Version\s+([0-9\.]+)#", $line,$re)){
            if($GLOBALS["VERBOSE"]){echo "SAFEGUARD_AUTH: VERSION NO MATCH: \"$line\"\n";}
            continue;}
        $GLOBALS["SAFEGUARD_AUTH"]=$re[1];
        return $GLOBALS["SAFEGUARD_AUTH"];

    }
    return "";
}
function APP_RUSTDESK_VERSION():string{
    if(isset($GLOBALS["APP_RUSTDESK_VERSION"])){return $GLOBALS["APP_RUSTDESK_VERSION"];}
    if(!is_file("/usr/local/bin/hbbr")){return "0.0";}
    exec("/usr/local/bin/hbbr -V 2>&1",$results);
    foreach ($results as $index=>$line){
        if(!preg_match("#hbbr\s+([0-9\.]+)$#", $line,$re)){
            if($GLOBALS["VERBOSE"]){echo "VERSION NO MATCH: \"$line\"\n";}
            continue;
        }
        $GLOBALS["APP_RUSTDESK_VERSION"]=$re[1];
        return $GLOBALS["APP_RUSTDESK_VERSION"];

    }
    return "0.0";

}

function APP_POSTGRES_VERSION(){
    if(isset($GLOBALS["APP_POSTGRES_VERSION"])){return $GLOBALS["APP_POSTGRES_VERSION"];}
    if(!is_file("/usr/local/ArticaStats/bin/pg_config")){return "0.0";}
    exec("/usr/local/ArticaStats/bin/pg_config --version 2>&1",$results);
    foreach ($results as $index=>$line){
        if(!preg_match("#PostgreSQL\s+([0-9\.]+)$#", $line,$re)){
            if($GLOBALS["VERBOSE"]){echo "VERSION NO MATCH: \"$line\"\n";}
            continue;
        }
        $GLOBALS["APP_POSTGRES_VERSION"]=$re[1];
        return $GLOBALS["APP_POSTGRES_VERSION"];

    }
    return "0.0";
}
function GEOIPUPDATE_VERSION(){
	if(isset($GLOBALS["GEOIPUPDATE_VERSION"])){return $GLOBALS["GEOIPUPDATE_VERSION"];}

	$geoipupdate=$GLOBALS["CLASS_UNIX"]->find_program("geoipupdate");

	exec("$geoipupdate -V 2>&1",$results);
	foreach ($results as $index=>$line){
		if(!preg_match("#geoipupdate\s+([0-9\.]+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "VERSION NO MATCH: \"$line\"\n";}
			continue;}
		$GLOBALS["GEOIPUPDATE_VERSION"]=$re[1];
		return $GLOBALS["GEOIPUPDATE_VERSION"];

	}

}

function APP_ARTICAPCAP_VERSION():string{
    if(!is_file("/usr/sbin/articapsniffer")){return "0.0.0";}
    if(isset($GLOBALS["ARTICA_PCAP_VERSION"])){return $GLOBALS["ARTICA_PCAP_VERSION"];}
    $bin="/usr/sbin/articapsniffer";

    exec("$bin -version 2>&1",$results);
    foreach ($results as $num=>$line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^Version:\s+([0-9\.]+)#i",$line,$re)){continue;}
        $GLOBALS["ARTICA_PCAP_VERSION"]=$re[1];
        return $GLOBALS["ARTICA_PCAP_VERSION"];
    }
    return "0.0.0";
}





function FIREHOL_VERSION(){
    $link=null;
	if(isset($GLOBALS["FIREHOL_VERSION"])){return $GLOBALS["FIREHOL_VERSION"];}
    if(is_link("/usr/sbin/firehol")) {
        $link = @readlink("/usr/sbin/firehol");
    }
    if($link==null){return "0.0.0";}
	$path=dirname($link);
	$path=str_replace("../","/usr/",$path);
	if($GLOBALS["VERBOSE"]){echo "FIREHOL: path: $path\n";}

	if(is_file("$path/install.config")) {
		$results = explode("\n", @file_get_contents("$path/install.config"));
		foreach ($results as $num => $line) {
			$line = trim($line);
			if ($line == null) {
				continue;
			}
			if (!preg_match("#VERSION=.*?([0-9\.]+)#", $line, $re)) {
				if ($GLOBALS["VERBOSE"]) {
					echo "FIREHOL: NO MATCH $line;\n";
				}
				continue;
			}
			$GLOBALS["FIREHOL_VERSION"] = $re[1];
			return $GLOBALS["FIREHOL_VERSION"];
		}
	}
}


function WORDPRESS_SRC_VERSION(){
	if(isset($GLOBALS["WORDPRESS_SRC_VERSION"])){return $GLOBALS["WORDPRESS_SRC_VERSION"];}
	$f=explode("\n",@file_get_contents("/usr/share/wordpress-src/wp-includes/version.php"));
	foreach ($f as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#wp_version.*?([0-9\.]+)#i",$line,$re)){continue;}
		$GLOBALS["WORDPRESS_SRC_VERSION"]=$re[1];
		return $GLOBALS["WORDPRESS_SRC_VERSION"];
	}
	return "0.0.0";
}


function APP_DNSCRYPT_PROXY_VERSION(){
	if(isset($GLOBALS["APP_DNSCRYPT_PROXY_VERSION"])){return $GLOBALS["APP_DNSCRYPT_PROXY_VERSION"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("dnscrypt-proxy");
	exec("$bin -version 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^([0-9\.]+)#i",$line,$re)){continue;}
		$GLOBALS["APP_DNSCRYPT_PROXY_VERSION"]=$re[1];
		return $GLOBALS["APP_DNSCRYPT_PROXY_VERSION"];
	}
}


function APP_FILEBEAT_VERSION(){
	if(isset($GLOBALS["APP_FILEBEAT_VERSION"])){return $GLOBALS["APP_FILEBEAT_VERSION"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("filebeat");
	exec("$bin version 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^filebeat version\s+([0-9\.]+)#i",$line,$re)){continue;}
		$GLOBALS["APP_FILEBEAT_VERSION"]=$re[1];
		return $GLOBALS["APP_FILEBEAT_VERSION"];
	}
	return null;
}
function APP_SSHPORTAL_VERSION(){
	if(isset($GLOBALS["APP_SSHPORTAL_VERSION"])){return $GLOBALS["APP_SSHPORTAL_VERSION"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("sshportal");
	exec("$bin -v 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^version\s+([0-9\.]+)#i",$line,$re)){continue;}
		$GLOBALS["APP_SSHPORTAL_VERSION"]=$re[1];
		return $GLOBALS["APP_SSHPORTAL_VERSION"];
	}
    $sum=md5_file($bin);
    if($sum=="271bb583eed291f794b8add709cacdc5"){
        $GLOBALS["APP_SSHPORTAL_VERSION"]="2.0.1";
        return $GLOBALS["APP_SSHPORTAL_VERSION"];
    }
    if($sum=="548b7fec10ee730bf4c434c7eb798f18"){
        $GLOBALS["APP_SSHPORTAL_VERSION"]="2.0.2";
        return $GLOBALS["APP_SSHPORTAL_VERSION"];
    }

	return null;
}




function APP_DOCKER_VERSION():string{
    if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
    $bin=$GLOBALS["CLASS_UNIX"]->find_program("dockerd");
    exec("$bin -v 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#Docker version\s+([0-9\.\-]+),#i",$line,$re)){continue;}
        $GLOBALS[__FUNCTION__]=$re[1];
        return strval($GLOBALS[__FUNCTION__]);
    }
    return "";

}

//
function APP_ZABBIX_AGENT_VERSION():string{
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("zabbix_agentd");
	exec("$bin -V 2>&1",$results);
	foreach ($results as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^zabbix_agentd.*?([0-9\.]+)#i",$line,$re)){continue;}
		$GLOBALS[__FUNCTION__]=$re[1];
		return strval($GLOBALS[__FUNCTION__]);
	}
	return "";
}
function APP_TAILSCALE_VERSION():string{
    if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
    $bin="/usr/sbin/tailscaled";
    exec("$bin -version 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^([0-9\.]+)$#i",$line,$re)){continue;}
        $GLOBALS[__FUNCTION__]=$re[1];
        return strval($GLOBALS[__FUNCTION__]);
    }
    return "";
}

function WP_CLIENT_VERSION(){
	if(isset($GLOBALS["WP_CLIENT_VERSION"])){return $GLOBALS["WP_CLIENT_VERSION"];}
	$bin="/usr/sbin/wp-cli.phar";
	if($GLOBALS["VERBOSE"]){echo "WP_CLIENT_VERSION:$bin\n";}
	exec("$bin --allow-root --version 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^WP-CLI.*?\s+([0-9\.]+)#i",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "WP_CLIENT_VERSION:$line NO MATCH\n";}
			continue;}
		$GLOBALS["WP_CLIENT_VERSION"]=$re[1];
		return $GLOBALS["WP_CLIENT_VERSION"];
	}
	return null;

}




function APP_MULTIPATH_TCP_VERSION(){
	if(isset($GLOBALS["APP_MULTIPATH_TCP_VERSION"])){return $GLOBALS["APP_MULTIPATH_TCP_VERSION"];}
	$dmesg=$GLOBALS["CLASS_UNIX"]->find_program("dmesg");
	$grep=$GLOBALS["CLASS_UNIX"]->find_program("grep");

	$cmdline="$dmesg|$grep MPTCP 2>&1";
	if($GLOBALS["VERBOSE"]){echo "APP_MULTIPATH_TCP_VERSION ---> $cmdline\n";}
	exec($cmdline,$line);

	if($GLOBALS["VERBOSE"]){echo "APP_MULTIPATH_TCP_VERSION ---> (".count($line)." lines)\n";}
	foreach ($line as $ll){
		if($GLOBALS["VERBOSE"]){echo "APP_MULTIPATH_TCP_VERSION ---> ** ** $ll ** **\n";}
		if(preg_match("#MPTCP.*?v([0-9\.]+)#", $ll,$re)){
			$GLOBALS["APP_MULTIPATH_TCP_VERSION"]=$re[1];
			return $GLOBALS["APP_MULTIPATH_TCP_VERSION"];
		}
	}

	$GLOBALS["APP_MULTIPATH_TCP_VERSION"]="0.94";
	return $GLOBALS["APP_MULTIPATH_TCP_VERSION"];
}
function APP_XTABLES_ADDON_VERSION(){
	if(isset($GLOBALS["APP_XTABLES_ADDON_VERSION"])){return $GLOBALS["APP_XTABLES_ADDON_VERSION"];}
	$f=explode("\n",@file_get_contents("/usr/local/share/man/man8/xtables-addons.8"));
	if($GLOBALS["VERBOSE"]){echo "APP_XTABLES_ADDON_VERSION: ".count($f)." lines\n";}

	foreach ($f as $line){
		if(!preg_match("#\.TH xtables-addons.*?v([0-9\.]+)\s+\(#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "APP_XTABLES_ADDON_VERSION: NOMATCH $line\n";}
			continue;}
		if($GLOBALS["VERBOSE"]){echo "APP_XTABLES_ADDON_VERSION: Matches {$re[1]}\n";}
		$GLOBALS["APP_XTABLES_ADDON_VERSION"]=$re[1];
		return $GLOBALS["APP_XTABLES_ADDON_VERSION"];
	}
	return null;
}



function APP_LETSENCRYPT_VERSION(){
	if(isset($GLOBALS["APP_LETSENCRYPT_VERSION"])){return $GLOBALS["APP_LETSENCRYPT_VERSION"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("certbot");
	if($GLOBALS["VERBOSE"]){echo "APP_LETSENCRYPT_VERSION: $bin --version\n";}
	exec("$bin --version 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^certbot\s+([0-9\.]+)#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "APP_LETSENCRYPT_VERSION: $line == NO MATCH\n";}
			continue;}
		if($GLOBALS["VERBOSE"]){echo "letsencrypt version {$re[1]}\n";}
		$GLOBALS["APP_LETSENCRYPT_VERSION"]=$re[1];
		return $GLOBALS["APP_LETSENCRYPT_VERSION"];
	}
	return null;
}


function APP_MILTER_GREYLIST_VERSION(){
	if(isset($GLOBALS["APP_MILTER_GREYLIST_VERSION"])){return $GLOBALS["APP_MILTER_GREYLIST_VERSION"];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("milter-greylist");
	exec("$bin -r 2>&1",$results);
	foreach ($results as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^milter-greylist-([0-9a-z\.]+)#",$line,$re)){continue;}
		$GLOBALS["APP_MILTER_GREYLIST_VERSION"]=$re[1];
		return $GLOBALS["APP_MILTER_GREYLIST_VERSION"];
	}
	return null;
}



function APP_DANTED_VERSION():string{
    if(!is_file("/usr/sbin/danted")){return "0.0.0";}
    if(isset($GLOBALS["APP_DANTED_VERSION"])){return $GLOBALS["APP_DANTED_VERSION"];}
    exec("/usr/sbin/danted -v 2>&1",$array);
    foreach ($array as $line){
        if(preg_match("#Dante\s+([0-9\.]+)#i", $line,$re)){
            $GLOBALS["APP_DANTED_VERSION"]=$re[1];
            if($GLOBALS['VERBOSE']){echo "APP_DANTED_VERSION(), Success!!! {$GLOBALS["APP_DANTED_VERSION"]}\n";}
            return $GLOBALS["APP_DANTED_VERSION"];}

        if($GLOBALS['VERBOSE']){echo "APP_DANTED_VERSION(), $line, not found \n";}
    }

    return "";


}

function APP_PPTP_CLIENT_VERSION(){
    if(!is_file("/usr/sbin/pptp")){return "0.0.0";}
	if(isset($GLOBALS["APP_PPTP_CLIENT_VERSION"])){return $GLOBALS["APP_PPTP_CLIENT_VERSION"];}
	exec("/usr/sbin/pptp --version 2>&1",$array);
	foreach ($array as $line){
		if(preg_match("#pptp version\s+([0-9\.\-]+)#i", $line,$re)){
			$GLOBALS["APP_PPTP_CLIENT_VERSION"]=$re[1];
			if($GLOBALS['VERBOSE']){echo "APP_PPTP_CLIENT_VERSION(), Success!!! {$GLOBALS["APP_PPTP_CLIENT_VERSION"]}\n";}
			return $GLOBALS["APP_PPTP_CLIENT_VERSION"];}

		if($GLOBALS['VERBOSE']){echo "APP_PPTP_CLIENT_VERSION(), $line, not found \n";}
	}

	return null;
}

function APP_MANTICORE_VERSION(){
    if(isset($GLOBALS["APP_MANTICORE_VERSION"])){return $GLOBALS["APP_MANTICORE_VERSION"];}
    $bin="/usr/bin/searchd";
    if(!is_file($bin)){return "";}

    exec("$bin --version 2>&1",$array);
    foreach ($array as $line){
        if(preg_match("#Manticore\s+([0-9\.\-]+)#i", $line,$re)){
            $GLOBALS["APP_MANTICORE_VERSION"]=$re[1];
            if($GLOBALS['VERBOSE']){echo "APP_MANTICORE_VERSION(), Success!!! {$GLOBALS["APP_MANTICORE_VERSION"]}\n";}
            return $GLOBALS["APP_MANTICORE_VERSION"];}

        if($GLOBALS['VERBOSE']){echo "APP_MANTICORE_VERSION(), $line, not found \n";}
    }
    return "";
}
function APP_GREENSQL_VERSION():string{
	if(isset($GLOBALS["APP_GREENSQL_VERSION"])){return $GLOBALS["APP_GREENSQL_VERSION"];}
	$f=explode("\n", @file_get_contents("/usr/share/greensql-console/config.php"));
	foreach ($f as $ligne){
		if(preg_match("#\$version.+?\"([0-9\.]+)\"#", $ligne,$re)){
			$GLOBALS["APP_GREENSQL_VERSION"]=$re[1];
			return $re[1];
		}else{
			if($GLOBALS["VERBOSE"]){echo "\"$ligne\" ->NO MATCH\n";}
		}
	}
    return "";
}

function APP_RDPPROXY_VERSION():string{
	if(isset($GLOBALS["APP_RDPPROXY_VERSION"])){return $GLOBALS["APP_RDPPROXY_VERSION"];}
	$bin="/usr/local/bin/rdpproxy";
	if(!is_file($bin)){return "";}

	exec("$bin --version 2>&1",$array);
	foreach ($array as $line){
		if(preg_match("#ReDemPtion\s+([0-9\.\-]+)#i", $line,$re)){
			$GLOBALS["APP_RDPPROXY_VERSION"]=$re[1];
			if($GLOBALS['VERBOSE']){echo "APP_RDPPROXY_VERSION(), Success!!! {$GLOBALS["APP_RDPPROXY_VERSION"]}\n";}
			return $GLOBALS["APP_RDPPROXY_VERSION"];}

		if($GLOBALS['VERBOSE']){echo "APP_RDPPROXY_VERSION(), $line, not found \n";}
	}
    return "";
}




function APP_WAZHU_VERSION():string{
    if(isset($GLOBALS["APP_WAZHU_VERSION"])){return $GLOBALS["APP_WAZHU_VERSION"];}
    $MasterBin="/var/ossec/bin/wazuh-control";
    chmod($MasterBin,0755);
    exec("$MasterBin info 2>&1",$string);
    foreach ($string as $line){
        if(!preg_match("#WAZUH_VERSION=.*?([0-9\.]+)#",$line,$re)){
            if($GLOBALS["VERBOSE"]){echo "APP_WAZHU_VERSION: no match \"$line\"\n";}
            continue;
        }
        if($GLOBALS["VERBOSE"]){echo "APP_WAZHU_VERSION: FOUND {$re[1]}\n";}
        $GLOBALS["APP_WAZHU_VERSION"]=$re[1];
        return $re[1];
    }
    return "0.0.0";
}

function APP_UFDBCAT_VERSION(){
	if(isset($GLOBALS["APP_UFDBCAT_VERSION"])){return $GLOBALS["APP_UFDBCAT_VERSION"];}
	$MasterBin="/usr/share/artica-postfix/bin/install/rbldnsd";
	chmod($MasterBin,0755);
	exec("$MasterBin -h 2>&1",$string);
	foreach ($string as $line){
		if(!preg_match("#version\s+([0-9\.]+)#",$line,$re)){
			if($GLOBALS["VERBOSE"]){echo "APP_UFDBCAT_VERSION: no match \"$line\"\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "APP_UFDBCAT_VERSION: FOUND {$re[1]}\n";}
		$GLOBALS["APP_UFDBCAT_VERSION"]=$re[1];
		return $re[1];
	}

}

function mimedefang_version():string{
	if(isset($GLOBALS["mimedefang_version"])){return strval($GLOBALS["mimedefang_version"]);}
	$MasterBin=$GLOBALS["CLASS_UNIX"]->find_program("rbldnsd");
	$string=exec("$MasterBin -h 2>&1");
	if(preg_match("#version\s+([0-9\.]+)#",$string,$re)){
		$GLOBALS["mimedefang_version"]=$re[1];
		return $re[1];
	}
    return "";
}



function KLNAGENT_VERSION():string{
	if(isset($GLOBALS[__FUNCTION__])){return strval($GLOBALS[__FUNCTION__]);}

	$f=explode("\n",@file_get_contents("/opt/kaspersky/klnagent64/lib/bin/setup/appdata.pm"));
	foreach ($f as $line){
		if($line==null){continue;}
		if(preg_match("#^VER=([0-9\.]+)#", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}

	}
    return "";
}

function APP_CYRUS_VERSION():string{
	if(isset($GLOBALS[__FUNCTION__])){return intval($GLOBALS[__FUNCTION__]);}
	$binary=$GLOBALS["CLASS_UNIX"]->LOCATE_CYRUS_DELIVER_BIN_PATH();
	exec("$binary 2>&1",$results);
	foreach ($results as $ligne){
		if(preg_match("#421 4\.3\.0\s+([0-9\.]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '$re[1]'\n";}
			return $re[1];
		}
	}
	$GLOBALS[__FUNCTION__]=null;
    return "";
}


function FILEBEAT_VERSION():string{
	if(isset($GLOBALS[__FUNCTION__])){return strval($GLOBALS[__FUNCTION__]);}
	$program=$GLOBALS["CLASS_UNIX"]->find_program("filebeat");
	exec("$program version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#filebeat version\s+([0-9\.]+)#i", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '$re[1]'\n";}
			return $re[1];
		}


		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
	}
    return "";

}
function FRONTAIL_LINUX_VERSION():string{
	if(isset($GLOBALS[__FUNCTION__])){return strval($GLOBALS[__FUNCTION__]);}
	$program=$GLOBALS["CLASS_UNIX"]->find_program("frontail-linux");
	exec("$program --version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#([0-9\.]+)#i", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '$re[1]'\n";}
			return $re[1];
		}


		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
	}
    return "";
}







function MSMTP_VERSION():string{
	if(isset($GLOBALS[__FUNCTION__])){return strval($GLOBALS[__FUNCTION__]);}
	$pdns=$GLOBALS["CLASS_UNIX"]->find_program("msmtp");
	exec("$pdns --version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#msmtp version\s+([0-9\.]+)#i", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '$re[1]'\n";}
			return $re[1];
		}
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
	}
    return "";
}

function GLANCES_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$pdns=$GLOBALS["CLASS_UNIX"]->find_program("glances");
	exec("$pdns --version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#Glances\s+v([0-9\.]+)#i", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '$re[1]'\n";}
			return $re[1];
		}
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
	}
}


function APP_KEEPALIVED_VERSION(): string
{
    if (isset($GLOBALS[__FUNCTION__])) {
        return $GLOBALS[__FUNCTION__];
    }
    $binary = $GLOBALS["CLASS_UNIX"]->find_program("keepalived");
    exec("$binary --version 2>&1", $results);
    foreach ($results as $line) {
        $line = trim($line);
        if (preg_match("#(?:Keepalived V|v)\s*((?:[0-9]+\.?)+)#", trim($line), $re)) {
            $GLOBALS[__FUNCTION__] = $re[1];
            if ($GLOBALS["VERBOSE"]) {
                echo "[" . __FUNCTION__ . "]: Found '$re[1]'\n";
            }
            return $re[1];
        }
        if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
    }
    return "";
}

function APP_SAMHAIN_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$binary=$GLOBALS["CLASS_UNIX"]->find_program("samhain");
	exec("$binary -h 2>&1",$results);
	foreach ($results as $line){
        $line=trim($line);
		if(preg_match("#^samhain\s+.*?([0-9\.]+)#", trim($line),$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '$re[1]'\n";}
			return $re[1];
		}
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
	}
	return "";
}

function SMOKEPING_VERSION():string{
    $results=array();
    if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
    $binary = $GLOBALS["CLASS_UNIX"]->find_program("smokeping");
    if($binary==null){ $GLOBALS[__FUNCTION__]="";return  $GLOBALS[__FUNCTION__];}
    exec("$binary -v 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#([0-9\.]+)#", trim($line),$re)){
            $GLOBALS[__FUNCTION__]=$re[1];
            if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '$re[1]'\n";}
            return $re[1];
        }
        if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
    }
    return "";

}





function APP_SASLAUTHD_VERSION():string{
    $results=array();
    if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
    $binary = $GLOBALS["CLASS_UNIX"]->find_program("saslauthd");
    if($binary==null){ $GLOBALS[__FUNCTION__]="";return  $GLOBALS[__FUNCTION__];}
    exec("$binary -v 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#^saslauthd\s+.*?([0-9\.]+)#", trim($line),$re)){
            $GLOBALS[__FUNCTION__]=$re[1];
            if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '$re[1]'\n";}
            return $re[1];
        }
        if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
    }
    return "";

}



function APP_PDNS_VERSION():string{
	if(isset($GLOBALS[__FUNCTION__])){return strval($GLOBALS[__FUNCTION__]);}
	$pdns=$GLOBALS["CLASS_UNIX"]->find_program("pdns_server");
	exec("$pdns --version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#(Version:|PowerDNS Authoritative Server)\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[2];
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '$re[2]'\n";}
			return $re[2];
		}
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
	}
    return "";
}

function LICENSE_EVAL_POSTED():bool{
    $LicenseEvalPosted=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseEvalPosted"));
    if($LicenseEvalPosted==1){return true;}
    if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){return true;}
    if(!is_file("/etc/artica-postfix/INSTALL_TIME_LOCK")){return true;}
    $install_time=intval(@file_get_contents("/etc/artica-postfix/INSTALL_TIME_LOCK"));
    if($GLOBALS["CLASS_UNIX"]->time_min($install_time)>1440){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicenseEvalPosted",1);
        return false;
    }

    $LicenseEvalPostedTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseEvalPostedTime"));
    if($LicenseEvalPostedTime==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicenseEvalPostedTime",time());
        squid_admin_mysql(2,"{start}:{obtain_evaluation_license}",null,__FILE__,__LINE__);
       $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/key?isDemo=true");
        return true;
    }
    if($GLOBALS["CLASS_UNIX"]->time_min($LicenseEvalPostedTime)<15){
        return true;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicenseEvalPostedTime",time());
    squid_admin_mysql(2,"{start}:{obtain_evaluation_license}",null,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/key?isDemo=true");
    return true;
}

function APP_PDNS_RECURSOR_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$pdns_recursor=$GLOBALS["CLASS_UNIX"]->find_program("pdns_recursor");
	exec("$pdns_recursor --version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#(version:|PowerDNS Recursor)\s+([0-9\.]+)#i", @implode("", $results),$re)){
			$GLOBALS[__FUNCTION__]=$re[2];
			if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '{$re[2]}'\n";}
			return $GLOBALS[__FUNCTION__];
		}

		if(preg_match("#error while loading shared libraries: libboost_context\.so#", $line)){
			$TimeFile="/etc/artica-postfix/pids/libboost_context.time";
			$TimeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($TimeFile);
			if($TimeExec>20){
				$GLOBALS["CLASS_UNIX"]->DEBIAN_INSTALL_PACKAGE("libboost-context1.62.0");
				@unlink($TimeFile);
				@file_put_contents($TimeFile, time());
			}
		}

		if(preg_match("#error while loading shared libraries: liblua5\.3\.so#", $line)){
			$TimeFile="/etc/artica-postfix/pids/liblua53.time";
			$TimeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($TimeFile);
			if($TimeExec>20){
				$GLOBALS["CLASS_UNIX"]->DEBIAN_INSTALL_PACKAGE("liblua5.3-0");
				@unlink($TimeFile);
				@file_put_contents($TimeFile, time());
			}
		}
		if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
	}
	return null;}



function APP_STRONGSWAN_VERSION(){
	echo "strong";
	$unix=new unix();
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin_path=$unix->find_program("ipsec");
	exec("$bin_path version 2>&1",$results);

	foreach ($results as $line) {
		if (preg_match("#(?:Linux strongSwan U|u)\s*((?:[0-9]+\.?)+)#", $line, $re)) {
			$GLOBALS[__FUNCTION__] = $re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}return null;}

function APP_PRIVOXY_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("privoxy");
	exec("$masterbin --version 2>&1",$results);
	foreach ($results as $line){
		if(!preg_match("#version\s+([0-9\.]+)#", $line,$re)){continue;}
		$GLOBALS[__FUNCTION__]=$re[1];
	}
	return $GLOBALS[__FUNCTION__];
	return null;}


function MUNIN_NODE_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$filename="/usr/sbin/munin-node";
	$f=explode("\n",@file_get_contents($filename));
	foreach ($f as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if($GLOBALS["VERBOSE"]){echo "MUNIN_NODE_VERSION(): $ligne\n";}
		if(preg_match("#munin-node v([0-9\.\-]+)#", $ligne,$re)){
			if($GLOBALS["VERBOSE"]){echo "MUNIN_NODE_VERSION(): OK $ligne -> {$re[1]}\n";}
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}
	return null;}


function SEAFILE_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$filename="/usr/share/seafile-server/seahub/seahub/settings.py";
	$f=explode("\n",@file_get_contents($filename));
	foreach ($f as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#SEAFILE_VERSION.*?([0-9\.\-]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}

	return null;}

function PEAR_PACKAGES(){
    $MODULES=array();
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("pear");
	exec("$masterbin list 2>&1",$results);
	foreach ($results as $line){
		if(!preg_match("#(.+?)\s+[0-9\.]+\s+[a-zA-Z]+#", $line,$re)){continue;}
		$MODULES[]=$re[1];
	}
	$GLOBALS[__FUNCTION__]=@implode(";", $MODULES);
	return $GLOBALS[__FUNCTION__];
	}
function IWCONFIG_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("iwconfig");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin --version 2>&1",$results);
	foreach ($results as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(!preg_match("#iwconfig.+?([0-9\.]+)#", $ligne,$re)){if($GLOBALS["VERBOSE"]){echo "IWCONFIG_VERSION():: FAILED {$ligne}\n";}continue;}
		$GLOBALS[__FUNCTION__]=trim($re[1]);
		if($GLOBALS["VERBOSE"]){echo "IWCONFIG_VERSION():: SUCCESS {$re[1]}\n";}
		return $GLOBALS[__FUNCTION__];
	}
	return null;}

function MONIT_UNINSTALL_NGINX():bool{
    $MONIT_UNINSTALL[]="/etc/monit/conf.d/APP_NGINX.monitrc";
    $MONIT_UNINSTALL[]="/etc/monit/conf.d/APP_NGINX_STATS.monitrc";
    $MONIT_UNINSTALL[]="/etc/monit/conf.d/APP_NGINX_PHP.monitrc";

    $RELOAD_MONIT=false;
    foreach ($MONIT_UNINSTALL as $monitfile){
        if(is_file($monitfile)){
            @unlink($monitfile);
            $RELOAD_MONIT=true;
        }
    }
    if($RELOAD_MONIT){
        system("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state reload");
    }
    return true;
}

function FREERADIUS_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("freeradius");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -v 2>&1",$results);
	foreach ($results as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#Version\s+([0-9\.]+)#", $ligne,$re)){
            if($GLOBALS["VERBOSE"]){echo "FREERADIUS_VERSION():: SUCCESS {$ligne}\n";}
            $GLOBALS[__FUNCTION__]=trim($re[1]);
            return $GLOBALS[__FUNCTION__];
        }
        if(preg_match("#FreeRADIUS\s+Version\s+([0-9\.]+)#", $ligne,$re)){
            if($GLOBALS["VERBOSE"]){echo "FREERADIUS_VERSION():: SUCCESS {$ligne}\n";}
            $GLOBALS[__FUNCTION__]=trim($re[1]);
            return $GLOBALS[__FUNCTION__];
        }

		if($GLOBALS["VERBOSE"]){echo "FREERADIUS_VERSION():: FAILED {$re[1]}\n";}
		return $GLOBALS[__FUNCTION__];
	}
	return "0.0.0";
}
function ZIPROXY_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ziproxy");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -h 2>&1",$results);
	foreach ($results as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(!preg_match("#Ziproxy\s+([0-9\.]+)#", $ligne,$re)){if($GLOBALS["VERBOSE"]){echo "ZIPROXY_VERSION():: FAILED {$ligne}\n";}continue;}
		$GLOBALS[__FUNCTION__]=trim($re[1]);
		if($GLOBALS["VERBOSE"]){echo "ZIPROXY_VERSION():: SUCCESS {$re[1]}\n";}
		return $GLOBALS[__FUNCTION__];
	}

	return null;}


function TC_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("tc");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -V 2>&1",$results);
	foreach ($results as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(!preg_match("#iproute2-([a-z0-9\.]+)#", $ligne,$re)){if($GLOBALS["VERBOSE"]){echo "TC_VERSION():: FAILED {$ligne}\n";}continue;}
		$GLOBALS[__FUNCTION__]=trim($re[1]);
		if($GLOBALS["VERBOSE"]){echo "TC_VERSION():: SUCCESS {$re[1]}\n";}
		return $GLOBALS[__FUNCTION__];
	}
	return null;}

function curlftpfs_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("curlftpfs");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin --version 2>&1",$results);
	foreach ($results as $num=>$val){
		if($GLOBALS["VERBOSE"]){echo "curlftpfs_version(): $val\n";}
		if(preg_match("#curlftpfs\s+([0-9\.\-]+)#", $val,$re)){
			$GLOBALS[__FUNCTION__]=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "curlftpfs_version(): ----> '{$GLOBALS[__FUNCTION__]}'\n";}
			return $GLOBALS[__FUNCTION__];
		}
	}
	return null;}

function kibana_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$filename="/usr/share/kibana/package.json";
	$f=explode("\n",@file_get_contents($filename));
	foreach ($f as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#version\":.*?([0-9\.\-]+)\"#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}

	return null;}
function ntopng_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$masterbin="/usr/local/bin/ntopng";
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin --version 2>&1",$results);
	foreach ($results as $num=>$val){
		if($GLOBALS["VERBOSE"]){echo "ntopng_version(): $val\n";}
		if(preg_match("#ntopng.*?v\.([0-9\.]+)#", $val,$re)){
			$GLOBALS[__FUNCTION__]=trim($re[1]);
			return $GLOBALS[__FUNCTION__];
		}

		if(preg_match("#v\.([0-9\.]+)\s+\[Community#", $val,$re)){
			$GLOBALS[__FUNCTION__]=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "ntopng_version():: SUCCESS {$re[1]}\n";}
			return $GLOBALS[__FUNCTION__];
		}

		if(preg_match("#v\.([0-9\.]+)\s+\[Enterprise\/Professional#", $val,$re)){
			$GLOBALS[__FUNCTION__]=trim($re[1]);
			if($GLOBALS["VERBOSE"]){echo "ntopng_version():: SUCCESS {$re[1]}\n";}
			return $GLOBALS[__FUNCTION__];
		}
		if(preg_match("#Version:\s+([0-9\.]+)#", $val,$re)){
            $GLOBALS[__FUNCTION__]=trim($re[1]);
            if($GLOBALS["VERBOSE"]){echo "ntopng_version():: SUCCESS {$re[1]}\n";}
            return $GLOBALS[__FUNCTION__];
        }
	}
	return null;}

function fping_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("fping");
	if(!is_file($masterbin)){return "0.0.0";}
	exec("$masterbin -v 2>&1",$results);
	foreach ($results as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(!preg_match("#Version\s+([0-9\.]+)#", $ligne,$re)){if($GLOBALS["VERBOSE"]){echo "fping_version():: FAILED {$ligne}\n";}continue;}
		$GLOBALS[__FUNCTION__]=trim($re[1]);
		if($GLOBALS["VERBOSE"]){echo "fping_version():: SUCCESS {$re[1]}\n";}
		return $GLOBALS[__FUNCTION__];
	}
	return null;}



function elasticsearch_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$filename="/usr/share/elasticsearch/modules/reindex/plugin-descriptor.properties";
	$f=explode("\n",@file_get_contents($filename));
	foreach ($f as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#^version=([0-9\.]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}
	return null;}

function logstash_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$f=explode("\n",@file_get_contents("/usr/share/logstash/logstash-core/versions-gem-copy.yml"));
	foreach ($f as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#logstash:\s+([0-9\.]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}
	return null;}

function java_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("java");
	exec("$bin_path -version 2>&1",$results);
	foreach ($results as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#openjdk version.*?([0-9\.\_]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}
	return null;}


function oinkmaster_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("oinkmaster");
	exec("$bin_path -V 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#Oinkmaster\s+v([0-9\.a-z]+),#i", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}
	return null;}
function fireqos_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin_path=$GLOBALS["CLASS_UNIX"]->find_program("fireqos");
	exec("$bin_path --help 2>&1",$results);
	foreach ($results as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#^FireQOS\s+([0-9\.]+)(,|$)#i", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "fireqos_version():: SUCCESS -> {$re[1]}\n";}
			return $re[1];
		}
		if($GLOBALS["VERBOSE"]){echo "fireqos_version():: '$ligne' -> FALSE\n";}
	}

	return null;}

function pdns_stats_version(){
	$master=$GLOBALS["CLASS_UNIX"]->find_program("dsc");
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	exec("$master -v 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#dsc version ([0-9\.]+)#i", @implode("", $results),$re)){$GLOBALS[__FUNCTION__]=$re[1]; return $GLOBALS[__FUNCTION__];}

	}
	return null;}

//========================================================================================================================================================
function fail2ban_version(){
	$function=__FUNCTION__;
	if(isset($GLOBALS["fail2ban_version"])){return $GLOBALS["fail2ban_version"];}
	$fail2ban=$GLOBALS["CLASS_UNIX"]->find_program("fail2ban-server");
	if(!is_file($fail2ban)){
		if($GLOBALS["VERBOSE"]){echo "$function: fail2ban-server, no such file...\n";}
		return "0.0.0";}

	exec("$fail2ban -V 2>&1",$results);

	foreach ($results as $md=>$line){
		if(preg_match("#(Fail2Ban v|)([0-9\.]+)#i", $line,$re)){
			$GLOBALS["fail2ban_version"]=$re[2];
			if($GLOBALS["VERBOSE"]){echo "$function: $line FOUND..\n";}
			return $re[2];
		}else{
			if($GLOBALS["VERBOSE"]){echo "$function: $line NOT FOUND..\n";}
		}

	}
	if($GLOBALS["VERBOSE"]){echo "$function: No version found.\n";}
	return "0.0.0";
	return null;}
//========================================================================================================================================================

function ufdbcatVersion(){
	if(isset($GLOBALS["ufdbcatVersion"])){return $GLOBALS["ufdbcatVersion"];}
	exec("/opt/ufdbcat/bin/ufdbcatdd -v 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#ufdbguardd.*?([0-9\.]+)#", $line,$re)){$GLOBALS["ufdbcatVersion"]= $re[1];return $GLOBALS["ufdbcatVersion"];}
	}
	return null;}


function adagent_version($binpath=null):string{
    if(isset($GLOBALS["adagent_version"])){return $GLOBALS["adagent_version"];}
    if($GLOBALS["VERBOSE"]){echo "adagent_version():: Checking $binpath\n";}
    if(!is_file($binpath)){return "";}
    exec("$binpath -v 2>&1",$results);
    foreach ($results as $line){
        if($GLOBALS["VERBOSE"]){echo "adagent_version():: Checking $line\n";}
        if(preg_match("#^(HA-Proxy|HAProxy)\s+version\s+([0-9\.]+)#", trim($line),$re)){
            $GLOBALS["adagent_version"]=$re[2];
            return $re[2];
        }
    }
    return "";
}




function APT_MIRROR_VERSION():string{
    if(isset($GLOBALS["APT_MIRROR_VERSION"])){return $GLOBALS["APT_MIRROR_VERSION"];}
    if(!is_file("/usr/bin/apt-mirror")){return "0.0.0";}
    $dpkg=$GLOBALS["CLASS_UNIX"]->find_program("dpkg");
    exec("$dpkg -s apt-mirror 2>&1",$results);
    foreach ($results as $index=>$line){
        if(!preg_match("#Version:.*?([0-9\.]+)#", $line,$re)){
            if($GLOBALS["VERBOSE"]){echo "VERSION NO MATCH: \"$line\"\n";}
            continue;}
        $GLOBALS["APT_MIRROR_VERSION"]=$re[1];
        return $GLOBALS["APT_MIRROR_VERSION"];

    }
    return "0.0.0";
}


function dbus_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("dbus-daemon");
	exec("$bin --version 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#Bus Daemon\s+([0-9\.]+)#", $line,$re)){$GLOBALS[__FUNCTION__]=$re[1];return $re[1];}
	}

	return null;}

function omega_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("omindex");
	if($GLOBALS["VERBOSE"]){echo "omega_version():: Binary: $bin\n";}
	exec("$bin -V 2>&1",$results);
	foreach ($results as $num=>$line){
		if($GLOBALS["VERBOSE"]){echo "omega_version():: Checking $line\n";}
		if(preg_match("#\s+([0-9\.]+)#i", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "omega_version():: Found {$re[1]}\n";}
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}
	return null;}

function monit_daemon_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("monit");
	if($GLOBALS["VERBOSE"]){echo "monit_daemon_version():: Monit binary: $bin\n";}
	exec("$bin -V 2>&1",$results);
	foreach ($results as $num=>$line){
		if($GLOBALS["VERBOSE"]){echo "monit_daemon_version():: Checking $line\n";}
		if(preg_match("#monit version\s+([0-9\.]+)#i", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "monit_daemon_version():: Found {$re[1]}\n";}
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}
	return null;}
//========================================================================================================================================================




function iscsi_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$iscsiadm=$GLOBALS["CLASS_UNIX"]->find_program("iscsiadm");
	exec("$iscsiadm -V 2>&1",$results);
	foreach ($results as $line){
		if($GLOBALS["VERBOSE"]){echo "iscsi_version: $line\n";}
		if(preg_match("#version\s+([0-9\.\-]+)#i", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}

	return null;}

function WSUSOFFLINE_VERSION(){
	if(isset($GLOBALS["WSUSOFFLINE_VERSION"])){return $GLOBALS["WSUSOFFLINE_VERSION"];}
	$results=explode("\n",@file_get_contents("/usr/share/wsusoffline/sh/versions/installed-version.txt"));
	foreach ($results as $line){
		if(preg_match("#^([0-9\.]+)(.*?)\s+#", $line,$re)){
			$GLOBALS["WSUSOFFLINE_VERSION"]=$re[1].$re[2];
			return $GLOBALS["WSUSOFFLINE_VERSION"];
		}
	}

	return null;}

function APP_SPLUNK_FORWARDER_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$f=explode("\n",@file_get_contents("/opt/splunkforwarder/etc/splunk.version"));
	foreach ($f as $line){

		if(preg_match("#VERSION=([0-9\.]+)#", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
	return null;}

function __debian_version(){
	if(isset($GLOBALS["DEBIAN_VERSION"])){return $GLOBALS["DEBIAN_VERSION"];}
	if(!is_file("/etc/debian_version")){return 0;}
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	$Major=$re[1];
	if(!is_numeric($Major)){return 0;}
	$GLOBALS["DEBIAN_VERSION"]=$Major;
	return $Major;

}


function nmap_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("nmap");
	exec("$binpath -V 2>&1",$results);
	foreach ($results as $ligne){
		if(preg_match("#Nmap version\s+([0-9\.]+)#i", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
	return null;}



function __MSKTUTIL_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$msktutil=$GLOBALS["CLASS_UNIX"]->find_program("msktutil");
	$t=exec("$msktutil --version 2>&1");
	if($GLOBALS["VERBOSE"]){echo "$msktutil -> $t\n";}
	if(preg_match("#msktutil version\s+([0-9\.]+)#", $t,$re)){
		$GLOBALS[__FUNCTION__]=$re[1];
		return $GLOBALS[__FUNCTION__];
	}
	return null;}
function dnsfilterd_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$ufdbguardd_path="/opt/dnsfilterd/bin/dnsfilterd";
	exec("$ufdbguardd_path -v 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#ufdbguardd.*?([0-9\.]+)#", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}
	}
	return null;}





function __DSC_VERSION(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$dsc=$GLOBALS["CLASS_UNIX"]->find_program("dsc");
	$t=exec("$dsc -v 2>&1");
	if($GLOBALS["VERBOSE"]){echo "$dsc -> $t\n";}
	if(preg_match("#dsc version\s+([0-9\.]+)#", $t,$re)){
		$GLOBALS[__FUNCTION__]=$re[1];
		return $GLOBALS[__FUNCTION__];
	}

	return null;}
function __ulogd_version(){
	if(isset($GLOBALS["ULOGDVERSION"])){return $GLOBALS["ULOGDVERSION"];}
	exec("/usr/local/sbin/ulogd -V 2>&1",$results);
	foreach ($results as $line){

		if(preg_match("#ulogd Version\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["ULOGDVERSION"]= $re[1];
			return $GLOBALS["ULOGDVERSION"];
		}
	}

	return null;}



function __tftpd_version(){
	if(isset($GLOBALS["tftpd_version"])){return $GLOBALS["tftpd_version"];}
	$Masterbin="/usr/sbin/in.tftpd";
	if(!is_file($Masterbin)){return null;}
	@chmod($Masterbin,0755);
	exec("$Masterbin -V 2>&1",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^tftp-hpa\s+([0-9\.]+),#",$line,$re)){continue;}
		$GLOBALS["tftpd_version"]=$re[1];
	}

	if(isset($GLOBALS["tftpd_version"])){return $GLOBALS["tftpd_version"];}

	return null;}




function __debian_version_name(){
	if(isset($GLOBALS["PRETTY_NAME"])){return $GLOBALS["PRETTY_NAME"];}
	if(!is_file("/etc/os-release")){return null;}
	$f=explode("\n",@file_get_contents("/etc/os-release"));
	foreach ($f as $line){

		if(preg_match("#PRETTY_NAME=\"(.+?)[0-9]+\s+(.*?)\"#", $line,$re)){
			$debian_version=trim(@file_get_contents("/etc/debian_version"));
			$GLOBALS["PRETTY_NAME"]=$re[1]." $debian_version {$re[2]}";
			return $GLOBALS["PRETTY_NAME"];
		}
	}
	return null;
}
function __ArchStruct(){
	if(isset($GLOBALS["__ArchStruct"])){return $GLOBALS["__ArchStruct"];}
	$uname=$GLOBALS["CLASS_UNIX"]->find_program("uname");
	exec("$uname -m 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#i[0-9]86#", $line)){
			$GLOBALS["__ArchStruct"]=32;
			return 32;
		}
		if(preg_match("#x86_64#", $line)){
			$GLOBALS["__ArchStruct"]=64;
			return 64;
		}
	}
	return 64;
}

//##############################################################################

function Build_default_values(){
	$unix=new unix();
    $baseName=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/$baseName.".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/$baseName.".__FUNCTION__.".time";

    if(!$GLOBALS["FORCE"]) {
        $pid=$unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            return false;
        }
    }

	$pidtimeEx=$unix->file_time_min($pidtime);
	if($pidtimeEx<5){
	    if(!$GLOBALS["FORCE"]) {
            if ($GLOBALS["VERBOSE"]) {
                echo "Please wait at least 5Minutes, current is {$pidtimeEx}Mn\n";
            }
            return false;
        }
	}

	@unlink($pidtime);
	@file_put_contents($pidtime,time());
    @file_put_contents($pidfile,getmypid());
	$BArticaP="/usr/share/artica-postfix";
	$BAnsw="angular/font-awesome";
	$SetDaemons="/etc/artica-postfix/settings/Daemons";

	if(!is_dir("$BArticaP/$BAnsw/webfonts")){
		$ln=$unix->find_program("ln");
		shell_exec("$ln -sf $BArticaP/$BAnsw/fonts $BArticaP/$BAnsw/webfonts");
	}


	$XVF_WORKDIR=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2E=");
	$XVF_WORKFILE=base64_decode('LmxpYw==');
	$XVF_WORKPATH="$XVF_WORKDIR/$XVF_WORKFILE";
	$TVF_WORKPATH=base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL0NPUlBfTElDRU5TRQ==");
	if($GLOBALS["VERBOSE"]){echo "PROCESS 1 --- START --- \n";}
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}



	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	if(!is_file("$SetDaemons/EnableInfluxDB")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableInfluxDB", 1);}
	$EnableInfluxDB         = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableInfluxDB"));
    $EnableNginx            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
	if(!is_numeric($EnableInfluxDB)){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableInfluxDB", 1);}
	if(!is_file("/var/log/lastlog")){@touch("/var/log/lastlog");}

	if(is_file("/etc/monit/conf.d/APP_ARTICA_FRAMEPHP.monitrc")){
		@unlink("/etc/monit/conf.d/APP_ARTICA_FRAMEPHP.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	if(is_file("/etc/cron.d/GoogleSafeCountCache")){
		@unlink("/etc/cron.d/GoogleSafeCountCache");
		UNIX_RESTART_CRON();
	}






	if(is_file("$BArticaP/exec.rest-webfilter.client.php")) {
		if (!is_link("/usr/sbin/webfilter-rest")) {
			@symlink("$BArticaP/exec.rest-webfilter.client.php", "/usr/sbin/webfilter-rest");
			@chmod("$BArticaP/exec.rest-webfilter.client.php", 0755);
		}
	}
	if(is_file("$BArticaP/exec.rest.system.php")) {
		if (!is_link("/usr/sbin/system-rest")) {
			@symlink("$BArticaP/exec.rest.system.php", "/usr/sbin/system-rest");
			@chmod("$BArticaP/exec.rest.system.php", 0755);
		}
	}

	if(!is_file("/var/log/artica-webauth.log")){
		@touch("/var/log/artica-webauth.log");


	}
	@chmod("/var/log/artica-webauth.log",0755);
	@chown("/var/log/artica-webauth.log","www-data");
	@chgrp("/var/log/artica-webauth.log","www-data");

    if(!is_file("$SetDaemons/SquidGuardClientMaxMemoryItems")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardClientMaxMemoryItems", 100000);}
	if(!is_file("$SetDaemons/SquidLogsExceptions")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogsExceptions", "127.0.0.1");}
	if(!is_file("$SetDaemons/SquidCacheLevel")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidCacheLevel", 3);}

	if(!is_file("$SetDaemons/WebCommunityUpdatePool")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WebCommunityUpdatePool", 360);}
	if(!is_file("$SetDaemons/MilterGreyListAutoWhite")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MilterGreyListAutoWhite", 220);}
	if(!is_file("$SetDaemons/HardDisksWatchDog")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HardDisksWatchDog", serialize(array()));}

	$touch[]="/etc/artica-postfix/settings/Mysql/database_admin";
	$touch[]="/etc/artica-postfix/settings/Mysql/database_password";
	foreach ($touch as $path){
		if(!is_file($path)){@touch($path);}
	}

	if(!is_file("$SetDaemons/ActiveDirectoryConnections")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryConnections",serialize(array()));
	}



	if(!is_file("/etc/artica-postfix/settings/Daemons/TempWizard")){@touch("/etc/artica-postfix/settings/Daemons/TempWizard");@chown("/etc/artica-postfix/settings/Daemons/TempWizard","www-data");@chgrp("/etc/artica-postfix/settings/Daemons/TempWizard", "www-data");}
	if(!is_file("/etc/artica-postfix/settings/Daemons/TempWizardback")){@touch("/etc/artica-postfix/settings/Daemons/TempWizardback");@chown("/etc/artica-postfix/settings/Daemons/TempWizardback","www-data");@chgrp("/etc/artica-postfix/settings/Daemons/TempWizardback", "www-data");}

	if(is_file("/etc/init.d/.depend.boot")){@unlink("/etc/init.d/.depend.boot");}
	if(is_file("/etc/init.d/.depend.start")){@unlink("/etc/init.d/.depend.start");}
	if(is_file("/etc/init.d/.depend.stop")){@unlink("/etc/init.d/.depend.stop");}

	$ToDelete[]="exec.mpstat.php";

	foreach ($ToDelete as $filename){
		if(is_file("$BArticaP/$filename")){
			@unlink("$BArticaP/$filename");
		}
	}
    $ToKillSys[]="/usr/lib/systemd/system/snmpd.service";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/snmpd.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/snmpd.service";
    $ToKillSys[]="/usr/lib/systemd/system/redis-server.service";
    $ToKillSys[]="/usr/lib/systemd/system/redis-server@.service";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/redis-server.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/redis.service";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/redis-server.service";
    $ToKillSys[]="/etc/systemd/system/multi-user.target.wants/rsyslog.service";
    $ToKillSys[]="/usr/lib/systemd/system/rsyslog.service";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/rsyslog.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/rsyslog.service";
    $ToKillSys[]="/etc/systemd/system/multi-user.target.wants/rsyslog.service";
    $ToKillSys[]="/usr/lib/systemd/system/rsyslog.service";
    $ToKillSys[]="/usr/lib/systemd/system/glances.service";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/glances.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/glances.service";
    $ToKillSys[]="/etc/systemd/system/sshd.service";
    $ToKillSys[]="/etc/systemd/system/multi-user.target.wants/ssh.service";
    $ToKillSys[]="/usr/lib/systemd/system/rescue-ssh.target";
    $ToKillSys[]="/usr/lib/systemd/system/ssh.service";
    $ToKillSys[]="/usr/lib/systemd/system/ssh.socket";
    $ToKillSys[]="/usr/lib/systemd/system/ssh@.service";
    $ToKillSys[]="/usr/lib/systemd/user/gpg-agent-ssh.socket";
    $ToKillSys[]="/usr/lib/systemd/user/ssh-agent.service";
    $ToKillSys[]="/usr/lib/systemd/user/graphical-session-pre.target.wants/ssh-agent.service";
    $ToKillSys[]="/usr/lib/systemd/user/sockets.target.wants/gpg-agent-ssh.socket";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/ssh.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/ssh.socket.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/sshd.service";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/ssh.service";
    $ToKillSys[]="/etc/systemd/system/samba-ad-dc.service";
    $ToKillSys[]="/usr/lib/systemd/system/samba-ad-dc.service";
    $ToKillSys[]="/etc/systemd/system/multi-user.target.wants/apache2.service";
    $ToKillSys[]="/usr/lib/systemd/system/apache-htcacheclean.service";
    $ToKillSys[]="/usr/lib/systemd/system/apache-htcacheclean@.service";
    $ToKillSys[]="/usr/lib/systemd/system/apache2.service";
    $ToKillSys[]="/usr/lib/systemd/system/apache2@.service";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/apache-htcacheclean.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/apache2.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/apache2.service";
    $ToKillSys[]="/etc/systemd/system/unbound.service.wants";
    $ToKillSys[]="/etc/systemd/system/multi-user.target.wants/unbound.service";
    $ToKillSys[]="/etc/systemd/system/unbound.service.wants/unbound-resolvconf.service";
    $ToKillSys[]="/usr/lib/systemd/system/unbound-resolvconf.service";
    $ToKillSys[]="/usr/lib/systemd/system/unbound.service";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/unbound-resolvconf.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/unbound.service.dsh-also";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/unbound.service.wants";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/unbound.service";
    $ToKillSys[]="/var/lib/systemd/deb-systemd-helper-enabled/unbound.service.wants/unbound-resolvconf.service";

    foreach ($ToKillSys as $filename){
        if(is_file("$filename")){
            @unlink("$filename");
        }
    }
    $f[]="autofs.service";
    $f[]="avahi-daemon.service";
    $f[]="clamav-daemon.service";
    $f[]="clamav-freshclam.service";
    $f[]="collectd.service";
    $f[]="conntrackd.service";
    $f[]="cron.service";
    $f[]="cyrus-imapd.service";
    $f[]="danted.service";
    $f[]="firehol.service";
    $f[]="fireqos.service";
    $f[]="krb5-kdc.service";
    $f[]="lighttpd.service";
    $f[]="lm-sensors.service";
    $f[]="mariadb.service";
    $f[]="mosquitto.service";
    $f[]="munin-node.service";
    $f[]="nfs-client.target";
    $f[]="nmbd.service";
    $f[]="ntp.service";
    $f[]="open-vm-tools.service";
    $f[]="opendkim.service";
    $f[]="ospfd.service";
    $f[]="php7.3-fpm.service";
    $f[]="postfix.service";
    $f[]="pppd-dns.service";
    $f[]="prads.service";
    $f[]="privoxy.service";
    $f[]="quotarpc.service";
    $f[]="redsocks.service";
    $f[]="rpcbind.service";
    $f[]="rsync.service";
    $f[]="smartd.service";
    $f[]="smbd.service";
    $f[]="smokeping.service";
    $f[]="transmission-daemon.service";
    $f[]="winbind.service";
    $f[]="wpa_supplicant.service";
    $f[]="zebra.service";
    $systemctl=$unix->find_program("systemctl");
    foreach ($f as $systemd_file){
        if(is_file("/var/lib/systemd/deb-systemd-helper-enabled/$systemd_file.dsh-also")){
            shell_exec("$systemctl disable $systemd_file");
            continue;
        }
        if(is_file("/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/$systemd_file")){
            shell_exec("$systemctl disable $systemd_file");
            continue;
        }
        if(is_file("/usr/lib/systemd/system/$systemd_file")){
           shell_exec("$systemctl disable $systemd_file");
           continue;
        }
        if(is_file("/etc/systemd/system/$systemd_file")){
            shell_exec("$systemctl disable $systemd_file");
            continue;
        }
        $systemd_file=str_replace(".service","@.service",$systemd_file);
        if(is_file("/usr/lib/systemd/system/$systemd_file")){
            shell_exec("$systemctl disable $systemd_file");
            continue;
        }

    }

	$SET0[]="EnableVirtualDomainsInMailBoxes";
	$SET0[]="EnableClamavSecuriteSchedule";
	$SET0[]="NmapScanEnabled";
	$SET0[]="APACHE_MOD_BW";
	$SET0[]="EnableScanComputersNet";
	$SET0[]="WanProxyEnabled";
	$SET0[]="EnableProxyCompressor";
	$SET0[]="EnableFreeRadius";
	$SET0[]="StoreIDUrgency";
	$SET0[]="LMSensorsEnable";
	$SET0[]="PDNSStatsEnabled";
	$SET0[]="DisableOsSystemUpdate";
	$SET0[]="UseRemoteUfdbguardService";
	$SET0[]="PDNSStatsEnabled";
	$SET0[]="EnableUfdbSimplePage";
	$SET0[]="APACHE_INSTALLED";
	$SET0[]="EnableNginx";
	$SET0[]="DisableMemCacheSettings";
	$SET0[]="EnableIntelCeleron";
	$SET0[]="SquidPerformance";
	$SET0[]="EnableHDParmTests";
	$SET0[]="EnableUfdbGuard2";


	foreach ($SET0 as $filename){
		if(!is_file("$SetDaemons/$filename")){
			@file_put_contents("$SetDaemons/$filename",0);
			@chmod("$SetDaemons/$filename",0755);
			@chown("$SetDaemons/$filename","www-data");
			@chgrp("$SetDaemons/$filename","www-data");
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("$filename", 0);
		}
	}



	$SET1[]="AllowSquidKaspersky";
	$SET1[]="nonhierarchical_direct";
	$SET1[]="SambaDisableNetbios";
	$SET1[]="SquidBlackListDoNotCheckMD5";
	$SET1[]="ArticaAutoUpateOfficial";
	$SET1[]="AllowSquidWhitelistsSSL";
	$SET1[]="EnableMemcached";
	$SET1[]="SquidGuardClientEnableMemory";
	$SET1[]="AllowWindowsUpdates";
	$SET1[]="HideOfficialsCategory";
	$SET1[]="APP_VLAN_INSTALLED";

	if(!is_file("$SetDaemons/ufdbCatPort")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ufdbCatPort",3978);}
	foreach ($SET1 as $filename){ if(!is_file("$SetDaemons/$filename")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("$filename", 1);} }


    if(!is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")) {

        if(!is_file("/usr/bin/redis-server")){
            if(is_file("/usr/sbin/itcharter")) {
                @copy("/usr/sbin/itcharter", "/usr/bin/redis-server");
                @chmod("/usr/bin/redis-server", 0755);
            }
        }

        if (!is_file("/usr/local/sbin/ipaudit")) {
            if (is_file("$BArticaP/bin/install/ipaudit.tar.gz")) {
                $tar = $GLOBALS["CLASS_UNIX"]->find_program("tar");
                shell_exec("$tar xf $BArticaP/bin/install/ipaudit.tar.gz -C /usr/local/sbin/");
                if (is_file("/usr/local/sbin/ipaudit")) {
                    squid_admin_mysql(0, "New Feature installed IP traffic summarizer in System/Monitor !",
                        "See https://youtu.be/_jmH7_a3luA", __FILE__, __LINE__);
                }
            }
        }
    }


	PATCH_120_SQUID();
	$GLOBALS["CLASS_UNIX"]->CPU_NUMBER();
	$dialog=$GLOBALS["CLASS_UNIX"]->find_program("dialog");
	$msktutil=$GLOBALS["CLASS_UNIX"]->find_program("msktutil");
	$hostapd=$GLOBALS["CLASS_UNIX"]->find_program("hostapd");
	$losetup=$GLOBALS["CLASS_UNIX"]->find_program("losetup");
	$vconfig=$GLOBALS["CLASS_UNIX"]->find_program("vconfig");
	$iscsiadm=$GLOBALS["CLASS_UNIX"]->find_program("iscsiadm");
	$arpd=$GLOBALS["CLASS_UNIX"]->find_program("arpd");
	$iscsid=$GLOBALS["CLASS_UNIX"]->find_program("iscsid");
	$ftp_proxy=$GLOBALS["CLASS_UNIX"]->find_program("ftp-proxy");
	$wifidog=$GLOBALS["CLASS_UNIX"]->find_program("wifidog");
	$vde_switch=$GLOBALS["CLASS_UNIX"]->find_program("vde_switch");
	$ucarp=$GLOBALS["CLASS_UNIX"]->find_program("ucarp");
	$freeradius=$GLOBALS["CLASS_UNIX"]->find_program("freeradius");

    $adagent=$GLOBALS["CLASS_UNIX"]->find_program("ad-agent-lbl");
	$ebtables=$GLOBALS["CLASS_UNIX"]->find_program("ebtables");
	$squid27=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	$apache2=$GLOBALS["CLASS_UNIX"]->LOCATE_APACHE_BIN_PATH();
	$offlineimap=$GLOBALS["CLASS_UNIX"]->find_program("offlineimap");
	$postscreen=$GLOBALS["CLASS_UNIX"]->LOCATE_POSTFIX_POSTSCREEN();
	$ipset=$GLOBALS["CLASS_UNIX"]->find_program("ipset");
	$aspell=$GLOBALS["CLASS_UNIX"]->find_program("aspell");
	$muninnode=$GLOBALS["CLASS_UNIX"]->find_program("munin-node");
	$CURL_PATH=$GLOBALS["CLASS_UNIX"]->find_program("curl");
	$miltergreylist=$GLOBALS["CLASS_UNIX"]->find_program("milter-greylist");

	$php_fpm=$GLOBALS["CLASS_UNIX"]->find_program("php5-fpm");
	$ntopng=$GLOBALS["CLASS_UNIX"]->find_program("ntopng");
	$pureftpd=$GLOBALS["CLASS_UNIX"]->find_program("pure-ftpd");
	$sabnzbdplus=$GLOBALS["CLASS_UNIX"]->find_program("sabnzbdplus");
	$rpc_nfsd=$GLOBALS["CLASS_UNIX"]->find_program("rpc.nfsd");
	$dstat=$GLOBALS["CLASS_UNIX"]->find_program("dstat");
	$gnuplot=$GLOBALS["CLASS_UNIX"]->find_program("gnuplot");
	$VBoxService=$GLOBALS["CLASS_UNIX"]->find_program("VBoxService");
	$kinit=$GLOBALS["CLASS_UNIX"]->find_program("kinit");
	$iostat=$GLOBALS["CLASS_UNIX"]->find_program("iostat");
	$openvpn_bin=$GLOBALS["CLASS_UNIX"]->find_program("openvpn");

	$strongswan_bin=$GLOBALS["CLASS_UNIX"]->find_program("ipsec");
	$firehol=$GLOBALS["CLASS_UNIX"]->find_program("firehol");
	if(is_file("/usr/sbin/firehol")){$firehol="/usr/sbin/firehol";}
	$uname=$GLOBALS["CLASS_UNIX"]->find_program("uname");
	$nmap=$GLOBALS["CLASS_UNIX"]->find_program("nmap");
	$targetcli=$GLOBALS["CLASS_UNIX"]->find_program("targetcli");
	$monitbin=$GLOBALS["CLASS_UNIX"]->find_program("monit");
    $lighttpd=$GLOBALS["CLASS_UNIX"]->find_program("lighttpd");
	$fail2ban=$GLOBALS["CLASS_UNIX"]->find_program("fail2ban-server");
	$oinkmaster=$GLOBALS["CLASS_UNIX"]->find_program("oinkmaster");
	$fireqos=$GLOBALS["CLASS_UNIX"]->find_program("fireqos");
	$mountcifs=$GLOBALS["CLASS_UNIX"]->find_program("mount.cifs");
	$java=$GLOBALS["CLASS_UNIX"]->find_program("java");
	$fping=$GLOBALS["CLASS_UNIX"]->find_program("fping");
	$curlftpfs=$GLOBALS["CLASS_UNIX"]->find_program("curlftpfs");
	$iptaccount=$GLOBALS["CLASS_UNIX"]->find_program("iptaccount");
	$ipsec=$GLOBALS["CLASS_UNIX"]->find_program("ipsec");
	$pptpd=$GLOBALS["CLASS_UNIX"]->find_program("pptpd");
	$pptp=$GLOBALS["CLASS_UNIX"]->find_program("pptp");
	$pear=$GLOBALS["CLASS_UNIX"]->find_program("pear");
	$tc=$GLOBALS["CLASS_UNIX"]->find_program("tc");
	$sensors_detect=$GLOBALS["CLASS_UNIX"]->find_program("sensors-detect");
	$ziproxy=$GLOBALS["CLASS_UNIX"]->find_program("ziproxy");
	$iwconfig=$GLOBALS["CLASS_UNIX"]->find_program("iwconfig");
	$KERNEL_VERSION=$GLOBALS["CLASS_UNIX"]->KERNEL_VERSION();
	$namebench=$GLOBALS["CLASS_UNIX"]->find_program("namebench");
	$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
	$privoxy=$GLOBALS["CLASS_UNIX"]->find_program("privoxy");
	$pdns_server=$GLOBALS["CLASS_UNIX"]->find_program("pdns_server");
	$msmtp=$GLOBALS["CLASS_UNIX"]->find_program("msmtp");
	$glances=$GLOBALS["CLASS_UNIX"]->find_program("glances");
	$frontail_linux=$GLOBALS["CLASS_UNIX"]->find_program("frontail-linux");
	$cyrus=$GLOBALS["CLASS_UNIX"]->LOCATE_CYRUS_DAEMON();
	$php5=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
	$procmail=$GLOBALS["CLASS_UNIX"]->find_program("procmail");
	$mimedefang=$GLOBALS["CLASS_UNIX"]->find_program("mimedefang");
	$nginx=$GLOBALS["CLASS_UNIX"]->find_program("nginx");

	$tar=$GLOBALS["CLASS_UNIX"]->find_program("tar");
	$ln=$GLOBALS["CLASS_UNIX"]->find_program("ln");
	$letsencrypt=$GLOBALS["CLASS_UNIX"]->find_program("letsencrypt");
	$milter_greylist=$GLOBALS["CLASS_UNIX"]->find_program("milter-greylist");
	$openssl=$GLOBALS["CLASS_UNIX"]->find_program("openssl");
	$milter_regex=$GLOBALS["CLASS_UNIX"]->find_program("milter-regex");
	$dnscrypt_proxy=$GLOBALS["CLASS_UNIX"]->find_program("dnscrypt-proxy");
	$doh_server=$GLOBALS["CLASS_UNIX"]->find_program("doh-server");
	$linkbalancerbin=$GLOBALS["CLASS_UNIX"]->find_program("link-balancer");
	$filebeat=$GLOBALS["CLASS_UNIX"]->find_program("filebeat");
	$opendkim=$GLOBALS["CLASS_UNIX"]->find_program("opendkim");
	$prads=$GLOBALS["CLASS_UNIX"]->find_program("prads");
	$php5ExtensionDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("php5ExtensionDir");

	$php_geoip="$php5ExtensionDir/maxminddb.so";
	$geoipupdate=$GLOBALS["CLASS_UNIX"]->find_program("geoipupdate");
	$qemu_ga=$GLOBALS["CLASS_UNIX"]->find_program("qemu-ga");
	$zabbix_agentd=$GLOBALS["CLASS_UNIX"]->find_program("zabbix_agentd");

	$sshportal=$GLOBALS["CLASS_UNIX"]->find_program("sshportal");
	$pdnssec=$GLOBALS["CLASS_UNIX"]->find_program("pdnssec");
	$cgrulesengd=$GLOBALS["CLASS_UNIX"]->find_program("cgrulesengd");
	$samhain=$GLOBALS["CLASS_UNIX"]->find_program("samhain");
	$dsc=$GLOBALS["CLASS_UNIX"]->find_program("dsc");
	$keepalived=$GLOBALS["CLASS_UNIX"]->find_program("keepalived");
    $httrack=$GLOBALS["CLASS_UNIX"]->find_program("httrack");
    $smokeping=$GLOBALS["CLASS_UNIX"]->find_program("smokeping");
    $saslauthd = $GLOBALS["CLASS_UNIX"]->find_program("saslauthd");
    $aha = $GLOBALS["CLASS_UNIX"]->find_program("aha");
    $sshfs = $GLOBALS["CLASS_UNIX"]->find_program("sshfs");
    $dockerd = $GLOBALS["CLASS_UNIX"]->find_program("dockerd");

	$greensql=null;
	if(is_file("/usr/sbin/greensql-fw")){$greensql="/usr/sbin/greensql-fw";}
	if(is_file("$BArticaP/bin/ipcalc")){@chmod("$BArticaP/bin/ipcalc",0755);}

	if(is_file("/etc/init.d/ufdbcat")){
		shell_exec("$nohup $php5 $BArticaP/exec.dnscat.php --install >/dev/null 2>&1");
	}



	$BugzillaChecked=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaChecked"));
	if($BugzillaChecked==0){shell_exec("$nohup $php5 $BArticaP/exec.bugzilla.php --get-user >/dev/null 2>&1");}

    if(!is_file($namebench)){$namebench=$GLOBALS["CLASS_UNIX"]->find_program("namebench.py");}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOCATE_PHP5_BIN", $php5);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOCATE_NOHUP", $nohup);
    $etc_environment_430sp141=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("etc_environment_430sp141"));

    if($etc_environment_430sp141==0){
        @unlink("/etc/environment");
        @touch("/etc/environment");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("etc_environment_430sp141", 1);
    }


	$TimeFile[]="FROM_ISO";
	$TimeFile[]="artica-as-rebooted";
	$TimeFile[]="artica-iso-first-reboot";
	$TimeFile[]="artica-iso-setup-launched";
	$TimeFile[]="WIZARD_INSTALL_EXECUTED";

	foreach ($TimeFile as $basename){
		if(is_file("/etc/artica-postfix/$basename")){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("INSTALL_TIME",
				filemtime("/etc/artica-postfix/$basename"));
			break;
		}
	}
	// fs.file-max watchdog ----------
	watch_fsfilemax();
	//-------------------------------

    if(is_file("/etc/init.d/postfix")){postfix_verify_files();}
    if(!is_dir("/usr/share/snmp/mibs/iana")){@mkdir("/usr/share/snmp/mibs/iana",0755,true);}

	if($GLOBALS["VERBOSE"]){echo "openvpn		: $openvpn_bin\n";}
	if($GLOBALS["VERBOSE"]){echo "strongswan		: $strongswan_bin\n";}
	if($GLOBALS["VERBOSE"]){echo "firehol		: $firehol\n";}
	if($GLOBALS["VERBOSE"]){echo "fail2ban	: $fail2ban\n";}
	if($GLOBALS["VERBOSE"]){echo "oinkmaster	: $oinkmaster\n";}
	if($GLOBALS["VERBOSE"]){echo "ntopng		: $ntopng\n";}
	if($GLOBALS["VERBOSE"]){echo "FireQOS		: $fireqos\n";}
	if($GLOBALS["VERBOSE"]){echo "mount.cifs	: $mountcifs\n";}
	if($GLOBALS["VERBOSE"]){echo "java            : $java\n";}
	if($GLOBALS["VERBOSE"]){echo "fping           : $fping\n";}
	if($GLOBALS["VERBOSE"]){echo "curlftpfs       : $curlftpfs\n";}
	if($GLOBALS["VERBOSE"]){echo "ziproxy         : $ziproxy\n";}
	if($GLOBALS["VERBOSE"]){echo "php5            : $php5\n";}
	if($GLOBALS["VERBOSE"]){echo "nohup           : $nohup\n";}
	if($GLOBALS["VERBOSE"]){echo "msmtp           : $msmtp\n";}
	if($GLOBALS["VERBOSE"]){echo "glances         : $glances\n";}
	if($GLOBALS["VERBOSE"]){echo "filebeat        : $filebeat\n";}
	if($GLOBALS["VERBOSE"]){echo "frontail-linux  : $frontail_linux\n";}
	if($GLOBALS["VERBOSE"]){echo "nginx           : $nginx\n";}
	if($GLOBALS["VERBOSE"]){echo "greensql        : $greensql\n";}
	if($GLOBALS["VERBOSE"]){echo "milter-greylist : $milter_greylist\n";}
	if($GLOBALS["VERBOSE"]){echo "letsencrypt     : $letsencrypt\n";}
	if($GLOBALS["VERBOSE"]){echo "milter-regex    : $milter_regex\n";}
	if($GLOBALS["VERBOSE"]){echo "dnscrypt-proxy  : $dnscrypt_proxy\n";}
	if($GLOBALS["VERBOSE"]){echo "doh-server      : $doh_server\n";}
	if($GLOBALS["VERBOSE"]){echo "Prads           : $prads\n";}
	if($GLOBALS["VERBOSE"]){echo "PHP ExtensionDir: $php5ExtensionDir\n";}
	if($GLOBALS["VERBOSE"]){echo "PHP GeoIP2      : $php_geoip\n";}
	if($GLOBALS["VERBOSE"]){echo "geoipupdate     : $geoipupdate\n";}
	if($GLOBALS["VERBOSE"]){echo "zabbix_agentd   : $zabbix_agentd\n";}
	if($GLOBALS["VERBOSE"]){echo "sshportal       : $sshportal\n";}
	if($GLOBALS["VERBOSE"]){echo "pdnssec         : $pdnssec\n";}
	if($GLOBALS["VERBOSE"]){echo "cgrulesengd     : $cgrulesengd\n";}
	if($GLOBALS["VERBOSE"]){echo "samhain         : $samhain\n";}
	if($GLOBALS["VERBOSE"]){echo "dnsc            : $dsc\n";}
    if($GLOBALS["VERBOSE"]){echo "httrack         : $httrack\n";}
    if($GLOBALS["VERBOSE"]){echo "smokeping       : $smokeping\n";}
    if($GLOBALS["VERBOSE"]){echo "saslauthd       : $saslauthd\n";}
    if($GLOBALS["VERBOSE"]){echo "aha             : $aha\n";}

	if(!is_file("/usr/sbin/wp-cli.phar")){
		if(is_file("$BArticaP/bin/install/wp-cli.phar.tar.gz")){
			shell_exec("$tar xf $BArticaP/bin/install/wp-cli.phar.tar.gz -C /usr/sbin/");
			if(is_file("/usr/sbin/wp-cli.phar")){
				@chmod("/usr/sbin/wp-cli.phar", 0755);
				shell_exec("$ln -sf /usr/sbin/wp-cli.phar /usr/sbin/wp");
				squid_admin_mysql(2, "Success installing WordPress client", null,__FILE__,__LINE__);
			}
		}
	}




	$zip=$GLOBALS["CLASS_UNIX"]->find_program("zip");
	if(!is_file($zip)){$zip="/usr/bin/zip";}

	if(!is_file($php_fpm)){$php_fpm=$GLOBALS["CLASS_UNIX"]->find_program("php-fpm");}
	if($GLOBALS["VERBOSE"]){echo "PROCESS 1 --- ".__LINE__." --- \n";}

	var_lib_php();
	buildCronStatistics();
	if($GLOBALS["VERBOSE"]){echo "PROCESS 1 --- ".__LINE__." --- \n";}

	if(is_file($XVF_WORKPATH)){
		$fjih=strtoupper(trim(@file_get_contents($XVF_WORKPATH)));
		if($fjih=="TRUE"){
			@file_put_contents($TVF_WORKPATH,"VFJVRQ==");
			@chmod($TVF_WORKPATH,0755);
		}else{
			@unlink($TVF_WORKPATH);
		}
	}
	PERSONAL_CATEGORIES_CRON();
    LICENSE_EVAL_POSTED();
    check_go_shield_server_updates();
	if($GLOBALS["VERBOSE"]){echo "PROCESS 1 --- ".__LINE__." --- \n";}

	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DEBIAN_VERSION_NAME",__debian_version_name());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DEBIAN_VERSION",$GLOBALS["CLASS_UNIX"]->DEBIAN_VERSION());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("VMWARE_HOST",IS_VMWARE_HOST());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("VIRTUALBOX_HOST",IS_VIRTUALBOX_HOST());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("XEN_HOST",$GLOBALS["CLASS_UNIX"]->IS_CITRIXXEN_HOST());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NUTANIX_HOST",$GLOBALS["CLASS_UNIX"]->IS_NUTANIX_HOST());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HYPERV_HOST",IS_HYPERV_HOST());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArchStruct",__ArchStruct());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MEM_TOTAL_INSTALLEE",$GLOBALS["CLASS_UNIX"]->MEM_TOTAL_INSTALLEE());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LinuxDistributionCodeName", $GLOBALS["CLASS_UNIX"]->LINUX_CODE_NAME());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LinuxDistributionFullName", $GLOBALS["CLASS_UNIX"]->LINUX_DISTRIBUTION());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("TOTAL_MEMORY_MB", $GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ifconfig_all_ips", serialize($GLOBALS["CLASS_UNIX"]->ifconfig_all_ips()));
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SIEVE_PORT",SIEVE_PORT());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOCAL_SID",$GLOBALS["CLASS_UNIX"]->GET_LOCAL_SID());

	$LINUX_INFO_PATH="/etc/artica-postfix/settings/Daemons/LINUX_INFO_TXT";
	if(is_file($LINUX_INFO_PATH)){
		$TimeEx=$GLOBALS["CLASS_UNIX"]->file_time_min($LINUX_INFO_PATH);
		if($TimeEx>420){@unlink($LINUX_INFO_PATH);}
	}
	if(!is_file($LINUX_INFO_PATH)){
		@chmod("/usr/share/artica-postfix/bin/linux-info.sh",0755);
		shell_exec("/usr/share/artica-postfix/bin/linux-info.sh --all >$LINUX_INFO_PATH 2>&1");
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LINUX_INFO_TXT",@file_get_contents($LINUX_INFO_PATH));
	}

    $APP_GO_SHIELD_VERSION=APP_GO_SHIELD_VERSION();
    if($APP_GO_SHIELD_VERSION<>null){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_GO_SHIELD_VERSION",$APP_GO_SHIELD_VERSION);}
	$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));

	if($EnablePostfix==0){
		if(is_file("/usr/sbin/sendmail")){@chmod("/usr/sbin/sendmail",0600);}
	}else{
		if(is_file("/usr/sbin/sendmail")){@chmod("/usr/sbin/sendmail",0755);}
	}

    $RELOAD_CRON=false;
    if(!is_file("/etc/cron.d/lshw-infos")){
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("lshw-infos","50 5 * * *","exec.shellinabox.php");
        $RELOAD_CRON=true;
    }

    if(!is_file("/etc/cron.d/pictures-rrd")){
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("pictures-rrd","*/10 * * * *","exec.rrd.php --pictures-only");
        $RELOAD_CRON=true;
    }


	if(!is_file("/usr/share/artica-postfix/img/philesight/system.png")){
		$cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BArticaP/exec.philesight.php --run >/dev/null 2>&1");
		shell_exec2($cmd);
	}

	if(!is_file("/etc/cron.d/ping-cloud-license")){
		if(is_file("/etc/cron.d/ping-cloud")){@unlink("/etc/cron.d/ping-cloud");}
		Popuplate_cron_make("ping-cloud-license","15 */2 * * *","exec.web-community-filter.php --ping-cloud");
        $RELOAD_CRON=true;
	}

	if(!is_file("/etc/cron.d/register-lic")){
		Popuplate_cron_make("register-lic","30 */3 * * *","exec.web-community-filter.php --register-lic --set-stamp");
        $RELOAD_CRON=true;
	}

	if(!is_file("/etc/cron.d/philesight")){
		$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("philesight","45 1 * * *","philesight.php --run");
        $RELOAD_CRON=true;
	}

	if(!is_file("/etc/cron.d/verif-packages")){
		$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("verif-packages","0 */8 * * *", "exec.verif.packages.php");
        $RELOAD_CRON=true;
	}

    if(!is_file("/etc/cron.d/check-watchdogs")){
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("check-watchdogs","*/3 * * * *","exec.check.watchdogs.php");
        $RELOAD_CRON=true;
    }

    if($RELOAD_CRON){
        shell_exec("/etc/init.d/cron reload");
    }

	if($GLOBALS["VERBOSE"]){echo "PROCESS 1 --- ".__LINE__." --- \n";}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LinuxKernelVersion",exec("$uname -v 2>&1"));
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOCAL_SID", $GLOBALS["CLASS_UNIX"]->GET_LOCAL_SID());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CURL_PATH", $CURL_PATH);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EXEC_NICE", $GLOBALS["CLASS_UNIX"]->EXEC_NICE());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PHP_BIN_PATH", $GLOBALS["PHP5"]);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUID_BIN_PATH", $GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN());
	if(is_file("/etc/cron.d/php5")){@unlink("/etc/cron.d/php5");shell_exec2("/etc/init.d/cron reload");}
	if(is_file("/usr/local/bin/sarg")){@unlink("/usr/local/bin/sarg");}

	if($GLOBALS["CLASS_UNIX"]->ISMemoryHiger1G()){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MEM_HIGER_1G",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MEM_HIGER_1G",0);
	}
	if($GLOBALS["VERBOSE"]){echo "PROCESS 1 --- ".__LINE__." --- \n";}

    if(is_file("/var/ossec/bin/wazuh-control")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_WAZHU_INSTALLED", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_WAZHU_VERSION",APP_WAZHU_VERSION());
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_WAZHU_INSTALLED", 0);
    }


	$ndpi_path="/lib/modules/$KERNEL_VERSION/extra/xt_ndpi.ko";
	if($GLOBALS["VERBOSE"]){echo "[NDPI]: KERNEL VERSION:$KERNEL_VERSION\n";}
	if($GLOBALS["VERBOSE"]){echo "[NDPI]: xt_ndpi.ko....:$ndpi_path\n";}

	if(is_file($ndpi_path)){
		if($GLOBALS["VERBOSE"]){echo "[NDPI]: xt_ndpi.ko....:$ndpi_path SUCCESS\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_NDPI_INSTALLED", 1);
		if(is_file("/usr/share/nDPI/VERSION")){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_NDPI_VERSION", trim(@file_get_contents("/usr/share/nDPI/VERSION")));}

	}else{
		if($GLOBALS["VERBOSE"]){echo "[NDPI]: xt_ndpi.ko....:$ndpi_path NO SUCH FILE\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_NDPI_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_NDPI_VERSION", 0);
	}



	if(is_file($sshportal)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SSHPORTAL_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SSHPORTAL_VERSION", APP_SSHPORTAL_VERSION());

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SSHPORTAL_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSSHPortal", 0);

	}

	if(is_file("/opt/imapbox/imapbox.py")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_IMAPBOX_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_IMAPBOX_INSTALLED", 0);
	}
    
    if(is_file($httrack)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HTTRACK_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HTTRACK_INSTALLED", 0);
    }
    if(is_file($smokeping)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SMOKEPING_INSTALLED", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SMOKEPING_VERSION", SMOKEPING_VERSION());
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SMOKEPING_INSTALLED", 0);
    }

    if(is_file("/usr/bin/ccencrypt")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CCENCRYPT_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CCENCRYPT_INSTALLED", 0);
    }

    if(is_file($sshfs)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHFS_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSHFS_INSTALLED", 0);
    }

    if(is_file("/usr/bin/urbackupsrv")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_URBACKUP_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_URBACKUP_INSTALLED", 0);
    }


    if(is_file($saslauthd)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SASLAUTHD_INSTALLED", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SASLAUTHD_VERSION", APP_SASLAUTHD_VERSION());
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SASLAUTHD_INSTALLED", 0);
    }

    if(is_file($aha)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_AHA_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_AHA_INSTALLED", 0);
    }

    $lua_geoip_found=false;
    $lua_geoip[]="/usr/lib/x86_64-linux-gnu/lua/5.1/geoip.so";
    foreach ($lua_geoip as $fpath){
        if(is_file($fpath)){
            $lua_geoip_found=true;
            break;
        }
    }
    if($lua_geoip_found){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LUA_GEOIP_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LUA_GEOIP_INSTALLED", 0);
    }



    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ARTICAREST_VERSION",ARTICAREST_VERSION());

    if(is_file("/opt/quest/sbin/vasd")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SAFEGUARD_AUTH_INSTALLED", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SAFEGUARD_AUTH_VERSION", SAFEGUARD_AUTH());
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SAFEGUARD_AUTH_INSTALLED", 0);
    }


	if(is_file($samhain)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SAMHAIN_VERSION", APP_SAMHAIN_VERSION());
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SAMHAIN_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SAMHAIN_INSTALLED", 0);
	}

	if(is_file($keepalived)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_KEEPALIVED_VERSION", APP_KEEPALIVED_VERSION());
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_KEEPALIVED_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_KEEPALIVED_INSTALLED", 0);
    }



	if(is_file($cgrulesengd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CGROUPS_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CGROUPS_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("cgroupsEnabled", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("cgroupsEnabled", 0);
	}

	if(is_file("/usr/sbin/hv_kvp_daemon")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HYPERV_DAEMONS_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HYPERV_DAEMONS_INSTALLED", 0);
    }



	if(is_file("/usr/sbin/update-ipsets")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UPDATE_IPSETS_INSTALLED", 1);

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UPDATE_IPSETS_INSTALLED", 0);
	}

	if(is_file($opendkim)) {
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OPENDKIM_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OPENDKIM_VERSION",OPENDKIM_VERSION());
	}

	if(is_file($prads)) {
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_PRADS_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_PRADS_VERSION", "0.3.3");

	}
	if(is_file($php_geoip)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PHP_GEOIP_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PHP_GEOIP_VERSION",phpversion("maxminddb"));
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PHP_GEOIP_INSTALLED", 0);
	}

	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}


	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_UFDBCAT_VERSION",APP_UFDBCAT_VERSION());

    if(is_file("/usr/local/ArticaStats/bin/postgres")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_POSTGRES_INSTALLED", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_POSTGRES_VERSION", APP_POSTGRES_VERSION());
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_POSTGRES_INSTALLED", 0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_POSTGRES_VERSION", "0.0");
    }







	if(is_file($msmtp)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MSMTP_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MSMTP_VERSION", MSMTP_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MSMTP_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MSMTP_VERSION",0);
	}

	if(is_file($filebeat)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FILEBEAT_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FILEBEAT_VERSION", FILEBEAT_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FILEBEAT_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FILEBEAT_VERSION",0);
	}

	if(is_file($frontail_linux)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FRONTAIL_LINUX_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FRONTAIL_LINUX_VERSION", FRONTAIL_LINUX_VERSION());

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FRONTAIL_LINUX_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FRONTAIL_LINUX_VERSION",0);
	}


	if(is_file($cyrus)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_CYRUS_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_CYRUS_VERSION", APP_CYRUS_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_CYRUS_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_CYRUS_VERSION", 0);
	}

	if(is_file($glances)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("GLANCES_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("GLANCES_VERSION", GLANCES_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("GLANCES_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("GLANCES_VERSION",0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGlances",0);
	}
    if(is_file("/usr/bin/searchd")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MANTICORE_INSTALLED", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MANTICORE_VERSION", APP_MANTICORE_VERSION());
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MANTICORE_INSTALLED", 0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MANTICORE_VERSION", "0.0.0");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MantiCoreSearchEnabled", 0);
    }
    if(is_null($greensql)){
        $greensql="";
    }

	if(is_file($greensql)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_GREENSQL_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_GREENSQL_VERSION", APP_GREENSQL_VERSION());

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_GREENSQL_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_GREENSQL_VERSION", "0.0.0");
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGreenSQL", 0);
	}


	if(is_file($milter_regex)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MILTER_REGEX_INSTALLED", 1);
		$md5=md5_file($milter_regex);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MILTER_REGEX_VERSION", "2.0.0");
		if($md5=="b4593de12c36c8429466fdd25bf754b3"){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MILTER_REGEX_VERSION", "2.2.0");}
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MILTER_REGEX_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMilterRegex", 0);
	}

	if(is_file($dnscrypt_proxy)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DNSCRYPT_PROXY_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DNSCRYPT_PROXY_VERSION", APP_DNSCRYPT_PROXY_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DNSCRYPT_PROXY_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSCryptProxy", 0);
	}


	if( intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MULTIPATH_TCP_INSTALLED"))==1 ){
		$APP_MULTIPATH_TCP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MULTIPATH_TCP_VERSION");
		if($APP_MULTIPATH_TCP_VERSION==null){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MULTIPATH_TCP_VERSION", APP_MULTIPATH_TCP_VERSION());}
		if(is_file("/proc/net/mptcp_net/snmp")){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MULTIPATH_TCP_RUNNING",1);
		}else{
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MULTIPATH_TCP_RUNNING",0);
		}
	}

	if(is_file("$milter_greylist")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MILTER_GREYLIST_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MILTER_GREYLIST_VERSION", APP_MILTER_GREYLIST_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MILTER_GREYLIST_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MILTER_GREYLIST_VERSION",0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MilterGreyListEnabled",0);
	}

	if(is_file($zabbix_agentd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ZABBIX_AGENT_VERSION", APP_ZABBIX_AGENT_VERSION());
	}
    if(is_file($dockerd)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DOCKER_INSTALLED", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DOCKER_VERSION", APP_DOCKER_VERSION());
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DOCKER_INSTALLED", 0);
    }
    if(is_file($mimedefang)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MimeDefangInstalled", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MimeDefangVersion", mimedefang_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MimeDefangInstalled", 0);

	}


	if(is_file("/usr/libexec/postfix/mailspy")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MILTER_SPY_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MILTER_SPY_INSTALLED",0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMilterSpyDaemon",0);
	}

	if(is_file($procmail)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PROCMAIL_INSTALLED",1);

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PROCMAIL_INSTALLED",0);
	}

	if(is_file($tc)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("TC_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("TC_VERSION", TC_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("TC_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("TC_VERSION",0);
	}

	if(is_file("/usr/lib/ldap/syncprov.so")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LDAP_SYNCPROV", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LDAP_SYNCPROV", 0);
	}

	if(is_file($pear)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PEAR_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PEAR_PACKAGES", PEAR_PACKAGES());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PEAR_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PEAR_PACKAGES", "");
	}

	if(is_file($privoxy)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_PRIVOXY_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_PRIVOXY_VERSION", APP_PRIVOXY_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PEAR_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PEAR_PACKAGES", "");
	}


	if(is_file($pdns_server)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSInstalled", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSVersion", APP_PDNS_VERSION());
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSRecursorVersion", APP_PDNS_RECURSOR_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSInstalled", 0);
	}




	if(is_file($filebeat)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FILEBEAT_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FILEBEAT_VERSION", APP_FILEBEAT_VERSION());
	}

    if(!is_null($nginx)) {
        if (is_file($nginx)) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_NGINX_INSTALLED", 1);
        } else {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_NGINX_INSTALLED", 0);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableNginx", 0);
        }
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_NGINX_INSTALLED", 0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableNginx", 0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NGINX_COMPILE_PARAMS", base64_encode(serialize(array())));
    }


	if(is_file($pptpd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PPTPD_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PPTPD_INSTALLED", 0);
	}


	if(is_file($pptp)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PPTP_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PPTP_INSTALLED", 0);
	}

	if( is_file("/usr/sbin/sstpc") ){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSTP_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SSTP_INSTALLED", 0);
	}

	if(is_file($ipsec)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPSEC_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPSEC_INSTALLED", 0);
	}

	if(is_file($iptaccount)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPTABLES_ACCOUNTING_EXISTS", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPTABLES_ACCOUNTING_EXISTS", 0);
	}
	if(is_file($sensors_detect)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SENSORS_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SensorsInstalled", 1);

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SENSORS_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SensorsInstalled", 0);
	}
	if(is_file($ziproxy)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ZIPROXY_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ZIPROXY_VERSION", ZIPROXY_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ZIPROXY_INSTALLED", 0);
	}

	if(is_file($namebench)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NAMEBENCH_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NAMEBENCH_INSTALLED", 0);
	}

	if(is_file('/usr/sbin/hv_vss_daemon')){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HYPERV_DAEMONS_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HYPERV_DAEMONS_INSTALLED", 0);
	}


	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IWLWIFI_INSTALLED", 1);

    if(is_file("/usr/bin/php7.3")){
        if(is_file("/usr/bin/php7.4")){
            if(!is_link("/usr/bin/php7.3")) {
                shell_exec("$ln -sf /usr/bin/php7.4 /usr/bin/php7.3");
                squid_admin_mysql(1, "Success installing fake PHP compatibility client php7.3 to php7.4",
                    null,__FILE__,__LINE__);
            }
        }
    }



	if(is_file($pdnssec)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSSecInstalled", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSSecInstalled", 0);
	}

	if(is_file("/usr/sbin/mount.davfs")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DAVFS_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DAVFS_INSTALLED", 0);
	}





	if(is_file($iwconfig)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IWCONFIG_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IWCONFIG_VERSION", IWCONFIG_VERSION());

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IWCONFIG_INSTALLED", 0);
	}

	if(is_file("/usr/share/appdata/artica/VERSION")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FIRMWARES_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FIRMWARES_VERSION",trim(@file_get_contents("/usr/share/appdata/artica/VERSION")));
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FIRMWARES_INSTALLED", 0);
	}


	if(is_file("/usr/local/bin/rdpproxy")){
        $APP_RDPPROXY_VERSION=APP_RDPPROXY_VERSION();
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RDPPROXY_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RDPPROXY_VERSION", $APP_RDPPROXY_VERSION);


	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RDPPROXY_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RDPPROXY_VERSION", 0);
	}


	if(!is_file("/usr/sbin/ss5")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SS5Installed", 0);
	}else{
		if(!is_file("/var/lib/ss5/ss5/mod_socks4.so")){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SS5Installed", 0);
		}else{
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SS5Installed", 1);
		}
	}

	if(IS_INSIDE_VPS()){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AS_VPS_CLIENT", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AS_VPS_CLIENT", 0);
	}



	if(is_file($fail2ban)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FAIL2BAN_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FAIL2BAN_VERSION", fail2ban_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FAIL2BAN_INSTALLED", 0);

	}

    if(is_file($mountcifs)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CIFS_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CIFS_INSTALLED", 0);
	}




	if(is_file($letsencrypt)){
		if($GLOBALS["VERBOSE"]){echo "APP_LETSENCRYPT_VERSION --> Installed\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_LETSENCRYPT_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_LETSENCRYPT_VERSION", APP_LETSENCRYPT_VERSION());
	}else{
		if($GLOBALS["VERBOSE"]){echo "APP_LETSENCRYPT_VERSION --> UNKNOWN\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_LETSENCRYPT_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_LETSENCRYPT_VERSION", 0);
	}

	if(is_file("/usr/lib/x86_64-linux-gnu/security/pam_google_authenticator.so")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PAM_GOOGLE_AUTHENTICATOR_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PAM_GOOGLE_AUTHENTICATOR_INSTALLED", 0);
    }

	if(is_file("/usr/share/seafile-server/seahub/seahub/settings.py")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SEAFILE_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SEAFILE_VERSION", SEAFILE_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SEAFILE_INSTALLED", 0);
	}


	if(is_file($lighttpd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LIGHTTPD_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LIGHTTPD_INSTALLED", 0);
	}

	if(is_file("/usr/sbin/parprouted")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PARPROUTED_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PARPROUTED_INSTALLED", 0);
    }
    if(is_file("/usr/sbin/ospfd")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OSPFD_INSTALLED", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OSPFD_INSTALLED", 0);
    }




	if(is_file("/usr/sbin/wp")) {
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WP_CLIENT_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WP_CLIENT_VERSION", WP_CLIENT_VERSION());
	}else{
		if(is_file("/usr/sbin/wp-cli.phar")){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WP_CLIENT_INSTALLED", 1);
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WP_CLIENT_VERSION",WP_CLIENT_VERSION());
		}else{
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WP_CLIENT_INSTALLED", 0);
		}
	}



	if(is_file("/usr/bin/krenew")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KRENEW_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KRENEW_INSTALLED", 0);
	}



	if(is_file($fireqos)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FIREQOS_INSTALLED", 1);
		$fireqos_version=fireqos_version();
		if($GLOBALS["VERBOSE"]){echo "fireqos_version(): FINAL -- > $fireqos_version\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FIREQOS_VERSION", $fireqos_version);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FIREQOS_INSTALLED", 0);
	}


	if(is_file($java)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("JAVA_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("JAVA_VERSION", java_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("JAVA_INSTALLED", 0);
	}

	if(is_file("/usr/share/logstash/logstash-core/versions-gem-copy.yml")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOGSTASH_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOGSTASH_VERSION", logstash_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOGSTASH_INSTALLED", 0);
	}

	if(is_file("/usr/share/elasticsearch/modules/reindex/plugin-descriptor.properties")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ELASTICSEARCH_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ELASTICSEARCH_VERSION", elasticsearch_version());
	}

	if(is_file($fping)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FPING_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FPING_VERSION", fping_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FPING_INSTALLED", 0);
	}

	if(is_file($curlftpfs)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CURLFTPFS_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CURLFTPFS_VERSION", curlftpfs_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CURLFTPFS_INSTALLED", 0);
	}






	$XAPIAN_INSTALLED=false;
	if(is_file("/usr/lib/php/extensions/no-debug-non-zts-20160303/xapian.so")){
		file_put_contents("$SetDaemons/XAPIAN_PHP_INSTALLED",1);
		file_put_contents("$SetDaemons/XAPIAN_OMINDEX_VERSION",omega_version());
		$XAPIAN_INSTALLED=true;
	}
	if(is_file("/usr/lib/php/20151012/xapian.so")){
		file_put_contents("$SetDaemons/XAPIAN_PHP_INSTALLED",1);
		file_put_contents("$SetDaemons/XAPIAN_OMINDEX_VERSION",omega_version());
		$XAPIAN_INSTALLED=true;
	}
	if(!$XAPIAN_INSTALLED){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("XAPIAN_PHP_INSTALLED",0);}

	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireholInstalled",1);
	if(is_file($firehol)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireholVersion",FIREHOL_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireholVersion",0);
	}

	if(is_file($linkbalancerbin)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_LINK_BALANCER_INSTALLED",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_LINK_BALANCER_VERSION",FIREHOL_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_LINK_BALANCER_INSTALLED",0);
	}

	if(is_file("/usr/sbin/danted")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DANTED_INSTALLED",1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DANTED_VERSION",APP_DANTED_VERSION());
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DANTED_INSTALLED",0);
    }

	if(is_file("/usr/sbin/pptp")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_PPTP_CLIENT_INSTALLED",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_PPTP_CLIENT_VERSION",APP_PPTP_CLIENT_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_PPTP_CLIENT_INSTALLED",0);
	}





	if(is_file($oinkmaster)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_OINKMASTER_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_OINKMASTER_VERSION",oinkmaster_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_OINKMASTER_INSTALLED", 0);
	}


	if(is_file("/usr/share/wsusoffline/sh/versions/installed-version.txt")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WSUSOFFLINE_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WSUSOFFLINE_VERSION", WSUSOFFLINE_VERSION());

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WSUSOFFLINE_INSTALLED", 0);
	}
    if(is_dir("/sys/module/nf_conntrack")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nf_conntrack_loaded", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nf_conntrack_loaded", 0);
    }



	if(is_file("/opt/dnsfilterd/bin/dnsfilterd")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DNSFILTERD_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DNSFILTERD_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSFilterd", 0);
	}


	if(!is_file($nmap)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NMAPInstalled", 0);

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NMAPInstalled", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NMAPPath", $nmap);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NMAPVersion", nmap_version());

	}

	if(!is_file("/opt/splunkforwarder/bin/splunk")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SPLUNK_FORWARDER_INSTALLED", 0);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SPLUNK_FORWARDER_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SPLUNK_FORWARDER_VERSION", APP_SPLUNK_FORWARDER_VERSION());
	}


	if(!is_file($dsc)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSStatsInstalled", 0);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSCVersion", __DSC_VERSION());
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSC_VERSION", __DSC_VERSION());
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSStatsInstalled", 1);
	}
	if(is_file( "/etc/artica-postfix/KASPERSKY_WEB_APPLIANCE" )){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KASPERSKY_WEB_APPLIANCE",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KASPERSKY_WEB_APPLIANCE",0);
	}

	if(is_file("/etc/artica-postfix/WEBSECURIZE")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBSECURIZE",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBSECURIZE",0);
	}



	if(is_file("/usr/local/sbin/ulogd")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ULOGD_INSTALLED",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UlogdVersion", __ulogd_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ULOGD_INSTALLED",0);
	}



	if(is_file($zip)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ZIP_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ZIP_INSTALLED",0);
	}

	if(is_file("/usr/lib/x86_64-linux-gnu/xtables/libxt_ratelimit.so")){
		if($GLOBALS["VERBOSE"]){echo "APP_XTABLES_INSTALLED: TRUE\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_XTABLES_INSTALLED",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_XTABLES_VERSION",APP_XTABLES_ADDON_VERSION());
	}else{
		if($GLOBALS["VERBOSE"]){echo "APP_XTABLES_INSTALLED: FALSE\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_XTABLES_INSTALLED",0);
	}

    if(is_file("/usr/local/bin/hbbr")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RUSTDESK_INSTALLED",1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RUSTDESK_VERSION",APP_RUSTDESK_VERSION());


    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RUSTDESK_INSTALLED",0);
    }


	if(is_file("/usr/local/lib/openvpn-python.so")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_OPENVPN_PYTHON",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_OPENVPN_PYTHON",0);
	}

	if(is_file("/usr/local/bin/davserver")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DAVSERVER",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DAVSERVER",0);
	}
	if($GLOBALS["VERBOSE"]){echo "PROCESS 1 --- L.".__LINE__." --- \n";}

	if(is_file( $VBoxService )){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_VBOXADDINTION_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_VBOXADDINTION_INSTALLED",0);
	}


	if(is_file('/usr/local/bin/wanproxy')){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WANPROXY_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WANPROXY_VERSION", @file_get_contents("/usr/local/bin/wanproxy.version"));

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WANPROXY_INSTALLED", 0);
	}
	if(is_file($kinit)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KINIT_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KINIT_INSTALLED", 0);
	}
	if(is_file($iostat)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IOSTAT_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IOSTAT_INSTALLED", 0);
	}
	if(is_file("/usr/sbin/in.tftpd")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("TFTPD_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("TFTPD_VERSION", __tftpd_version());

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("TFTPD_INSTALLED", 0);
	}

	if(is_file("/opt/kaspersky/klnagent64/sbin/klnagent")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KLNAGENT_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KLNAGENT_VERSION", KLNAGENT_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KLNAGENT_INSTALLED", 0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KLNAGENT_VERSION", "");
	}



	if(is_file("/usr/local/bin/dsc")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSStatsInstalled", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSCVersion", pdns_stats_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PDNSStatsInstalled", 0);
	}


	if(is_file("/opt/ufdbcat/bin/ufdbcatdd")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_VERSION", ufdbcatVersion());


	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UFDBCAT_INSTALLED", 0);
	}
	if(is_file("/opt/kaspersky/UpdateUtility/UpdateUtility-Console")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KASPERSKY_UPDATE_UTILITY_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("KASPERSKY_UPDATE_UTILITY_INSTALLED", 0);
	}
	if(is_file("/usr/local/ArticaStats/bin/postgres")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("POSTGRESSQL_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("POSTGRESSQL_INSTALLED", 0);
	}
	if(is_file($pureftpd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PUREFTP_INSTALLED", 1);
		if(is_file("/etc/pure-ftpd/pureftpd.pdb")){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PUREFTP_USERS", 1);
		}else{
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PUREFTP_USERS", 0);
		}
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PUREFTP_INSTALLED", 0);
	}

	if(is_file($rpc_nfsd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NFS_SERVER_INTSALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NFS_SERVER_INTSALLED",0);
	}
	if(is_file($dstat)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSTAT_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DSTAT_INSTALLED",0);
	}
	if(is_file($gnuplot)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("GNUPLOT_PNG",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("GNUPLOT_PNG",0);
	}


	if(is_file($ntopng)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NtopNGInstalled", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NTOPNG_VERSION",ntopng_version());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NtopNGInstalled", 0);
	}

	if(is_file($sabnzbdplus)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SABNZBDPLUS_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SABNZBDPLUS_INSTALLED",0);
	}

	if(is_file($muninnode)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MUNIN_CLIENT_INSTALLED",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MUNIN_CLIENT_VERSION",MUNIN_NODE_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MUNIN_CLIENT_INSTALLED",0);
	}
	if(is_file($php_fpm)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PHPFPM_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PHPFPM_INSTALLED",0);
	}
	if(is_file($offlineimap)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("offlineimap_installed",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("offlineimap_installed",0);
	}
	if(is_file($postscreen)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("POSTSCREEN_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("POSTSCREEN_INSTALLED",0);
	}
	if(is_file($ipset)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPSET_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPSET_INSTALLED",0);
	}
	if(is_file($aspell)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ASPELL_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ASPELL_INSTALLED",0);
	}
	if(is_file($curlftpfs)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CURLFTPFS_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CURLFTPFS_INSTALLED",0);
	}


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ARTICAPCAP_VERSION",APP_ARTICAPCAP_VERSION());



	if(is_file($squid27)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SQUID27_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_SQUID27_INSTALLED",0);
	}
	if(is_file($ebtables)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_EBTABLES_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_EBTABLES_INSTALLED",0);
	}

    if(is_file($adagent)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ADAGENT_INSTALLED",1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ADAGENT_VERSION",adagent_version($adagent));

    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ADAGENT_INSTALLED",0);
    }
	if(is_file($freeradius)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FREERADIUS_INSTALLED",1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FREERADIUS_VERSION",FREERADIUS_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("FREERADIUS_INSTALLED",0);

	}

	if(is_file($ucarp)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UCARP_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UCARP_INSTALLED",0);
	}
	if(is_file($vde_switch)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("VDESWITCH_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("VDESWITCH_INSTALLED",0);
	}
	if(is_file($wifidog)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WIFIDOG_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WIFIDOG_INSTALLED",0);
	}
	if(is_file($ftp_proxy)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FTP_PROXY",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FTP_PROXY",0);
	}
	if(is_file("/usr/share/filez/index.php")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FILEZ_WEB",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_FILEZ_WEB",0);
	}

	if(is_file("/etc/artica-postfix/OPENVPN_APPLIANCE")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OPENVPN_APPLIANCE",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OPENVPN_APPLIANCE",0);
	}
    if(is_file("/usr/sbin/tailscaled")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_TAILSCALE_INSTALLED",1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_TAILSCALE_VERSION",APP_TAILSCALE_VERSION());
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_TAILSCALE_INSTALLED",0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableTailScaleService",0);
    }

	if(is_file("$openvpn_bin")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OPENVPN_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("OPENVPN_INSTALLED",0);
	}

	if(is_file("$strongswan_bin")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("STRONGSWAN_INSTALLED",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_STRONGSWAN_VERSION",APP_STRONGSWAN_VERSION());
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("StrongswanVersion",APP_STRONGSWAN_VERSION());

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("STRONGSWAN_INSTALLED",0);
	}

    if(is_file("/usr/bin/apt-mirror")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APT_MIRROR_INSTALLED",1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APT_MIRROR_VERSION",APT_MIRROR_VERSION());

    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APT_MIRROR_INSTALLED",0);
    }

	if(is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WORDPRESS_APPLIANCE",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WORDPRESS_APPLIANCE",0);
	}
	if(is_file("/usr/share/wordpress-src/wp-includes/version.php")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WORDPRESS_INSTALLED",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WORDPRESS_SRC_VERSION",WORDPRESS_SRC_VERSION());
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WORDPRESS_INSTALLED",0);
	}


	if(is_file($miltergreylist)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MILTERGREYLIST_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MILTERGREYLIST_INSTALLED",0);
	}
	if(is_file($iscsid)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSID_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSID_INSTALLED",0);
	}

	if(is_file($arpd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ARPD_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ARPD_INSTALLED",0);
	}
	if(is_file($cgrulesengd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CGROUPS_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CGROUPS_INSTALLED",0);
	}
	if(is_file($hostapd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HOSTAPD_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HOSTAPD_INSTALLED",0);
	}
	if(is_file($hostapd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HOSTAPD_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HOSTAPD_INSTALLED",0);
	}

	if(is_file($losetup)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOSETUP_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LOSETUP_INSTALLED",0);
	}
	if(is_file($vconfig)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("VLAN_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("VLAN_INSTALLED",0);
	}
	if(is_file($iscsiadm)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSI_CLIENT_INSTALLED",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSI_INSTALLED",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSI_VERSION",iscsi_version());

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSI_CLIENT_INSTALLED",0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSI_INSTALLED",0);
	}

	if(is_file($targetcli)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSI_TARGETCLI_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ISCSI_TARGETCLI_INSTALLED",0);
	}


	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("dbus_daemon_version",dbus_daemon_version());

	if(is_file($monitbin)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MONIT_INSTALLED", 1);
		$vv=monit_daemon_version();
		if($GLOBALS["VERBOSE"]){echo "SAVING APP_MONIT_VERSION -> $vv\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_MONIT_VERSION",$vv);

	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MONIT_INSTALLED", 0);
	}

	if(is_file("$doh_server")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DOH_SERVER_INSTALLED",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DOH_SERVER_INSTALLED",0);
	}

	if(is_file("/usr/share/kibana/package.json")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_KIBANA_INSTALLED", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_KIBANA_VERSION",kibana_version());

	}


	if(is_file($ipset)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPSetInstalled",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IPSetInstalled",0);
	}

//******************************************************************************************************
	if(is_file($msktutil)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MSKTUTIL_INSTALLED",1);
		if($GLOBALS["VERBOSE"]){echo "MSKTUTIL_VERSION -> ...\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MSKTUTIL_VERSION",__MSKTUTIL_VERSION());
	}else{
		if($GLOBALS["VERBOSE"]){echo "MSKTUTIL_VERSION -> NOT INSTALLED...\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MSKTUTIL_INSTALLED",0);
	}

//******************************************************************************************************
	if(is_file("/lib/modules/$KERNEL_VERSION/extra/xt_ndpi.ko")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("iptablesNDPI",1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("iptablesNDPI",0);
	}
//**************************************************************************************************

//**************************************************************************************************
	if(!is_file("/etc/cron.d/artica-clean-postgresql")){
		$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("artica-clean-postgresql",
			"30 3 * * *","exec.clean.postgres.php");
		shell_exec2("/etc/init.d/cron reload");

	}
//**************************************************************************************************

	if(!is_file($cgrulesengd)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CGROUPS_INSTALLED", 0);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CGROUPS_INSTALLED", 1);
	}

	if(!is_file("/usr/lib/apache2/modules/mod_cband.so")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ApacheModCbandInstalled", 0);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ApacheModCbandInstalled", 1);
	}

	if(is_file("/usr/local/ArticaStats/bin/postgres")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("StatsPostGres", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("StatsPostGres", 0);
	}

	if(is_file("/etc/monit/conf.d/APP_SLAPD.monitrc")){
		@unlink("/etc/monit/conf.d/APP_SLAPD.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}


	if(is_file("/etc/cron.d/access-failed-parser")){@unlink("/etc/cron.d/access-failed-parser");}
	if(is_file("/etc/cron.d/access-logs-parser")){@unlink("/etc/cron.d/access-logs-parser");}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableWebFTPFilter")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWebFTPFilter", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableDNSPerfs")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSPerfs", 1);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableSquidQuotasBandwidth")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSquidQuotasBandwidth", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidQuotaBandwidthRefresh")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidQuotaBandwidthRefresh", 30);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableMacAddressFilter")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableMacAddressFilter", 1);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/InfluxSyslogRemote")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("InfluxSyslogRemote", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableSuricata")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSuricata", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/cgroupsEnabled")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("cgroupsEnabled", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/cgroupsEnabled")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("cgroupsEnabled", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableClamavUnofficial")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableClamavUnofficial", 0);}

	$EnableClamavUnofficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavUnofficial"));

	if(!is_file("/etc/nginx/certificates/dhparam.pem")){
		if(!is_dir("/etc/nginx/certificates")){@mkdir("/etc/nginx/certificates",0755,true);}
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($GLOBALS["CLASS_UNIX"]->PIDOF($openssl))){
			system("$nohup $openssl dhparam -outform PEM -out /etc/nginx/certificates/dhparam.pem 2048 >/dev/null 2>&1");
		}

	}



	if(!is_file("/usr/local/bin/load_balance.pl")){
		$DEBIAN_VERSION=$GLOBALS["CLASS_UNIX"]->DEBIAN_VERSION();
		$tgz="/usr/share/artica-postfix/bin/install/netbalance-debian{$DEBIAN_VERSION}.tar.gz";
		if(is_file($tgz)){
			system("$tar -xhf $tgz -C /");
			squid_admin_mysql(1, "Installed Net Balance package for Debian $DEBIAN_VERSION", null,__FILE__,__LINE__);
		}
	}

	if(is_file("/usr/local/bin/load_balance.pl")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETBALANCE_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NETBALANCE_INSTALLED", 0);
	}


	if($GLOBALS["VERBOSE"]){echo "PROCESS 1 --- DONE --- \n";}

}

function var_lib_php(){
	$unix=new unix();
	$df=$unix->find_program("df");
	exec("$df /var/lib/php5 2>&1",$results);
	$percent=0;
	foreach ($results as $cidr=>$none){
		if(preg_match("#.*?\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)%\s+#", $none,$re)){
			$percent=$re[4];
			break;
		}
	}

	if($percent<92){return;}
	squid_admin_mysql(1, "/var/lib/php5 partition size execeed 92% ({$percent}%) [action=clean]", @implode("\n", $results),__FILE__,__LINE__);
	$files=$unix->DirFiles("/var/lib/php5");

	@unlink("/usr/share/artica-postfix/ressources/logs/php.log");
	@touch("/usr/share/artica-postfix/ressources/logs/php.log");

	foreach ($files as $filename=>$none){
		$fullpath="/var/lib/php5/$filename";
		$time=$unix->file_time_min($fullpath);
		if($time>15){@unlink($fullpath);continue;}

	}

}

function buildCronStatistics(){
	$ENABLED=true;

	$AsStatsAppliance=0;


	// if > 1 then no stats $SquidPerformance
	$InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));


	if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
		$InfluxUseRemote=0;
		$AsStatsAppliance=1;
	}
	if($InfluxUseRemote==1){$ENABLED=false;}


	if($ENABLED){
		if(!is_file("/etc/cron.d/statistics-monthly")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("statistics-monthly","15 1 * * *","exec.squid.stats.compress.php");
			shell_exec2("/etc/init.d/cron reload");
		}
		if(!is_file("/etc/cron.d/statistics-yearly")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("statistics-yearly","30 3 1 * *","exec.squid.stats.compress.php --compress-year");
			shell_exec2("/etc/init.d/cron reload");
		}


		buildCronStatistics_run();
		buildCronStatistics_year_run();
	}

	if(!$ENABLED){
		if(is_file("/etc/cron.d/statistics-monthly")){
			@unlink("/etc/cron.d/statistics-monthly");
			shell_exec2("/etc/init.d/cron reload");
		}
		if(is_file("/etc/cron.d/statistics-yearly")){
			@unlink("/etc/cron.d/statistics-yearly");
			shell_exec2("/etc/init.d/cron reload");
		}

	}

}
function buildCronStatistics_run(){
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidStatsMonthQueue")){return;}
	$SquidStatsMonthQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStatsMonthQueue"));
	if($SquidStatsMonthQueue==0){return;}
	if(system_is_overloaded()){return;}
	$pidfile="/etc/artica-postfix/pids/SquidStatsMonthQueue.pid";
	$pidTime="/etc/artica-postfix/pids/SquidStatsMonthQueue.time";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
	if($timeExec<15){return;}
	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.stats.compress.php >/dev/null 2>&1 &";
	shell_exec2(trim($cmd));
}
function buildCronStatistics_year_run(){

	$cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.stats.compress.php --compress-year >/dev/null 2>&1 &";
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidStatsYearQueue")){return;}
	$SquidStatsYearQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidStatsYearQueue"));
	if($SquidStatsYearQueue==0){return;}
	if(system_is_overloaded()){return;}
	$pidfile="/etc/artica-postfix/pids/SquidStatsYearQueue.pid";
	$pidTime="/etc/artica-postfix/pids/SquidStatsYearQueue.time";
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
	$timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
	if($timeExec<15){return;}
	shell_exec2(trim($cmd));
}


function IS_VMWARE_HOST(){
	if(is_file("/etc/artica-postfix/VMWARE_NOHOST")){
		@unlink("/etc/artica-postfix/VMWARE_HOST");
		@unlink("/etc/artica-postfix/VMWARE_NOHOST");
	}

	if(is_file("/etc/artica-postfix/VMWARE_HOST")){
		$xtime=$GLOBALS["CLASS_UNIX"]->file_time_get("/etc/artica-postfix/VMWARE_HOST");
		if($xtime>120){@unlink("/etc/artica-postfix/VMWARE_HOST");}
	}
	if(is_file("/etc/artica-postfix/VMWARE_HOST")){
		$data=trim(@file_get_contents("/etc/artica-postfix/VMWARE_HOST"));
		if(is_numeric($data)){return intval($data);}
	}

	if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
		$xtime=$GLOBALS["CLASS_UNIX"]->file_time_get("/etc/artica-postfix/dmidecode-type-1.cache");
		if($xtime>480){@unlink("/etc/artica-postfix/dmidecode-type-1.cache");}
	}
	if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
		$results=unserialize(@file_get_contents("/etc/artica-postfix/dmidecode-type-1.cache"));

	}else{
		$dmidecode=$GLOBALS["CLASS_UNIX"]->find_program('dmidecode');
		if (!is_file("$dmidecode")){return 0;}
		exec("$dmidecode --type 1 2>&1",$results);
		@file_put_contents("/etc/artica-postfix/dmidecode-type-1.cache", serialize($results));
	}

	foreach($results as $line){
		if(preg_match("#VMware Virtual Platform#i",$line)){
			@file_put_contents("/etc/artica-postfix/VMWARE_HOST",1);
			return 1;
		}
	}
	@file_put_contents("/etc/artica-postfix/VMWARE_HOST",0);
	return 0;
}
//##############################################################################
function IS_VIRTUALBOX_HOST(){
	if(is_file("/etc/artica-postfix/VIRTUALBOX_NOHOST")){
		@unlink("/etc/artica-postfix/VIRTUALBOX_HOST");
		@unlink("/etc/artica-postfix/VIRTUALBOX_NOHOST");
	}

	if(is_file("/etc/artica-postfix/VIRTUALBOX_HOST")){
		$xtime=$GLOBALS["CLASS_UNIX"]->file_time_get("/etc/artica-postfix/VIRTUALBOX_HOST");
		if($xtime>120){@unlink("/etc/artica-postfix/VIRTUALBOX_HOST");}
	}
	if(is_file("/etc/artica-postfix/VIRTUALBOX_HOST")){
		$data=trim(@file_get_contents("/etc/artica-postfix/VIRTUALBOX_HOST"));
		if($GLOBALS["VERBOSE"]){echo "L.".__LINE__." VIRTUALBOX_HOST='$data'\n";}
		if(is_numeric($data)){return intval($data);}
	}

	if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
		$xtime=$GLOBALS["CLASS_UNIX"]->file_time_get("/etc/artica-postfix/dmidecode-type-1.cache");
		if($xtime>480){@unlink("/etc/artica-postfix/dmidecode-type-1.cache");}
	}
	if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
		$results=unserialize(@file_get_contents("/etc/artica-postfix/dmidecode-type-1.cache"));

	}else{
		$dmidecode=$GLOBALS["CLASS_UNIX"]->find_program('dmidecode');
		if (!is_file("$dmidecode")){return 0;}
		exec("$dmidecode --type 1 2>&1",$results);
		@file_put_contents("/etc/artica-postfix/dmidecode-type-1.cache", serialize($results));
	}

	foreach($results as $line){
		if(preg_match("#VirtualBox#",$line)){
			@file_put_contents("/etc/artica-postfix/VIRTUALBOX_HOST",1);
			return 1;
		}
	}
	@file_put_contents("/etc/artica-postfix/VIRTUALBOX_HOST",0);
	return 0;
}

function IS_HYPERV_HOST(){
	if(is_file("/etc/artica-postfix/HYPERV_NOHOST")){
		@unlink("/etc/artica-postfix/HYPERV_HOST");
		@unlink("/etc/artica-postfix/HYPERV_NOHOST");
	}

	if(is_file("/etc/artica-postfix/HYPERV_HOST")){
		$xtime=$GLOBALS["CLASS_UNIX"]->file_time_get("/etc/artica-postfix/HYPERV_HOST");
		if($xtime>120){@unlink("/etc/artica-postfix/HYPERV_HOST");}
	}
	if(is_file("/etc/artica-postfix/HYPERV_HOST")){
		$data=trim(@file_get_contents("/etc/artica-postfix/HYPERV_HOST"));
		if(is_numeric($data)){return intval($data);}
	}

	if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
		$xtime=$GLOBALS["CLASS_UNIX"]->file_time_get("/etc/artica-postfix/dmidecode-type-1.cache");
		if($xtime>480){@unlink("/etc/artica-postfix/dmidecode-type-1.cache");}
	}
	if(is_file("/etc/artica-postfix/dmidecode-type-1.cache")){
		$results=unserialize(@file_get_contents("/etc/artica-postfix/dmidecode-type-1.cache"));

	}else{
		$dmidecode=$GLOBALS["CLASS_UNIX"]->find_program('dmidecode');
		if (!is_file("$dmidecode")){return 0;}
		exec("$dmidecode --type 1 2>&1",$results);
		@file_put_contents("/etc/artica-postfix/dmidecode-type-1.cache", serialize($results));
	}

	foreach($results as $line){
		if(preg_match("#Microsoft Corporation#",$line)){
			@file_put_contents("/etc/artica-postfix/HYPERV_HOST",1);
			return 1;
		}
	}
	@file_put_contents("/etc/artica-postfix/HYPERV_HOST",0);
	return 0;
}

function LOGROTATE(){

}




function PERSONAL_CATEGORIES_CRON(){
	$RESTART_CRON=false;

    if(!is_file("/etc/init.d/apt-get-update")){
        $sapt="/usr/bin/apt-get";
        $apt[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
        $apt[]="MAILTO=\"\"";
        $apt[]="50 3 * * *	root	$sapt update -y --allow-releaseinfo-change && $sapt update -y >/dev/null 2>&1";
        $apt[]="";
        @file_put_contents("/etc/init.d/apt-get-update",@implode("\n",$apt));
        $RESTART_CRON=true;
    }



	if(is_file("/etc/init.d/proxy-pac")){
		if(!is_file("/etc/cron.d/proxy-pac-stats")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("proxy-pac-stats","*/10 * * * *","exec.proxy-pac.stats.php");
			$RESTART_CRON=true;
		}
	}





	if(!is_file("/etc/cron.d/letsencrypt-renew")){
		$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("letsencrypt-renew","45 2 * * *","exec.letsencrypt.renew.php");
		$RESTART_CRON=true;
	}



	$useCGuardCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCGuardCategories"));



	if($useCGuardCategories==1){
		if(!is_file("/etc/cron.d/cguard")){
			$GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("cguard","20 */2 * * *","exec.cguard.php --sync");
			$RESTART_CRON=true;
		}
	}else{
		if(is_file("/etc/cron.d/cguard")){@unlink("/etc/cron.d/cguard");$RESTART_CRON=true;}
	}



	if(!is_file("/etc/cron.d/categories-maintenance")) {
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("categories-maintenance", "30 */2 * * *", "exec.categories.maintenance.php");
        $RESTART_CRON = true;
    }

	if($RESTART_CRON){
		UNIX_RESTART_CRON();
	}

}

function PATCH_120_SQUID(){
	if(!is_file("/usr/sbin/proxy-logs-monitor")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("RESTART_INSTALL_SQUID",0);
		return true;
	}
	if(!is_file("/etc/init.d/squid")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("RESTART_INSTALL_SQUID",0);
		return true;
	}

	$f=explode("\n",@file_get_contents("/etc/init.d/squid"));
	foreach ($f as $line){
		if(preg_match("#SQUID_ARGS.*?-sYC#",$line)){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("RESTART_INSTALL_SQUID",0);
			return true;
		}

		if(preg_match("#SQUID_ARGS=.*?-s\s+-C\s+-f#",$line)){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("RESTART_INSTALL_SQUID",0);
			return true;
		}
	}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("RESTART_INSTALL_SQUID",1);
	return false;
}

function IS_INSIDE_VPS(){
	if(is_file('/proc/vz/veinfo')){return true;}
	if(is_file('/proc/vz/version')){return true;}
	if(is_dir('/proc/xen')){return true;}
	if(is_file('/proc/1/cgroup')){
		exec('/bin/cat /proc/1/cgroup 2>&1',$results);
		if(preg_match("#cpuset:/(.*)$#", @implode(" ", $results))){ return true;}
	}
	return null;
}
function SIEVE_PORT(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	$f=explode("\n","/etc/services");
	foreach ($f as $line){
		if(preg_match("#^sieve\s+([0-9]+)#", $line,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $re[1];
		}

	}
	$GLOBALS[__FUNCTION__]=null;
	return null;
}
function watch_fsfilemax(){
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $pfixcmd="{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix";
    if($SQUIDEnable==0){return true;}
	$filemax=intval(@file_get_contents("/proc/sys/fs/file-max"));

    if($filemax<80128){
        squid_admin_mysql(1,"Restoring Kernel Max File descriptors from $filemax".
            " must at least 80 128",null,__FILE__,__LINE__);
        shell_exec("/sbin/sysctl -w fs.file-max=80128");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fs_filemax",80128);
        $filemax=80128;
        $cmd=trim("$pfixcmd/exec.sysctl.php --build >/dev/null 2>&1 &");
        shell_exec($cmd);
    }

	$max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));

    if($max_filedesc>$filemax){
        $max_filedesc=$max_filedesc+5000;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fs_filemax",$max_filedesc);
        squid_admin_mysql(1,"Restoring Kernel Max File descriptors from $filemax".
            " must at least $max_filedesc",null,__FILE__,__LINE__);
        shell_exec("/sbin/sysctl -w fs.file-max=$max_filedesc");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fs_filemax",$max_filedesc);
        $cmd=trim("$pfixcmd/exec.sysctl.php --build >/dev/null 2>&1 &");
        shell_exec($cmd);
        return true;
    }

    $fs_filemax=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("fs_filemax"));
    if($fs_filemax>$filemax){
        squid_admin_mysql(1,"Restoring Kernel Max File descriptors from $filemax".
            " must at least $fs_filemax",null,__FILE__,__LINE__);
        shell_exec("/sbin/sysctl -w fs.file-max=$fs_filemax");
        $cmd=trim("$pfixcmd/exec.sysctl.php --build >/dev/null 2>&1 &");
        shell_exec($cmd);
        return true;

    }

    $file_nr=@file_get_contents("/proc/sys/fs/file-nr");
    preg_match("#^([0-9]+)\s+#",$file_nr,$re);
    $current=intval($re[1]);
    $prc=$current/$filemax;
    $prc=$prc*100;
    $prc=round($prc);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fs_filemax_prc",$prc);

    if($prc>95){
        $new_filemax=$filemax+10000;
        squid_admin_mysql(0,"System filedescriptors {$prc}% reached! Restoring Kernel Max File descriptors from $filemax".
            " to $new_filemax",null,__FILE__,__LINE__);
        shell_exec("/sbin/sysctl -w fs.file-max=$fs_filemax");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fs_filemax",$new_filemax);
        return true;
    }


    return true;
}

function postfix_verify_files():bool{
    $postmap="/usr/sbin/postmap";
    if(!is_file("/etc/postfix/bad_recipients.db")){
        if(is_file("/etc/postfix/bad_recipients")){
            shell_exec("$postmap hash:/etc/postfix/bad_recipients");
        }else{
            @touch("/etc/postfix/bad_recipients");
            shell_exec("$postmap hash:/etc/postfix/bad_recipients");
        }

    }
    return true;


}



function check_go_shield_server_updates():bool{
    if(!is_file("/etc/init.d/squid")){return true;}
    check_go_shield_connector_updates();
    return true;
}

function check_go_shield_connector_updates():bool{
    if(!is_file("/etc/init.d/squid")){return true;}
    $Root="/usr/share/artica-postfix/bin/go-shield";
    $go_shield_connector_src       = "$Root/client/external_acl_first/bin/go-shield-connector";
    $go_shield_connector_dst       = "/lib/squid3/go-shield-connector";
    if(!is_dir("/lib/squid3")){@mkdir("/lib/squid3",0755,true);}

    if(is_file($go_shield_connector_src)) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_CONNECTOR_SRC", md5_file($go_shield_connector_src));
        if (!is_file($go_shield_connector_dst)) {
            @copy($go_shield_connector_src, $go_shield_connector_dst);
        }
    }
    if(is_file($go_shield_connector_dst)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_CONNECTOR_DST",md5_file($go_shield_connector_dst));
    }
    return true;
}


function APP_GO_SHIELD_VERSION():string{
    $Go_Shield_Server_Version="";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"))==0){return "";}
    if(!function_exists("curl_init")){return "";}
    $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
    $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
    if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}
    $cURLConnection = curl_init();
    curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/get-version");
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cURLConnection, CURLOPT_NOPROXY, "*");
    $resp = curl_exec($cURLConnection);
    curl_close($cURLConnection);
    $jsonArrayResponse = json_decode($resp,true);
    if(isset($jsonArrayResponse["version"])){$Go_Shield_Server_Version = $jsonArrayResponse["version"];}
    return $Go_Shield_Server_Version;
}
