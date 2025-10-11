<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){
    ini_set('display_errors', 1);
    ini_set('html_errors',0);ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    $GLOBALS["VERBOSE"]=true;
}


if($argv[1]=="--create"){create_swap();exit();}
if($argv[1]=="--delete"){delete_swap();exit();}

function build_progress($pourc,$text){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents(PROGRESS_DIR."/system.swap.progress", serialize($array));
    @chmod(PROGRESS_DIR."/system.swap.progress",0755);

}

function create_swap(){
    $unix=new unix();
    $sock=new sockets();
    $CREATE_NEW_SWAP=unserializeb64($sock->GET_INFO("CREATE_NEW_SWAP"));

    if(!isset($CREATE_NEW_SWAP["path"])){
        build_progress(110,"Nothing to do...");
        $sock->SET_INFO("CREATE_NEW_SWAP","");
        exit;
    }
    if(!isset($CREATE_NEW_SWAP["size"])){
        build_progress(110,"Nothing to do...");
        $sock->SET_INFO("CREATE_NEW_SWAP","");
        exit;
    }

    if(intval($CREATE_NEW_SWAP["size"])<100){
        build_progress(110,"Size is less than 100MB!");
        $sock->SET_INFO("CREATE_NEW_SWAP","");
        exit;
    }

    $SIZE=intval($CREATE_NEW_SWAP["size"])*1024;
    echo "Create a swap in directory {$CREATE_NEW_SWAP["path"]} with $SIZE kb\n";
    @mkdir($CREATE_NEW_SWAP["path"],0600,true);

    build_progress(20,"{formatting}....");
    $time=time();
    $swapfile="{$CREATE_NEW_SWAP["path"]}/$time.swap";
    $dd=$unix->find_program("dd");
    $mkswap=$unix->find_program("mkswap");
    $swapon=$unix->find_program("swapon");
    $chmod=$unix->find_program("chmod");
    system("$dd if=/dev/zero of=$swapfile bs=1024 count=$SIZE");
    if(!is_file($swapfile)){
        build_progress(110,"{formatting} {failed}....");
        exit;
    }
    system("$chmod 0600 $swapfile");
    build_progress(30,"{formatting}....");
    system("$mkswap $swapfile");
    build_progress(50,"{activate} $swapfile....");
    system("$swapon $swapfile");
    if(!SwapExists($swapfile)){Swapfstab($swapfile);}
    build_progress(100,"{success}...");
    $sock->SET_INFO("CREATE_NEW_SWAP","");
}

function delete_swap(){

    $unix=new unix();
    $sock=new sockets();
    $swapfile=base64_decode($sock->GET_INFO("DELETE_SWAP"));

    if($swapfile==null){
        build_progress(110,"Nothing to do...");
        $sock->SET_INFO("DELETE_SWAP","");
        exit;
    }

    $swapoff=$unix->find_program("swapoff");

    echo "Deleting $swapfile\n";
    build_progress(50,"{removing} $swapfile....");
    if(SwapExists($swapfile)){SwapDeletefstab($swapfile);}
    build_progress(70,"{cleaning_memory} $swapfile....");
    $swapfileEnc=$unix->shellEscapeChars($swapfile);
    system("$swapoff -v $swapfileEnc");
    build_progress(90,"{removing} $swapfile....");
    if(is_file($swapfile)){@unlink($swapfile);}
    build_progress(100,"{removing} $swapfile {done}....");



}
function Swapfstab($filepath){
    @copy("/etc/fstab","/etc/fstab.".time());
    $f=explode("\n",@file_get_contents("/etc/fstab"));
    $f[]="$filepath\tswap\tswap\tdefaults\t0 0\n";
    @file_put_contents("/etc/fstab",@implode("\n",$f));

}

function SwapDeletefstab($filepath){
    $filepath=str_replace("/","\/",$filepath);
    $filepath=str_replace(".","\.",$filepath);
    $pattern="^$filepath\s+swap\s+swap";
    $NEWF=array();
    $f=explode("\n",@file_get_contents("/etc/fstab"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#$pattern#",$line)){continue;}
        $NEWF[]=$line;


    }
    @file_put_contents("/etc/fstab",@implode("\n",$NEWF)."\n");

}

function SwapExists($filepath){
    $filepath=str_replace("/","\/",$filepath);
    $filepath=str_replace(".","\.",$filepath);
    $pattern="^$filepath\s+swap\s+swap";

    $f=explode("\n",@file_get_contents("/etc/fstab"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#$pattern#",$line)){return true;}


    }
    return false;
}