<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.hd.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["clonage"])){clonage();exit;}
if(isset($_GET["extend-partition"])){extend_partition();exit;}
if(isset($_GET["uuid-from-dev"])){uuid_from_dev();exit;}
if(isset($_GET["cpu-check-nx"])){check_nx();exit;}
if(isset($_GET["fstab-add"])){fstab_add();exit;}
if(isset($_GET["mountlist"])){mountlist();exit;}
if(isset($_GET["filesize"])){getfilesize();exit;}
if(isset($_GET["symlink"])){Getsymlink();exit;}
if(isset($_GET["DeleteFile"])){DeleteFile();exit;}
if(isset($_GET["unlink-disk"])){unlink_disk();exit;}
if(isset($_GET["create-swap"])){create_swap();exit;}
if(isset($_GET["remove-swap"])){remove_swap();exit;}
if(isset($_GET["rescan-swap"])){rescan_swap();exit;}
if(isset($_GET["hdparm"])){hdparm_direct();exit;}
if(isset($_GET["move-disk"])){move_disk();exit;}

foreach ($_GET as $num=>$ligne){$a[]="$num=$ligne";}
writelogs_framework("Unable to understand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);


function getfilesize(){
	$filename=$_GET["filesize"];
	writelogs_framework("$filename -> size",__FUNCTION__,__FILE__,__LINE__);
	if(!is_file($filename)){
		writelogs_framework("$filename no such file",__FUNCTION__,__FILE__,__LINE__);
		return;
	}

	echo "<articadatascgi>".@filesize($_GET["filesize"])."</articadatascgi>";
	
}
function DeleteFile(){
	$filename=$_GET["DeleteFile"];
	$filename=str_replace("../", "/", $filename);
	$filename=str_replace("./", "/", $filename);
	if(!is_file($filename)){return;}
	@unlink($filename);
}
function Getsymlink(){
	$filename=$_GET["symlink"];
	$filename=str_replace("../", "/", $filename);
	$filename=str_replace("./", "/", $filename);
	$dest=PROGRESS_DIR."/".basename($filename);
	if(!is_file($filename)){
		writelogs_framework("$filename no such file",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$cmdline="$ln -sf $filename $dest";
	writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$cmdline");
	@chmod($dest,0777);
}


function uuid_from_dev(){
	$unix=new unix();
	$dev=$_GET["uuid-from-dev"];
	$hd=new hd($dev);
	echo "<articadatascgi>".base64_encode($hd->uuid_from_dev())."</articadatascgi>";
}

function check_nx(){
	$unix=new unix();
	$check=$unix->find_program("check-bios-nx");
	if(strlen($check)<5){return;}
	exec("$check --verbose 2>&1",$results);
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";	
}
function fstab_add(){
	$dev=$_GET["dev"];
	$mount=$_GET["mount"];
	$unix=new unix();
	writelogs_framework("Add Fstab $dev -> $mount ",__FUNCTION__,__FILE__,__LINE__);
	$unix->AddFSTab($dev,$mount);

}
function mountlist(){
	$unix=new unix();
	$mount=$unix->find_program("mount");
	exec("$mount -l 2>&1",$results);
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";
}



function clonage(){
    $unix=new unix();
    $unix->framework_execute("exec.apt-get.php --grubpc","clone.progress","clone.log");
}

function move_disk(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $part=base64_encode($_GET["move-disk"]);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.extend.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.mv.txt";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($ARRAY["PROGRESS_FILE"], serialize($array));
    @chmod($ARRAY["LOG_FILE"],0777);

    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.system.build-partition.php --move \"$part\" >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__);
    shell_exec($cmd);


}

function extend_partition(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.extend.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.extend.txt";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($ARRAY["PROGRESS_FILE"], serialize($array));
    @chmod($ARRAY["LOG_FILE"],0777);

    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.system.build-partition.php --extend \"{$_GET["extend-partition"]}\" >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__);
    shell_exec($cmd);

}

function unlink_disk(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/system.partition.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/system.partition.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.system.build-partition.php --unlink \"{$_GET["unlink-disk"]}\" >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__);
	shell_exec($cmd);

}
function create_swap(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/system.swap.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/system.swap.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.system.swap.php --create >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__);
    shell_exec($cmd);

}

function rescan_swap(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    writelogs_framework("$php5 /usr/share/artica-postfix/exec.status.php --swap",__FUNCTION__,__FILE__);
    system("$php5 /usr/share/artica-postfix/exec.status.php --swap");
}

function remove_swap(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/system.swap.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/system.swap.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.system.swap.php --delete >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__);
    shell_exec($cmd);
}


function NOHUP_EXEC($cmdline){
	$cmdline=str_replace(">/dev/null 2>&1 &", "", $cmdline);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmdfinal="$nohup $cmdline >/dev/null 2>&1 &";
	writelogs_framework("$cmdfinal",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmdfinal);
}