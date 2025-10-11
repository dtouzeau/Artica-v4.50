<?php
$GLOBALS["FULL"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');

if($argv[1]=="--site"){GetCatzFromOpenDNS($argv[2]);exit();}


NotCategorized();


function NotCategorized(){
	$sock=new sockets();
	$GLOBALS["UUID"]=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$q=new mysql_squid_builder();
	$sql="SELECT sitename FROM visited_sites WHERE LENGTH(category)=0 ORDER BY HitsNumber DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	$Maxnum=mysqli_num_rows($results);
	if($Maxnum==0){if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":<\"$sql\"> return 0\n";}return;}
	$c=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $ligne["sitename"])){echo "Skipped {$ligne["sitename"]}\n";continue;}
		sleep(3);
		
		$categories=GetCatzFromOpenDNS($ligne["sitename"]);
		if(isset($categories["ARTICA"])){
			$c++;
			while (list ($category, $b) = each ($categories["ARTICA"]) ){			
				echo "Found: $c/$Maxnum Adding $category for {$ligne["sitename"]}\n";
				CategorizeAWebSite($ligne["sitename"],$category);
				
			}
		}
		
	//	if(isset())
		
		
		
	}
	
	echo "$c/$Maxnum websites Found...\n";
	
	
	
	
}

function CategorizeAWebSite($www,$category){
	$md5=md5($www.$category);
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$uuid=$GLOBALS["UUID"];
	$category_table=$q->category_transform_name($category);
	$sql_add="INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')";
	$sql_add2="INSERT IGNORE INTO category_$category_table (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')";
	$q->QUERY_SQL($sql_add);
	if(!$q->ok){echo $q->mysql_error."\n$sql_add\n";return false;}
	$q->QUERY_SQL($sql_add2);
	if(!$q->ok){echo $q->mysql_error."\n$sql_add2\n";return false;}
	$categories=$q->GET_CATEGORIES($www,true);
	if($categories<>null){
		$sql="UPDATE visited_sites SET category='$categories' WHERE sitename='$www'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n$sql\n";return false;}
	}
	
	
}


function GetCatzFromOpenDNS($sitename){
	echo "Testing $sitename\n";
	$uri="http://domain.opendns.com/$sitename";
	$curl=new ccurl($uri);
	if(!$curl->get()){echo "Unable to get $uri\n";return;}
	$categories=getcategory($curl->data);
	if(count($categories)==0){echo "Not found {$ligne["sitename"]} is not an array\n";return array();}	
	return $categories;
	
}






function getcategory($text){
	
	if(!preg_match("#Tagged:(.*?)</span>#is", $text,$re)){echo "Tagged:(.*?)</span> Not Found\n";return array();}
	$dirty=$re[1];
	$pos=strpos($dirty, ">");
	if($pos>0){$dirty=substr($dirty, $pos+1,strlen($dirty));
	$dirtyArray=explode(",", $dirty);
	while (list ($num, $ligne) = each ($dirtyArray) ){
		$ligne=str_replace("\n", "", $ligne);
		$ligne=str_replace("\r", "", $ligne);
		$ligne=trim($ligne);
		$ligne=str_replace(" ", "", $ligne);
		if($ligne==null){continue;}
		echo "getcategory:: Found Category : `$ligne`\n";
		$finalz[]=$ligne;
		
	}
		
	}
	
	
	$finalCategory_array=TransFormCategory($finalz);
	return $finalCategory_array;
	
}

function TransFormCategory($array){
	
	$arrayS["Pornography"]="porn";
	$arrayS["Automotive"]="automobile/cars";
	$arrayS["Adult Themes"]="mixed_adult";
	$arrayS["Advertising"]="publicite";
	$arrayS["Adware"]="spyware";
///$arrayS["Alcohol"]="xxxxx";
//$arrayS["Auctions"]="xxxxx";

$arrayS["Blogs"]="blog";
//$arrayS["Business Services"]="xxxxx";
$arrayS["Chat"]="chat";
//$arrayS["Classifieds"]="xxxxx";
$arrayS["Dating"]="dating";
$arrayS["Drugs"]="drugs";
$arrayS["Ecommerce/Shopping"]="shopping";
$arrayS["Educational Institutions"]="recreation/schools";
$arrayS["File storage"]="filehosting";
$arrayS["Financial institutions"]="financial";
$arrayS["Forums/Message boards"]="forums";
$arrayS["Gambling"]="gamble";
$arrayS["Games"]="games";
$arrayS["Government"]="governments";
$arrayS["Hate/Discrimination"]="agressive";
$arrayS["Health"]="health";
$arrayS["Humor"]="recreation/humor";
$arrayS["Instant messaging"]="chat";
$arrayS["Jobs/Employment"]="jobsearch";
$arrayS["Lingerie/Bikini"]="sex/lingerie";
$arrayS["Movies"]="movies";
$arrayS["Music"]="music";
$arrayS["News/Media"]="news";
//$arrayS["Non-profits"]="xxxxx";
$arrayS["Nudity"]="mixed_adult";
$arrayS["P2P/File sharing"]="downloads";
$arrayS["Parked Domains"]="reaffected";
$arrayS["Photo sharing"]="imagehosting";
//$arrayS["Podcasts"]="xxxxx";
$arrayS["Politics"]="politic";
$arrayS["Portals"]="isp";
//$arrayS["Proxy/Anonymizer"]="xxxxx";
$arrayS["Radio"]="webradio";
$arrayS["Religious"]="religion";
//$arrayS["Research/Reference"]="xxxxx";
$arrayS["Search engines"]="searchengines";
$arrayS["Sexuality"]="sexual_education";
$arrayS["Social networking"]="socialnet";
$arrayS["Software/Technology"]="science/computing";
$arrayS["Sports"]="recreation/sports";
$arrayS["Tasteless"]="violence";
$arrayS["Television"]="webtv";
//$arrayS["Tobacco"]="xxxxx";
$arrayS["Travel"]="recreation/travel";
$arrayS["Video sharing"]="audio-video";
$arrayS["Weapons"]="weapons";
$arrayS["Webmail"]="webmail";

$arrayS["EducationalInstitutions"]="recreation/schools";
$arrayS["Socialnetworking"]="socialnet";
$arrayS["Photosharing"]="imagehosting";	
$arrayS["Instantmessaging"]="chat";	
$arrayS["AdultThemes"]="mixed_adult";
$arrayS["Searchengines"]="searchengines";
$arrayS["Filestorage"]="filehosting";	
$arrayS["Videosharing"]="audio-video";
$arrayS["P2P/Filesharing"]="downloads";
$arrayS["Visualsearchengines"]="searchengines";


	foreach ($array as $num=>$ligne){
		if(!isset($arrayS[$ligne])){
			echo "\"$ligne\" is not a transformed category\n";
			$arrayF["OTHER"][]=$ligne;
			continue;}
		echo "\"$ligne\" transformed to '{$arrayS[$ligne]}'\n";
		$arrayF["ARTICA"][$arrayS[$ligne]]=true;
	}
	
	$count=count($arrayF["ARTICA"]);
	
	echo "\"$count\" Categories\n";
	return $arrayF;
	
}