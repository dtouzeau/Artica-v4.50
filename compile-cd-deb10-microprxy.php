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
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-1.7.092600.tgz\"";
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-iso\"";
	$f[]="profiles=\"ARTICA\"";
	$f[]="auto_profiles=\"ARTICA\"";

	@mkdir($GLOBALS["PROFILES_DIR"],0755,true);
	if(!is_file("{$GLOBALS["PROFILES_DIR"]}/ARTICA.conf")){
		echo "Creating {$GLOBALS["PROFILES_DIR"]}/ARTICA.conf\n";
		@file_put_contents("{$GLOBALS["PROFILES_DIR"]}/ARTICA.conf", @implode("\n", $f)."\n");
	}
    @file_put_contents("/usr/share/simple-cdd/profiles/ARTICA.conf", @implode("\n", $f)."\n");

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
    BuildDefaultsFiles();
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
    @file_put_contents("/root/build/etc/artica-postfix/artica-iso-first-reboot","1");
    @file_put_contents("/root/build/etc/artica-postfix/microservice","1");
    $WorkSourceDir="/root/work/usr/share/artica-postfix";

	if(!is_file($Artica)){
		echo "$Artica no such file\n";
		return false;
	}

    $MAIN_ARTICAVER="";
    echo "$type: Extracting $Artica\n";
    shell_exec("rm -rf /root/work");
	@mkdir("/root/work/usr/share",0755,true);
    shell_exec("tar -xhf $Artica -C /root/work/usr/share/");
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
            shell_exec("tar -xhf $HotFixFile -C /root/work/usr/share/");
        }
    }


    $CopyBins[]="logon.sh";
    $CopyBins[]="bin/articarest";
    $CopyBins[]="bin/HaClusterClient";
    $CopyBins[]="bin/license";
    $CopyBins[]="bin/go-shield/client/external_acl_first/bin/go-shield-connector";
    $CopyBins[]="bin/go-shield/client/external_acls_ldap/bin/go-squid-auth";
    $CopyBins[]="bin/go-shield/server/bin/go-shield-server";

    $PKGS=array("squid.tar.gz","memcached.tar.gz","monit.tar.gz");

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

    echo "Creating /root/build/usr/share/artica-postfix/bin\n";
    echo "Installing ".count($CopyBins)." Elements...\n";
    mkdir("/root/build/usr/share/artica-postfix/bin", 0755, true);
    foreach ($CopyBins as $fname){
        $Target="/root/build/usr/share/artica-postfix/$fname";
        $Dirname=dirname($Target);
        if(!is_dir($Dirname)) {
            mkdir($Dirname, 0755, true);
        }
        if(is_file($Target)){@unlink($Target);}
        copy("$WorkSourceDir/$fname",$Target);
        $fsize=filesize($Target);
        echo "Copy $fname - $fsize bytes\n";
    }



	echo "Removing /root/build/usr/include\n";
    system("rm -rf /root/build/usr/include");
    $EXTRACT_PATH=$GLOBALS["EXTRACT_PATH"];

    echo "\t* * * * * * $EXTRACT_PATH * * * * * *\n";
    echo "Compressing $EXTRACT_PATH/simple-cdd/package.tar.gz\n";
	@chdir("/root/build");
	$cmd="cd /root/build && {$GLOBALS["tar"]} -czf $EXTRACT_PATH/simple-cdd/package.tar.gz *";
	system($cmd);

	$fsize=@filesize("{$GLOBALS["EXTRACT_PATH"]}/simple-cdd/package.tar.gz");
	$fsize=round($fsize/1048576,2);
	echo "$EXTRACT_PATH/simple-cdd/package.tar.gz {$fsize}MB\n";


	$productname="Artica";
	if(is_file("/root/$type.ProductName")){
		$productname=trim(@file_get_contents("/root/$type.ProductName"));
	}
    $addedver=GetArticaSPVers();

    if(strlen($HotfixBin)>3){
        $addedver=$addedver." Hotfix $HotfixBin";
    }

	if(preg_match("#artica-([0-9\.]+)\.tgz#", basename($Artica),$re)){
		$version=$re[1];
		$version=str_replace(".000000","",$version);
		echo "$productname Version: $version$addedver\n";
	}

	$title="MicroNode v$version$addedver";

    $f=array();
	$f[]="label auto";
	$f[]="menu label ^$title";
	$f[]="kernel /install.amd/vmlinuz";
	$f[]="append  preseed/file=/cdrom/simple-cdd/ARTICA.preseed auto=true priority=critical interface=auto vga=788 initrd=/install.amd/initrd.gz -- quiet";
	@file_put_contents("{$GLOBALS["EXTRACT_PATH"]}/isolinux/adtxt.cfg",@implode("\n", $f));
	echo "{$GLOBALS["EXTRACT_PATH"]}/isolinux/adtxt.cfg done\n";

	$f[]="mirror_components=\"main contrib non-free\"";
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-1.7.092600.tgz\"";
	$f[]="all_extras=\"\$all_extras \$simple_cdd_dir/profiles/artica-iso\"";
        $f[]="profiles=\"ARTICA\"";
        $f[]="auto_profiles=\"ARTICA\"";


	if(!is_file("{$GLOBALS["EXTRACT_PATH"]}/simple-cdd/ARTICA.conf")){
		echo "{$GLOBALS["EXTRACT_PATH"]}/simple-cdd/ARTICA.conf\n";
		@file_put_contents("{$GLOBALS["EXTRACT_PATH"]}/simple-cdd/ARTICA.conf", @implode("\n", $f)."\n");
	}



	if($type=="PROXY_APPLIANCE"){
		$isoname="artica-microproxy-$version.debian10-amd64.dvd.iso";
	}

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
    system("split -b 3m --verbose $srcpath/package.tar.gz /home/spliteddeb12/full-package.split");



	$BaseWorkDir="/home/spliteddeb12";
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
$sfcomand="sftp://root:$password@37.187.156.120/home/www.artica.fr/download/mainpkg12/$basename";
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


function pressed($targetpath=null,$type="PROXY_APPLIANCE"){

    $productname="Artica";
    if(is_file("/root/$type.ProductName")){
        $productname=trim(@file_get_contents("/root/$type.ProductName"));
    }

	echo "Prepare Packages in $targetpath/ARTICA.packages - $type\n";
	packages("$targetpath/ARTICA.packages");
	$packages=packages();

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



	$hostname="microproxy";



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
    $f[]="d-i     d-i netcfg/wireless_wep string";
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
    @copy("/root/init.d/artica-iso.service", "$targetpath/artica-iso.service");
    @chmod("$targetpath/remove-systemd.sh",0755);
	@chmod("$targetpath/artica-iso",0755);

	$pressed[]="cp /cdrom/simple-cdd/artica-iso /target/etc/init.d/";
    $pressed[]="cp /cdrom/simple-cdd/artica-iso.service /target/etc/systemd/system/";
	$pressed[]="chmod 0755 /target/etc/init.d/artica-iso";
	//$pressed[]="tar -xkf /cdrom/simple-cdd/package.tar.gz -C /target/ >target/var/log/extract.log 2>&1";
    $pressed[]="cp /cdrom/simple-cdd/package.tar.gz /target/home/package.tar.gz";
   // $pressed[]="in-target tar -xkf /home/package.tar.gz -C / >/var/log/extract.log 2>&1";
	$pressed[]="in-target chmod 0755 /etc/init.d/artica-iso >/dev/null 2>&1";
	$pressed[]="in-target systemctl enable artica-iso";
	$f[]="d-i preseed/late_command string " .@implode(";", $pressed);
	$f[]="";

	echo "* * * * * * * * * * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * *\n";
	echo "* * * * * * * * $targetpath/ARTICA.preseed - DONE - * * * * * * * *\n";
    echo "* * * * * * * * * * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * ** * * * * * * *\n";
	@file_put_contents("$targetpath/ARTICA.preseed", @implode("\n", $f));
}
function packages($targetfile=null){
    $packages=InPressed();

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

function InPressed(){

    // voir aussi /usr/share/simple-cdd/profiles

	$f[]="locales";
	$f[]="less";
    $f[]="ntp";
	$f[]="util-linux-locales";
	$f[]="openssh-server";
	$f[]="openssl";
	$f[]="libcap2"; // Important for Artica Rest
    $f[]="htop";
	$f[]="rrdtool";
    $f[]="openssh-server";
	$f[]="bridge-utils";
	$f[]="krb5-kdc";
	$f[]="dialog";
    $f[]="ipset";
    // Squid
    $f[]="libltdl7";
    $f[]="libecap3";

    // Memcached
    $f[]="libevent-2.1-6";

    $packages["rsync"]=true;
    $packages["pv"]=true;
    $packages["ethtool"]=true;
    $packages["dnsutils"]=true;
    $packages["isc-dhcp-client"]=true;
    $packages["krb5-user"]=true;
    $packages["krb5-config"]=true;
    $packages["krb5-kdc"]=true;
    $packages["vlan"]=true;
    $packages["wget"]=true;
    $packages["udev"]=true;
    $packages["usbutils"]=true;
    $packages["apparmor-utils"]=true;
    $packages["arp-scan"]=true;
    $packages["lsb-release"]=true;
    $packages["netcat-openbsd"]=true;
    $packages["cifs-utils"]=true;
    $packages["locales-all"]=true;

    foreach ($f as $package){
        $packages[trim($package)]=true;
	}

	return $packages;

}
function BuildDefaultsFiles(){
    DefaultDownload();
    DefaultPackages();
    ARTICAPackages();
    DefaultExcludes();
    ARTICAPreseed();
    DefaultUdebs();
}
function DefaultDownload(){

    $target_file="/usr/share/simple-cdd/profiles/default.downloads";
    $f[]="# keep grub-pc and grub-efi or debian-installer may not work properly on";
    $f[]="# i386/amd64.";
    $f[]="grub-pc";
    $f[]="grub-efi";
    $f[]="";
    $f[]="popularity-contest";
    $f[]="console-setup";
    $f[]="";
    $f[]="usbutils";
    $f[]="acpi";
    $f[]="acpid";
    $f[]="eject";
    $f[]="";
    $f[]="# needed for debian-installer's LVM, software RAID or encrypted disks:";
    $f[]="lvm2";
    $f[]="mdadm";
    $f[]="cryptsetup";
    $f[]="";
    $f[]="# to support reiserfs, JFS and XFS filesystems";
    $f[]="reiserfsprogs";
    $f[]="jfsutils";
    $f[]="xfsprogs";
    $f[]="";
    $f[]="# debian-cd uses debootstrap from the mirror";
    $f[]="debootstrap";
    $f[]="";
    $f[]="# initramfs-tools recommends busybox, and we don't yet support recommends";
    $f[]="busybox";
    $f[]="";
    $f[]="# newer debian-cd (0.3.5+) requires syslinux-common or syslinux in the mirror";
    $f[]="syslinux-common";
    $f[]="syslinux";
    $f[]="# newer debian-cd (3.1.5+) requires isolinux";
    $f[]="isolinux";
    $f[]="";
    $f[]="# necessary if user chooses to not set a root password.";
    $f[]="sudo";
    $f[]="";
    $f[]="# Artica default packages";
    $Array=InPressed();
    foreach($Array as $package=>$none){
        $f[]=$package;
    }

    $f[]="";
    $f[]="# Kasfaleia adding packages";
    $f[]="shim-unsigned";
    $f[]="shim-signed";
    $f[]="shim-signed-common";
    $f[]="shim-helpers-amd64-signed";
    $f[]="sbsigntool";
    $f[]="efibootmgr";
    $f[]="grub-efi-amd64";
    $f[]="grub-efi-amd64-bin";
    $f[]="grub-pc-bin";
    $f[]="";
    mkdir(dirname($target_file),0755,true);
    $Data=implode("\n",$f);
    echo "Writing [$target_file]\n";
    @file_put_contents($target_file,$Data);
}
function DefaultPackages(){

    $target_file = "/usr/share/simple-cdd/profiles/default.packages";
    $f[] = "# less is more intuituve";
    $f[] = "less";
    $f[] = "# Artica default packages";
    $Array = InPressed();
    foreach ($Array as $package => $none) {
        $f[] = $package;
    }
    mkdir(dirname($target_file), 0755, true);
    $Data = implode("\n", $f);
    echo "Writing [$target_file]\n";
    @file_put_contents($target_file, $Data);
}
function ARTICAPackages(){
    $target_file = "/usr/share/simple-cdd/profiles/ARTICA.packages";
    $Array = InPressed();
    foreach ($Array as $package => $none) {
        $f[] = $package;
    }
    mkdir(dirname($target_file), 0755, true);
    $Data = implode("\n", $f);
    echo "Writing [$target_file]\n";
    @file_put_contents($target_file, $Data);
}
function DefaultUdebs(){
    $target_file = "/usr/share/simple-cdd/profiles/default.udebs";
    $f[]="# the udeb needed for simple-cdd";
    $f[]="simple-cdd-profiles\n";
    mkdir(dirname($target_file), 0755, true);
    $Data = implode("\n", $f);
    echo "Writing [$target_file]\n";
    @file_put_contents($target_file, $Data);
}
function DefaultExcludes(){
    $target_file = "/usr/share/simple-cdd/profiles/default.excludes";
    $f[] ="# Exclude console-setup-freebsd to prevent warnings about uninstallable files,";
    $f[] ="# see bug #619328.";
    $f[] ="#";
    $f[] ="# Comment out this exclude if you are building freebsd images.";
    $f[] ="console-setup-freebsd";
    $f[]="";
    mkdir(dirname($target_file), 0755, true);
    $Data = implode("\n", $f);
    echo "Writing [$target_file]\n";
    @file_put_contents($target_file, $Data);
}
function ARTICAPreseed()
{
    $target_file = "/usr/share/simple-cdd/profiles/ARTICA.preseed";

    $f[] = "####################################################################";
    $f[] = "#  PRESEED - Created  " . date("Y-m-d H:i:s") . "   #";
    $f[] = "####################################################################";
    $f[] = "";
    $f[] = "# Wiki: http://wiki.debian.org/DebianInstaller/Preseed";
    $f[] = "";
    $f[] = "####################################################################";
    $f[] = "# Installation Sources";
    $f[] = "####################################################################";
    $f[] = "";
    $f[] = "# Pull bits from the local CD only";
    $f[] = "";
    $f[] = "# On ne souhaite pas installer les paquets recommandés";
    $f[] = "# L'installation sera limitée aux paquets \"essentials\"";
    $f[] = "d-i base-installer/install-recommends boolean false";
    $f[] = "d-i mirror/file/directory string /cdrom";
    $f[] = "d-i mirror/suite string";
    $f[] = "d-i mirror/http/proxy string";
    $f[] = "";
    $f[] = "# Post install APT setup - Skip it";
    $f[] = "d-i apt-setup/use_mirror boolean false";
    $f[] = "d-i apt-setup/services-select multiselect \"\"";
    $f[] = "#d-i     apt-setup/uri_type      select d-i";
    $f[] = "#d-i     apt-setup/hostname      string mirrors.kernel.org";
    $f[] = "#d-i     apt-setup/directory     string /debian/";
    $f[] = "#d-i     apt-setup/another       boolean false";
    $f[] = "#d-i     apt-setup/security-updates      boolean false";
    $f[] = "#d-i     finish-install/reboot_in_progress note";
    $f[] = "#d-i     prebaseconfig/reboot_in_progress        note";
    $f[] = "";
    $f[] = "d-i     apt-setup/non-free 	boolean true";
    $f[] = "d-i     apt-setup/contrib 	boolean true";
    $f[] = "";
    $f[] = "####################################################################";
    $f[] = "# Networking";
    $f[] = "####################################################################";
    $f[] = "";
    $f[] = "# Network Configuration";
    $f[] = "d-i     netcfg/enable boolean   true";
    $f[] = "d-i     netcfg/get_hostname     string  artica-appliance";
    $f[] = "d-i     netcfg/get_domain       string  company.tld";
    $f[] = "d-i     netcfg/disable_dhcp     boolean false";
    $f[] = "d-i     netcfg/choose_interface auto";
    $f[] = "d-i     netcfg/wireless_wep     string";
    $f[] = "d-i     netcfg/dhcp_failed note";
    $f[] = "d-i     netcfg/dhcp_options select Configure network manually";
    $f[] = "d-i     d-i hw-detect/load_firmware boolean true";
    $f[] = "";
    $f[] = "####################################################################";
    $f[] = "# Disk Partitioning/Boot loader";
    $f[] = "####################################################################";
    $f[] = "";
    $f[] = "d-i partman-efi/non_efi_system boolean true";
    $f[] = "d-i partman-partitioning/confirm_write_new_label boolean true";
    $f[] = "# On partionne en normal: pas de RAID ni de LVM";
    $f[] = "d-i partman-auto/method string regular";
    $f[] = "d-i partman-auto/choose_recipe select atomic";
    $f[] = "";
    $f[] = "# Pour être sûr, on supprime une éventuelle configuration LVM";
    $f[] = "# Chaînes pour ne pas toucher la configuration LVM (donc pas de configuration)";
    $f[] = "d-i partman-lvm/device_remove_lvm boolean true";
    $f[] = "d-i partman-lvm/confirm boolean true";
    $f[] = "d-i partman-lvm/confirm_nooverwrite boolean true";
    $f[] = "";
    $f[] = "# Même chose pour le RAID";
    $f[] = "d-i partman-md/device_remove_md boolean true";
    $f[] = "d-i partman-md/confirm boolean true";
    $f[] = "";
    $f[] = "d-i partman/choose_partition select finish";
    $f[] = "d-i partman/confirm boolean true";
    $f[] = "d-i partman/confirm_nooverwrite boolean true";
    $f[] = "d-i partman-basicfilesystems/no_swap boolean false";
    $f[] = "";
    $f[] = "d-i grub-installer/only_debian boolean true";
    $f[] = "d-i grub-installer/bootdev  string default";
    $f[] = "d-i grub-installer/with_other_os  boolean true";
    $f[] = "d-i grub-installer/grub2_instead_of_grub_legacy boolean true";
    $f[] = "";
    $f[] = "####################################################################";
    $f[] = "# Localizations";
    $f[] = "####################################################################";
    $f[] = "";
    $f[] = "# Install Time ";
    $f[] = "#d-i	console-tools/archs string skip-config < - enable";
    $f[] = "#d-i 	debian-installer/locale string en_US";
    $f[] = "#d-i 	console-keymaps-at/keymap select us";
    $f[] = "";
    $f[] = "#d-i     languagechooser/language-name-fb    select English";
    $f[] = "#d-i     debian-installer/locale             select en_US.UTF-8";
    $f[] = "";
    $f[] = "# Timezone - Skip setting this which will force the installer to prompt";
    $f[] = "#d-i     tzconfig/gmt            boolean true";
    $f[] = "#d-i     tzconfig/choose_country_zone/Europe select Paris";
    $f[] = "#d-i     tzconfig/choose_country_zone_single boolean true";
    $f[] = "#d-i	time/zone	select	Europe/Paris";
    $f[] = "d-i	clock-setup/utc	boolean	true";
    $f[] = "#d-i	kbd-chooser/method	select	American English";
    $f[] = "d-i	mirror/country	string	manual";
    $f[] = "d-i     clock-setup/ntp boolean false";
    $f[] = "";
    $f[] = "# X11 config";
    $f[] = "xserver-xorg     xserver-xorg/autodetect_monitor              boolean true";
    $f[] = "xserver-xorg     xserver-xorg/config/monitor/selection-method select medium";
    $f[] = "xserver-xorg     xserver-xorg/config/monitor/mode-list        select 1024x768 @ 60 Hz";
    $f[] = "xserver-xorg     xserver-xorg/config/display/modes            multiselect 1024x768, 800x600";
    $f[] = "";
    $f[] = "# LDAP Config - Have to have this or will be prompted at install";
    $f[] = "";
    $f[] = "ldap-auth-config    ldap-auth-config/binddn    string    cn=Manager,dc=yourdomain,dc=com";
    $f[] = "ldap-auth-config    ldap-auth-config/bindpw    password    secret";
    $f[] = "ldap-auth-config    ldap-auth-config/dblogin    boolean    false";
    $f[] = "ldap-auth-config    ldap-auth-config/dbrootlogin    boolean    true";
    $f[] = "ldap-auth-config    ldap-auth-config/ldapns/base-dn    string    dc=yourdomain,dc=com";
    $f[] = "ldap-auth-config    ldap-auth-config/ldapns/ldap-server    string    ldap://127.0.0.1/";
    $f[] = "ldap-auth-config    ldap-auth-config/ldapns/ldap_version    select    3";
    $f[] = "ldap-auth-config    ldap-auth-config/move-to-debconf    boolean    true";
    $f[] = "ldap-auth-config    ldap-auth-config/override    boolean    true";
    $f[] = "ldap-auth-config    ldap-auth-config/pam_password    select    crypt";
    $f[] = "ldap-auth-config    ldap-auth-config/rootbinddn    string    cn=manager,dc=yourdomain,dc=com";
    $f[] = "ldap-auth-config    ldap-auth-config/rootbindpw    password   ";
    $f[] = "libnss-ldap    libnss-ldap/binddn    string    cn=proxyuser,dc=yourdomain,dc=com";
    $f[] = "libnss-ldap    libnss-ldap/bindpw    password    ";
    $f[] = "libnss-ldap    libnss-ldap/confperm    boolean    false";
    $f[] = "libnss-ldap    hd-medialibnss-ldap/dblogin    boolean    false";
    $f[] = "libnss-ldap    libnss-ldap/dbrootlogin    boolean    true";
    $f[] = "libnss-ldap    libnss-ldap/nsswitch    note    ";
    $f[] = "libnss-ldap    libnss-ldap/override    boolean    true";
    $f[] = "libnss-ldap    libnss-ldap/rootbinddn    string    cn=manager,dc=yourdomain,dc=com";
    $f[] = "libnss-ldap    libnss-ldap/rootbindpw    password    ";
    $f[] = "libnss-ldap    shared/ldapns/base-dn    string    dc=yourdomain,dc=com";
    $f[] = "libnss-ldap    shared/ldapns/ldap-server    string    ldap://127.0.0.1/";
    $f[] = "libnss-ldap    shared/ldapns/ldap_version    select    3";
    $f[] = "libpam-ldap    libpam-ldap/binddn    string    cn=proxyuser,dc=yourdomain,dc=com";
    $f[] = "libpam-ldap    libpam-ldap/bindpw    password    ";
    $f[] = "libpam-ldap    libpam-ldap/dblogin    boolean    false";
    $f[] = "libpam-ldap    libpam-ldap/dbrootlogin    boolean    false";
    $f[] = "libpam-ldap    libpam-ldap/override    boolean    true";
    $f[] = "libpam-ldap    libpam-ldap/pam_password    select    crypt";
    $f[] = "libpam-ldap    libpam-ldap/rootbinddn    string    cn=manager,dc=yourdomain,dc=com";
    $f[] = "libpam-ldap    libpam-ldap/rootbindpw    password    ";
    $f[] = "libpam-ldap    shared/ldapns/base-dn    string    dc=yourdomain,dc=com";
    $f[] = "libpam-ldap    shared/ldapns/ldap-server    string    ldap://127.0.0.1/";
    $f[] = "libpam-ldap    shared/ldapns/ldap_version    select    3";
    $f[] = "libpam-runtime    libpam-runtime/profiles    multiselect    unix, ldap";
    $f[] = "";
    $f[] = "krb5-config	krb5-config/default_realm    string ";
    $f[] = "krb5-config	krb5-config/kerberos_servers    string";
    $f[] = " ";
    $f[] = "####################################################################";
    $f[] = "# User Creation";
    $f[] = "####################################################################";
    $f[] = "";
    $f[] = "# Root User";
    $f[] = "d-i passwd/root-password password artica";
    $f[] = "d-i passwd/root-password-again password artica";
    $f[] = "d-i passwd/make-user boolean false";
    $f[] = "";
    $f[] = "# Mortal User";
    $f[] = "#d-i	passwd/user-fullname            string Artica User";
    $f[] = "#d-i	passwd/username                 string artica";
    $f[] = "#d-i 	passwd/user-password password artica";
    $f[] = "#d-i 	passwd/user-password-again password artica";
    $f[] = "";
    $f[] = "####################################################################";
    $f[] = "# Finish setup";
    $f[] = "####################################################################";
    $f[] = "";
    $f[] = "d-i cdrom-detect/eject boolean true";
    $f[] = "d-i finish-install/reboot_in_progress note";
    $f[] = "";
    $f[] = "####################################################################";
    $f[] = "# Software Selections";
    $f[] = "####################################################################";
    $f[] = "";
    $f[] = "tasksel	tasksel/first	multiselect	standard";

    $Array = InPressed();
    foreach ($Array as $package => $none) {
        $pp[] = $package;
    }
    $PackageInLine=@implode(" ",$pp);

    $f[] = "d-i pkgsel/include string $PackageInLine locales-all shim-unsigned shim-signed shim-signed-common shim-helpers-amd64-signed-template shim-helpers-amd64-signed sbsigntool efibootmgr";
    $f[] = "";
    $f[] = "####################################################################";
    $f[] = "# Additional preseed entries (from data/debconf)";
    $f[] = "####################################################################";
    $f[] = "";
    $f[] = "exim4-config exim4/no_config boolean true";
    $f[] = "base-config     apt-setup/non-free      boolean true";
    $f[] = "base-config 	  apt-setup/security-updates  boolean false";
    $f[] = "popularity-contest popularity-contest/participate boolean false";
    $f[] = "d-i apt-setup/cdrom/set-first boolean false";
    $f[] = "simple-cdd simple-cdd/profiles multiselect ARTICA";
    $f[] = "samba-common samba-common/dhcp boolean false";
    $f[] = "davfs2 davfs2/suid_file boolean true";
    $f[] = "krb5-config krb5-config/default_realm string";
    $f[] = "krb5-config krb5-config/kerberos_servers string";
    $f[] = "krb5-config krb5-config/admin_server string";
    $f[] = "krb5-config krb5-config/add_servers_realm string";
    $f[] = "bandwidthd bandwidthd/dev select any";
    $f[] = "bandwidthd bandwidthd/subnet string";
    $f[] = "slapd slapd/password1 password";
    $f[] = "slapd slapd/password2 password";
    $f[] = "";
    $f[] = "#!/bin/sh";
    $f[] = "update-rc.d artica-iso defaults || true";
    $f[] = "/usr/bin/apt install sysvinit-core sysvinit systemd-sysv- || true";
    $f[] = "";
    $f[] = "d-i preseed/late_command string cp /cdrom/simple-cdd/artica-iso /target/etc/init.d/;cp /cdrom/simple-cdd/remove-systemd.sh /target/root/;chmod 0755 /target/etc/init.d/artica-iso;chmod 0755 /target/root/remove-systemd.sh;cp /cdrom/simple-cdd/package.tar.gz /target/home/package.tar.gz;cp /cdrom/simple-cdd/package.tar.gz /target/home/package.src.tar.gz";
    $f[] = "";

    mkdir(dirname($target_file), 0755, true);
    $Data = implode("\n", $f);
    echo "Writing [$target_file]\n";
    @file_put_contents($target_file, $Data);
}
?>
