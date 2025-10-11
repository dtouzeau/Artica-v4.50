<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian.compile.log";
if(posix_getuid()<>0){
	if(isset($_GET["SquidGuardWebAllowUnblockSinglePass"])){parseTemplate_SinglePassWord();exit();}
	
	if(isset($_POST["USERNAME"])){parseTemplate_LocalDB_receive();exit();}
	if(isset($_POST["password"])){parseTemplate_SinglePassWord_receive();exit();}
	if(isset($_GET["parseTemplate-SinglePassWord-popup"])){parseTemplate_SinglePassWord_popup();exit();}
	if(isset($_GET["SquidGuardWebUseLocalDatabase"])){parseTemplate_LocalDB();exit();}
	parseTemplate();exit();}

if(preg_match("#--schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["GETPARAMS"]=@implode(" Params:",$argv);


include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squidguard.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.ufdbguard.inc");

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');

if($argv[1]=="--crypted"){crypted();exit();}
if($argv[1]=="--import-translated"){import_translated();exit();}
if($argv[1]=="--export-dynamic"){export_dynamic();exit();}
if($argv[1]=="--parse"){parse_txt($argv[2]);exit();}



if($argv[1]=="download"){

// http://www.namejet.com/Download/1-01-2011.txt
$currentYear=date("Y");
$currentmonth=intval(date('m'));
$currentDay=intval(date('d'));

for ($i=1;$i<$currentmonth;$i++){
	$month=$i;
	for($y=1;$y<30;$y++){
		$day=$y;
		if(strlen($day)==1){$day="0{$day}";}
		echo "http://www.namejet.com/Download/$month-$day-$currentYear.txt\n";
		@mkdir("/home/namejet",0755,true);
		shell_exec("wget http://www.namejet.com/Download/$month-$day-$currentYear.txt -O /home/namejet/$month-$day-$currentYear.txt");
	}
	
}

exit();

}


$filename=$argv[1];



if(!is_file($filename)){echo "$filename No such file\n";exit();}


$handle = @fopen($filename, "r");
if (!$handle) {echo "Failed to open file\n";return;}
$q=new mysql_squid_builder();

$c=0;
$Catgorized=0;
while (!feof($handle)){
	$c++;
	$www =trim(fgets($handle, 4096));
	$www=str_replace('"', "", $www);
	$www=trim(strtolower($www));
	echo "$www ";
	$category=$q->GET_CATEGORIES($www,true,true,true);
	if($category<>null){
		$Catgorized++;
		echo " $Catgorized/$c Already categorized as $category\n";
		continue;
	}
	
	$category=$q->GET_CATEGORIES($www);
	if($category<>null){
		$Catgorized++;
		echo " $Catgorized/$c New categorized as $category {$GLOBALS["CATZWHY"]}\n";
		cloudlogs("$Catgorized/$c $www New categorized as $category {$GLOBALS["CATZWHY"]}");
		continue;
	}
	
	cloudlogs("$Catgorized/$c $www unknown");
	echo " $Catgorized/$c Unknown\n";
	
}

@fclose($handle);
@unlink($filename);


function crypted(){
	$ipClass=new IP();
	$array=unserialize(base64_decode(@file_get_contents("/root/notcategorized.db")));
    if(!is_array($array)){$array=array();}
	


$MAINZ=unserialize(@file_get_contents("/root/translated"));
$NODETECT=unserialize(@file_get_contents("/root/nodetect"));
	
	while (list ($uuid, $MAINARRAY) = each ($array)){
		$max=count($MAINARRAY);
		$i=1;
		while (list ($sitename, $ligne) = each ($MAINARRAY)){
			echo "$i/$max\n";
			$i++;
			if(isset($MAINZ[$sitename])){continue;}
			if(isset($NODETECT[$sitename])){continue;}
			$domain=$ligne["domain"];
			$country=$ligne["country"];
			$hits=$ligne["hits"];
			$size=$ligne["size"];
			$sitename=stripslashes($sitename);
			$sitename=str_replace("'", "", $sitename);
			if($ipClass->isIPAddress($sitename)){
				$ipaddr=$sitename;
				$sitename=gethostbyaddr($ipaddr);
			}else{
				$ipaddr=gethostbyname($sitename);
			}
			
			if(isset($MAINZ[$sitename])){continue;}
			if(isset($NODETECT[$sitename])){continue;}
			
			
			$GLOBALS["COUNTTR"]=0;
			$cat=GetCategory("http://$sitename");
			if($cat<>null){
				if(!isset($CATZ[$cat])){echo "$sitename [$ipaddr]  {".GetCategory("http://$sitename")."}\n";continue;}
				$translated=$CATZ[$cat];
				
				if($translated<>null){
					$MAINZ[$sitename]=$translated;
					@file_put_contents("/root/translated", serialize($MAINZ));
					continue;
				}
			}
			 
			
			
			$NODETECT[$sitename]=true;
			@file_put_contents("/root/nodetect", serialize($NODETECT));
			
		}
		
	}
	
	
}

function import_translated(){
	
	$q=new mysql_squid_builder();
	
	$MAINZ=unserialize(@file_get_contents("/root/translated"));
	$translated_done=unserialize(@file_get_contents("/root/translated_done"));
	$max=count($MAINZ);
	$gg=new generic_categorize();
	$i=1;
	while (list ($www, $category) = each ($MAINZ)){
		
		echo "$i/$max $www ";
		$i++;
		if(isset($translated_done[$www])){echo "\n";continue;}
		$category_artica=$gg->GetCategories($www);
		if($category_artica<>null){
			echo "-> ARTICA $category_artica\n";
			$q->categorize($www, $category_artica);
			$translated_done[$www]=true;
			@file_put_contents("/root/translated_done", serialize($translated_done));
			continue;
		}
		
		$category_artica=$q->GET_CATEGORIES($www,true,true,true,true);
		if($category_artica<>null){
			echo "-> ARTICA $category<>$category_artica\n";
			$translated_done[$www]=true;
			@file_put_contents("/root/translated_done", serialize($translated_done));
			continue;
		}
		
		echo "$category\n";
		$q->categorize($www, $category);
		$translated_done[$www]=true;
		@file_put_contents("/root/translated_done", serialize($translated_done));
		
	}
	
	
}

function parse_txt($filename){
	
	echo "Loading translated_parse\n";
	
	$MAINZ=unserialize(@file_get_contents("/root/translated_parse"));
	
	
	$NODETECT=unserialize(@file_get_contents("/root/nodetect_parse"));
	
	echo "Loading nodetect_parse\n";
	$gg=new generic_categorize();
	$ipClass=new IP();
	$handle = @fopen($filename, "r");
	if (!$handle) {echo "Failed to open file $filename\n";return;}
	while (!feof($handle)){
		$c++;

		
		
		$www =trim(fgets($handle, 4096));
		if($www==null){$CBADNULL++;continue;}
		$www=str_replace('"', "", $www);
		$www=stripslashes($www);
		$www=str_replace("'", "", $www);
		
		$date=date("Y-m-d H:i:s");
		$logprefix="[$date]: $c $www ";
		echo "$logprefix ";
		if(isset($MAINZ[$www])){
		echo "already done\n";
			continue;}
					if(isset($NODETECT[$www])){
					echo "already done\n";
			continue;}		
		
		
		if($ipClass->isIPAddress($www)){
			$ipaddr=$www;
			$www=gethostbyaddr($ipaddr);
		}else{
			$ipaddr=gethostbyname($www);
		}
			
		echo " -$ipaddr- ";
		
		if(isset($MAINZ[$www])){
			echo "already done\n";
			continue;}
		if(isset($NODETECT[$www])){
			echo "already done\n";
			continue;}
		
		$category_artica=$gg->GetCategories($www);
		if($category_artica<>null){
			echo "$logprefix $www -> ARTICA $category_artica\n";
			$MAINZ[$www]=$category_artica;
			@file_put_contents("/root/translated_parse", serialize($MAINZ));
			continue;
		}
		
		$cat=GetCategory("http://$www");
		
		if(is_numeric($cat)){
			echo "$www -> continue;\n";
			continue;
		}
		
		if($cat<>null){
			echo "$www -> $cat;\n";
			$MAINZ[$www]=$cat;
			@file_put_contents("/root/translated_parse", serialize($MAINZ));
			continue;
		}
		
		
			
		echo "$www -> NOPE;\n";
		$NODETECT[$www]=true;
		@file_put_contents("/root/nodetect_parse", serialize($NODETECT));

	}
			
		
	
}


function GetCategory($uri){
	
	$CATZ["business"]="industry";
	$CATZ["advertising"]="publicite";
	$CATZ["search engines and portals"]="searchengines";
	$CATZ["unrated"]="";
	$CATZ["global"]="";
	$CATZ["finance"]="financial";
	$CATZ["news and media"]="news";
	$CATZ["pornography"]="porn";
	$CATZ["education"]="recreation/schools";
	$CATZ["personal websites and blogs"]="blog";
	$CATZ["shopping and auction"]="shopping";
	$CATZ["sports"]="recreation/sports";
	$CATZ["real estate"]="finance/realestate";
	$CATZ["reference"]="dictionaries";
	$CATZ["information technology"]="science/computing";
	$CATZ["restaurant and dining"]="recreation/nightout";
	$CATZ["weapons"]="weapons";
	$CATZ["personal vehicles"]="automobile/cars";
	$CATZ["health and wellness"]="health";
	$CATZ["travel"]="recreation/travel";
	$CATZ["lingerie and swimsuit"]="sex/lingerie";
	$CATZ["freeware and software downloads"]="downloads";
	$CATZ["advocacy organizations"]="justice";
	$CATZ["entertainment"]="hobby/other";
	$CATZ["finance and banking"]="finance/banking";
	$CATZ["content servers"]="science/computing";
	$CATZ["web hosting"]="isp";
	$CATZ["arts and culture"]="hobby/arts";
	$CATZ["newsgroups and message boards"]="forums";
	$CATZ["society and lifestyles"]="society";
	$CATZ["malicious websites"]="malware";
	$CATZ["meaningless content"]="society";
	$CATZ["government and legal organizations"]="governments";
	$CATZ["general organizations"]="associations";
	$CATZ["social networking"]="socialnet";
	$CATZ["alcohol"]="alcohol";
	$CATZ["job search"]="jobsearch";
	$CATZ["games"]="games";
	$CATZ["illegal or unethical"]="suspicious";
	$CATZ["web-based applications"]="webapps";
	$CATZ["web-based email"]="webmail";
	$CATZ["domain parking"]="reaffected";
	$CATZ["gambling"]="gamble";
	$CATZ["dynamic content"]="redirector";
	$CATZ["other adult materials"]="mixed/adult";
	$CATZ["proxy avoidance"]="proxy";
	$CATZ["armed forces"]="weapons";
	$CATZ["secure websites"]="sslsites";
	$CATZ["file sharing and storage"]="filehosting";
	$CATZ["streaming media and download"]="audio-video";
	$CATZ["sex education"]="sexual_education";
	$CATZ["abortion"]="abortion";
	$CATZ["nudity and risque"]="mixed/adult";
	$CATZ["dating"]="dating";
	$CATZ["marijuana"]="drugs";
	$CATZ["tobacco"]="tobacco";
	$CATZ["explicit violence"]="violence";
	$CATZ["internet radio and tv"]="webradio";
	$CATZ["folklore"]="";
	$CATZ["global religion"]="religion";
	$CATZ["political organizations"]="politic";
	$CATZ["child education"]="children";
	$CATZ["brokerage and trading"]="stockexchange";
	$CATZ["digital postcards"]="gifts";
	$CATZ["instant messaging"]="chat";
	$CATZ["hacking"]="hacking";
	$CATZ["internet telephony"]="webphone";
	$CATZ["phishing"]="phishing";
	$CATZ["spam urls"]="mailing";
	$CATZ["medicine"]="medical";
	$CATZ["sports hunting and war games"]="violence";
	
	
	$curl=new ccurl($uri);
	$curl->noproxyload=true;
	$curl->NoHTTP_POST=true;
	$curl->ArticaProxyServerEnabled="no";
	$curl->interface="10.32.0.36";
	$curl->Timeout=5;
	if(!$curl->get()){
	
		if(preg_match("#Category:\s+([A-Za-z\_\-0-9\s]+)#is", $curl->orginal_data,$re)){
			$category=trim(strtolower($re[1]));
			if($category=="unrated"){return null;}
			$newcategory=$CATZ[$category];
			if($newcategory==null){echo "$uri \"$category\" not translated\n";return 1;}
			return $newcategory;
		}
		echo $curl->orginal_data;
	}
	
	if(isset($curl->CURL_ALL_INFOS["redirect_url"])){
		$curl->CURL_ALL_INFOS["redirect_url"]=trim($curl->CURL_ALL_INFOS["redirect_url"]);
		if($curl->CURL_ALL_INFOS["redirect_url"]<>null){
			$GLOBALS["COUNTTR"]++;
			if($GLOBALS["COUNTTR"]<4){
				return GetCategory($curl->CURL_ALL_INFOS["redirect_url"]);
			}
		}
	}
	
	
	return null;
	
	
}


function cloudlogs($text=null){
	$logFile="/var/log/cleancloud.log";
	$time=date("Y-m-d H:i:s");
	$PID=getmypid();
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$time [$PID]: $text\n");
	@fclose($f);
}
