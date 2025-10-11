#!/usr/bin/php
<?php
$GLOBALS["VERSION"]=@file_get_contents("/usr/share/artica-postfix/VERSION");
$GLOBALS["useraccount"]="dtouzeau";
$GLOBALS["FORCE_VERSION"]=0;
$GLOBALS["FORCE_COMMIT"]=null;
$GLOBALS["NO_UPLOAD"] = false;

include_once(dirname(__FILE__)."/ressources/class.ssh.client.inc");

build_patch();

function Get_version():string{
    $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/active-directory-rest.py"));
    foreach ($f as $line){
        if(!preg_match("#self\.version.*?=.*?\"([0-9\.]+)\"#",$line,$re)){
           continue;
        }
        return $re[1];
    }
    return "";
}

function build_patch(){

    $GLOBALS["VERSION"]=Get_version();

    if($GLOBALS["VERSION"]==null){
        echo "Unable to Get Version...\n";
        die();
    }
    echo "Compiling v{$GLOBALS["VERSION"]}\n";
    $MORKDIR="/home/{$GLOBALS["useraccount"]}/Bureau/api-rest";
    $PATCHDIR="$MORKDIR/{$GLOBALS["VERSION"]}";
    $useraccount=$GLOBALS["useraccount"];
    $VERSION=$GLOBALS["VERSION"];
    $TARGET_DIR="$PATCHDIR/artica-postfix";
    if(!is_dir($TARGET_DIR)){@mkdir($TARGET_DIR,0755,true);}

    $MAIN_PATH="/usr/share/artica-postfix";


    $Files[]="ressources/class.cherrypy.certificate.inc";
    $Files[]="ressources/postgressql.py";
    $Files[]="active-directory-rest.py";
    $Files[]="exec.active-directory-rest.php";


    foreach ($Files as $srcfile){
        $src_path="$MAIN_PATH/$srcfile";
        $dst_path="$TARGET_DIR/$srcfile";
        $dst_dir=dirname($dst_path);
        if(!is_dir($dst_dir)){@mkdir($dst_dir,0755,true);}
        if(is_file($dst_path)){@unlink($dst_path);}
        @copy($src_path,$dst_path);

    }
    $tbal="artica-monit-active-directory-$VERSION.tar.gz";
    $package_path="$MORKDIR/$tbal";
    echo "Building $package_path\n";
    if(is_file($package_path)){@unlink($package_path);}
    chdir($PATCHDIR);
    system("cd $PATCHDIR");
    shell_exec("tar -czvf $package_path artica-postfix");
    echo "$package_path done\n";
    shell_exec("chown -R $useraccount:$useraccount $MORKDIR");

    $params=ssh_parse_config();
    $remotebase=$params["basename"];
    $remotebase=str_replace("/UPatchs","",$remotebase);
    echo "Remote base [$remotebase]\n";
    $ssh_client=new ssh_client($params["hostname"],$params["port"],$params["user"],$params["password"]);

    if(!$ssh_client->connect()){
        echo "SSH Connection failed\n";
        die();
    }

    $remotepatch=$remotebase."/$tbal";

    echo "Copy patch to {$params["hostname"]}:$remotepatch\n";
    if(!$ssh_client->copyfile($package_path,$remotepatch,0755)){
        echo "Copy patch to {$params["hostname"]}:$remotepatch Failed\n";
        $ssh_client->disconnect();
        return false;
    }
    echo "Copy patch to {$params["hostname"]}:$remotepatch Success\n";
    return true;
}

function ssh_parse_config(){

    $user=$GLOBALS["useraccount"];
    $filename="/home/$user/.articassh.conf";
    $f=explode("\n",@file_get_contents($filename));

    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^username=(.+)#",$line,$re)){$params["user"]=$re[1];}
        if(preg_match("#^password=(.+)#",$line,$re)){$params["password"]=$re[1];}
        if(preg_match("#^hostname=(.+)#",$line,$re)){$params["hostname"]=$re[1];}
        if(preg_match("#^port=([0-9]+)#",$line,$re)){$params["port"]=$re[1];}
        if(preg_match("#^basename=(.+)#",$line,$re)){$params["basename"]=$re[1];}


    }
    return $params;

}

function SoftIndex(){
    $PATCHDIR=$GLOBALS["PATCHDIR"];
    $TARGET_DIR="$PATCHDIR/artica-postfix";
    $SOFTPATH="/usr/share/artica-postfix";

    exec("find $TARGET_DIR 2>&1",$results);
    foreach ($results as $line){
        if(is_dir($line)){continue;}
        $md5=md5_file($line);

        $line=str_replace($TARGET_DIR,$SOFTPATH,$line);
        $MAIN[$line]=$md5;
    }

    return serialize($MAIN);

}

