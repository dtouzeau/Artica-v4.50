<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.artica.graphs.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');


$GLOBALS["Q"]=new mysql_squid_builder();
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	
	
}


if($argv[1]=="--explode"){explodeFile($argv[2]);die("DIE " .__FILE__." Line: ".__LINE__);}



function explodeFile($strFileName){
	$q=new mysql_squid_builder();
	$ayK9Cats = array('1' => 'porn','3' => 'porn','4' => 'sexual_education','5' => 'sex/lingerie','6' => 'sexual_education','7' => 'agressive','9' => 'malware',
	'11' => 'gamble','14' => 'violence','15' => 'weapons','16' => 'abortion','17' => 'hacking','18' => 'phishing',
	'20' => 'hobby/arts',
	'21' => 'industry',
	'22' => 'sect',
	
	'23' => 'alcohol','24' => 'tobacco','25' => 'drugs','27' => 'recreation/schools','29' => 'associations','31' => 'financial',
	'32' => 'stockexchange','33' => 'games',	'34' => 'governments','35' => 'violence','36' => 'politic','37' => 'health','38' => 'science/computing','40' => 'searchengines','43' => 'spyware','44' => 'spyware','45' => 'jobsearch','46' => 'news','47' => 'dating',
	'50' => 'pictureslib','51' => 'chat','52' => 'webmail','53' => 'forums','54' => 'religion',	
	'55' => 'socialnet','56' => 'filehosting','57' => 'remote-control',	'58' => 'shopping',	'59' => 'shopping',
	'60' => 'finance/realestate',
	'61' => 'hobby/other',
	'63' => 'blog',
	'64' => 'recreation/nightout','65' => 'recreation/sports','66' => 'recreation/travel','67' => 'automobile/cars',
	'68' => 'recreation/humor',	'71' => 'downloads','83' => 'downloads','84' => 'webradio',	
	'85' => 'webapps',
	'86' => 'proxy',
	'87' => 'children','88' => 'publicite','89' => 'isp',
	'92' => 'suspicious','93' => 'sexual_education','94' => 'sexual_education','96' => 'tracker','97' => 'imagehosting','98' => 'reaffected',"999"=>null,"504"=>"tracker","503"=>"warez","901"=>"porn");
	$WorkingDir=dirname($strFileName);
$c=0;
  if (!file_exists($strFileName)) {echo "$strFileName no such file\n";}
	if ($fd = fopen($strFileName, 'r')) {
     	 while (!feof($fd)) {
       	 	$strline= fgets($fd, 4096);
       	 	$SS=explode(",",$strline);
       	 	$SS[0]=trim($SS[0]);
       	 	$SS[1]=trim($SS[1]);
       	 	$Category=$ayK9Cats[$SS[1]];
       	 	if($Category<>null){
       	 		$table_name="category_".$q->category_transform_name($Category);
       	 		if(!isset($FOPENARRAY[$table_name])){$FOPENARRAY[$table_name]=@fopen("$WorkingDir/$table_name", 'a');}
       	 		fwrite($FOPENARRAY[$table_name], "{$SS[0]}\n");
       	 	}else{
       	 		if(!isset($FOPENARRAY[$SS[1]])){$FOPENARRAY[$SS[1]]=@fopen("$WorkingDir/unknown_num_{$SS[1]}", 'a');}
       	 		fwrite($FOPENARRAY[$SS[1]], "{$SS[0]}\n");
       	 	}
 			$c++;
       	 	
     	 }
     	 
	}
	
	fclose($fd);
	echo "Success $c Lines\n";
	while (list ($filename, $handle) = each ($FOPENARRAY) ){
		echo "Close $filename\n";
		@fclose($handle);
	}
	
	
	
}

