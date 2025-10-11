<?php

include_once('ressources/class.sockets.inc');
include_once('ressources/logs.inc');
include_once('ressources/class.crypt.php');
include_once('ressources/class.user.inc');
if(isset($_GET["path"])){
	$sock=new sockets();	
	if(strpos($_GET["path"],'..')>0){die('HACK: ..');}
	$path="{$_GET["org"]}/{$_GET["path"]}";
	$file=basename($path);
	$sock=new sockets();
	
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode($path)));
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");	
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	$fsize = filesize($path); 
	header("Content-Length: ".$fsize); 
	ob_clean();
	flush();
	readfile($path);	
	
	//$sock->download_attach($path,$_GET["file"]);
}

if(isset($_GET["xapian-file"])){
	if(($_SESSION["uid"]==null) OR ($_SESSION["uid"]==-100)){
		$ldap=new clladp();
		$pass=$ldap->ldap_password;
	}else{	
		$ct=new user($_SESSION["uid"]);
		$pass=$ct->password;
	}
	
	
	
	$cr=new SimpleCrypt($pass);
	$crypted=base64_decode($_GET["xapian-file"]);
	
	$path=$cr->decrypt(base64_decode($_GET["xapian-file"]));
	writelogs("Receive crypted file: $path ",__FUNCTION__,__FILE__,__LINE__);
	if(!is_file($path)){die("DIE " .__FILE__." Line: ".__LINE__);}
	if(strpos($path,'..')>0){die('HACK: ..');}
	$file=basename($path);
	$sock=new sockets();
	
	$content_type=base64_decode($sock->getFrameWork("cmd.php?mime-type=".base64_encode($path)));
	header('Content-type: '.$content_type);
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$file\"");	
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé	
	$fsize = filesize($path); 
	header("Content-Length: ".$fsize); 
	ob_clean();
	flush();
	readfile($path);	
}


?>