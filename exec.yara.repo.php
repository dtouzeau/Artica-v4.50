<?php
if(!is_dir("/home/www.artica.fr/webfilters-databases")){exit();}
$destfile="/home/www.artica.fr/webfilters-databases/yara-rules.tar.gz";
$destIndex="/home/www.artica.fr/webfilters-databases/yara-rules.txt";
$tgzfile="/root/yara-rules.tar.gz";

if(is_dir("/home/yara")){
	echo "Remove /home/yara\n";
	system("/bin/rm -rf /home/yara");
}

@mkdir("/home/yara",0755,true);
@mkdir("/home/yara-temp",0755,true);
shell_exec("/usr/bin/git clone https://github.com/Yara-Rules/rules.git /home/yara/");
shell_exec("/usr/bin/find /home/yara -type f -name \"*.yar\" >/home/yara/find.txt");


$f=explode("\n",@file_get_contents("/home/yara/find.txt"));
@mkdir("/home/yara/export",0755,true);

$c=0;
foreach ($f as $index=>$filename){
	$filename=trim($filename);
	if($filename==null){continue;}
	$basename=basename($filename);
	$CurMD5=md5_file($filename);
	$YaraRuleDest="/home/yara-temp/$basename";
	if(is_file($YaraRuleDest)){
		$oldmd5=md5_file($YaraRuleDest);
		if($oldmd5==$CurMD5){echo "$basename [up-to-date]\n";continue;}
	}
	$c++;
	echo "$basename [update]\n";
	@unlink("/home/yara-temp/$basename");
	@copy($filename, "/home/yara-temp/$basename");
	
}
if(is_file($destfile)){
	if($c==0){
		echo "$destfile already exists\n";
		@chmod($destfile,0755);
		@chmod($destIndex,0755);
		@chown($destfile, "www-data");
		@chown($destIndex, "www-data");
		echo "Nothing to do ....\n";
		exit();
	}
}

chdir("/home/yara-temp");
system("cd /home/yara-temp");
echo "Compressing /home/yara-temp -> $tgzfile\n";
system("/bin/tar -czf $tgzfile *");

$NewMD5=md5_file($tgzfile);
echo "$tgzfile = $NewMD5\n";
@unlink($destfile);
@copy($tgzfile, $destfile);

$ARRAY["TIME"]=time();
$ARRAY["MD5"]=$NewMD5;

@file_put_contents($destIndex, base64_encode(serialize($ARRAY)));
@chmod($destfile,0755);
@chmod($destIndex,0755);
@chown($destfile, "www-data");
@chown($destIndex, "www-data");
echo "$tgzfile = > $destfile [updated]\n";
?>