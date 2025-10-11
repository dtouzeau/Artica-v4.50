#!/usr/bin/php

<?php
# apt-get install libarchive-tools genisoimage php7.3-cli simple-cdd xorriso
$GLOBALS["EXTRACT_PACKAGES"]=false;
$GLOBALS["WORKDIR"]="/home/ISO_PROFILE";
$GLOBALS["EXTRACT_PATH"]="{$GLOBALS["WORKDIR"]}/images/extract";
$GLOBALS["bsdtar"]="/usr/bin/bsdtar";
$GLOBALS["genisoimage"]="/usr/bin/genisoimage";
$GLOBALS["xorriso"]="/usr/bin/xorriso";
$GLOBALS["rm"]="/bin/rm";
$GLOBALS["build-simple-cdd"]="/usr/bin/build-simple-cdd";
$GLOBALS["tar"]="/bin/tar";
$GLOBALS["INITD"]["squid-db"]="PROXY_APPLIANCE";
$GLOBALS["INITD"]["zarafa-db"]="ZARAFA_APPLIANCE";
chdir("/root");
shell_exec("cd /root");
if(isset($argv[1])){
	if($argv[1]=="--prepare-iso"){perpare_iso();die("DIE " .__FILE__." Line: ".__LINE__);}
	if($argv[1]=="--install"){install_profiles();die("DIE " .__FILE__." Line: ".__LINE__);}
	if($argv[1]=="--mirror"){mirror();die("DIE " .__FILE__." Line: ".__LINE__);}
	if($argv[1]=="--proxy"){make_appliance("PROXY_APPLIANCE");die("DIE " .__FILE__." Line: ".__LINE__);}
	if($argv[1]=="--proxy-packages"){$GLOBALS["EXTRACT_PACKAGES"]=true;packages();die("DIE " .__FILE__." Line: ".__LINE__);}
	if($argv[1]=="--prepare-iso-squid"){prepareiso("PROXY_APPLIANCE");die("DIE " .__FILE__." Line: ".__LINE__);}
	if($argv[1]=="--packages"){echo packages();die("DIE " .__FILE__." Line: ".__LINE__);}
	if($argv[1]=="--update-iso"){update_iso($argv[2]);die("DIE " .__FILE__." Line: ".__LINE__);}
	if($argv[1]=="--update-proxy"){update_iso();die("DIE " .__FILE__." Line: ".__LINE__);}
	if($argv[1]=="--split"){ScanSplitted();exit;}
    if($argv[1]=="--sp"){echo GetArticaSPVers();exit;}
    if($argv[1]=="--hotfix"){echo GetLatestArticaHotFix()."\n";exit;}

}


echo @implode($argv)." ...??\n";
echo "--mirror [type]		:............. Build the profile and create the mirror\n";
echo "--prepare-iso		:............. Prepare the ISO file.\n";
echo "--install		:............. Install Artica Profiles.\n";
echo "--proxy			:............  Compiling Proxy Appliance 64 bits ISO\n";
echo "--gateway               :............  Compiling Gateway 64 bits ISO\n";
echo "--prepare-iso-squid     :............  Prepare ISO for Proxy\n";
echo "--prepare-iso-kav4proxy :............  Prepare ISO for Kav For Proxy\n";
echo "--update-cyrus          :............  Prepare ISO for IMAP Appliance\n";
echo "Tokens\n";
echo "PROXY_APPLIANCE, GATEWAY_APPLIANCE,ZARAFA_APPLIANCE,NAS_APPLIANCE,KASPER_MAIL_APP,KASPERSKY_WEB_APPLIANCE,HAPRROXY_APPLIANCE\n";
echo "\n";




function make_appliance($type="PROXY_APPLIANCE"){
	$workdir="/home/working-profiles/$type";
	$GLOBALS["WORKDIR"]=$workdir;
	$GLOBALS["PROFILES_DIR"]="$workdir/tmp/profiles";
	$isodir="$workdir/tmp/images";
	$extract="$isodir/extract";
	$profiles_dir="$workdir/tmp/profiles";
	$GLOBALS["EXTRACT_PATH"]=$extract;
	if(!is_dir($workdir)){@mkdir($workdir,0755,true);echo "Creating $workdir\n";}
	if(!is_dir($isodir)){echo "Creating $isodir\n";@mkdir($isodir,0755,true);}
	if(!is_dir($profiles_dir)){echo "Creating $profiles_dir\n";@mkdir($profiles_dir,0755,true);}

	echo "Workdir....: {$GLOBALS["WORKDIR"]}\n";
	echo "Extract Dir: $extract\n";

	pressed($profiles_dir,$type);



	$f[]="mirror_components=\"main contrib non-free\"";
	//$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-1.7.092600.tgz\"";
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-iso\"";
	$f[]="profiles=\"ARTICA\"";
	$f[]="auto_profiles=\"ARTICA\"";

	@mkdir($GLOBALS["PROFILES_DIR"],0755,true);
	if(!is_file("{$GLOBALS["PROFILES_DIR"]}/ARTICA.conf")){
		echo "Creating {$GLOBALS["PROFILES_DIR"]}/ARTICA.conf\n";
		@file_put_contents("{$GLOBALS["PROFILES_DIR"]}/ARTICA.conf", @implode("\n", $f)."\n");
	}
	$f=array();
	pressed($GLOBALS["PROFILES_DIR"],$type);
	@chdir($GLOBALS["PROFILES_DIR"]);


	$iso=buildedgetiso($isodir);

	if(!is_file($iso)){
		echo "Unable to stat any iso in $isodir\n";
		return;
	}


	if(is_dir($extract)){
		echo "Remove $extract\n";
		shell_exec("{$GLOBALS["rm"]} -rf $extract");
	}

	echo "Create $extract\n";
	@mkdir($extract,0755,true);
	echo "$type: Extracting ISO in $extract\n";
	shell_exec("{$GLOBALS["bsdtar"]} -C $extract -xf $iso");

	if(is_dir("$extract/isolinux")){
		echo "Remove $extract/isolinux\n";
		shell_exec("{$GLOBALS["rm"]} -rf $extract/isolinux");
	}
	echo "Installing Isolinux\n";
	shell_exec("{$GLOBALS["tar"]} -xf /home/new-profile/isolinux.tar -C $extract/");
	if(is_file("/root/$type.png")){
		echo "Installing /root/$type.png\n";
		if(!is_file("$extract/isolinux/splash.png")){
			echo "$extract/isolinux/splash.png no such file... die\n";
			die("DIE " .__FILE__." Line: ".__LINE__);
		}


		@unlink("$extract/isolinux/splash.png");
		@copy("/root/$type.png","$extract/isolinux/splash.png");

	}else{
		echo "/root/$type.png no such image\n";
	}

	$f=array();
	$welcome="Welcome to the Artica Installer";
	if(is_file("/root/$type.Welcome")){
		$welcome=trim(@file_get_contents("/root/$type.Welcome"));
	}
	$f[]="menu hshift 7";
	$f[]="menu width 61";
	$f[]="";
	$f[]="menu title $welcome";
	$f[]="include stdmenu.cfg";
	$f[]="include adtxt.cfg";
	$f[]="menu end";
	$f[]="label help";
	$f[]="	menu label ^Help";
	$f[]="	text help";
	$f[]="   Display help screens; type 'menu' at boot prompt to return to this menu";
	$f[]="	endtext";
	$f[]="	config prompt.cfg\n";

	if(!is_file("$extract/isolinux/menu.cfg")){
		echo "$extract/isolinux/menu.cfg no such file... die\n";
		die("DIE " .__FILE__." Line: ".__LINE__);

	}

	@file_put_contents("$extract/isolinux/menu.cfg", @implode("\n", $f));

	pressed("$extract/simple-cdd",$type);
	prepareiso($type);


}

function create_iso_source($isodir,$type){
	$extract="$isodir/extract";
	$originalisopath=buildedgetiso($isodir);
	$firmware_path="/home/firmware.cpio.gz";
	if(!is_file($originalisopath)){
		echo "ALL:[".__LINE__."]: could not find any iso in $isodir\n";
		return false;
	}

	$size=filesize($originalisopath);
	echo "ALL:[".__LINE__."]: ".basename($originalisopath)." $size bytes\n";
//	if($size<421986304){echo "$originalisopath wrong size: $size < 421986304\n";return false;}
	if($size<100000){echo "$originalisopath wrong size: $size < 100000\n";return false;}

	if(is_dir($extract)){
		echo "ALL:[".__LINE__."]: removing $extract directory\n";
		shell_exec("{$GLOBALS["rm"]} -rf $extract");
	}

	echo "ALL:[".__LINE__."]: Create $extract\n";
	@mkdir($extract,0755,true);
	echo "ALL:[".__LINE__."]: Extracting ISO in $extract\n";
	shell_exec("{$GLOBALS["bsdtar"]} -C $extract -xf $originalisopath");

	if(!is_dir("$extract/isolinux")){echo "ALL:[".__LINE__."]: $extract/isolinux no such directory\n";return false;}
	echo "ALL:[".__LINE__."]: Remove $extract/isolinux\n";
	shell_exec("{$GLOBALS["rm"]} -rf $extract/isolinux");
	echo "ALL:[".__LINE__."]: Installing new isolinux\n";
	shell_exec("{$GLOBALS["tar"]} -xf /home/new-profile/isolinux.tar -C $extract/");
	if(!is_dir("$extract/isolinux")){echo "ALL:[".__LINE__."]: $extract/isolinux no such directory\n";return false;}

	shell_exec("{$GLOBALS["tar"]} -xf /home/new-profile/isolinux.tar -C $extract/");
	if(is_file("/root/$type.png")){
		echo "Installing /root/$type.png\n";
		if(!is_file("$extract/isolinux/splash.png")){
		echo "$extract/isolinux/splash.png no such file... die\n";
		die("DIE " .__FILE__." Line: ".__LINE__);
		}


	if(is_file($firmware_path)){
			echo "initrd.gz / install.amd\n";
			if(!is_dir("$extract/install.amd")){
				echo "ALL:[".__LINE__."]: * * * FATAL $extract/install.amd * * *\n";
				echo "ALL:[".__LINE__."]: * * *         NOT FOUND     * * *\n";
				die("DIE " .__FILE__." Line: ".__LINE__);
			}

			$initrd_original_path="$extract/install.amd/initrd.gz";
			$initrd_original_copy_path="$initrd_original_path.orig";


			if(!is_file($initrd_original_path)){
			echo "ALL:[".__LINE__."]: * * * FATAL $initrd_original_path no such file * * *\n";
			die("DIE " .__FILE__." Line: ".__LINE__);
			}
			if(is_file($initrd_original_copy_path)){@unlink($initrd_original_copy_path);}

			@copy($initrd_original_path, $initrd_original_copy_path);

			echo "ALL:[".__LINE__."]: Patching firmware\n";
			$cmd="/bin/cat $initrd_original_copy_path $firmware_path > $initrd_original_path";
			echo "ALL:[".__LINE__."]: $cmd\n";
			shell_exec($cmd);
	}else{
		echo "ALL:[".__LINE__."]:* * * WARNING $firmware_path was not generated * * *\n";
		echo "ALL:[".__LINE__."]: Please contact our support team, this file should include non-free firemwares\n";
		sleep(2);


	}



	@unlink("$extract/isolinux/splash.png");
	@copy("/root/$type.png","$extract/isolinux/splash.png");
	}else{
		echo "/root/$type.png no such image\n";
	}

	$f=array();
	$welcome="Welcome to the Artica CD-ROM";
	if(is_file("/root/$type.Welcome")){
		$welcome=trim(@file_get_contents("/root/$type.Welcome"));
	}
	$f[]="menu hshift 7";
	$f[]="menu width 61";
	$f[]="";
	$f[]="menu title $welcome";
	$f[]="include stdmenu.cfg";
	$f[]="include adtxt.cfg";
	$f[]="menu end";
	$f[]="label help";
	$f[]="	menu label ^Help";
	$f[]="	text help";
	$f[]="   Display help screens; type 'menu' at boot prompt to return to this menu";
	$f[]="	endtext";
	$f[]="	config prompt.cfg\n";

	if(!is_file("$extract/isolinux/menu.cfg")){
		echo "$extract/isolinux/menu.cfg no such file... die\n";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}


	@file_put_contents("$extract/isolinux/menu.cfg", @implode("\n", $f));
	return true;
}

function update_iso(){
	$type="PROXY_APPLIANCE";
	$workdir="/home/working-profiles/$type/tmp";
	@mkdir($workdir,0755,true);
	echo "$type:[".__LINE__."]: $type: Working directory on `$workdir`\n";
	pressed("$workdir/profiles",$type);
	packages("$workdir/profiles/ARTICA.packages",$type,true);
	echo "export verifyrelease=\"blindtrust\"\n";
	echo "cd $workdir\n";
	echo "build-simple-cdd --conf /etc/builcdd.conf --verbose --debug --profiles ARTICA --force-root --debian-mirror ftp://ftp.fr.debian.org/debian/\n";
}

function INITD_EXCLUDE($filename,$type){

	if(!isset($GLOBALS["INITD"][$filename])){return false;}
	if($type<>$GLOBALS["INITD"][$filename]){return true;}

}


function prepareiso($type="PROXY_APPLIANCE"):bool{
	$workdir="/home/working-profiles/$type";
	$isodir="$workdir/tmp/images";
	$extract="$isodir/extract";
	$GLOBALS["EXTRACT_PATH"]=$extract;
    $title=null;

	echo "$type:[".__LINE__."]: ISO directory: `$isodir`\n";
	echo "$type:[".__LINE__."]: Extract Path : `{$GLOBALS["EXTRACT_PATH"]}`\n";

	if(!create_iso_source($isodir,$type)){
		return false;
	}


	if(!is_dir("{$GLOBALS["EXTRACT_PATH"]}/simple-cdd")){
		echo "$type:[".__LINE__."]: {$GLOBALS["EXTRACT_PATH"]}/simple-cdd no such directory -> Extract CDD\n";
		if(!create_iso_source($isodir,$type)){return false;}

	}


	pressed("{$GLOBALS["EXTRACT_PATH"]}/simple-cdd",$type);
	$Artica=GetLatestArticaFile();

	shell_exec("{$GLOBALS["rm"]} -rf /root/build");
	@mkdir("/root/build/etc/init.d",0755,true);



	foreach (glob("/root/init.d/*") as $filename) {

		if(INITD_EXCLUDE(basename($filename),$type)){
			echo "Prepare skip init.d ".basename($filename)."\n";
			continue;
		}


		echo "Prepare init.d ".basename($filename)."\n";
		@copy($filename,"/root/build/etc/init.d/". basename($filename));
	}


	@mkdir("/root/build/home/artica",0755,true);
	@mkdir("/root/build/etc/artica-postfix",0755,true);

	@mkdir("/root/build/usr/share",0755,true);
	@mkdir("/root/build/etc/artica-postfix/settings/Daemons",0755,true);
	@file_put_contents("/root/build/etc/artica-postfix/FROM_ISO", time());
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/SquidTemplateSimple",1);
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/IsPortsConverted",1);
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/EnablePHPFPM",0);
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/UpgradeTov10",1);
    	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/SQUIDEnable",0);
    	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/ProxyUseArticaDB",0);



	if(!is_file($Artica)){
		echo "$Artica no such file\n";
		return false;
	}

	$LIGHT=FALSE;
	if($type=="CATEGORIES_APPLIANCE"){$LIGHT=true;}
    $MAIN_ARTICAVER="";
    echo "$type: Extracting $Artica\n";
	@mkdir("/root/build/usr/share",0755,true);
    shell_exec("tar -xhf $Artica -C /root/build/usr/share/");
    if(preg_match("#artica-(.+?)\.tgz$#",$Artica,$re)){
        $MAIN_ARTICAVER=$re[1];
    }


    $HotfixBin="";
    echo "Get Service Pack for $MAIN_ARTICAVER...\n";
    $spfile=GetLatestArticaServicePack();
    if(is_file($spfile)){
        $spbin=GetArticaSPVerBin();
        echo "Service Pack: v$spbin\n";
        if($spbin>0){
            echo "Service Pack: Extracting $spfile Service Pack $spbin\n";
            shell_exec("tar -xhf $spfile -C /root/build/usr/share/");
            if(!is_dir("/root/build/usr/share/artica-postfix/SP")) {
                @mkdir("/root/build/usr/share/artica-postfix/SP", 0755, true);
            }
            @file_put_contents("/root/build/usr/share/artica-postfix/SP/$MAIN_ARTICAVER",$spbin);
        }
    }else{
        echo "Get Service Pack no file ! Seems no service pack...\n";
    }

    echo "Get HotFix for $MAIN_ARTICAVER...\n";
    $HotFixFile=GetLatestArticaHotFix();
    if(strlen($HotFixFile)>3) {
        if (is_file($HotFixFile)) {
            $HotfixBin = str_replace(".tgz", "", basename($HotFixFile));
            echo "HotFix: v$HotFixFile\n";
            echo "HotFix: Extracting $HotfixBin HotFix $HotfixBin\n";
            shell_exec("tar -xhf $HotFixFile -C /root/build/usr/share/");
        }
    }


//realtek.tar.gz
//"splunkforwarder.tgz","phpipam.tar.gz",
    $PKGS=array("postgres.tar.gz","webconsole.tar.gz","filebeat.tar.gz","frontail-linux.tar.gz","aescrypt.tar.gz","shellinabox.tar.gz","ndpi-filter.tar.gz","monit.tar.gz","nginx.tar.gz","tailon.tar.gz","memcached.tar.gz","php-7.4.tar.gz","xtables.tar.gz","vmtools.tar.gz","syslog.tar.gz","mariadb.tar.gz");

    foreach ($PKGS as $package) {$FFPCKG[$package]=true;}

    foreach ($FFPCKG as $package=>$none) {

        $fsource = "/root/$package";
        if (!is_file($fsource)) {
            echo "$package, missing\n";
            continue;
        }
        echo "$type: Extracting echo \"$type: Extracting /root/$package\n";
        shell_exec("tar -xhf /root/$package -C /root/build/");
    }


    if(is_dir("/root/build/usr/local/share/ntopng/httpdocs/geoip")){
		system("/bin/rm /root/build/usr/local/share/ntopng/httpdocs/geoip/*");
	}

	if(is_dir("/root/build/usr/bandwidthd")){
		system("/bin/rm -rf /root/build/usr/bandwidthd");
	}

	if(is_file("/root/build/usr/sbin/swat")){
		system("/bin/rm -f /root/build/usr/sbin/swat");
	}

	if(is_file("/root/build/usr/local/sbin/barnyard2")){
		@unlink("/root/build/usr/local/sbin/barnyard2");
	}

	$usrbin[]="nmap-os-db";
	$usrbin[]="nmap-service-probes";
	$usrbin[]="eventlogadm";
	$usrbin[]="imginfo";

    foreach ($usrbin as $bin){
		if(is_file("/root/build/usr/bin/$bin")){@unlink("/root/build/usr/bin/$bin");}

	}
    $ArticaBins[]="cpu-stress";
    $ArticaBins[]="authlogs";
    $ArticaBins[]="adblock2privoxy";
    $ArticaBins[]="artica-error-page";
    $ArticaBins[]="artica-fsmonitor";
    $ArticaBins[]="artica-letsencrypt";
    $ArticaBins[]="artica-milter";
    $ArticaBins[]="artica-proxy-auth";
    $ArticaBins[]="artica-rotate";
    $ArticaBins[]="ArticaStats";
    $ArticaBins[]="ddump";
    $ArticaBins[]="bandwhich";
    $ArticaBins[]="docker-client";
    $ArticaBins[]="hotspot-web";
    $ArticaBins[]="internetwatch";
    $ArticaBins[]="checkdomain";
    $ArticaBins[]="compile-category";
    $ArticaBins[]="hotspot-web";
    $ArticaBins[]="msktutil";
    $ArticaBins[]="sqlite-to-csv";
    $ArticaBins[]="percpu";
    $ArticaBins[]="php-upgrade";
    $ArticaBins[]="proxy-pac";
    $ArticaBins[]="proxy-watchdog";
    $ArticaBins[]="smtp-client";
    $ArticaBins[]="squid-users";
    $ArticaBins[]="ssh-keypair-gen";
    $ArticaBins[]="storeid_file_rewrite";
    $ArticaBins[]="theshields-cgi";
    $ArticaBins[]="authlogs";
    $ArticaBins[]="compile-category";
    $ArticaBins[]="debian-mirror";
    $ArticaBins[]="dnsproxy";
    $ArticaBins[]="getdisks";
    foreach ($ArticaBins as $binaryfile){
        echo "Removing /root/build/usr/share/artica-postfix/bin/$binaryfile\n";
        unlink("/root/build/usr/share/artica-postfix/bin/$binaryfile");

    }

    system("rm -rf /root/build/usr/share/artica-postfix/bin/go-shield/client");
    system("rm -rf /root/build/usr/share/artica-postfix/bin/go-shield/server");
    system("rm -rf /root/build/usr/share/artica-postfix/bin/go-shield/fs-watcher");
    system("rm -rf /root/build/usr/share/artica-postfix/bin/install/squid");
    system("rm -rf /root/build/usr/share/artica-postfix/bin/install/Smarty");
    system("rm -rf /root/build/usr/share/artica-postfix/ressources/phpsysinfo");
    system("rm -rf /root/build/usr/share/artica-postfix/ressources/externals/Zend");
    system("rm -rf /root/build/usr/share/artica-postfix/ressources/externals/vendor/elasticsearch");
    system("rm -rf /root/build/usr/local/ArticaWebConsole/fastcgi_temp/*");
    system("rm -f /root/build/usr/local/modsecurity/liblibmodsecurity.a");

    echo "Removing /root/build/usr/include\n";
    system("rm -rf /root/build/usr/include");
    echo "Compressing /root/build to {$GLOBALS["EXTRACT_PATH"]}/simple-cdd/package.tar.gz\n";
	@chdir("/root/build");
	$cmd="cd /root/build && {$GLOBALS["tar"]} -cvzf {$GLOBALS["EXTRACT_PATH"]}/simple-cdd/package.tar.gz *";
	system($cmd);

	$fsize=@filesize("{$GLOBALS["EXTRACT_PATH"]}/simple-cdd/package.tar.gz");
	$fsize=round($fsize/1048576,2);
	echo "{$GLOBALS["EXTRACT_PATH"]}/simple-cdd/package.tar.gz {$fsize}MB\n";


	if(!is_dir("/home/splited")){
		@mkdir("/home/splited",0755,true);
	}
	echo "Creating splited files\n";
	system("split -b 1m --verbose {$GLOBALS["EXTRACT_PATH"]}/simple-cdd/package.tar.gz /home/splited/full-package.split");



	$productname="Reverse-Proxy";

    $addedver=GetArticaSPVers();

    if(strlen($HotfixBin)>3){
        $addedver=$addedver." Hotfix $HotfixBin";
    }
    $Orgversion="";
	if(preg_match("#artica-([0-9\.]+)\.tgz#", basename($Artica),$re)){
		$version=$re[1];
        $Orgversion=$version;
		$version=str_replace(".000000","",$version);
		echo "$productname Version: $version$addedver\n";
	}

	$title="Reverse-Proxy v$version$addedver";


    $f=array();
	$f[]="label auto";
	$f[]="menu label ^$title";
	$f[]="kernel /install.amd/vmlinuz";
	$f[]="append  preseed/file=/cdrom/simple-cdd/ARTICA.preseed auto=true priority=critical interface=auto vga=788 initrd=/install.amd/initrd.gz -- quiet";
	@file_put_contents("{$GLOBALS["EXTRACT_PATH"]}/isolinux/adtxt.cfg",@implode("\n", $f));
	echo "{$GLOBALS["EXTRACT_PATH"]}/isolinux/adtxt.cfg done\n";

	$f[]="mirror_components=\"main contrib non-free\"";
	//$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-1.7.092600.tgz\"";
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-iso\"";
        $f[]="profiles=\"ARTICA\"";
        $f[]="auto_profiles=\"ARTICA\"";


	if(!is_file("{$GLOBALS["EXTRACT_PATH"]}/simple-cdd/ARTICA.conf")){
		echo "{$GLOBALS["EXTRACT_PATH"]}/simple-cdd/ARTICA.conf\n";
		@file_put_contents("{$GLOBALS["EXTRACT_PATH"]}/simple-cdd/ARTICA.conf", @implode("\n", $f)."\n");
	}




	$isoname="artica-reverseproxy-$Orgversion.debian10-amd64.dvd.iso";


	$cmds="{$GLOBALS["xorriso"]} -as mkisofs -r -checksum_algorithm_iso md5,sha1 -o /home/$isoname -J -joliet-long -cache-inodes -isohybrid-mbr /usr/lib/ISOLINUX/isohdpfx.bin -b isolinux/isolinux.bin -c isolinux/boot.cat -boot-load-size 4 -boot-info-table -no-emul-boot -eltorito-alt-boot -e boot/grub/efi.img -no-emul-boot -isohybrid-gpt-basdat -isohybrid-apm-hfsplus {$GLOBALS["EXTRACT_PATH"]}";
	echo "Command:\n$cmds\n";
	if(is_file("/home/$isoname")){@unlink("/home/$isoname");}
	shell_exec($cmds);
	$size=@filesize("/home/$isoname");
	$size=$size/1024;
	$size=$size/1024;
	$size=round($size,2);
	echo "/home/$isoname {$size}MB\n";
    return true;
}


function GetLatestArticaServicePack():string{
    foreach (glob("/root/ArticaP*.tgz") as $filename) {
        return $filename;
    }
    return "";
}
function GetLatestArticaHotFix():string{
    foreach (glob("/root/*.tgz") as $filename) {
       if(!preg_match("#[0-9]+-[0-9]+\.tgz$#",$filename)){
           continue;
       }
       return $filename;
    }
    return "";
}

function GetArticaSPVerBin():int{
    $filename=GetLatestArticaServicePack();
    if($filename==null){return "";}
    if(preg_match("#ArticaP([0-9]+)\.tgz$#",$filename,$re)){
        return $re[1];
    }
    return 0;
}

function GetArticaSPVers():string{
    $filename=GetLatestArticaServicePack();
    if($filename==null){return "";}
    $Sp=GetArticaSPVerBin();
    if($Sp==0){return "";}
    return " Service Pack $Sp";
}

function GetLatestArticaFile():string{
	foreach (glob("/root/artica-*.tgz") as $filename) {return $filename;}
    return "";
}

function ScanSplitted(){

    shell_exec("rm -rf /home/splited");

    if(!is_dir("/home/splited")){
        @mkdir("/home/splited",0755,true);
    }


    $srcpath="/home/working-profiles/PROXY_APPLIANCE/tmp/images/extract/simple-cdd";

    echo "Creating splited files\n";
    system("split -b 3m --verbose $srcpath/package.tar.gz /home/splited/full-package.split");



	$BaseWorkDir="/home/splited";
	if (!$handle = opendir($BaseWorkDir)) {return;}
	echo "Scanning $BaseWorkDir\n";
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if( !preg_match("#^full-package\.split#",$filename) ){continue;}
		$localfile="$BaseWorkDir/$filename";
		if(!ssh_upload_mirror($localfile)){return false;}
	}

	system("curl http://articatech.net/package.php?verbose=yes >/dev/null");

}


function ssh_upload_mirror($localfile){
$basename=basename($localfile);
$ch = curl_init();
$fp = fopen($localfile, "r");
$password=trim(@file_get_contents("/home/37.187.156.120"));
$sfcomand="sftp://root:$password@37.187.156.120/home/www.artica.fr/download/mainpkg/$basename";
curl_setopt($ch, CURLOPT_URL, $sfcomand);
curl_setopt($ch, CURLOPT_UPLOAD, 1);
curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_SFTP);
curl_setopt($ch, CURLOPT_INFILE, $fp);
curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localfile));
curl_exec ($ch);
$error_no = curl_errno($ch);
curl_close ($ch);
if ($error_no == 0) {
	echo "$localfile, success to upload\n";
	return true;
}
echo "$localfile, failed to upload $sfcomand >Err. $error_no\n";
return false;
}

function default_excludes():bool{

    $f[]="# Exclude console-setup-freebsd to prevent warnings about uninstallable files,";
    $f[]="# see bug #619328.";
    $f[]="#";
    $f[]="# Comment out this exclude if you are building freebsd images.";
    $f[]="console-setup-freebsd";
    @file_put_contents("/usr/share/simple-cdd/profiles/default.excludes",@implode("\n",$f));
    echo "/usr/share/simple-cdd/profiles/default.excludes [DONE]\n";
    return true;
}

function pressed($targetpath=null,$type="PROXY_APPLIANCE"){

    $productname="Artica";
    if(is_file("/root/$type.ProductName")){
        $productname=trim(@file_get_contents("/root/$type.ProductName"));
    }

	echo "Prepare Packages in $targetpath/ARTICA.packages - $type\n";
	packages("$targetpath/ARTICA.packages",$type,true);
    //packages("/usr/share/simple-cdd/profiles/ARTICA.packages",$type,true);
    //packages("/usr/share/simple-cdd/profiles/default.downloads",$type,true);
    //packages("/usr/share/simple-cdd/profiles/default.packages",$type,true);
    default_excludes();
    //@file_put_contents("/usr/share/simple-cdd/profiles/default.udebs","# the udeb needed for simple-cdd\nsimple-cdd-profiles\n");
	$packages=packages(null,$type);
     //@file_put_contents("/usr/share/simple-cdd/profiles/x-basic.description","very basic X.org system with window manager and console");
    //@file_put_contents("/usr/share/simple-cdd/profiles/x-basic.packages","icewm\nrxvt-unicode\nmenu\nxdm\nxbase-clients\nxorg\nxserver-xorg-input-all\nxserver-xorg-video-all");

	echo "$productname package: GetLatestArticaFile()\n";
	$articafile=GetLatestArticaFile();

	if($articafile==null){
		echo "**** NO ARTICA PACKAGE in /root !!! ****\n";die("DIE " .__FILE__." Line: ".__LINE__);
	}

	echo "$productname package: $articafile\n";

	if(preg_match("#artica-([0-9\.]+)\.tgz#", basename($articafile),$re)){
		$version=$re[1];
		echo "$productname Version: $version\n";
	}
    $hostname="articaproxy";




	if(!is_file("/root/$type.hostname")){
		echo "/root/$type.hostname no such file, assume `$hostname`\n";

	}else{
		$hostname=@file_get_contents("/root/$type.hostname");
	}

	if($hostname==null){

		echo " * * *  Hostname missing die();\n";
	}


	$f[]="####################################################################";
	$f[]="#  PRESEED - Created  ".date("Y-m-d H:i:s")."   #";
	$f[]="####################################################################";
	$f[]="";
	$f[]="# Wiki: http://wiki.debian.org/DebianInstaller/Preseed";
	$f[]="";
	$f[]="####################################################################";
	$f[]="# Installation Sources";
	$f[]="####################################################################";
	$f[]="";
	$f[]="# Pull bits from the local CD only";
	$f[]="";
    $f[]="# On ne souhaite pas installer les paquets recommandés";
    $f[]="# L'installation sera limitée aux paquets \"essentials\"";
    $f[]="d-i base-installer/install-recommends boolean false";


	//$f[]="d-i cdrom-detect/try-usb boolean true";
	$f[]="d-i mirror/file/directory string /cdrom";
	$f[]="d-i mirror/suite string";
	$f[]="d-i mirror/http/proxy string";
	$f[]="";
	$f[]="# Post install APT setup - Skip it";
	$f[]="d-i apt-setup/use_mirror boolean false";
	$f[]="d-i apt-setup/services-select multiselect \"\"";
	$f[]="#d-i     apt-setup/uri_type      select d-i";
	$f[]="#d-i     apt-setup/hostname      string mirrors.kernel.org";
	$f[]="#d-i     apt-setup/directory     string /debian/";
	$f[]="#d-i     apt-setup/another       boolean false";
	$f[]="#d-i     apt-setup/security-updates      boolean false";
	$f[]="#d-i     finish-install/reboot_in_progress note";
	$f[]="#d-i     prebaseconfig/reboot_in_progress        note";
	$f[]="";
	$f[]="d-i     apt-setup/non-free 	boolean true";
	$f[]="d-i     apt-setup/contrib 	boolean true";

	$f[]="";
	$f[]="####################################################################";
	$f[]="# Networking";
	$f[]="####################################################################";
	$f[]="";
	$f[]="# Network Configuration";
	$f[]="d-i     netcfg/enable boolean   true";
	$f[]="d-i     netcfg/get_hostname     string  artica-appliance";
	$f[]="d-i     netcfg/get_domain       string  company.tld";
	$f[]="d-i     netcfg/disable_dhcp     boolean false";
	$f[]="d-i     netcfg/choose_interface auto";
	$f[]="d-i     netcfg/wireless_wep     string";
    $f[]="d-i     netcfg/dhcp_failed note";
    $f[]="d-i     netcfg/dhcp_options select Configure network manually";
    $f[]="d-i     d-i hw-detect/load_firmware boolean true";
	$f[]="";
	$f[]="####################################################################";
	$f[]="# Disk Partitioning/Boot loader";
	$f[]="####################################################################";
	$f[]="";
	//UEFI support, Kasfaleia modification
	$f[]="d-i partman-efi/non_efi_system boolean true"; // Force UEFI installation
    //$f[]="d-i partman-partitioning/choose_label string gpt";  // UEFI
    //$f[]="d-i partman-partitioning/default_label string gpt"; // UEFI
    $f[]="d-i partman-partitioning/confirm_write_new_label boolean true";


    $f[]="# On partionne en normal: pas de RAID ni de LVM";
    $f[]="d-i partman-auto/method string regular";
    $f[]="d-i partman-auto/choose_recipe select atomic";
    $f[]="";
    $f[]="# Pour être sûr, on supprime une éventuelle configuration LVM";
    $f[]="# Chaînes pour ne pas toucher la configuration LVM (donc pas de configuration)";
    $f[]="d-i partman-lvm/device_remove_lvm boolean true";
    $f[]="d-i partman-lvm/confirm boolean true";
    $f[]="d-i partman-lvm/confirm_nooverwrite boolean true";
    $f[]="";
    $f[]="# Même chose pour le RAID";
    $f[]="d-i partman-md/device_remove_md boolean true";
    $f[]="d-i partman-md/confirm boolean true";
    $f[]="";

    //$f[]="d-i partman-auto/choose_recipe select root";
	//$f[]="d-i partman-auto/expert_recipe string root :: 1000 50 -1 ext4 \$primary{ } \$bootable{ } method{ format } format{ } use_filesystem{ } filesystem{ xfs } mountpoint{ / } .";

	$f[]="d-i partman/choose_partition select finish";
	$f[]="d-i partman/confirm boolean true";
	$f[]="d-i partman/confirm_nooverwrite boolean true";

    //f[]="d-i partman/choose_label string gpt"; // UEFI
	//$f[]="d-i partman/default_label string gpt"; // UEFI

	//$f[]="d-i partman-basicfilesystems/choose_label string gpt";
    //$f[]="d-i partman-basicfilesystems/default_label string gpt";


	$f[]="d-i partman-basicfilesystems/no_swap boolean false";
	$f[]="";
	$f[]="d-i grub-installer/only_debian boolean true";
	$f[]="d-i grub-installer/bootdev  string default";
	$f[]="d-i grub-installer/with_other_os  boolean true";
	//$f[]="d-i grub-installer/force-efi-extra-removable boolean true";
	$f[]="d-i grub-installer/grub2_instead_of_grub_legacy boolean true";

	$f[]="";
	$f[]="####################################################################";
	$f[]="# Localizations";
	$f[]="####################################################################";
	$f[]="";
	$f[]="# Install Time ";
	$f[]="#d-i	console-tools/archs string skip-config < - enable";
	$f[]="#d-i 	debian-installer/locale string en_US";
	$f[]="#d-i 	console-keymaps-at/keymap select us";
	$f[]="";
	$f[]="#d-i     languagechooser/language-name-fb    select English";
	$f[]="#d-i     debian-installer/locale             select en_US.UTF-8";
	$f[]="";
	$f[]="# Timezone - Skip setting this which will force the installer to prompt";
	$f[]="#d-i     tzconfig/gmt            boolean true";
	$f[]="#d-i     tzconfig/choose_country_zone/Europe select Paris";
	$f[]="#d-i     tzconfig/choose_country_zone_single boolean true";
	$f[]="#d-i	time/zone	select	Europe/Paris";
	$f[]="d-i	clock-setup/utc	boolean	true";
	$f[]="#d-i	kbd-chooser/method	select	American English";
	$f[]="d-i	mirror/country	string	manual";
	$f[]="d-i     clock-setup/ntp boolean false";
	$f[]="";
	$f[]="# X11 config";
	$f[]="xserver-xorg     xserver-xorg/autodetect_monitor              boolean true";
	$f[]="xserver-xorg     xserver-xorg/config/monitor/selection-method select medium";
	$f[]="xserver-xorg     xserver-xorg/config/monitor/mode-list        select 1024x768 @ 60 Hz";
	$f[]="xserver-xorg     xserver-xorg/config/display/modes            multiselect 1024x768, 800x600";
	$f[]="";
	$f[]="# LDAP Config - Have to have this or will be prompted at install";
	$f[]="";
	$f[]="ldap-auth-config    ldap-auth-config/binddn    string    cn=Manager,dc=yourdomain,dc=com";
	$f[]="ldap-auth-config    ldap-auth-config/bindpw    password    secret";
	$f[]="ldap-auth-config    ldap-auth-config/dblogin    boolean    false";
	$f[]="ldap-auth-config    ldap-auth-config/dbrootlogin    boolean    true";
	$f[]="ldap-auth-config    ldap-auth-config/ldapns/base-dn    string    dc=yourdomain,dc=com";
	$f[]="ldap-auth-config    ldap-auth-config/ldapns/ldap-server    string    ldap://127.0.0.1/";
	$f[]="ldap-auth-config    ldap-auth-config/ldapns/ldap_version    select    3";
	$f[]="ldap-auth-config    ldap-auth-config/move-to-debconf    boolean    true";
	$f[]="ldap-auth-config    ldap-auth-config/override    boolean    true";
	$f[]="ldap-auth-config    ldap-auth-config/pam_password    select    crypt";
	$f[]="ldap-auth-config    ldap-auth-config/rootbinddn    string    cn=manager,dc=yourdomain,dc=com";
	$f[]="ldap-auth-config    ldap-auth-config/rootbindpw    password   ";
	$f[]="libnss-ldap    libnss-ldap/binddn    string    cn=proxyuser,dc=yourdomain,dc=com";
	$f[]="libnss-ldap    libnss-ldap/bindpw    password    ";
	$f[]="libnss-ldap    libnss-ldap/confperm    boolean    false";
	$f[]="libnss-ldap    hd-medialibnss-ldap/dblogin    boolean    false";
	$f[]="libnss-ldap    libnss-ldap/dbrootlogin    boolean    true";
	$f[]="libnss-ldap    libnss-ldap/nsswitch    note    ";
	$f[]="libnss-ldap    libnss-ldap/override    boolean    true";
	$f[]="libnss-ldap    libnss-ldap/rootbinddn    string    cn=manager,dc=yourdomain,dc=com";
	$f[]="libnss-ldap    libnss-ldap/rootbindpw    password    ";
	$f[]="libnss-ldap    shared/ldapns/base-dn    string    dc=yourdomain,dc=com";
	$f[]="libnss-ldap    shared/ldapns/ldap-server    string    ldap://127.0.0.1/";
	$f[]="libnss-ldap    shared/ldapns/ldap_version    select    3";
	$f[]="libpam-ldap    libpam-ldap/binddn    string    cn=proxyuser,dc=yourdomain,dc=com";
	$f[]="libpam-ldap    libpam-ldap/bindpw    password    ";
	$f[]="libpam-ldap    libpam-ldap/dblogin    boolean    false";
	$f[]="libpam-ldap    libpam-ldap/dbrootlogin    boolean    false";
	$f[]="libpam-ldap    libpam-ldap/override    boolean    true";
	$f[]="libpam-ldap    libpam-ldap/pam_password    select    crypt";
	$f[]="libpam-ldap    libpam-ldap/rootbinddn    string    cn=manager,dc=yourdomain,dc=com";
	$f[]="libpam-ldap    libpam-ldap/rootbindpw    password    ";
	$f[]="libpam-ldap    shared/ldapns/base-dn    string    dc=yourdomain,dc=com";
	$f[]="libpam-ldap    shared/ldapns/ldap-server    string    ldap://127.0.0.1/";
	$f[]="libpam-ldap    shared/ldapns/ldap_version    select    3";
	$f[]="libpam-runtime    libpam-runtime/profiles    multiselect    unix, ldap";
	$f[]="";
	$f[]="krb5-config	krb5-config/default_realm    string ";
	$f[]="krb5-config	krb5-config/kerberos_servers    string";
	$f[]=" ";
	$f[]="####################################################################";
	$f[]="# User Creation";
	$f[]="####################################################################";
	$f[]="";
	$f[]="# Root User";
	$f[]="d-i passwd/root-password password artica";
	$f[]="d-i passwd/root-password-again password artica";
	$f[]="d-i passwd/make-user boolean false";
	$f[]="";
	$f[]="# Mortal User";
	$f[]="#d-i	passwd/user-fullname            string Artica User";
	$f[]="#d-i	passwd/username                 string artica";
	$f[]="#d-i 	passwd/user-password password artica";
	$f[]="#d-i 	passwd/user-password-again password artica";


	$f[]="";
	$f[]="####################################################################";
	$f[]="# Finish setup";
	$f[]="####################################################################";
	$f[]="";
	$f[]="d-i cdrom-detect/eject boolean true";
	$f[]="d-i finish-install/reboot_in_progress note";

	$f[]="";
	$f[]="####################################################################";
	$f[]="# Software Selections";
	$f[]="####################################################################";
	$f[]="";
	$f[]="tasksel	tasksel/first	multiselect	standard";
	//$f[]="d-i pkgsel/include string locales util-linux-locales cdrom-detect $packages";
	$f[]="d-i pkgsel/include string locales util-linux-locales $packages";
	$f[]="";
	$f[]="####################################################################";
	$f[]="# Additional preseed entries (from data/debconf)";
	$f[]="####################################################################";
	$f[]="";
	$f[]="exim4-config exim4/no_config boolean true";
	$f[]="base-config     apt-setup/non-free      boolean true";
	$f[]="base-config 	  apt-setup/security-updates  boolean false";
	// Kasfaleia preseed modifications
	$f[]="popularity-contest popularity-contest/participate boolean false";
	$f[]="d-i apt-setup/cdrom/set-first boolean false";
	$f[]="simple-cdd simple-cdd/profiles multiselect ARTICA";
	$f[]="samba-common samba-common/dhcp boolean false";
	$f[]="davfs2 davfs2/suid_file boolean true";
	$f[]="krb5-config krb5-config/default_realm string";
	$f[]="krb5-config krb5-config/kerberos_servers string";
	$f[]="krb5-config krb5-config/admin_server string";
	$f[]="krb5-config krb5-config/add_servers_realm string";
	$f[]="bandwidthd bandwidthd/dev select any";
	$f[]="bandwidthd bandwidthd/subnet string";
	$f[]="slapd slapd/password1 password";
	$f[]="slapd slapd/password2 password";
	// End Kasfaleia modifications
	$f[]="";


    $f[]="#!/bin/sh";
    $f[]="update-rc.d artica-iso defaults || true";
    $f[]="/usr/bin/apt install sysvinit-core sysvinit systemd-sysv- || true";
    $f[]="";
    @file_put_contents("$targetpath/remove-systemd.sh",@implode("\n",$f));
	@copy("/root/init.d/artica-iso", "$targetpath/artica-iso");
    @chmod("$targetpath/remove-systemd.sh",0755);
	@chmod("$targetpath/artica-iso",0755);

	$pressed[]="cp /cdrom/simple-cdd/artica-iso /target/etc/init.d/";
    $pressed[]="cp /cdrom/simple-cdd/remove-systemd.sh /target/root/";
	$pressed[]="chmod 0755 /target/etc/init.d/artica-iso";
    $pressed[]="chmod 0755 /target/root/remove-systemd.sh";
	$pressed[]="cp /cdrom/simple-cdd/package.tar.gz /target/home/package.tar.gz";
    $pressed[]="chroot /target /root/remove-systemd.sh >remove-systemd.txt";
	//$pressed[]="in-target chmod 0755 /etc/init.d/artica-iso >/dev/null 2>&1";
	//pressed[]="in-target /usr/sbin/update-rc.d -f artica-iso defaults >/dev/null 2>&1";
	$f[]="d-i preseed/late_command string " .@implode(";", $pressed);
	$f[]="";

	echo "* * * * * * * * * * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * *\n";
	echo "* * * * * * * * $targetpath/ARTICA.preseed - DONE - * * * * * * * *\n";
    echo "* * * * * * * * /usr/share/simple-cdd/profiles/ARTICA.preseed - DONE - * * * * * * * *\n";
    echo "* * * * * * * * * * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * *\n";
	@file_put_contents("$targetpath/ARTICA.preseed", @implode("\n", $f));
    @file_put_contents("/usr/share/simple-cdd/profiles/ARTICA.preseed", @implode("\n", $f));
}


function packages($targetfile=null,$type="PROXY_APPLIANCE",$makeiso=false){
    $packages=array();

    //Obligatoire pour PHP
    $packages["libmemcached11"]=true;
    $packages["libaspell15"]=true;
    $packages["libmemcachedutil2"]=true;

    $packages["libzip4"]=true;
    $packages["zip"]=true;
    $packages["gnutls-bin"]=true;
    $packages["apt-mirror"]=true;
    $packages["curl"]=true;
    $packages["sqlite3"]=true;
    $packages["targetcli-fb"]=true;
    $packages["libzmq5"]=true;
    $packages["net-tools"]=true;
    $packages["uuid-dev"]=true;
    $packages["libmnl-dev"]=true;
    $packages["apt-transport-https"]=true;
    $packages["libxau6"]=true;
    $packages["libonig5"]=true;
    $packages["ipset"]=true;
    $packages["python-pycurl"]=true;
    $packages["python-lxml"]=true;
    $packages["python"]=true;
    $packages["python-apt"]=true;
    $packages["python-requests"]=true;
    $packages["python-daemon"]=true;
    $packages["namebench"]=true;

    $packages["python-apt-common"]=true;
    $packages["python-debian"]=true;
    $packages["python-debianbts"]=true;
    $packages["python-fpconst"]=true;
    $packages["python-minimal"]=true;
    $packages["libsnmp30"]=true; // Important DNS Firewall
    $packages["python-lockfile"]=true;
    $packages["python-requests-oauthlib"]=true;
    $packages["python-iniparse"]=true;
    $packages["python-setuptools"]=true;
    $packages["python-enum34"]=true;
    $packages["python-httplib2"]=true;
    $packages["python-graphy"]=true;
    $packages["python2.7-minimal"]=true;
    $packages["kbd"]=true;
    $packages["libdumbnet1"]=true;
    $packages["liblz4-dev"]=true;
    $packages["liblz4-1"]=true; // OpenVPN
    $packages["libnss3"]=true;
    $packages["libjansson4"]=true;
    $packages["libnuma-dev"]=true;
    $packages["dialog"]=true;
    $packages["libpq5"]=true;
    $packages["tcpdump"]=true;
    $packages["rsync"]=true;
    $packages["cgroup-bin"]=true;
    $packages["ipcalc"]=true;
    $packages["wakeonlan"]=true;
    $packages["syslinux"]=true;
    $packages["ntp"]=true;
    $packages["expect"]=true;
    $packages["iucode-tool"]=true;
    $packages["firmware-realtek"]=true;
    $packages["p7zip"]=true;
    $packages["unrar"]=true;
    $packages["libbrotli1"]=true;
    $packages["ethtool"]=true;
    $packages["liblua5.3-0"]=true;
    $packages["needrestart"]=true;
    $packages["libcap2-bin"]=true;
    $packages["libswscale5"]=true;
    $packages["strace"]=true;
    $packages["ebtables"]=true;
    $packages["whois"]=true;
    $packages["iotop"]=true;
    $packages["lshw"]=true;
    $packages["acl"]=true;
    $packages["socat"]=true;
    $packages["mlocate"]=true;
    $packages["libmaxminddb0"]=true;
    $packages["mmdb-bin"]=true;
    $packages["pv"]=true;
    $packages["arj"]=true;
    $packages["bridge-utils"]=true; //OpenVPN Mode bridge
    $packages["build-essential"]=true;
    $packages["curlftpfs"]=true;
    $packages["davfs2"]=true;
    $packages["discover"]=true;
    $packages["dnsutils"]=true;
    $packages["firmware-misc-nonfree"]=true;
    $packages["schedtool"]=true;
    $packages["htop"]=true;
    $packages["ifenslave"]=true;
    $packages["iptables-dev"]=true;
    $packages["iputils-arping"]=true;
    $packages["isc-dhcp-client"]=true;
    $packages["mingetty"]=true;
    $packages["getdns-utils"]=true;
    $packages["fping"]=true;
    $packages["locales"]=true;
    $packages["util-linux-locales"]=true;
    $packages["lsof"]=true;
    $packages["ntpdate"]=true;
    $packages["openssh-client"]=true;
    $packages["openssh-server"]=true;
    $packages["openssl"]=true;
    $packages["bandwidthd"]=true;
    $packages["rdate"]=true;
    $packages["rrdtool"]=true;
    $packages["scons"]=true;
    $packages["slapd"]=true;
    $packages["sshfs"]=true;
    $packages["tcsh"]=true;
    $packages["telnet"]=true;
    $packages["unzip"]=true;
    $packages["vnstat"]=true;
    $packages["vnstati"]=true;
    $packages["libcdb-dev"]=true;
    $packages["libxslt1-dev"]=true;
    $packages["apt-file"]=true;
    $packages["hdparm"]=true;
    $packages["conntrack"]=true;
    $packages["conntrackd"]=true;
    $packages["attr"]=true;
    $packages["libnetfilter-conntrack3"]=true;
    $packages["netdiscover"]=true;
    $packages["re2c"]=true;
    $packages["liblzo2-2"]=true; // OpenVPN
    $packages["dh-autoreconf"]=true;
    $packages["libpcap-dev"]=true;
    $packages["libmagic-dev"]=true;
    $packages["libgd3"]=true;
    $packages["snmp"]=true;
    $packages["snmpd"]=true;
    $packages["libnetfilter-conntrack-dev"]=true;
    $packages["less"]=true;
    $packages["libtinfo5"]=true;
    $packages["libacl1"]=true;
    $packages["libaprutil1"]=true;
    $packages["libaprutil1-dbd-sqlite3"]=true;
    $packages["libaprutil1-ldap"]=true;
    $packages["libapt-inst2.0"]=true;
    $packages["libapt-pkg-perl"]=true;
    $packages["libapt-pkg5.0"]=true;
    $packages["libasprintf0v5"]=true;
    $packages["libpam0g-dev"]=true;
    $packages["libpam-google-authenticator"]=true;
    $packages["libattr1"]=true;
    $packages["libaudit1"]=true;
    $packages["libavahi-client3"]=true;
	$packages["libpmem1"]=true;


    $packages["libblkid1"]=true;
    $packages["libbsd0"]=true;
    $packages["libbz2-1.0"]=true;
    $packages["libbz2-dev"]=true;
    $packages["libc-bin"]=true;
    $packages["libc-dev-bin"]=true;
    $packages["libc6-dev"]=true;
    $packages["libc6"]=true;
    $packages["libcairo2"]=true;
    $packages["libcdio18"]=true;
    $packages["libclass-isa-perl"]=true;
    $packages["libcomerr2"]=true;
    $packages["libconfuse-common"]=true;
    $packages["libcurl4"]=true;
    $packages["libcwidget3v5"]=true;
    $packages["libdaemon0"]=true;
    $packages["libdatrie1"]=true;
    $packages["libdb5.3"]=true;
    $packages["libdbi-perl"]=true;
    $packages["libdbi1"]=true;
    $packages["libdiscover2"]=true;
    $packages["libdns1104"]=true;
    $packages["libdpkg-perl"]=true;
    $packages["libdrm-intel1"]=true;
    $packages["libdrm-dev"]=true;
    $packages["libdrm-nouveau2"]=true;
    $packages["libept1.5.0"]=true;
    $packages["libev4"]=true;
    $packages["libevent-2.1-6"]=true;
    $packages["libexpat1"]=true;
    $packages["libfam0"]=true;
    $packages["libffi-dev"]=true;
    $packages["libffi6"]=true;
    $packages["libdbd-pg-perl"]=true;
    $packages["libfreetype6"]=true;
    $packages["libgc1c2"]=true;
    $packages["libgcc1"]=true;
    $packages["libgcrypt20-dev"]=true;
    $packages["libgcrypt20"]=true;
    $packages["libgdbm6"]=true;
    $packages["libgeoip1"]=true;
    $packages["libgeoip-dev"]=true;
    $packages["libcurl4-openssl-dev"]=true;
    $packages["libgif7"]=true;
    $packages["libglib2.0-0"]=true;
    $packages["libglib2.0-data"]=true;
    $packages["libgmp10"]=true;
    $packages["libgnutls28-dev"]=true;
    $packages["libgnutls-openssl27"]=true;
    $packages["libgomp1"]=true;
    $packages["libgpg-error-dev"]=true;
    $packages["libgpg-error0"]=true;
    $packages["libgpgme11"]=true;
    $packages["libgpm2"]=true;
    $packages["libgraph-perl"]=true;
//    $packages["libgssapi-krb5-2"]=true;
    $packages["libgssglue1"]=true;
    $packages["libgssrpc4"]=true;
    $packages["libheap-perl"]=true;
    $packages["libice6"]=true;
    $packages["libidn11-dev"]=true;
    $packages["libidn11"]=true;
    $packages["libisc1100"]=true;
    $packages["libisccc161"]=true;
    $packages["libisccfg163"]=true;
    $packages["libitm1"]=true;
    $packages["libkeyutils1"]=true;
    $packages["libklibc"]=true;
    $packages["libkmod2"]=true;
    $packages["libldap-2.4-2"]=true;
    $packages["liblockfile-bin"]=true;
    $packages["liblockfile1"]=true;
    $packages["libltdl-dev"]=true;
    $packages["libltdl7"]=true;
    $packages["liblzma5"]=true;
    $packages["libmagic1"]=true;
    $packages["libmcrypt4"]=true;
    $packages["libmount1"]=true;
    $packages["libmpc3"]=true;
    $packages["libmpfr6"]=true;
    $packages["libncurses5"]=true;
    $packages["libncursesw5"]=true;
    $packages["libneon27-gnutls"]=true;
    $packages["libnet1"]=true;
    $packages["libnewt0.52"]=true;
    $packages["libnfnetlink0"]=true;
    $packages["libnfsidmap2"]=true;
    $packages["libnids1.21"]=true;
    $packages["libntlm0"]=true;
    $packages["libodbc1"]=true;
    $packages["libp11-kit-dev"]=true;
    $packages["libp11-kit0"]=true;
    $packages["libpam-modules-bin"]=true;
    $packages["libpam-runtime"]=true;
    $packages["libpam0g"]=true;
    $packages["libpango1.0-0"]=true;
    $packages["libpci3"]=true;
    $packages["libpciaccess0"]=true;
    $packages["libpcre3"]=true;
    $packages["libpipeline1"]=true;
    $packages["libpixman-1-0"]=true;
    $packages["libpopt0"]=true;
    $packages["libprocps7"]=true;
    $packages["libpth20"]=true;
    $packages["libpython2.7"]=true;
    $packages["libqdbm14"]=true;
    $packages["libquadmath0"]=true;
    $packages["libreadline5"]=true;
    $packages["libreadline7"]=true;
    $packages["librrd8"]=true;
    $packages["librtmp-dev"]=true;
    $packages["librtmp1"]=true;
    $packages["libselinux1"]=true;
    $packages["libsemanage-common"]=true;
    $packages["libpcap0.8-dev"]=true;
    $packages["liblua5.3-dev"]=true;
    $packages["libsm6"]=true;
    $packages["libsqlite3-0"]=true;
    $packages["libsysfs2"]=true;
    $packages["libtasn1-6"]=true;
    $packages["libtdb1"]=true;
    $packages["libthai-data"]=true;
    $packages["libthai0"]=true;
    $packages["libtinfo-dev"]=true;
    $packages["libtirpc3"]=true;
    $packages["libudev1"]=true;
    $packages["libustr-1.0-1"]=true;
    $packages["libuuid1"]=true;
    $packages["libxml2"]=true;
    $packages["libxt6"]=true;
    $packages["libyaml-0-2"]=true;
    $packages["lib32z1"]=true;
    $packages["libcap2"]=true;
    $packages["libconfuse2"]=true;
    $packages["libfuse2"]=true;
    $packages["libgsasl7"]=true;
    $packages["libiodbc2"]=true;
    $packages["libldap2-dev"]=true;
    $packages["libpam-ldap"]=true;
    $packages["libpam-modules"]=true;
    $packages["libsasl2-modules"]=true;
    $packages["libsasl2-modules-ldap"]=true;
    $packages["libtevent0"]=true;
    $packages["libtalloc2"]=true;
    $packages["libv4l-0"]=true;
    $packages["libcap-dev"]=true;
    $packages["vlan"]=true;
    $packages["wget"]=true;
    $packages["udev"]=true;
    $packages["usbutils"]=true;


    // AJoutés 4.40
    $packages["apparmor-utils"]=true;
    $packages["arp-scan"]=true;
    $packages["lsb-release"]=true;
    $packages["sysvinit-core"]=true;

    $packages["netcat-openbsd"]=true;
    $packages["cifs-utils"]=true;
    $packages["liblua5.2-0"]=true;
    $packages["libfuzzy2"]=true;
    $packages["libluajit-5.1-2"]=true;
    $packages["locales-all"]=true;

    // Kasfaleia adding packages



    foreach ($packages as $package=>$none){
		$t[]=$package;
	}

	if($targetfile==null){
		echo count($t)." packages done..\n";
		if($GLOBALS["EXTRACT_PACKAGES"]){echo @implode(" ", $t);}
		return @implode(" ", $t);

	}

	echo count($t)." packages done..\n";
	@mkdir(dirname($targetfile),0755,true);
    echo "$targetfile done...\n";
	@file_put_contents($targetfile, @implode("\n", $t));
	return @implode(" ", $t);


}



function mirror($type="PROXY_APPLIANCE"){
	$workdir="/home/working-profiles/$type";
	$GLOBALS["WORKDIR"]=$workdir;
	$GLOBALS["PROFILES_DIR"]="$workdir/tmp/profiles";
	$GLOBALS["PROFILES_CHDIR"]="$workdir/tmp";

	echo "Workdir......: $workdir\n";
	echo "chdir to.....: {$GLOBALS["PROFILES_CHDIR"]}\n";

	if(!is_file($GLOBALS["build-simple-cdd"])){echo "Fatal, {$GLOBALS["build-simple-cdd"]} no such binary\n";die("DIE " .__FILE__." Line: ".__LINE__);}


	@mkdir("{$GLOBALS["PROFILES_DIR"]}/tmp",0755,true);
	if(!@chdir("{$GLOBALS["PROFILES_DIR"]}")){echo "Fatal, cannot chdir to {$GLOBALS["WORKDIR"]}\n";}
	echo "chdir to \"{$GLOBALS["PROFILES_CHDIR"]}\" success\n";
	echo "Current dir: ".getcwd()."\n";
	echo "Building ARTICA UEFI profile\n";
	ARTICA_CONF("{$GLOBALS["PROFILES_DIR"]}");
	pressed($GLOBALS["PROFILES_DIR"],$type);
	packages("{$GLOBALS["PROFILES_DIR"]}/ARTICA.packages",$type,true);
	$cmd="cd {$GLOBALS["PROFILES_CHDIR"]} && {$GLOBALS["build-simple-cdd"]} --profiles ARTICA --force-root --conf /etc/builcdd.conf --debian-mirror ftp://ftp.fr.debian.org/debian --keyring /home/new-debian-archive-keyring.gpg\n";
	echo $cmd."\n";


	system($cmd);


}


function ARTICA_CONF($path){
	$f[]="debian_mirror=\"http://ftp2.fr.debian.org/debian\"";
	$f[]="mirror_components=\"main contrib non-free\"";
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/package.tar.gz\"";
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-iso\"";
        $f[]="profiles=\"ARTICA\"";
        $f[]="auto_profiles=\"ARTICA\"";
	echo "Creating $path/ARTICA.conf\n";
	@mkdir($path);
	@file_put_contents("$path/ARTICA.conf", @implode("\n", $f)."\n");



}


function install_profiles(){


	$dirs[]=$GLOBALS["WORKDIR"];
	$dirs[]="{$GLOBALS["WORKDIR"]}/build/profiles";
	$dirs[]="{$GLOBALS["WORKDIR"]}/images";
	$dirs[]="{$GLOBALS["WORKDIR"]}/images/isolinux-new/isolinux";

    foreach ($dirs as $directory){
		if(is_dir($directory)){echo "$directory - success\n";continue;}
		echo "Creating $directory\n";
		@mkdir($directory,0755,true);

	}

	$f[]="mirror_components=\"main contrib non-free\"";
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-1.7.092600.tgz\"";
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-iso\"";
        $f[]="profiles=\"ARTICA\"";
        $f[]="auto_profiles=\"ARTICA\"";
	if(!is_file("{$GLOBALS["WORKDIR"]}/build/profiles/ARTICA.conf")){
		echo "Creating {$GLOBALS["WORKDIR"]}/build/profiles/ARTICA.conf\n";
		@file_put_contents("{$GLOBALS["WORKDIR"]}/build/profiles/ARTICA.conf", @implode("\n", $f)."\n");
	}

	pressed();
	testisolinux();
}






function perpare_iso(){

	//if(!is_file($GLOBALS["genisoimage"])){echo "{$GLOBALS["genisoimage"]} no such binary\n";die("DIE " .__FILE__." Line: ".__LINE__);}
	if(!is_file($GLOBALS["xorriso"])){echo "{$GLOBALS["xorriso"]} no such binary\n";die("DIE " .__FILE__." Line: ".__LINE__);}
	$firmware_path="/home/firmware.cpio.gz";

	$isopath=buildedgetiso();
	if(!is_file($isopath)){echo "No such ISO\n";return;}
	echo "Builded ISO `$isopath`\n";
	if(is_dir($GLOBALS["EXTRACT_PATH"])){
		echo "*** Removing {$GLOBALS["EXTRACT_PATH"]} ***\n";
		shell_exec("{$GLOBALS["rm"]} -rf {$GLOBALS["EXTRACT_PATH"]}");
	}

	echo "Creating {$GLOBALS["EXTRACT_PATH"]}\n";
	@mkdir($GLOBALS["EXTRACT_PATH"],0755,true);
	echo "perpare_iso: Extracting $isopath\n";
	shell_exec("{$GLOBALS["bsdtar"]} -C {$GLOBALS["EXTRACT_PATH"]}/ -xf $isopath");
	if(!is_dir("{$GLOBALS["EXTRACT_PATH"]}/isolinux")){echo "Failed `{$GLOBALS["EXTRACT_PATH"]}/isolinux` no such directory\n";die("DIE " .__FILE__." Line: ".__LINE__);}
	echo "perpare_iso: Removing {$GLOBALS["EXTRACT_PATH"]}/isolinux\n";
	shell_exec("/bin/rm -rf {$GLOBALS["EXTRACT_PATH"]}/isolinux");
	echo "perpare_iso: Creating {$GLOBALS["EXTRACT_PATH"]}/isolinux\n";
	@mkdir("{$GLOBALS["EXTRACT_PATH"]}/isolinux");
	echo "perpare_iso: Patching isolinux...\n";

	foreach (glob("{$GLOBALS["WORKDIR"]}/images/isolinux-new/isolinux/*") as $filename) {
		echo "Copy ".basename($filename)."\n";
		@copy($filename, "{$GLOBALS["EXTRACT_PATH"]}/isolinux/".basename($filename));
	}


	if(is_file($firmware_path)){
		echo "initrd.gz / install.amd\n";
		if(!is_dir("{$GLOBALS["EXTRACT_PATH"]}/install.amd")){
			echo "* * * FATAL {$GLOBALS["EXTRACT_PATH"]}/install.amd * * *\n";
			echo "* * *         NOT FOUND     * * *\n";
			die("DIE " .__FILE__." Line: ".__LINE__);
		}

		$initrd_original_path="{$GLOBALS["EXTRACT_PATH"]}/install.amd/initrd.gz";
		$initrd_original_copy_path="$initrd_original_path.orig";


		if(!is_file($initrd_original_path)){
			echo "* * * FATAL $initrd_original_path no such file * * *\n";
			die("DIE " .__FILE__." Line: ".__LINE__);
		}
		if(is_file($initrd_original_copy_path)){@unlink($initrd_original_copy_path);}

		@copy($initrd_original_path, $initrd_original_copy_path);

		echo "Patching firmware\n";
		$cmd="/bin/cat $initrd_original_copy_path $firmware_path > $initrd_original_path";
		echo "$cmd\n";
		shell_exec($cmd);
	}else{
		echo "* * * WARNING $firmware_path was not generated * * *\n";
		echo "Please contact our support team, this file should include non-free firemwares\n";
		sleep(2);


	}





	echo "Patching ISO...\n";
	pressed();
	$date=date("Y-m-d-H");
	echo "Building ISO $date ---------------------------------------------------------\n";
    echo "Building ISO /home/$isoname\n";

	//exec("{$GLOBALS["genisoimage"]} -o /home/artica-$date.iso -r -J -no-emul-boot -boot-load-size 4 -boot-info-table -b isolinux/isolinux.bin -c isolinux/boot.cat {$GLOBALS["EXTRACT_PATH"]} 2>&1",$results);
	exec("{$GLOBALS["xorriso"]} -as mkisofs -r -checksum_algorithm_iso md5,sha1 -o /home/$isoname -J -joliet-long -cache-inodes -isohybrid-mbr /usr/lib/ISOLINUX/isohdpfx.bin -b isolinux/isolinux.bin -c isolinux/boot.cat -boot-load-size 4 -boot-info-table -no-emul-boot -eltorito-alt-boot -e boot/grub/efi.img -no-emul-boot -isohybrid-gpt-basdat -isohybrid-apm-hfsplus {$GLOBALS["EXTRACT_PATH"]}",$results);
    foreach ($results as $b){
		echo "Building ISO $b\n";
	}
    echo "Building ISO DONE\n\n";
}






function removeisos(){foreach (glob("/home/ISO_PROFILE/images/*.iso") as $filename) {echo "Removing ISO ".basename($filename)."\n";@unlink($filename);}}
function buildedgetiso($dir){
		foreach (glob("$dir/*.iso") as $filename) {
				return $filename;
		}
}

function testisolinux(){
	$f["adgtk.cfg"]=true;$f["boot.cat"]=true;$f["f10.txt"]=true;$f["f2.txt"]=true;$f["f4.txt"]=true;$f["f6.txt"]=true;$f["f8.txt"]=true;$f["gtk.cfg"]=true;$f["isolinux.bin"]=true;$f["menu.cfg"]=true;$f["prompt.cfg"]=true;$f["rqtxt.cfg"]=true;$f["splash.png"]=true;$f["txt.cfg"]=true;$f["adtxt.cfg"]=true;$f["exithelp.cfg"]=true;$f["f1.txt"]=true;$f["f3.txt"]=true;$f["f5.txt"]=true;$f["f7.txt"]=true;$f["f9.txt"]=true;$f["instalinux@instalinux.com"]=true;$f["isolinux.cfg"]=true;$f["menu.tar"]=true;$f["rqgtk.cfg"]=true;$f["spkgtk.cfg"]=true;$f["stdmenu.cfg"]=true;$f["vesamenu.c32"]=true;
	foreach ($f as $a=>$b){
		$target="{$GLOBALS["WORKDIR"]}/images/isolinux-new/isolinux/$a";
		if(!is_file($target)){
			echo "Missing {$GLOBALS["WORKDIR"]}/images/isolinux-new/isolinux/$a\nYou should create \"{$GLOBALS["WORKDIR"]}/images/isolinux-new/isolinux\"\nand uncompress isolinux.tar\nInside this directory\n";
			return false;
		}
		echo "isolinux/$a - success\n";
	}

	return true;
}


?>
