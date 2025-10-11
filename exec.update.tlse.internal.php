<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if($argv[1]=="--remove-all"){remove_all_categories();exit;}
if($argv[1]=="--repair"){repair();exit;}
if($argv[1]=="--recover"){recover();exit;}
if($argv[1]=="--count"){CountOfItems();exit;}

xstart();

function xstart(){
	
	$UTSCacheFile=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UTSCacheFile"));
	
	$f["publicite"]=166;
	$f["adult"]=167;
	$f["agressif"]=168;
	$f["arjel"]=169;
	$f["associations_religieuses"]=170;
	$f["astrology"]=171;
	$f["audio-video"]=172;
	$f["bank"]=173;
	$f["bitcoin"]=174;
	$f["blog"]=175;
	$f["celebrity"]=176;
	$f["chat"]=177;
	$f["child"]=178;
	$f["cleaning"]=179;
	$f["cooking"]=180;
	$f["cryptojacking"]=181;
	$f["dangerous_material"]=182;
	$f["dating"]=183;
	$f["ddos"]=184;
	$f["dialer"]=185;
	$f["download"]=186;
	$f["drogue"]=187;
	$f["educational_games"]=188;
	$f["filehosting"]=189;
	$f["financial"]=190;
	$f["forums"]=191;
	$f["gambling"]=192;
	$f["games"]=193;
	$f["hacking"]=195;
	$f["jobsearch"]=196;
	$f["lingerie"]=197;
	$f["liste_blanche"]=198;
	$f["liste_bu"]=199;
	$f["malware"]=200;
	$f["manga"]=201;
	$f["marketingware"]=202;
	$f["mixed_adult"]=203;
	$f["mobile-phone"]=204;
	$f["phishing"]=205;
	$f["press"]=207;
	$f["redirector"]=208;
	$f["radio"]=210;
	$f["reaffected"]=211;
	$f["remote-control"]=213;
	$f["sect"]=214;
	$f["sexual_education"]=215;
	$f["shopping"]=216;
	$f["shortener"]=217;
	$f["social_networks"]=218;
	$f["special"]=219;
	$f["sports"]=220;
	$f["strict_redirector"]=221;
	$f["strong_redirector"]=222;
	$f["translation"]=223;
	$f["tricheur"]=224;
	$f["update"]=225;
	$f["warez"]=226;
	$f["webmail"]=227;
	
	$q=new postgres_sql();
	$q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_utsremotecontrol' WHERE category_id='213'");
	$q->QUERY_SQL("UPDATE personal_categories SET categorytable='utsaudiovideo' WHERE category_id='172'");
	
	
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$tempfile=$unix->FILE_TEMP();
	$tempDir=$unix->TEMP_DIR();
	$curl=new ccurl("http://dsi.ut-capitole.fr/blacklists/download/MD5SUM.LST");
	if(!$curl->GetFile($tempfile)){
		echo "failed to download http://dsi.ut-capitole.fr/blacklists/download/MD5SUM.LST\n";
		@unlink($tempfile);
		return;
	}
	
	$tt=explode("\n",@file_get_contents($tempfile));
	@unlink($tempfile);
	foreach ($tt as $line){
		$line=trim($line);if($line==null){continue;}
		if(preg_match("#(LICENSE|README|blacklists|verisign|global_usage)#", $line)){continue;}
		
		
		if(!preg_match("#^(.+?)\s+(.+?)\.tar\.gz$#", $line,$re)){
			echo "$line no match....\n";
			continue;
		}
		$md5=$re[1];
		$dbname=$re[2];
		$dbfile="$dbname.tar.gz";
		
		if(!isset($f[$dbname])){echo "$dbname not in table....\n";continue;}
		
		$old_md5=$UTSCacheFile[$dbname];
		$category_id=$f[$dbname];
		if($old_md5==$md5){continue;}
		
		echo "Downloading $dbfile\n";
		$curl=new ccurl("http://dsi.ut-capitole.fr/blacklists/download/$dbfile");
		if(!$curl->GetFile("$tempDir/$dbfile")){
			echo "failed to download $dbfile\n";
			@unlink("$tempDir/$dbfile");
			continue;
		}
		
		@mkdir("$tempDir/$dbname",0755,true);
		$SourceDomainsPath="$tempDir/$dbname/$dbname/domains";
		echo "Extracting $tempDir/$dbfile -> $tempDir/$dbname\n";
		system("$tar xf $tempDir/$dbfile -C $tempDir/$dbname");
		@unlink("$tempDir/$dbfile");
		if(!is_file($SourceDomainsPath)){
			echo "$SourceDomainsPath, no such file\n";
			shell_exec("$rm -rf $tempDir/$dbname");
			continue;
		}
		
		echo "Injecting $SourceDomainsPath in $category_id\n";
		if(inject($SourceDomainsPath,$category_id)){
			$UTSCacheFile[$dbname]=$md5;
		}
		shell_exec("$rm -rf $tempDir/$dbname");
		
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UTSCacheFile",serialize($UTSCacheFile));
	CountOfItems();
}

function inject($SourceDomainsPath,$category_id){
	$q=new postgres_sql();
    if(!$q->TABLE_EXISTS("personal_categories")){return false;}
	$unix=new unix();
	$sql="SELECT * FROM personal_categories WHERE category_id='$category_id'";
	$ligne=$q->mysqli_fetch_array($sql);
	$tablename=$ligne["categorytable"];
	
	$handle = @fopen($SourceDomainsPath, "r");
	if (!$handle) {echo "Failed to open file\n";return;}
	$c=0;
	$n=array();
	$CountLines=$unix->COUNT_LINES_OF_FILE($SourceDomainsPath);
	echo "$SourceDomainsPath ---> $CountLines rows\n";
	$n=array();
	
	if($q->TABLE_EXISTS($tablename)){
		echo "$SourceDomainsPath ---> Droping table\n";
		$q->QUERY_SQL("DROP TABLE $tablename");
	}
	
	if(!$q->TABLE_EXISTS($tablename)){
		$catz=new categories();
		echo "$SourceDomainsPath ---> Creating table\n";
		$catz->CreateCategoryTable(null,$tablename);
	}
	
	$prefix="INSERT INTO $tablename (sitename) VALUES ";
	
	$ipclass=new IP();
	$n=array();
	while (!feof($handle)){
		$c++;
		$prcF=$c/$CountLines;
		$prcF=round($prcF*100,2);
		$www =trim(fgets($handle, 4096));
		if($ipclass->isIPv4($www)){$www=ip2long($www).".addr";}
		
		$n[]=utf8_encode("('$www')");
		
		
		if(count($n)>150000){
			$mem=round(((memory_get_usage()/1024)/1000),2);
			$sql=$prefix.@implode(",",$n). " ON CONFLICT DO NOTHING";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error." ---> \"$www\"\n";$n=array();continue;}
			$text= numberFormat($c,0,""," ")." items";
			echo "$prcF: $text - $CountLines\n";
			$n=array();
		
		}

	
	}
	

	if(count($n)>0){
		$mem=round(((memory_get_usage()/1024)/1000),2);
		$sql=$prefix.@implode(",",$n). " ON CONFLICT DO NOTHING";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";$n=array();return false;}
		$text= numberFormat($c,0,""," ")." items";
		echo "$prcF: $text - $CountLines\n";
		$n=array();
	
	}
	
	$q->QUERY_SQL("ANALYZE $tablename");
	$COUNT=$q->COUNT_ROWS_LOW($tablename);
	$q->QUERY_SQL("UPDATE personal_categories SET items='$COUNT' WHERE category_id=$category_id");
	@unlink($SourceDomainsPath);
	return true;
	
}

function CountOfItems(){
	$q=new postgres_sql();
	$results=$q->QUERY_SQL("SELECT categorytable,category_id FROM personal_categories");
	
	while ($ligne = pg_fetch_assoc($results)) {
		$categorytable=$ligne["categorytable"];
		$category_id=$ligne["category_id"];
		if(!$q->TABLE_EXISTS($categorytable)){continue;}
		
		
		$q->QUERY_SQL("VACUUM (ANALYZE) $categorytable;");
		$q->QUERY_SQL("ANALYZE $categorytable");
		if(!$q->ok){echo $q->mysql_error."\n";}
		$COUNT=$q->COUNT_ROWS_LOW($categorytable);
		echo "$categorytable $COUNT elements\n";
		$q->QUERY_SQL("UPDATE personal_categories SET items='$COUNT' WHERE category_id=$category_id");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
}

function recover(){
	
	$unix=new unix();
	$q=new postgres_sql();
	
	$q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_chat' WHERE category_id='34'");
	$q->QUERY_SQL("UPDATE personal_categories SET categoryname='Internet Providers',categorytable='category_isp' WHERE category_id='83'");
	
	
	$f=$unix->DirFiles("/root/mycategories");
	foreach ($f as $filename=>$true){
		$database_name=str_replace(".gz", "", $filename);
		$sql="SELECT category_id FROM personal_categories WHERE categorytable='$database_name'";
		$ligne=$q->mysqli_fetch_array($sql);
		if(intval($ligne["category_id"])==0){echo "$filename,$database_name no such id\n";continue;}
		$category_id=$ligne["category_id"];
		$extractedpath="/root/mycategories/$database_name.txt";
		echo "Extracting /root/mycategories/$filename";
		if(!$unix->uncompress("/root/mycategories/$filename", $extractedpath)){
			echo "Unable to extract...\n";continue;
		}
		
		if($q->TABLE_EXISTS($database_name)){$q->QUERY_SQL("DROP TABLE $database_name");}
		inject($extractedpath,$category_id);
	}
	
}


