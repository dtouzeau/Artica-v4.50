<?php
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if($argv[1]=="--scan"){scan();exit;}

xrun($argv[1]);


function scan(){
	$data=@file_get_contents("/home/dtouzeau/ca-bundle.crt");
	preg_match_all("#-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----#is", $data, $re);
	
	foreach ($re[1] as $cert){
		
		
		
		
		$cert1="-----BEGIN CERTIFICATE-----\n$cert\n-----END CERTIFICATE-----\n";
		$md5=md5($cert1);
		
		$cert=str_replace("\r", "", $cert);
		$cert=trim($cert);
		if($cert==null){
			@unlink("/home/dtouzeau/Téléchargements/ca-certificates_20161130_all/data/usr/share/ca-certificates/mozilla/$md5.crt");
			continue;
		}
		$cert="-----BEGIN CERTIFICATE-----\n$cert\n-----END CERTIFICATE-----\n";
		@file_put_contents("/home/dtouzeau/Téléchargements/ca-certificates_20161130_all/data/usr/share/ca-certificates/mozilla/$md5.crt", $cert);
		echo "$md5.crt\n";
		
		
	}
	
	
	
}


function xrun($path){
	$finalp="/usr/share/artica-postfix/bin/install/public-certificates.db";
	if(is_file($path)){
		$MAIN=unserialize(@file_get_contents($finalp));
		$data=trim(@file_get_contents("$path"));
		$md5=md5_file("$path");
		$MAIN[$md5]["CONTENT"]=@file_get_contents("$path");
		$MAIN=openssl_extract_info("$path",$MAIN,$md5);
		@file_put_contents("{$path}cabundle.pem", serialize($MAIN));
		echo "{$MAIN[$md5]["Issuer"]} Expire:{$MAIN[$md5]["NotAfter"]} Subject: {$MAIN[$md5]["Subject"]}\n";
		echo "Saved in $finalp\n";
		return;
	}
	
	if(!is_dir($path)){
		echo "$path no such file or directory\n";
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	$MAIN=unserialize(@file_get_contents($finalp));
	
	$unix=new unix();
	$fZ=$unix->DirFiles($path,"\.crt$");
	if(!preg_match("#\/$#", $path)){$path="$path/";}
    foreach ($fZ as $none=>$filename){
		echo "Get content from $path$filename\n";
		$data=trim(@file_get_contents("$path$filename"));
		if($data==null){continue;}
		$md5=md5_file("$path$filename");

		$MAIN[$md5]["CONTENT"]=@file_get_contents("$path$filename");
		$MAIN=openssl_extract_info("$path$filename",$MAIN,$md5);
		echo "**********************\nIssuer:[{$MAIN[$md5]["Issuer"]}] Expire:{$MAIN[$md5]["NotAfter"]}\n";
	}
	
	@file_put_contents($finalp, serialize($MAIN));
	echo "Saved in $finalp\n";
	
}


function openssl_extract_info($path,$MAIN,$md5){
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	exec("$openssl x509 -in $path -text -noout 2>&1",$results);
	$email=null;
	foreach ($results as $line){
		if(preg_match("#Issuer:\s+(.+)#i", $line,$re)){
			$Issuer=$re[1];
			continue;
		}
		
		if(preg_match("#Not After :\s+(.+)#",  $line,$re)){
			$NotAfter=$re[1];
			$xtime=strtotime($NotAfter);
			$NotAfter=date("Y-m-d H:i:s",$xtime);
			continue;
		}
		
		if(preg_match("#Subject:\s+(.+)#",  $line,$re)){
			$Subject=$re[1];
			continue;
		}
		
		if(preg_match("#email:(.+?)@(.+)#",$line,$re)){
			$email=$re[1];
			continue;
		}
		
	}
	
	
	
	$MAIN[$md5]["Issuer"]=$Issuer;
	$MAIN[$md5]["NotAfter"]=$NotAfter;
	$MAIN[$md5]["Subject"]=$Subject;
	if($email<>null){$MAIN[$md5]["Issuer"]=$email;}
	
	if($MAIN[$md5]["Issuer"]==$MAIN[$md5]["Subject"]){
		$tt=explode(",",$MAIN[$md5]["Subject"]);
		foreach ($tt as $line){
			$line=trim($line);
			if(preg_match("#CN=(.+)#", $line,$re)){
				$MAIN[$md5]["Issuer"]=trim($re[1]);
				continue;
			}
			if($MAIN[$md5]["Issuer"]==$MAIN[$md5]["Subject"]){
				if(preg_match("#O=(.+)#", $line,$re)){
					$MAIN[$md5]["Issuer"]=trim($re[1]);
					continue;
				}
			}
			
			
		}
		
	}
	
	
	return $MAIN;
}