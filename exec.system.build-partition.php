<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["BYWIZARD"]=false;
$GLOBALS["NO_FSTAB"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--bywizard#",implode(" ",$argv))){$GLOBALS["BYWIZARD"]=true;}
if(preg_match("#--nofstab#",implode(" ",$argv))){$GLOBALS["NO_FSTAB"]=true;}

include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.auth.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}


if($argv[1]=="--unlink"){disk_unlink($argv[2]);exit();}
if($argv[1]=="--full"){disk_build_unique_partition($argv[2],$argv[3],$argv[4]);exit();}
if($argv[1]=="--1part"){FindFirstPartition($argv[2]);exit();}
if($argv[1]=="--extend"){extend($argv[2]);exit();}
if($argv[1]=="--move"){move_to($argv[2]);exit();}


function build_progress_extend($text,$pourc){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/system.extend.progress";
    if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}
    $cachefile=$GLOBALS["CACHEFILE"];
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}

function move_to($encoded){
    $mounted    = base64_decode($encoded);
    $unix       = new unix();
    $rsync      = $unix->find_program("rsync");
    $rm         = $unix->find_program("rm");
    $cp         = $unix->find_program("cp");

    $ln         = $unix->find_program("ln");
    if(!is_file($rsync)){
        echo "Rsync, no such binary\n";
        build_progress_extend("{move_to} {failed}",110);
        return false;
    }

    build_progress_extend("{move_to} $mounted",5);
    echo "Move System Directories to the partition mounted on $mounted\n";
    $DirSrc="$mounted/system";

    $DIRS[]="/home/artica/patchsBackup";
    $DIRS[]="/home/artica/tmp";
    $DIRS[]="/home/artica/influx";
    $DIRS[]="/home/artica/philesight";

    $DIRS[]="/var/cache/apt";
    $DIRS[]="/var/lib/clamav";
    $DIRS[]="/usr/lib/jvm";
    $DIRS[]="/var/lib/apt";
    $DIRS[]="/usr/share/locale";
    $DIRS[]="/usr/share/artica-postfix/angular";
    $DIRS[]="/var/log";
    $DIRS[]="/usr/lib/x86_64-linux-gnu/dri";

    if(!is_dir($DirSrc)) {
        @mkdir($DirSrc);
    }
    if(!is_dir($DirSrc)){
        echo "$DirSrc permission denied.";
    }

    $c=10;
    foreach ($DIRS as $DirectorySource){
        $c++;
        $useCP          = false;
        $DirAsSource    = $DirectorySource;
        if(is_link($DirectorySource)){
            $useCP=true;
            $DirectorySource=readlink($DirectorySource);
        }
        if(!is_dir($DirectorySource)){
            echo "$DirectorySource no such directory\n";
            continue;
        }
        $DirectoryTarget="$DirSrc$DirAsSource";

        if($DirectorySource==$DirectoryTarget){
            echo "$DirectorySource is the same of $DirectoryTarget\n";
            continue;
        }
        build_progress_extend("{move_to} $DirectorySource",$c);

        if(!is_dir($DirectoryTarget)){@mkdir($DirectoryTarget,0755,true);}
        echo "\n\n1) Copy $DirectorySource to $DirectoryTarget\n";
        if(!$useCP) {
            $cmd = "$rsync -arulHpEXogt $DirectorySource/ $DirectoryTarget/ 2>&1";
        }else{
            $cmd = "$cp -rfd $DirectorySource/* $DirectoryTarget/ 2>&1";
        }
        $rsyncresults=array();
        exec($cmd,$rsyncresults);
        foreach ($rsyncresults as $line){
            echo $line."\n";
        }

        echo "1.1) Get sizes..\n";
        $size1=$unix->DIRSIZE_BYTES($DirectorySource,false);
        $size2=$unix->DIRSIZE_BYTES($DirectoryTarget,false);
        $results=$size1-$size2;
        $resultsKO=squid_admin_mysql_FormatBytes($results/1024);
        echo "2) Size diff = $results ($resultsKO) ".
            "Source dir = ".squid_admin_mysql_FormatBytes($size1/1024).
            " > > Dest dir = ".squid_admin_mysql_FormatBytes($size2/1024).
            "\n";
        if($results>937886){
            echo "Issue during copy...\n";
            shell_exec("$rm -rf $DirectoryTarget");
            continue;
        }

        echo "3) Remove $DirectorySource directory\n";
        shell_exec("$rm -rf $DirectorySource");

        if(is_dir($DirectorySource)){shell_exec("$rm -rf $DirectorySource");}
        echo "4) Symlink $DirectoryTarget to $DirAsSource \n";
        shell_exec("$ln -sf $DirectoryTarget $DirAsSource");


    }



    build_progress_extend("{success}",100);
}

function extend($path){
    $unix=new unix();
    $embiggen="/usr/share/artica-postfix/bin/embiggen-disk";
    build_progress_extend("Extend path $path",20);
    if(!is_file($embiggen)){
        build_progress_extend("Extend {failed} embiggen-disk not found",110);
        die();
    }
    @chmod($embiggen,0755);
    build_progress_extend("Extend path $path",50);
    exec("$embiggen -verbose $path 2>&1",$array);
    $resize2fs_path=$path;

    foreach ($array as $line){
        echo "$line\n";
        if(preg_match("#error: running resize2fs \[resize2fs\s+(.+?)\]#",$line,$re)){
            $resize2fs_path=$re[1];
        }
    }
    echo "/usr/sbin/resize2fs $resize2fs_path\n";

    $resize2fs=$unix->find_program("resize2fs");
    $array[]="$resize2fs $resize2fs_path";
    build_progress_extend("resize2fs $resize2fs_path...",60);
    exec("/usr/sbin/resize2fs $resize2fs_path 2>&1",$array2);

    foreach ($array2 as $line){
        echo "resize2fs: $line\n";
    }

    if( !embiggen($path) ){
        build_progress_extend("resize2fs $resize2fs_path...",65);
        exec("/usr/sbin/resize2fs $resize2fs_path 2>&1",$array2);
        if(!embiggen($path) ){
            build_progress_extend("resize2fs 2 times failed...",110);
            return false;
        }
    }

    build_progress_extend("{refresh}...",70);
    squid_admin_mysql(1,"$path extend partition results...",@implode("\n",$array));
    build_progress_extend("{done}...",100);
}
function embiggen($path){
    exec("/usr/sbin/resize2fs $path 2>&1",$embiggen);

    foreach ($embiggen as $line) {
        if (preg_match("The filesystem is already#", $line, $re)) {
            return true;
        }
        echo "resize2fs: $line\n";
    }
    return false;

}
function disk_unlink($dev){
	$unix=new unix();
	
	build_progress("{unlink} $dev",20);
	if($dev==null){
		build_progress("{unlink} $dev {failed}",110);
		return;
	}
	
	
	$fdisk=$unix->find_program("fdisk");
	$umount=$unix->find_program("umount");
	exec("$fdisk -l $dev 2>&1",$results);
	$parts=array();
	foreach ($results as $num=>$val){
		if(preg_match("#^(.+?)\s+.*?Linux#", $val,$re)){
			echo "Found mounted Partition {$re[1]}\n";
			build_progress("{found} {$re[1]}",30);
			$parts[$re[1]]=true;
		}
	
	}

	foreach ($parts as $dev=>$val){
		echo "Umount $dev\n";
		shell_exec("$umount -l $dev");
		echo "$umount -l $dev\n";
		build_progress("{umount} {$dev}",30);
		echo "Remove $dev from fstab\n";
		disk_remove_fstab($dev);
	}
	
	build_progress("{rescan-disk-system}",70);
	build_progress("{rescan-disk-system}",71);
	build_progress("{unlink} $dev {success}",100);
	return;
	
	
	
}

function disk_remove_fstab($dev=null){
	$unix=new unix();
	if($dev==null){
		events("disk_remove_fstab():: No target specified...");
		return;
	}
	$UUID_TABLE=array();
	$uuidregex=null;
	$array=$unix->BLKID_ARRAY();
	
	while (list ($dev, $subarray) = each ($array) ){
		if($subarray["UUID"]<>null){
			$UUID_TABLE[$subarray["UUID"]]=$dev;
		}
	}
	
	reset($array);
	$UUID=$array[$dev]["UUID"];

	
	if($UUID<>null){
		$uuidregex="UUID=$UUID";
	}
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	$t=array();
	$devRegex=str_replace("/", "\/", $dev);
	$devRegex=str_replace(".", "\.", $devRegex);
	
	
	
	$found=false;
	foreach ( $f as $num=>$val ){
		$val=trim($val);
		if($val==null){continue;}
		if(count($UUID_TABLE)>0){
			if(preg_match("#UUID=(.+?)\s+#", $val,$re)){
				if(!isset($UUID_TABLE[$re[1]])){
					if($GLOBALS["VERBOSE"]){echo "**** REMOVE {$re[1]} ****\n";}
					continue;
				}
			}
		}
		
		if(preg_match("#^$devRegex\s+#", $val)){
			if($GLOBALS["VERBOSE"]){echo "**** REMOVE $val ****\n";}
			continue;}
		if($uuidregex<>null){
			if(preg_match("#^$uuidregex\s+#", $val)){
				if($GLOBALS["VERBOSE"]){echo "**** REMOVE $val ****\n";}
				continue;}
			
		}
		if($GLOBALS["VERBOSE"]){echo "NO MATCH #^$uuidregex\s+# or #^$devRegex\s+# $val \n";}
		$t[]=$val;
	}
	
	@file_put_contents("/etc/fstab", @implode("\n", $t)."\n");	
	
	
}


function build_progress_wizard($text,$pourc){
	$cachefile=PROGRESS_DIR."/squid.newcache.center.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function build_progress($text,$pourc){
	
	if($GLOBALS["BYWIZARD"]){
		if($pourc<20){$pourc=20;}
		if($pourc>90){$pourc=90;}
		build_progress_wizard($text,$pourc);
	}
	
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/system.partition.progress";
	
	
	if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function disk_build_unique_partition($dev,$label,$fs_type=null){
	$filelogs=PROGRESS_DIR."/system.partition.txt";
	$GLOBALS["FILELOG"]=$filelogs;

	
	
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".md5($dev.$label);
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		events("Already PID $pid exists, aborting...");
		build_progress("Already PID $pid exists, aborting",110);
		return;
	}
	
	build_progress("{checking}",5);
	events("***********************************");
	events("Dev.........: $dev");
	events("Label.......: $label");
	events("FileSystem..: $fs_type");
	
	
	$mount=$unix->find_program("mount");
	
	$disk_label=str_replace(" ", "_", $label);
	$targetMountPoint=$unix->isDirInFsTab("/media/$disk_label");
	events("Target Mount.: $targetMountPoint");
	build_progress("Target Mount point = $targetMountPoint",10);
	
	events("***********************************");
	
	if($GLOBALS["NO_FSTAB"]){
		if($targetMountPoint<>null){
			events("unmounting the new media");
			$unix->DelFSTab($dev);
			$targetMountPoint=null;
		}
	}
	
	if($targetMountPoint<>null){
		events("/media/$disk_label already set in fstab!! remove entry in fstab first...");
		events("Mounting the new media");
		build_progress("Mounting the new media = /media/$disk_label",15);
		$cmd="$mount /media/$disk_label 2>&1";	
		$results=array();
		exec($cmd,$results);
		foreach ($results as $num=>$val){events($val);}
		build_progress("{success}",100);
		return;
	}
	
	$tmpfile=$unix->FILE_TEMP();
	build_progress("Creating disk configuration",20);
	events("Writing to $tmpfile");
	@file_put_contents($tmpfile, ",,L\n");
	if(!is_file($tmpfile)){
		build_progress("Creating disk configuration $tmpfile {failed}",110);
		return;
	}
	
	events("Cleaning $dev..., please wait...");
	$dd=$unix->find_program("dd");
	$sfdisk=$unix->find_program("sfdisk");
	$mkfs=$unix->find_program("mkfs.ext4");
	$btrfs=$unix->find_program("mkfs.btrfs");
	$xfs=$unix->find_program("mkfs.xfs");
	$mount=$unix->find_program("mount");

	events("$dev filesystem $fs_type");
	$extV=$fs_type;
	$e2label=$unix->find_program("e2label");
	$e2label_EX=true;
	
	$MKFS["ext3"]="-b 4096 -L \"$disk_label\"";
	$MKFS["ext4"]="-L \"$disk_label\" -i 8096 -I 256 -Tlargefile4";
	$MKFS["btrfs"]="--label \"$disk_label\"";
	$MKFS["xfs"]="-f -L \"$disk_label\"";
	$MKFS["reiserfs"]="-q --label \"$disk_label\"";
	
	
	if($fs_type==null){$fs_type="ext4";}
	$pgr=$unix->find_program("mkfs.$fs_type");
	events("mkfs.$fs_type = $pgr");

	if(is_file($pgr)){
		$mkfs="$pgr {$MKFS[$fs_type]} ";
		$extV="$fs_type";
		$e2label_EX=false;
	}
		

	build_progress("Cleaning $dev..., {please_wait}",30);
	events("Cleaning $dev..., please wait...");
	$cmd="$sfdisk -f $dev <$tmpfile 2>&1";
	
	events($cmd);
	$results=array();
	exec($cmd,$results);
	foreach ($results as $num=>$val){
		events($val);
	}

	
	$FindFirstPartition=FindFirstPartition($dev);
	events("First partition = `$FindFirstPartition`");
	build_progress("First partition $FindFirstPartition",50);
	
	if($FindFirstPartition==null){
		build_progress("Find first partition failed",110);
		events("First partition = FAILED");
		return;
	}	
	
	build_progress("Building $FindFirstPartition..., {please_wait}",40);
	$cmd="$dd if=/dev/zero of=$FindFirstPartition bs=512 count=1 2>&1";
	events($cmd);
	$results=array();
	exec($cmd,$results);
	foreach ($results as $val){events($val);}
	build_progress("Formating $FindFirstPartition",60);
	$cmd="$mkfs $FindFirstPartition 2>&1";
	events("Formatting  $FindFirstPartition, please wait....");
	events($cmd);
	$results=array();
	exec($cmd,$results);
	foreach ($results as $val){events($val);}

	if($e2label_EX){
		build_progress("Set label to $disk_label",70);
		events("Set label to $disk_label");
        $tune2fs=$unix->find_program("tune2fs");
		$cmd="$tune2fs $FindFirstPartition -L $disk_label 2>&1";
		events($cmd);
		$results=array();
		exec($cmd,$results);
		foreach ($results as $val){events($val);}
	}

	if(!$GLOBALS["NO_FSTAB"]){
		build_progress("Change fstab $FindFirstPartition to /media/$disk_label",80);
		events("Change fstab to include new media $FindFirstPartition to /media/$disk_label");
		disk_change_fstab($FindFirstPartition,$extV,"/media/$disk_label");
	
		build_progress("Mounting the new media",90);
		events("Mounting the new media");
		$cmd="$mount $FindFirstPartition 2>&1";
		events($cmd);
		$results=array();
		exec($cmd,$results);
		foreach ($results as $num=>$val){events($val);}
	}
	
	

	
	build_progress("{success}",100);
	events("done...");	
	
}

function FindFirstPartition($dev){
	$unix=new unix();
	$fdisk=$unix->find_program("fdisk");
	$cmd="$fdisk -l $dev 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	foreach ($results as $num=>$val){
		if(!preg_match("#^(.+?)\s+.*?Linux#", $val,$re)){
			if($GLOBALS["VERBOSE"]){echo "'$val' NO MATCH #^(.+?)\s+.*?Linux#\n";}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "'$val' MATCH #^(.+?)\s+.*?Linux# -> {$re[1]}\n";}
		return $re[1];
		
		
	}
	
}

function disk_change_fstab($dev,$ext,$target){
	$unix=new unix();
	if($target==null){
		events("disk_change_fstab():: No target specified...");
		return;
	}
	$uuidregex=null;
	$array=$unix->BLKID_ARRAY();
	$UUID=$array[$dev]["UUID"];
	
	$optionsZ["ext3"]="defaults,relatime,errors=continue";
	$optionsZ["ext4"]="defaults,rw,noatime,async,data=writeback,barrier=0,commit=100,nobh,errors=continue";
	$optionsZ["reiserfs"]="defaults,notail,noatime,user_xattr,acl,barrier=none";
	$optionsZ["btrfs"]="defaults,noatime";
	$optionsZ["xfs"]="defaults,noatime,nodiratime,nosuid,nodev,allocsize=64m,quota";
	
	$options=$optionsZ[$ext];
	$tune2fs=$unix->find_program("tune2fs");
	if($ext=="ext4"){shell_exec("$tune2fs -o journal_data_writeback $dev");}
	
	$line="$dev\t$target\t$ext\t$options  0    1";
	if($UUID<>null){
		$line="UUID=$UUID\t$target\t$ext\t$options  0    1";
		$uuidregex="UUID=$UUID";
	}
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	
	$devRegex=str_replace("/", "\/", $dev);
	$devRegex=str_replace(".", "\.", $dev);
	
	
	@mkdir($target,0755,true);
	$found=false;
	foreach ( $f as $num=>$val ){
		if(preg_match("#^$devRegex\s+#", $val)){
			$f[$num]=$line;
			$found=true;
			continue;
		}
		if($uuidregex<>null){
			if(preg_match("#^$uuidregex\s+#", $val)){
				$f[$num]=$line;
				$found=true;
				continue;
			}
		}
		
	}
	if(!$found){$f[]=$line."\n";}
	@file_put_contents("/etc/fstab", @implode("\n", $f));
}
//##############################################################################
function events($text){
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile=$GLOBALS["FILELOG"];

	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [$pid]: $text\n";}
	@fwrite($f, "$date [$pid]: $text\n");
	@fclose($f);
	@chmod($logFile, 0777);
}