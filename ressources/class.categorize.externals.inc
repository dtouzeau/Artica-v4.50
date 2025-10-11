<?php
define('HDOM_TYPE_ELEMENT', 1);
define('HDOM_TYPE_COMMENT', 2);
define('HDOM_TYPE_TEXT',    3);
define('HDOM_TYPE_ENDTAG',  4);
define('HDOM_TYPE_ROOT',    5);
define('HDOM_TYPE_UNKNOWN', 6);
define('HDOM_QUOTE_DOUBLE', 0);
define('HDOM_QUOTE_SINGLE', 1);
define('HDOM_QUOTE_NO',     3);
define('HDOM_INFO_BEGIN',   0);
define('HDOM_INFO_END',     1);
define('HDOM_INFO_QUOTE',   2);
define('HDOM_INFO_SPACE',   3);
define('HDOM_INFO_TEXT',    4);
define('HDOM_INFO_INNER',   5);
define('HDOM_INFO_OUTER',   6);
define('HDOM_INFO_ENDSPACE',7);
define('DEFAULT_TARGET_CHARSET', 'UTF-8');
define('DEFAULT_BR_TEXT', "\r\n");
include_once(dirname(__FILE__)."/class.sockets.inc");
include_once(dirname(__FILE__)."/externals/Net/DNS2.inc");


class external_categorize{
	
	var $sitename;
	var $ArticaProxyServerEnabled="no";
	var $ArticaProxyServerName='';
	var $ArticaProxyServerPort=3128;
	var $ArticaProxyServerUsername=null;
	var $ArticaProxyServerUserPassword=null;
	var $hostname=null;
	var $NOK9=false;	
	
	public function __construct($sitname=null){
		if(is_file("/usr/share/phpGSB-master/phpgsb.class.php")){
			include_once("/usr/share/phpGSB-master/phpgsb.class.php");
			if(!isset($GLOBALS["OVHMySQLPass"])){if(is_file("/etc/artica-postfix/settings/Daemons/OVHMySQLPass")){$GLOBALS["OVHMySQLPass"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OVHMySQLPass");}}
			if(!isset($GLOBALS["GoogleApiKey"])){if(is_file("/etc/artica-postfix/settings/Daemons/GoogleApiKey")){$GLOBALS["GoogleApiKey"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoogleApiKey");}}
		}
		
		if(!isset($GLOBALS["DEBUG_BRIGHT"])){$GLOBALS["DEBUG_BRIGHT"]=false;}
		if(!isset($GLOBALS["DEBUG_EXTERN"])){$GLOBALS["DEBUG_EXTERN"]=false;}
		$users=new usersMenus();
		$this->hostname=$users->hostname;
		$BANNED_BRIGHT["ks220503.kimsufi.com"]=true;
		if(!isset($GLOBALS["BIGDEBUG"])){$GLOBALS["BIGDEBUG"]=false;}
		$this->sitename=$sitname;
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$datas=$sock->GET_INFO("ArticaProxySettings");
		if(trim($datas)<>null){
			$ini->loadString($datas);
			$this->ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
			$this->ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
			$this->ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
			$this->ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
			$this->ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
		}
		
		
		if(!isset($GLOBALS["BlueCoatKey"])){$GLOBALS["BlueCoatKey"]=trim($sock->GET_INFO("BlueCoatKey"));}
		
	}
	
	public function K9_TESTS($sitename=null){
		if($sitename<>null){$this->sitename=$sitename;}
		$array=$this->UBoxBluecoatGetCatCode($this->sitename);
		if(!is_array($array)){if($GLOBALS["DEBUG_EXTERN"]){echo "K9_TESTS: $this->sitename NONE\n";}return;}
		$category=trim($array["scat"]);
		if($category==null){if($GLOBALS["DEBUG_EXTERN"]){echo "K9_TESTS: $this->sitename NONE\n";}return;}
		
		if($GLOBALS["DEBUG_EXTERN"]){echo "K9_TESTS: $this->sitename $category\n";}
		
		if($category=="hobby/arts"){
			if(preg_match("#(music|musique|musica|mp3|jazz|musician)#", $this->sitename)){$category="music";}
			if(preg_match("#(movie|film|cine|stream)#", $this->sitename)){$category="movies";}
			if(preg_match("#video#", $this->sitename)){$category="audio-video";}
			if(preg_match("#\.tv$#", $this->sitename)){$category="webtv";}
			if(preg_match("#radio#", $this->sitename)){$category="webradio";}
			if(preg_match("#(cosmetic|cosmetique|makeup)#",$this->sitename)){$category="cosmetics";}
		}
			
			
		if($category=="industry"){
			if(preg_match("#(cosmetic|cosmetique|makeup)#",$this->sitename)){return "cosmetics";}
			if(preg_match("#(voiture|vehicule|truck)#", $this->sitename)){ return "automobile/cars";}
		}
			
		if($GLOBALS["VERBOSE"]){echo "K9: \"$this->sitename\": CAT:`$category` F:".__FUNCTION__." L:".__LINE__."\n";}
		if($category=="science/computing"){if(preg_match("#\.free\.fr$#",$this->sitename)){return null;}}
			
		
		if($GLOBALS["DEBUG_EXTERN"]){echo " -> $category";}
		$GLOBALS["CATEGORIZELOGS"][]="K9 SUCCESS";
		writelogs("K9[OK]:: Category $category for $this->sitename done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return $category;
		
		
	}
	
	public function K9(){
		$NOK9=false;
		if(trim($GLOBALS["BlueCoatKey"])==null){
			if($GLOBALS["VERBOSE"]){echo "K9: \"$this->sitename\": STOP! F:".__FUNCTION__." L:".__LINE__."\n";}
			return null;
		}
		
		if(preg_match("#\.com\.my$#", $this->sitename)){$NOK9=true;}
		if(!$NOK9){$NOK9=$this->NOK9;}
		
		if(!$NOK9){
			$category=$this->K9_TESTS();
			if($category<>null){
				if($GLOBALS["VERBOSE"]){echo "K9_TESTS(): \"$this->sitename\": CAT:`$category` F:".__FUNCTION__." L:".__LINE__."\n";}
				$GLOBALS["CATEGORIZELOGS"][]="K9_TESTS SUCCESS";
				writelogs("K9_TESTS[OK]:: Category $category for $this->sitename done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				if($GLOBALS["DEBUG_EXTERN"]){echo " -> $category";}
				return $category;
			}else{
				if($GLOBALS["DEBUG_EXTERN"]){echo " -> NOT DETECTED";}
			}
		}
		
		
		$category=$this->BITDEF_TESTS($this->sitename);
		if($category<>null){
			if($GLOBALS["VERBOSE"]){echo "BITDEF_TESTS(): \"$this->sitename\": CAT:`$category` F:".__FUNCTION__." L:".__LINE__."\n";}
			$GLOBALS["CATEGORIZELOGS"][]="BITDEF_TESTS SUCCESS";
			writelogs("BITDEF_TESTS[OK]:: Category $category for $this->sitename done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["DEBUG_EXTERN"]){echo " -> $category";}
			return $category;
		}else{
			if($GLOBALS["DEBUG_EXTERN"]){echo " -> NOT DETECTED";}
		}
		
		
		$category=$this->BlockSi($this->sitename);
		if($category<>null){
			if($GLOBALS["VERBOSE"]){echo "BlockSi(): \"$this->sitename\": CAT:`$category` F:".__FUNCTION__." L:".__LINE__."\n";}
			$GLOBALS["CATEGORIZELOGS"][]="BlockSi SUCCESS";
			writelogs("BlockSi[OK]:: Category $category for $this->sitename done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["DEBUG_EXTERN"]){echo " -> $category";}
			return $category;
		}else{
			if($GLOBALS["DEBUG_EXTERN"]){echo " -> NOT DETECTED";}
		}
		
		
		
		if($GLOBALS["OUTPUT"]){echo date("H:i:s")." KNton($this->sitename)\n";}
		$category=$this->KNton($this->sitename);
		if($category<>null){
			if($GLOBALS["VERBOSE"]){echo "KNton(): \"$this->sitename\": CAT:`$category` F:".__FUNCTION__." L:".__LINE__."\n";}
			$GLOBALS["CATEGORIZELOGS"][]="KNTON SUCCESS";
			writelogs("KNTON[OK]:: Category $category for $this->sitename done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["DEBUG_EXTERN"]){echo " -> $category";}
			return $category;
		}else{
			if($GLOBALS["DEBUG_EXTERN"]){echo " -> NOT DETECTED";}
		}
		
		if($GLOBALS["OUTPUT"]){echo date("H:i:s")." blueprintdata($this->sitename)\n";}
		$category=$this->blueprintdata();
		if($category<>null){
			if($GLOBALS["VERBOSE"]){echo "blueprintdata(): \"$this->sitename\": CAT:`$category` F:".__FUNCTION__." L:".__LINE__."\n";}
			$GLOBALS["CATEGORIZELOGS"][]="blueprintdata SUCCESS";
			writelogs("BLUE[OK]:: Category $category for $this->sitename done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			if($GLOBALS["DEBUG_EXTERN"]){echo " -> $category";}
			return $category;
		}else{
			if($GLOBALS["DEBUG_EXTERN"]){echo " -> NOT DETECTED";}
		}	
		
	/*	if(!isset($BANNED_BRIGHT[$this->hostname])){
			if($GLOBALS["OUTPUT"]){echo date("H:i:s")." BrightcloudGetCatCode($this->sitename)\n";}
			$category=$this->BrightcloudGetCatCode();
			if($category<>null){
				if($GLOBALS["VERBOSE"]){echo "BrightcloudGetCatCode(): \"$this->sitename\": CAT:`$category` F:".__FUNCTION__." L:".__LINE__."\n";}
				$GLOBALS["CATEGORIZELOGS"][]="BRIGHT SUCCESS";
				writelogs("BRIGHT[OK]:: Category $category for $this->sitename done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				if($GLOBALS["DEBUG_EXTERN"]){echo " -> $category";}
				return $category;
			}else{
				if($GLOBALS["DEBUG_EXTERN"]){echo " -> NOT DETECTED";}
			}
			
			
			$GLOBALS["CATEGORIZELOGS"][]="K9/BRIGHT FAILED";
		}
		*/
		/*$category=$this->GenericCatCode($this->sitename);
		if($category<>null){
			$GLOBALS["CATEGORIZELOGS"][]="GENERIC TESTA SUCCESS";
			writelogs("GENERIC[OK]:: Category $category for $this->sitename done",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return $category;
		}		
	*/
		
		
		return null;		
		//'21' => 'finance/other'
		//	'999' => 'Unknown',	'90' => 'Unrated','61' => 'Society / Daily Living','49' => 'Reference',		
	}
	
	public function bright(){
		$category=$this->BrightcloudGetCatCode();
		if($GLOBALS["VERBOSE"]){echo "BrightcloudGetCatCode(): \"$this->sitename\": CAT:`$category` F:".__FUNCTION__." L:".__LINE__."\n";}
		return $category;
	}
	
	
	public function BlockSi($sitename=null){
	
		if($sitename==null){$sitename=$this->sitename;}
		if($sitename==null){return;}
	
$ayBlockSiCats = array(
    1 => array(
        1 => "drugs",
        2 => "hacking",
        3 => "violence",
        4 => "violence",
        5 => "violence",
        6 => "proxy",
        7 => "tricheur",
        8 => "porn",
        9 => "terrorism"
    ),
   2 => array(
        1 => "sect",
        2 => "abortion",
        3 => "mixed_adult",
        4 => "politic",
        5 => "gamble",
        6 => "mixed_adult",
        7 => "porn",
        8 => "dating",
        9 => "weapons",
        10 => "mixed_adult",
        11 => "drugs",
        12 => "sexual_education",
        13 => "alcohol",
        14 => "tobacco",
        15 => "sex/lingerie",
        16 => "recreation/sports"
    ),
    3 => array(
        1 => "downloads",
        2 => "filehosting",
        3 => "movies",
        4 => "filehosting",
        5 => "webtv",
        6 => "webphone"
    ),
    4 => array(
        1 => "malware",
        2 => "phishing",
        3 => "mailing"
    ),
    5 => array(
        1 => "financial",
        2 => "searchengines",
       // 3 => "General Organizations",
        4 => "industry",
        5 => "science/computing",
        6 => "governments",
        7 => "weapons",
        8 => "isp",
        10 => "webapps"
    ),
    6 => array(
        1 => "publicite",
        2 => "stockexchange",
        3 => "games",
        4 => "webmail",
       // 5 => "Entertainment",
        6 => "hobby/arts",
        7 => "recreation/schools",
        8 => "health",
        9 => "jobsearch",
        10 => "medical",
        11 => "news",
        12 => "socialnet",
        13 => "politic",
       // 14 => "Reference",
        15 => "religion",
        16 => "shopping",
        17 => "society",
        18 => "recreation/sports",
        19 => "automobile/cars",
        //20 => "Dynamic Content",
        //21 => "Meaningless Content",
        22 => "society",
        23 => "chat",
        24 => "chat",
        25 => "forums",
        26 => "gifts",
        27 => "children",
        28 => "finance/realestate",
        29 => "recreation/nightout",
        30 => "blog",
       // 31 => "Content Servers",
        32 => "reaffected",
        33 => "blog",
        34 => "recreation/travel"
    ),
    7 => array(
        //0 => "Unrated"
    )
);

	$RateUrl = "http://service.block.si/getRating";
	$apikey = "f0ViYdyLrRWCDssBoETOMZYxy";
	$url = $sitename;
    $szPath = $RateUrl."?apikey=".$apikey."&url=".urlencode($url);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $szPath);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    $data = curl_exec($ch);
    $ay = curl_getinfo($ch);
    //print_r($ay);
    curl_close($ch);

    $ayData = json_decode($data, true);
    if($GLOBALS["VERBOSE"]){echo $ayData."\n";}
    $ayCats = explode('_', $ayData['Category']);
    $iMainCat = $ayCats[0];
    $iSubCat = $ayCats[1];
    $szCat = $ayBlockSiCats[$iMainCat][$iSubCat];
    if($GLOBALS["VERBOSE"]){echo "BlockSi:: $sitename \"$szCat\" ($iMainCat - $iSubCat )\n";}
    
   	return $szCat;
	}
	
	
function GenericCatCode($www){
		if($www==null){return;}

	
}

public function blueprintdata($www=null){
	if(trim($www)<>null){$this->sitename=$www;}
	
	$t=time();
	if($GLOBALS["DEBUG_EXTERN"]){echo "blueprint:";}
	
	$szUrl = "http://url.blueprintdata.com/urlreview/api/urlLookupG.do?urls=".urlencode($this->sitename);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $szUrl);
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	$data=curl_exec($ch);
	
	
	
	$array=json_decode($data, 1);
	$distanceInSeconds = round(abs(time() - $t));
	
	
	
	if(!isset($array["response"])){
		if($GLOBALS["DEBUG_EXTERN"]){echo " $this->sitename No response\n";}
		return null;
	}
	
	$MAINCATS["Search Engines"]="searchengines";
	$MAINCATS["Reference / Homework & Studying"]="recreation/schools";
	$MAINCATS["Travel / Tourism"]="recreation/travel";
	$MAINCATS["Recreation / Hobbies"]="hobby/other";
	$MAINCATS["Science"]="sciences";
	$MAINCATS["Health & Medical"]="health";
	$MAINCATS["No Content"]="suspicious";
	$MAINCATS["Under Construction / Parked Domains"]="reaffected";
	$MAINCATS["Unreachable / Dead Sites"]="reaffected";
	$MAINCATS["Business (the website is for a business - ex: BluePrint Data.com)"]="industry";
	$MAINCATS["Cultures / Society"]="society";
	$MAINCATS["Sports"]="recreation/sports";
	$MAINCATS["Internet / Net Services"]="isp";
	$MAINCATS["Arts"]="hobby/arts";
	$MAINCATS["Computing, General"]="science/computing";
	$MAINCATS["Education, General"]="recreation/schools";
	$MAINCATS["Music / Radio / TV"]="audio-video";
	$MAINCATS["Web based e-mail"]="webmail";
	$MAINCATS["Libraries + Museums"]="culture";
	$MAINCATS["Government / Politics & Political Activist Groups"]="politic";
	$MAINCATS["Shopping"]="shopping";
	$MAINCATS["Colleges/Universities"]="recreation/schools";
	$MAINCATS["Lavasoft Malware"]="malware";
	$MAINCATS["Motor Vehicles"]="automobile/cars";
	$MAINCATS["Redirect"]="redirector";
	$MAINCATS["Entertainment"]="recreation/nightout";
	$MAINCATS["Home & Garden"]="housing/accessories";
	$MAINCATS["Image (photo) & Video Search"]="audio-video";
	$MAINCATS["Game Media / Game Playing"]="games";
	$MAINCATS["Advertisements and pop-ups"]="publicite";
	$MAINCATS["Personal Web Content / Hosting Sites"]="blog";
	$MAINCATS["Blogs / Forums / Web based message boards & Newsgroups"]="blog";
	$MAINCATS["Investing / Stocks / Financial Services"]="financial";
	$MAINCATS["Classified / Auctions"]="smallads";
	$MAINCATS["Intimate Apparel / Swimsuits"]="sex/lingerie";
	
	$MAINCATS["Gay / Lesbian"]="mixed_adult";
	$MAINCATS["Religion"]="religion";
	$MAINCATS["Food and eating"]="hobby/cooking";
	$MAINCATS["Comics / Humor and Jokes"]="recreation/humor";
	$MAINCATS["News"]="news";
	$MAINCATS["Real Estate"]="finance/realestate";
	$MAINCATS["Sexual / Porn (hardcore porn)"]="porn";
	$MAINCATS["Link Farms / Click Fraud"]="malware";
	$MAINCATS["Personals / Dating / Romance"]="dating";
	$MAINCATS["Domain For Sale"]="reaffected";
	$MAINCATS["Kids & Teens"]="children";
	$MAINCATS["Illegal Drugs/Alcohol/Tobacco use or abuse"]="alcohol";
	$MAINCATS["Job Search / Careers"]="jobsearch";
	$MAINCATS["Nudity (i.e. softcore porn)"]="mixed_adult";
	$MAINCATS["Streaming Media"]="movies";
	$MAINCATS["Retirement / Seniors"]="hospitals";
	$MAINCATS["Fashion / Style"]="womanbrand";
	$MAINCATS["Web based Chat & Web Cams"]="chat";
	$MAINCATS["Fashion / Style"]="womanbrand";
	$MAINCATS["Cult and Occult"]="sect";
	$MAINCATS["Hunting Fishing"]="phishing";
	
	
	$MAINCATS["Suicide Promotion"]="violence";
	$MAINCATS["Violence"]="violence";
	$MAINCATS["Anonymous Proxies"]="proxy";
	$MAINCATS["Gambling"]="gamble";
	
	$MAINCATS["Weapons Promotion or Sale"]="weapons";
	$MAINCATS["Fraud / Illegal Activities"]="malware";
	$MAINCATS["Sex Education (including Abortion)"]="sexual_education";
	$MAINCATS["Malformed / Invalid URLs"]= "reaffected";
	$MAINCATS["Botnets / Phishing / Malware / Key loggers and Monitoring"]="tracker";
	

	
	
	$main=$array["response"]["urls"][0];
	
	if($GLOBALS["DEBUG_EXTERN"]){echo " $this->sitename status: {$main["status"]}";}
	
	if($main["status"]<>"FOUND"){
		if($GLOBALS["DEBUG_EXTERN"]){echo " NONE\n";}
		if($GLOBALS["VERBOSE"]){echo " $this->sitename {$main["status"]}\n";}
		return null;
	}
	$primaryCategory=$main["primaryCategory"];
	if($GLOBALS["DEBUG_EXTERN"]){echo " primaryCategory = $primaryCategory";}
	if(isset($MAINCATS[$primaryCategory])){
		if($GLOBALS["DEBUG_EXTERN"]){echo " = {$MAINCATS[$primaryCategory]}\n";}
		return $MAINCATS[$primaryCategory];}
	
	if(!isset($MAINCATS[$primaryCategory])){
		if($GLOBALS["DEBUG_EXTERN"]){echo " = UNKNOWN\n";}
		if($GLOBALS["VERBOSE"]){echo " \n\n\n****** $primaryCategory $this->sitename  *****\n\n\n";}
		$this->writeToFile("unkonw \"$primaryCategory\" \"$this->sitename\"\n", "/var/log/blueprintdata.log");
	}
	

	

}


public function KNTON_TESTS($www){
	echo $this->KNton($www)."\n";
}

public function BITDEF_TESTS($www){
	$szUrl = "https://nimbus.bitdefender.net/url/status?url=".urlencode("http://".$www);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $szUrl);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	$data = curl_exec($ch);
	curl_close($ch);
	
	$class=json_decode($data);
	if(!is_array($class->categories)){
		if($GLOBALS["DEBUG_EXTERN"]){echo "BITDEF_TESTS: class->categories not an array for $www\n";}
		return;}
	if(count($class->categories)==0){if($GLOBALS["DEBUG_EXTERN"]){echo "BITDEF_TESTS: \"$www\" NONE\n";}return;}
	$category=$class->categories[0];
	$CATZ["computersandsoftware"]="science/computing";
	$CATZ["porn"]="porn";
	$CATZ["searchengines"]="searchengines";
	$CATZ["onlineshop"]="shopping";
	$CATZ["education"]="recreation/schools";
	$CATZ["blogs"]="blog";
	$CATZ["business"]="industry";
	$CATZ["onlinedating"]="dating";
	$CATZ["travel"]="recreation/travel";
	$CATZ["news"]="news";
	$CATZ["sports"]="recreation/sports";
	$CATZ["radiomusic"]="webradio";
	$CATZ["hosting"]="isp";
	$CATZ["hobbies"]="hobby/other";
	$CATZ["religion"]="religion";
	$CATZ["webproxy"]="proxy";
	$CATZ["parked"]="reaffected";
	$CATZ["health"]="health";
	$CATZ["games"]="games";
	$CATZ["maturecontent"]="mixed_adult";
	$CATZ["filesharing"]="filehosting";
	$CATZ["videos"]="audio-video";
	$CATZ["government"]="governments";
	$CATZ["financial"]="financial";
	$CATZ["webmail"]="webmail";
	$CATZ["jobsearch"]="jobsearch";
	$CATZ["gambling"]="gamble";
	$CATZ["im"]="chat";
	$CATZ["drugs"]="drugs";
	$CATZ["entertainment"]="recreation/nightout";
	$CATZ["socialnetworks"]="socialnet";
	$CATZ["tabloids"]="society";
	$CATZ["hacking"]="hacking";
	
	if(!isset($CATZ[$category])){
		if($GLOBALS["DEBUG_EXTERN"]){echo "unkonw $category \"$www\"\n";}
		$this->writeToFile("unkonw $category \"$www\"\n", "/var/log/bitdef.log");
	}
	
	if($category=="entertainment"){
		if(preg_match("#video#", $www)){
			return "audio-video";	
		}
		if(preg_match("#(movies|film)#", $www)){
			return "movies";
		}
		
		if(preg_match("#(music|musiq|song)#", $www)){
			return "music";
		}		
		
	}
	
	return trim($CATZ[$category]);
	
}

private function UBoxNortonIsBlocked($szDom){
	$ayBlockIPl = array(2627383070, 2627383326);
	$bBlocked = 0;

	$ayNsr = array("nameservers" => array("199.85.126.30"));
	$resolver = new Net_DNS2_Resolver($ayNsr);
	$resolver->timeout = 1;
	try {
		$response = $resolver->query($szDom);
	}
	catch (Net_DNS2_Exception $e) {
		unset($ayNsr);
		unset($resolver);
		return $bBlocked;
	}
	unset($ayNsr);
	unset($resolver);

	foreach ($response->answer as $rrr) {
		if ($rrr->name == $szDom && $rrr->type == 'A' && $rrr->address != "") {
			$iIp = ip2long($rrr->address);
			if (in_array($iIp, $ayBlockIPl)) {
				$bBlocked = 1;
				break;
			}
		}
	}

	return $bBlocked;
}

function UBoxNortonGetCode($szDom){
	$iCode = 0;

	$bIsBlocked = $this->UBoxNortonIsBlocked($szDom);
	if ($bIsBlocked == 0) {
		return array('code' => $iCode, 'block' => $bIsBlocked);
	}

	$szUrl = $this->UBoxNortonGetCurl($szDom);
	if ($szUrl != NULL) {
		$szQuery = parse_url($szUrl, PHP_URL_QUERY);
		parse_str($szQuery, $ayQuery);
		$iCode = (isset($ayQuery['msgID1']) ? $ayQuery['msgID1'] : 0);
		unset($ayQuery);

		while ($iCode < 116595) {
			$iCode += 48;
		}
	}

	return array('code' => $iCode, 'block' => $bIsBlocked);
}


private function UBoxNortonGetCurl($szDom){
	$ayBlockIP = array('156.154.175.30', '156.154.176.30');

	$nBlockIP = count($ayBlockIP);
	$irand = rand(0, $nBlockIP - 1);
	$sznBlockIP = $ayBlockIP[$irand];

	$ayHeader = array("host: ".$szDom);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_URL, $sznBlockIP);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $ayHeader);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_REFERER, "http://".$szDom);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; Artica)");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);

	curl_exec($ch);
	$ay = curl_getinfo($ch);
	$szUrl = (isset($ay['url']) ? $ay['url'] : NULL);
	unset($ay);

	unset($ayHeader);
	curl_close($ch);

	return $szUrl;
}

private function KNton($www=null,$restart=false){
	$www=trim($www);
	$t=time();
	if($www==null){$www=$this->sitename;}
	
	
	$UBoxNortonGetCode=$this->UBoxNortonGetCode($www);
	$ID=$UBoxNortonGetCode["code"];
	
	if($GLOBALS["DEBUG_EXTERN"]){echo "KNton:: $ID for $www\n";}
	$CATZ["116595"]="abortion";
	$CATZ["116596"]="publicite";
	$CATZ["116597"]="alcohol";
	$CATZ["116598"]="hobby/arts";
	$CATZ["116599"]="automobile/cars";
	$CATZ["116600"]="industry";
	$CATZ["116601"]="hacking";
	$CATZ["116603"]="sect";
	$CATZ["116604"]="socialnet";
	$CATZ["116605"]="drugs";
	$CATZ["116606"]="music";
	$CATZ["116607"]="filehosting";
	$CATZ["116608"]="gamble";
	$CATZ["116609"]="games";
	//CATZ["116610"]="General";
	$CATZ["116611"]="governments";
	$CATZ["116612"]="violence";
	$CATZ["116613"]="health";
	$CATZ["116614"]="jobsearch";
	$CATZ["116615"]="children";
	$CATZ["116616"]="justice";
	$CATZ["116617"]="sex/lingerie";
	$CATZ["116618"]="mixed/adult";
	$CATZ["116619"]="weapons";
	$CATZ["116620"]="news";
	$CATZ["116621"]="Chat";
	$CATZ["116622"]="blog";
	$CATZ["116623"]="politic";
	$CATZ["116624"]="porn";
	$CATZ["116625"]="recreation/schools";
	$CATZ["116626"]="religion";
	$CATZ["116627"]="searchengines";
	$CATZ["116628"]="sexual_education";
	$CATZ["116629"]="mixed_adult";
	$CATZ["116630"]="shopping";
	$CATZ["116631"]="recreation/sports";
	$CATZ["116632"]="violence";
	$CATZ["116633"]="science/computing";
	$CATZ["116634"]="tobacco";
	$CATZ["116635"]="recreation/travel";
	$CATZ["116636"]="violence";
	$CATZ["116637"]="weapons";
	$CATZ["116638"]="proxy";
	$CATZ["116639"]="webmail";
	//45$CATZ["116640"]="Uncategorized";
	$CATZ["116641"]="forums";
	if($GLOBALS["DEBUG_EXTERN"]){echo "$www {$CATZ[$ID]}\n";}
	if($GLOBALS["VERBOSE"]){echo "$www {$CATZ[$ID]}\n";}
	
	if(!isset($CATZ[$ID])){return null;}
	
	return $CATZ[$ID];
	
	$distanceInSeconds = round(abs(time() - $t));
	if($distanceInSeconds==5){$GLOBALS["KNtonLock"]=true;}
	if($GLOBALS["DEBUG_EXTERN"]){echo "query KNton {$distanceInSeconds}s ";}
	
	
	
}
	
	
private function ayK9Cats(){

	$ayK9Cats = array(
	'1' => 'porn',
	'3' => 'porn',
	'4' => 'sexual_education',
	'5' => 'sex/lingerie',
	'6' => 'sexual_education',
	'7' => 'agressive',
	'9' => 'malware',
	'11' => 'gamble',
	'14' => 'violence',
	'15' => 'weapons',
	'16' => 'abortion',
	'17' => 'hacking',
	'18' => 'phishing',
	'20' => 'hobby/arts',
	'21' => 'industry',
	'22' => 'sect',
	'23' => 'alcohol',
	'24' => 'tobacco',
	'25' => 'drugs',
	'27' => 115,
	'29' => 13,
	'30' => 'hobby/arts',
	'31' => 'financial',
	'32' => 'stockexchange',
	'33' => 'games',	
	'34' => 'governments',
	'35' => 'violence',
	'36' => 'politic',
	'37' => 66,
	'38' => 'science/computing',
	'40' => 'searchengines',
	'43' => 'spyware',
	'44' => 'spyware',
	'45' => 'jobsearch',
	'46' => 103,
	'47' => 'dating',
	'50' => 'pictureslib',
	'51' => 'chat',
	'52' => 'webmail',
	'53' => 'forums',
	'54' => 'religion',	
	'55' => 'socialnet',
	'56' => 'filehosting',
	'57' => 'remote-control',	
	'58' => 'shopping',	
	'59' => 'shopping',
	'60' => 'finance/realestate',
	'61' => 'society',
	'63' => 'blog',
	'64' => 'recreation/nightout',
	'65' => 'recreation/sports',
	'66' => 'recreation/travel',
	'67' => 'automobile/cars',
	'68' => 'recreation/humor',	
	'71' => 'downloads',
	'72' => 'paytosurf',
	'83' => 'downloads',
	'84' => 'audio-video',	
	'85' => 'webapps',
	'86' => 'proxy',
	'87' => 'children',
	'88' => 'publicite',
	'89' => 'isp',
	'92' => 'suspicious',
	'93' => 'mixed_adult',
	'94' => 'mixed_adult',
	'95' =>'translators',
	'96' => 'tracker',
	'97' => 'imagehosting',
	'98' => 'reaffected',
	'101'=> 'mailing',
	'102'=> 'spyware',
	'103'=> 'dynamic',
	'106'=> 'gifts',
	'107'=>  'news',
	'110'=> 'webphone',
	'111'=> 'meetings',
	'112'=> 'imagehosting',
	'113'=> 'webradio',
	'114'=>'webtv',
	'503'=>'warez',
	'504'=>'tracker',
	'505'=>'internal',
	'506'=>'recreation/wellness',
	'602'=>'pictureslib',	
	'901'=>'porn',
	'990'=>'tobacco',
	'991'=>'hobby/cooking',
	'992'=>'astrology',
	'995'=>'humanitarian',
	'996'=>'justice',
	'997'=>'hobby/other',
	'998'=>'culture',
	"999"=>null);
	
	return $ayK9Cats;
}

private function writeToFile($text,$filepath){
	$f = @fopen($filepath, 'a');
	@fwrite($f,$text);
	@fclose($f);
}

public function UboxBlueaCoatAlter(){
	$BC["092B"]=92;
	$BC["063D"]=93; //mixed adult
	$BC["012E"]=93; //mixed adult
	$BC["013D"]=93; //mixed adult
	$BC["145E"]=93; //mixed adult
	$BC["053F"]=93; //mixed adult
	$BC["013F"]=93; //mixed adult
	$BC["012F"]=93; //mixed adult
	$BC["425E"]=93; //mixed adult
	$BC["2E5E"]=93; //mixed adult
	$BC["063F"]=93; //mixed adult
	$BC["013A"]=93; //mixed adult
	$BC["015D"]=93; //mixed adult
	$BC["01053A"]=93; //mixed adult
	$BC["013F44"]=93; //mixed adult
	
	$BC["05063A"]=5; //sex/lingerie
	$BC["043A"]=4; //sexual_education
	
	$BC["38595C"]=56; //Filehosting
	
	$BC["1B40"]=991; //cooking
	
	
	$BC["5A"]=999;
	$BC["5C"]=92; // suspicious
	$BC["475C"]=92; // suspicious
	$BC["5C60"]=92; // suspicious
	$BC["595C"]=92; // suspicious
	$BC["26585C"]=92; // suspicious
	$BC["263F"]=38;
	$BC["032B"]=901;//porn
	$BC["033F"]=901;//porn
	$BC["032F"]=901;//porn
	$BC["03585C"]=901;//porn
	$BC["033A47"]=901;//porn
	$BC["032B2C"]=901;//porn
	$BC["032F"]=901;//porn
	$BC["035C"]=901;//porn
	$BC["03070E"]=901;//porn
	
	$BC["053A"]=5;//sex/lingerie
	
	
	$BC["1F34"]=101;//mailing
	$BC["345C"]=101;//mailing
	
	$BC["39475C"]=57;//remote control
	
	$BC["11475C"]=17;//hacking*
	$BC["111B262B"]=17;//hacking*
	$BC["3F44"]=68; //humor
	
	
	$BC["2134475C"]=17;//games
	$BC["213A"]=33;//games
	$BC["213D"]=33;//games
	$BC["21353A"]=33;//games
	
	$BC["3A3F"]=88;//publicite
	$BC["3D58"]=88;//publicite
	
	
	$BC["0B58"]=11;//gamble
	$BC["3A"]=58; //shopping
	$BC["3C"]=60; //finance/realestate
	$BC["3C61"]=60; //finance/realestate
	$BC["15223C"]=60; //finance/realestate
	$BC["313C"]=60; //finance/realestate
	$BC["203C"]=60; //finance/realestate
	$BC["3C55"]=60; //finance/realestate
	$BC["283C42"]=60; //finance/realestate
	
	$BC["183A"]=990; //tobacco
	
	
	
	
	$BC["3B"]=58; //shopping
	$BC["3B61"]=58; //shopping
	$BC["313A"]=58; //shopping
	$BC["153A"]=58; //shopping
	$BC["3A3B"]=58; //shopping
	$BC["323A"]=58; //shopping
	$BC["1B3A"]=58; //shopping
	$BC["3A59"]=58; //shopping
	
	$BC["163D"]=992; //astrology
	
	$BC["26353D"]=53; //forums
	
	
	
	$BC["1B36"]=54; //religion
	$BC["3D"]=61; // society
	$BC["3F"]=63; //blog
	$BC["3F62"]=63; //blog
	$BC["3F59"]=63; //blog
	$BC["373F"]=63; //blog
	$BC["323F"]=63; //blog
	$BC["2E3F"]=63; //blog
	$BC["353F"]=63; //blog
	$BC["3F54"]=63; //blog
	$BC["14323F"]=63; //blog
	
	$BC["3C42"]=66; //recreation/travel
	$BC["3F42"]=66; //recreation/travel
	$BC["3A42"]=66; //recreation/travel
	$BC["1B42"]=66; //recreation/travel
	$BC["1F42"]=66; //recreation/travel
	$BC["143F"]=998; //culture
	$BC["323F59"]=63; //blog
	$BC["223F"]=996; //justice
	$BC["222E"]=34; //governments
	$BC["1F22"]=34; //governments	
	$BC["1D"]=29; //associations
	$BC["1B"]=27; //Recreation/schools
	$BC["1B3157"]=27; //Recreation/schools
	$BC["1F"]=31; //financial
	$BC["1F3F"]=31; //financial
	$BC["1F20"]=31; //financial
	$BC["0B"]=11;
	$BC["0E"]=14;// violence
	$BC["070E"]=14;// violence
	$BC["0F"]=15;// weapons
	$BC["0F3B"]=15;//weapons
	$BC["0F3A"]=15;//weapons
	$BC["2C54"]=44; //spy
	$BC["2C"]=44; //spyware
	$BC["282B"]=44; //spyware
	
	$BC["173F"]=23; //alcoohol
	$BC["173A"]=23; //alcoohol
	
	
	$BC["2B"]=9; // malware
	$BC["2B3A"]=9; // malware
	$BC["05142B2C"]=9; // malware
	$BC["2B2C59"]=9; // malware
	$BC["2B60"]=9; // malware
	$BC["2B59"]=9; // malware
	$BC["282B32"]=9; // malware
	$BC["2B2C"]=9; // malware
	$BC["2B3F"]=9; // malware
	$BC["2B34"]=9; // malware
	$BC["112B47"]=9; // malware
	$BC["112B"]=9; // malware
	$BC["2B37"]=9; //malware
	$BC["0911265C"]=9; // malware
	$BC["09585C"]=9; // malware
	$BC["262B"]=9; // malware
	
	$BC["3A58"]=88; // publicite
	
	$BC["485C"]=72; //PaytoSurf
	
	$BC["2F33"]=51; // chat
	$BC["2B33"]=51; // chat
	
	$BC["2E"]=46; //news
	$BC["2E3A"]=46; //news
	$BC["282E"]=46; //news
	$BC["152E"]=46; //news
	$BC["2F"]=47; //dating
	$BC["2F61"]=47; //dating
	$BC["2F353D"]=47; //dating
	
	$BC["2F3F5C"]=47; //dating
	$BC["1B22"]=34; // governements
	$BC["1D22"]=34; // governements
	$BC["151B"]=27; //spyware
	$BC["153F"]=31; //financial
	$BC["1B1F"]=31; //financial
	$BC["151F"]=31; //financial
	$BC["1F2E"]=31; //financial
	$BC["1F25"]=31; //financial
	$BC["1F35"]=31; //financial
	$BC["1F31"]=31; //financial
	$BC["1F28"]=31; //financial
	$BC["151F25"]=31; //financial
	$BC["15263C"]=31; //financial
	
	
	
	$BC["243D"]=36; //politic
	$BC["15243F"]=36; //politic
	
	$BC["213A57"]=87; //children
	$BC["3A57"]=87; //children
	$BC["1B57"]=87; //children
	
	$BC["143D"]=61; //society
	$BC["313D"]=61; //society
	$BC["3D3F"]=61; //society
	$BC["3A3D"]=61; //society
	$BC["3D61"]=61; //society
	$BC["353D"]=61; //society
	$BC["253D"]=61; //society
	$BC["3D59"]=61; //society
	$BC["153D"]=61; //society
	$BC["2E3D"]=61; //society
	$BC["223D"]=61; //society
	$BC["3D54"]=61; //society
	$BC["3D41"]=61; //society
	$BC["142F333D"]=61; //society
	
	$BC["3A47"]=71; //downloads
	$BC["2B47"]=71; //downloads
	
	$BC["3F40"]=64; //recreation/nightout
	$BC["3A40"]=64; //recreation/nightout
	$BC["14213A"]=64; //recreation/nightout
	
	$BC["373A"]=55; //socialnet
	$BC["373D"]=55; //socialnet
	$BC["253A"]=37; //health
	$BC["253F"]=37; //health
	$BC["3A41"]=65; //sports
	$BC["3F41"]=65; //sports
	
	
	$BC["3A43"]=67;	//Automobile/cars
	$BC["3A4143"]=67;	//Automobile/cars
	$BC["3A43"]=67;	//Automobile/cars
	$BC["1B43"]=67;	//Automobile/cars
	
	$BC["31353D"]=61; //society

	$BC["1B21"]=33; //gamesF
	$BC["213F"]=33; //games
	$BC["1B31"]=27; //Recreation/schools
	$BC["1B3F"]=27; //Recreation/schools
	$BC["1B25"]=27; //Recreation/schools
	$BC["1B26"]=27; //Recreation/schools
	$BC["1B37"]=27; //Recreation/schools
	$BC["1B3D"]=61; //society
	$BC["1B35"]=27; //Recreation/schools
	$BC["1D31"]=29; //associations
	$BC["143A"]=58; //shopping
	$BC["2C58"]=44; //spyware
	$BC["1B313D57"]=27; //Recreation/schools
	$BC["1B35"]=27; //Recreation/schools
	$BC["141B"]=27; //Recreation/schools
	$BC["141B31"]=27; //Recreation/schools
	$BC["1B28"]=27; //Recreation/schools
	$BC["1B2631"]=27; //Recreation/schools
	$BC["313F"]=63; //blog
	$BC["163F"]=54; //religion
	$BC["35363D"]=54; //religion	
	$BC["243F"]=36; //Politic
	
	$BC["095C"]=92; //suspicious
	$BC["285C"]=92; //suspicious
	$BC["315C"]=92; //suspicious
	$BC["405C"]=92; //suspicious
	$BC["5C62"]=92; //suspicious
	$BC["145C"]=92; //suspicious
	$BC["585C"]=92; //suspicious
	$BC["091B"]=92; //suspicious
	$BC["155C"]=92; //suspicious
	$BC["01065C"]=92; //suspicious
	$BC["34365C"]=92; //suspicious
	$BC["2E5C"]=92; //suspicious
	$BC["3D5C"]=92; //suspicious
	
	$BC["263C"]=38; // science/computing
	$BC["263A"]=38; // science/computing
	$BC["262E"]=38; // science/computing
	$BC["263A47"]=38; // science/computing
	$BC["242E"]=36; // Politic
	$BC["141B24"]=36; // Politic
	$BC["142E"]=997; // hobby/other
	$BC["143F54"]=84; // Audio-video
	
	$BC["283A"]=40; // searchengines
	$BC["283F"]=40; // searchengines
	$BC["1B1D"]=995; //humanitarian
	$BC["041D"]=995; //humanitarian
	$BC["1D25"]=995; //humanitarian
	$BC["1D37"]=995; //humanitarian
	
	
	$BC["202E"]= 32; //stockexchange
	$BC["141B1D"]= 998; //culture
	
	$BC["2D"]=45; //jobsearch
	$BC["2D2E"]=45; //jobsearch
	$BC["152D"]= 45; //jobsearch
	$BC["262D"]= 45; //jobsearch
	$BC["142D"]= 45; //jobsearch
	$BC["142D3D"]= 45; //jobsearch
	$BC["1B2D"]= 45; //jobsearch
	$BC["1B2D31"]= 45; //jobsearch
	$BC["222D"]= 45; //jobsearch
	return $BC;
}
	
	
private function UBoxBluecoatGetCatCode($szDom){
	
	$ayK9Cats=$this->ayK9Cats();
	$data = $this->UBoxBluecoatGetCurlProxy($szDom);
	$szCode=null;
	$hCat=null;
	
	if(strpos($data, "<DirC>5A</DirC>")>0){
		if($GLOBALS["DEBUG_EXTERN"]){echo "UBoxBluecoatGetCatCode:: 5A -> NOT DETECTED\n";}
		return false;}
	
	if($GLOBALS["VERBOSE"]){
		echo "\n\n**************** \n\n$data\n\n****************\n";
	}
	
	if (preg_match("#<Code>(.+?)</Code>#is", $data, $re)) {$szCode = $re[1];}
	if (preg_match("#<DirC>(.+?)</DirC>#is", $data, $re)) {$hCat = $re[1];$iCat = hexdec($hCat);}
	if (preg_match("#<DomC>(.+?)</DomC>#is", $data, $re)) {$hCat = $re[1];$iCat = hexdec($hCat);}	
	
	$BC=$this->UboxBlueaCoatAlter();
	
	if(!is_numeric($hCat)){
		if($GLOBALS["DEBUG_EXTERN"]){echo "UBoxBluecoatGetCatCode:: hCat not a  numeric, trying BC\n";}
		if(isset($BC[$hCat])){$hCat=$BC[$hCat];$iCat=$hCat;}
	}
	
	if($hCat==11){$hCat=0;$iCat=0;}
	if($hCat==20){$hCat=0;$iCat=0;}
	
	if(!is_numeric($hCat)){
		if($GLOBALS["DEBUG_EXTERN"]){echo "UBoxBluecoatGetCatCode:: Fatal, not a numeric $hCat -> 999 \n";}
		$this->writeToFile("$hCat \"$szDom\"\n", "/var/log/bluealpha.log");
		$hCat = "999";	$iCat = 999;	
	}
	
	
	$BB=array();
	if($GLOBALS["DEBUG_EXTERN"]){echo "iCat => $iCat\n";}
	$szCat = (isset($ayK9Cats[$iCat]) ? $ayK9Cats[$iCat] : $ayK9Cats[999]);
	
	if($iCat==999){
		if(isset($BB[$szCode])){
			$szCat=$ayK9Cats[$BB[$szCode]];
		}
	}	
	
	$result = array(
		'code' => $szCode,	/* Code Bluecoat */
		'hcat' => $hCat,	/* Category in Hexa */
		'icat' => $iCat,	/* Category in Decimal */
		'scat' => $szCat,	/* Name of the Category */
	);
	

	
	
	if($GLOBALS["DEBUG_EXTERN"]){echo "UBoxBluecoatGetCatCode:: \"$this->sitename\": iCat:$iCat HCAT:$hCat BlueCoat Code:$szCode F:".__FUNCTION__." L:".__LINE__."\n";}
	return $result;
}	
	
	private function UBoxBluecoatGetCurlProxy($szDom){
		if(!function_exists("curl_setopt")){return null;}

		$szUrl = "http://sp.cwfservice.net/1/R/{$GLOBALS["BlueCoatKey"]}/K9-00006/0/GET/HTTP/".$szDom."/80/";
		$t=time();

		if($GLOBALS["DEBUG_EXTERN"]){echo "K9: $szUrl\n";}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $szUrl);
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla"); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		//curl_setopt ($ch, CURLOPT_PROXY,"127.0.0.1:60846");
		
		
		//	curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
		//	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		//	curl_setopt($ch, CURLOPT_PROXY, "41.89.130.4");
		//	curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
			
				
		
		
	//	curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
	//	curl_setopt($ch, CURLOPT_PROXY, $szProxyIP);
	//	curl_setopt($ch, CURLOPT_PROXYPORT, $szProxyPort);
		$data = curl_exec($ch);
		$length=strlen($data);
		//echo $data;
		curl_close($ch);
		$distanceInSeconds = round(abs(time() - $t));
		if($GLOBALS["DEBUG_EXTERN"]){echo " Done {$distanceInSeconds}s $data";}
		return $data;
	}


	
	
private function str_get_html($str, $lowercase=true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT)
{
    $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $defaultBRText);
    if (empty($str))
    {
        $dom->clear();
        return false;
    }
    $dom->load($str, $lowercase, $stripRN);
    return $dom;
}	


public function BrightcloudGetCatCode(){
	if(!is_file(dirname(__FILE__)."/UBoxBrightcloud.php")){
		if($GLOBALS["VERBOSE"]){echo "BRIGTH: \"UBoxBrightcloud.php\": No such file F:".__FUNCTION__." L:".__LINE__."\n";}
		return null;}
	if(!function_exists("UBoxBrightcloudGetCatCode")){require_once(dirname(__FILE__)."/class.categorize.externals.bright.inc");}
	
	$zcat=UBoxBrightcloudGetCatCode($this->sitename,true);
	
	$ayK9Cats=$this->ayK9Cats();
	if($zcat["icat"]>0){
		$category=$ayK9Cats[$zcat["icat"]];
		if($category==null){return null;}
		if($GLOBALS["VERBOSE"]){echo "BRIGTH: $this->sitename=$category F:".__FUNCTION__." L:".__LINE__."\n";}
		return $category;
	}
	}

private function BrightCatToMyCat($szCat){
	$cat["Adult and Pornography"]="porn";
	$cat["Personal sites and Blogs"]="blog";
	$cat["Sports"]="recreation/sports";
	$cat["Swimsuits and Intimate Apparel"]="sex/lingerie";
	$cat["Gambling"]="gamble";
	$cat["Phishing and Other Frauds"]="phishing";
	$cat['Entertainment and Arts']= "hobby/arts";
	$cat['Music']="music";
	$cat["Business and Economy"]="industry";
	$cat["Financial Services"]="financial";
	$cat["Educational Institutions"]="recreation/schools";
	$cat["Games"]="games";
	$cat["Government"]="governments";
	$cat["Philosophy and Political Advocacy"]="politic";
	$cat["Health and Medicine"]="health";
	$cat["Computer and Internet Info"]="science/computing";
	$cat["Internet Portals"]="isp";
	$cat["Search Engines"]="searchengines";
	$cat["Spyware and Adware"]="spyware";
	$cat["Malware Sites"]="malware";
	$cat["Job Search"]="jobsearch";
	$cat["News and Media"]="news";
	$cat["Dating"]="dating";
	$cat["Reference and Research"]="searchengines";
	$cat["Web based email"]="webmail";
	$cat["Religion"]="religion";
	$cat["Social Network"]="socialnet";
	$cat["Personal Storage"]="filehosting";
	$cat["Shopping"]="shopping";
	$cat["Auctions"]="shopping";
	$cat["Real Estate"]="finance/realestate";
	$cat["Travel"]="recreation/travel";
	$cat["Motor Vehicles"]="automobile/cars";
	$cat["Shareware and Freeware"]="downloads";
	$cat["Peer to Peer"]="downloads";
	$cat["Streaming Media"]="movies";
	$cat["Image and Video Search"]="audio-video";
	$cat["Proxy Avoid and Anonymizers"]="proxy";
	$cat["Kids"]="children";
	$cat["Web Advertisements"]="publicite";
	$cat["Web Hosting Sites"]="isp";
	$cat["SPAM URLs"]="mailing";
	$cat["Computer and Internet Security"]="science/computing";
	$cat["Translation"]="translators";
	$cat["Dead Sites (db Ops only)"]="reaffected";
	$cat["Training and Tools"]="learning";
	$cat["Legal"]="justice";
	$cat["Alcohol and Tobacco"]="alcohol";
	$cat["Stock Advice and Tools"]="stockexchange";
	$cat["Sex Education"]="sexual_education";
	$cat["Cult and Occult"]="sect";
	$cat["Dead Sites"]="reaffected";
	$cat["Fashion and Beauty"]="recreation/wellness";
	$cat["Private IP Addresses"]="internal";
	$cat["Private Ips"]="internal";
	$cat["Hacking"]="hacking";
	$cat["Keyloggers and Monitoring"]="tracker";
	$cat["Marijuana"]="drugs";
	$cat["Military"]="violence";
	$cat["Nudity"]="mixed_adult";
	$cat["Weapons"]="weapons";
	$cat["Abused Drugs"]="drugs";
	$cat["Confirmed SPAM Sources"]="mailing";
	$cat["Home and Garden"]="housing/accessories";
	$results=$cat[$szCat];
	
	if($GLOBALS["VERBOSE"]){echo "BRIGHT: \"$this->sitename\": $szCat/$results F:".__FUNCTION__." L:".__LINE__."\n";}
	//$cat["Local Information"]
	//$cat["Internet Communications"]//
	//$cat["Pay to Surf"]...???
	//$cat["Society"]="associations";
	$GLOBALS["BRIGHTALDY"][$this->sitename]=$results;
	return $results;
}


function TrendToMyCat($szCat){

if(trim($szCat==null)){return;}
$cat = array(
	'Adult / Mature Content'		=> "mixed_adult",
	'Pornography'					=> "porn",
	'Sex Education'					=> "sexual_education",
	'Illegal / Questionable'		=> "",
	'Gambling'						=> "gamble",
	'Violence / Hate / Racism'		=> "violence",
	'Weapons'						=> "weapons",
	'Hacking'						=> "hacking",
	'Phishing'						=> "phishing",
	'Entertainment'					=> "recreation/travel",
	'Business / Economy'			=> "industry",
	'Alcohol / Tobacco'				=> "alcohol",
	'Alcohol / Tobacco'				=> "alcohol",
	'Illegal Drugs'					=> "drugs",
	'Cultural Institutions'			=> "culture",
	'Financial Services'			=> "financial",
	'Games'							=> "games",
	'Government / Legal'			=> "governments",
	'Military'						=> "violence",
	'Activist Groups'				=> "politic",
	'Health'						=> "health",
	'Computers / Internet'			=> "science/computing",
	'Internet Infrastructure'		=> "isp",
	'Search Engines / Portals'		=> "searchengines",
	'Disease Vector'				=> "",
	'Job Search / Careers'			=> "jobsearch",
	'News / Media'					=> "news",
	'Personals / Dating'			=> "dating",
	'Reference'						=> "",
	'Chat / Instant Messaging'		=> "chat",
	'Email'							=> "webmail",
	'Newsgroups'					=> "forums",
	'Social Networking'				=> "socialnet",
	'Personal Network Storage / File Download Servers'	=> "filehosting",
	'Shopping'						=> "shopping",
	'Auctions'						=> "shopping",
	'Real Estate'					=> "finance/realestate",
	'Society / Lifestyle'			=> "hobby/other",
	'Blogs / Web Communications'	=> "blog",
	'Restaurants / Food'			=> "recreation/nightout",
	'Sports'						=> "recreation/sports",
	'Travel'						=> "recreation/travel",
	'Vehicles'						=> "automobile/cars",
	'Humor'							=> "recreation/humor",
	'Software Downloads'			=> "downloads",
	'Pay to Surf'					=> "paytosurf",
	'Peer-to-peer'					=> "downloads",
	'Internet Radio and TV'			=> "webtv",
	'Proxy Avoidance'				=> "proxy",
	'Web Advertisement'				=> "publicite",
	'Web Hosting'					=> "isp",
	'Untested'						=> "",
	'Spam'							=> "mailing",
	'Internet Telephony'			=> "webphone",
	'Photo Searches'				=> "pictureslib",
	'Education'						=> "recreation/schools",
	'Malware Accomplice'			=> "malware",
	'Religion'						=> 'religion',
	'For Kids'						=> 'children',
	'Disease Vector'				=> 'suspicious',
	'Nudity'						=> 'mixed_adult',
	'Politics'						=> 'politic',
	'Streaming Media / MP3'			=> 'audio-video'
);	

	$results=$cat[$szCat];
	if($results==null){
		@mkdir(dirname(__FILE__)."/unknown_cats");
		@file_put_contents(dirname(__FILE__)."/unknown_cats/".md5($szCat), $szCat."\n");
	}
	if($GLOBALS["VERBOSE"]){echo "TREND: \"$this->sitename\": CURL: $szCat/$results F:".__FUNCTION__." L:".__LINE__."\n";}
	$GLOBALS["BRIGHTALDY"][$this->sitename]=$results;
	return $results;
	
}

private	function UBoxTrendmicroGetCurl(){
	$postdata = array('urlname' => $this->sitename);
	$ckfile = tempnam(dirname(__FILE__)."/logs", "tmcook-");

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://global.sitesafety.trendmicro.com/result.php");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	
		if($this->ArticaProxyServerEnabled=="yes"){
			curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			curl_setopt($ch, CURLOPT_PROXY, $this->ArticaProxyServerName);
			curl_setopt($ch, CURLOPT_PROXYPORT, $this->ArticaProxyServerPort);
			//curl_setopt ($ch, CURLOPT_PROXY,"$this->ArticaProxyServerName:$this->ArticaProxyServerPort");
			if($this->ArticaProxyServerUsername<>null){
				curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->ArticaProxyServerUsername.':'.$this->ArticaProxyServerUserPassword);
			}
		}	
	
	$data = curl_exec($ch);
	curl_close($ch);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://global.sitesafety.trendmicro.com/result.php");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		if($this->ArticaProxyServerEnabled=="yes"){
			curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			curl_setopt($ch, CURLOPT_PROXY, $this->ArticaProxyServerName);
			curl_setopt($ch, CURLOPT_PROXYPORT, $this->ArticaProxyServerPort);
			//curl_setopt ($ch, CURLOPT_PROXY,"$this->ArticaProxyServerName:$this->ArticaProxyServerPort");
			if($this->ArticaProxyServerUsername<>null){
				curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->ArticaProxyServerUsername.':'.$this->ArticaProxyServerUserPassword);
			}
		}	
	
	$data = curl_exec($ch);
	
	curl_close($ch);

	unlink($ckfile);
	if($GLOBALS["VERBOSE"]){echo "TREND: \"$this->sitename\": CURL:".strlen($data)." bytes F:".__FUNCTION__." L:".__LINE__."\n";}
	return $data;
	}



public function UBoxTrendmicroGetCatCode(){
		return;
		$szBody = $this->UBoxTrendmicroGetCurl($this->sitename);
		if($GLOBALS["VERBOSE"]){echo "$this->sitename *********************** \n$szBody\n"; }
		$html = str_get_html($szBody);
		if ($html == false)
			return FALSE;
		
		$result = NULL;
		$iCount = 0;
		foreach ($html->find('div[class=content1]') as $key => $info) {
			$iCount++;
			$szTmp = trim($info->innertext);
			preg_match('#<h5>(.*?)</h5>#is', $szTmp, $re);
	
			if ($iCount > 1 && isset($re[1])) {
				$szCat = $re[1];
				if($GLOBALS["VERBOSE"]){echo "TREND: \"$this->sitename\": $szCat F:".__FUNCTION__." L:".__LINE__."\n";}
				return $this->TrendToMyCat($szCat);
				break;
			}
		}
		return (count($result) > 0 ? $result : FALSE);
	}


	
	
}

function file_get_html($url, $use_include_path = false, $context=null, $offset = -1, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT)
{
    // We DO force the tags to be terminated.
    $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $defaultBRText);
    // For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
    $contents = file_get_contents($url, $use_include_path, $context, $offset);
    // Paperg - use our own mechanism for getting the contents as we want to control the timeout.
//    $contents = retrieve_url_contents($url);
    if (empty($contents))
    {
        return false;
    }
    // The second parameter can force the selectors to all be lowercase.
    $dom->load($contents, $lowercase, $stripRN);
    return $dom;
}

// get html dom from string
function str_get_html($str, $lowercase=true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT)
{
    $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $defaultBRText);
    if (empty($str))
    {
        $dom->clear();
        return false;
    }
    $dom->load($str, $lowercase, $stripRN);
    return $dom;
}

// dump html dom tree
function dump_html_tree($node, $show_attr=true, $deep=0)
{
    $node->dump($node);
}

/**
 * simple html dom node
 * PaperG - added ability for "find" routine to lowercase the value of the selector.
 * PaperG - added $tag_start to track the start position of the tag in the total byte index
 *
 * @package PlaceLocalInclude
 */
class simple_html_dom_node {
    public $nodetype = HDOM_TYPE_TEXT;
    public $tag = 'text';
    public $attr = array();
    public $children = array();
    public $nodes = array();
    public $parent = null;
    public $_ = array();
    public $tag_start = 0;
    private $dom = null;

    function __construct($dom)
    {
        $this->dom = $dom;
        $dom->nodes[] = $this;
    }

    function __destruct()
    {
        $this->clear();
    }

    function __toString()
    {
        return $this->outertext();
    }

    // clean up memory due to php5 circular references memory leak...
    function clear()
    {
        $this->dom = null;
        $this->nodes = null;
        $this->parent = null;
        $this->children = null;
    }

    // dump node's tree
    function dump($show_attr=true, $deep=0)
    {
        $lead = str_repeat('    ', $deep);

        echo $lead.$this->tag;
        if ($show_attr && count($this->attr)>0)
        {
            echo '(';
            foreach ($this->attr as $k=>$v)
                echo "[$k]=>\"".$this->$k.'", ';
            echo ')';
        }
        echo "\n";

        foreach ($this->nodes as $c)
            $c->dump($show_attr, $deep+1);
    }


    // Debugging function to dump a single dom node with a bunch of information about it.
    function dump_node()
    {
        echo $this->tag;
        if (count($this->attr)>0)
        {
            echo '(';
            foreach ($this->attr as $k=>$v)
            {
                echo "[$k]=>\"".$this->$k.'", ';
            }
            echo ')';
        }
        if (count($this->attr)>0)
        {
            echo ' $_ (';
            foreach ($this->_ as $k=>$v)
            {
                if (is_array($v))
                {
                    echo "[$k]=>(";
                    foreach ($v as $k2=>$v2)
                    {
                        echo "[$k2]=>\"".$v2.'", ';
                    }
                    echo ")";
                } else {
                    echo "[$k]=>\"".$v.'", ';
                }
            }
            echo ")";
        }

        if (isset($this->text))
        {
            echo " text: (" . $this->text . ")";
        }

        echo " children: " . count($this->children);
        echo " nodes: " . count($this->nodes);
        echo " tag_start: " . $this->tag_start;
        echo "\n";

    }

    // returns the parent of node
    function parent()
    {
        return $this->parent;
    }

    // returns children of node
    function children($idx=-1)
    {
        if ($idx===-1) return $this->children;
        if (isset($this->children[$idx])) return $this->children[$idx];
        return null;
    }

    // returns the first child of node
    function first_child()
    {
        if (count($this->children)>0) return $this->children[0];
        return null;
    }

    // returns the last child of node
    function last_child()
    {
        if (($count=count($this->children))>0) return $this->children[$count-1];
        return null;
    }

    // returns the next sibling of node
    function next_sibling()
    {
        if ($this->parent===null) return null;
        $idx = 0;
        $count = count($this->parent->children);
        while ($idx<$count && $this!==$this->parent->children[$idx])
            ++$idx;
        if (++$idx>=$count) return null;
        return $this->parent->children[$idx];
    }

    // returns the previous sibling of node
    function prev_sibling()
    {
        if ($this->parent===null) return null;
        $idx = 0;
        $count = count($this->parent->children);
        while ($idx<$count && $this!==$this->parent->children[$idx])
            ++$idx;
        if (--$idx<0) return null;
        return $this->parent->children[$idx];
    }

    // function to locate a specific ancestor tag in the path to the root.
    function find_ancestor_tag($tag)
    {
        global $debugObject;
        if (is_object($debugObject))
        {
            $debugObject->debugLogEntry(1);
        }

        // Start by including ourselves in the comparison.
        $returnDom = $this;

        while (!is_null($returnDom))
        {
            if (is_object($debugObject))
            {
                $debugObject->debugLog(2, "Current tag is: " . $returnDom->tag);
            }

            if ($returnDom->tag == $tag)
            {
                break;
            }
            $returnDom = $returnDom->parent;
        }
        return $returnDom;
    }

    // get dom node's inner html
    function innertext()
    {
        if (isset($this->_[HDOM_INFO_INNER])) return $this->_[HDOM_INFO_INNER];
        if (isset($this->_[HDOM_INFO_TEXT])) return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);

        $ret = '';
        foreach ($this->nodes as $n)
            $ret .= $n->outertext();
        return $ret;
    }

    // get dom node's outer text (with tag)
    function outertext()
    {
        global $debugObject;
        if (is_object($debugObject))
        {
            $text = '';
            if ($this->tag == 'text')
            {
                if (!empty($this->text))
                {
                    $text = " with text: " . $this->text;
                }
            }
            $debugObject->debugLog(1, 'Innertext of tag: ' . $this->tag . $text);
        }

        if ($this->tag==='root') return $this->innertext();

        // trigger callback
        if ($this->dom && $this->dom->callback!==null)
        {
            call_user_func_array($this->dom->callback, array($this));
        }

        if (isset($this->_[HDOM_INFO_OUTER])) return $this->_[HDOM_INFO_OUTER];
        if (isset($this->_[HDOM_INFO_TEXT])) return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);

        // render begin tag
        if ($this->dom && $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]])
        {
            $ret = $this->dom->nodes[$this->_[HDOM_INFO_BEGIN]]->makeup();
        } else {
            $ret = "";
        }

        // render inner text
        if (isset($this->_[HDOM_INFO_INNER]))
        {
            // If it's a br tag...  don't return the HDOM_INNER_INFO that we may or may not have added.
            if ($this->tag != "br")
            {
                $ret .= $this->_[HDOM_INFO_INNER];
            }
        } else {
            if ($this->nodes)
            {
                foreach ($this->nodes as $n)
                {
                    $ret .= $this->convert_text($n->outertext());
                }
            }
        }

        // render end tag
        if (isset($this->_[HDOM_INFO_END]) && $this->_[HDOM_INFO_END]!=0)
            $ret .= '</'.$this->tag.'>';
        return $ret;
    }

    // get dom node's plain text
    function text()
    {
        if (isset($this->_[HDOM_INFO_INNER])) return $this->_[HDOM_INFO_INNER];
        switch ($this->nodetype)
        {
            case HDOM_TYPE_TEXT: return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);
            case HDOM_TYPE_COMMENT: return '';
            case HDOM_TYPE_UNKNOWN: return '';
        }
        if (strcasecmp($this->tag, 'script')===0) return '';
        if (strcasecmp($this->tag, 'style')===0) return '';

        $ret = '';
        // In rare cases, (always node type 1 or HDOM_TYPE_ELEMENT - observed for some span tags, and some p tags) $this->nodes is set to NULL.
        // NOTE: This indicates that there is a problem where it's set to NULL without a clear happening.
        // WHY is this happening?
        if (!is_null($this->nodes))
        {
            foreach ($this->nodes as $n)
            {
                $ret .= $this->convert_text($n->text());
            }
        }
        return $ret;
    }

    function xmltext()
    {
        $ret = $this->innertext();
        $ret = str_ireplace('<![CDATA[', '', $ret);
        $ret = str_replace(']]>', '', $ret);
        return $ret;
    }

    // build node's text with tag
    function makeup()
    {
        // text, comment, unknown
        if (isset($this->_[HDOM_INFO_TEXT])) return $this->dom->restore_noise($this->_[HDOM_INFO_TEXT]);

        $ret = '<'.$this->tag;
        $i = -1;

        foreach ($this->attr as $key=>$val)
        {
            ++$i;

            // skip removed attribute
            if ($val===null || $val===false)
                continue;

            $ret .= $this->_[HDOM_INFO_SPACE][$i][0];
            //no value attr: nowrap, checked selected...
            if ($val===true)
                $ret .= $key;
            else {
                switch ($this->_[HDOM_INFO_QUOTE][$i])
                {
                    case HDOM_QUOTE_DOUBLE: $quote = '"'; break;
                    case HDOM_QUOTE_SINGLE: $quote = '\''; break;
                    default: $quote = '';
                }
                $ret .= $key.$this->_[HDOM_INFO_SPACE][$i][1].'='.$this->_[HDOM_INFO_SPACE][$i][2].$quote.$val.$quote;
            }
        }
        $ret = $this->dom->restore_noise($ret);
        return $ret . $this->_[HDOM_INFO_ENDSPACE] . '>';
    }

    // find elements by css selector
    //PaperG - added ability for find to lowercase the value of the selector.
    function find($selector, $idx=null, $lowercase=false)
    {
        $selectors = $this->parse_selector($selector);
        if (($count=count($selectors))===0) return array();
        $found_keys = array();

        // find each selector
        for ($c=0; $c<$count; ++$c)
        {
            // The change on the below line was documented on the sourceforge code tracker id 2788009
            // used to be: if (($levle=count($selectors[0]))===0) return array();
            if (($levle=count($selectors[$c]))===0) return array();
            if (!isset($this->_[HDOM_INFO_BEGIN])) return array();

            $head = array($this->_[HDOM_INFO_BEGIN]=>1);

            // handle descendant selectors, no recursive!
            for ($l=0; $l<$levle; ++$l)
            {
                $ret = array();
                foreach ($head as $k=>$v)
                {
                    $n = ($k===-1) ? $this->dom->root : $this->dom->nodes[$k];
                    //PaperG - Pass this optional parameter on to the seek function.
                    $n->seek($selectors[$c][$l], $ret, $lowercase);
                }
                $head = $ret;
            }

            foreach ($head as $k=>$v)
            {
                if (!isset($found_keys[$k]))
                    $found_keys[$k] = 1;
            }
        }

        // sort keys
        ksort($found_keys);

        $found = array();
        foreach ($found_keys as $k=>$v)
            $found[] = $this->dom->nodes[$k];

        // return nth-element or array
        if (is_null($idx)) return $found;
        else if ($idx<0) $idx = count($found) + $idx;
        return (isset($found[$idx])) ? $found[$idx] : null;
    }

    // seek for given conditions
    // PaperG - added parameter to allow for case insensitive testing of the value of a selector.
    protected function seek($selector, &$ret, $lowercase=false)
    {
        global $debugObject;
        if (is_object($debugObject))
        {
            $debugObject->debugLogEntry(1);
        }

        list($tag, $key, $val, $exp, $no_key) = $selector;

        // xpath index
        if ($tag && $key && is_numeric($key))
        {
            $count = 0;
            foreach ($this->children as $c)
            {
                if ($tag==='*' || $tag===$c->tag) {
                    if (++$count==$key) {
                        $ret[$c->_[HDOM_INFO_BEGIN]] = 1;
                        return;
                    }
                }
            }
            return;
        }

        $end = (!empty($this->_[HDOM_INFO_END])) ? $this->_[HDOM_INFO_END] : 0;
        if ($end==0) {
            $parent = $this->parent;
            while (!isset($parent->_[HDOM_INFO_END]) && $parent!==null) {
                $end -= 1;
                $parent = $parent->parent;
            }
            $end += $parent->_[HDOM_INFO_END];
        }

        for ($i=$this->_[HDOM_INFO_BEGIN]+1; $i<$end; ++$i) {
            $node = $this->dom->nodes[$i];

            $pass = true;

            if ($tag==='*' && !$key) {
                if (in_array($node, $this->children, true))
                    $ret[$i] = 1;
                continue;
            }

            // compare tag
            if ($tag && $tag!=$node->tag && $tag!=='*') {$pass=false;}
            // compare key
            if ($pass && $key) {
                if ($no_key) {
                    if (isset($node->attr[$key])) $pass=false;
                } else {
                    if (($key != "plaintext") && !isset($node->attr[$key])) $pass=false;
                }
            }
            // compare value
            if ($pass && $key && $val  && $val!=='*') {
                // If they have told us that this is a "plaintext" search then we want the plaintext of the node - right?
                if ($key == "plaintext") {
                    // $node->plaintext actually returns $node->text();
                    $nodeKeyValue = $node->text();
                } else {
                    // this is a normal search, we want the value of that attribute of the tag.
                    $nodeKeyValue = $node->attr[$key];
                }
                if (is_object($debugObject)) {$debugObject->debugLog(2, "testing node: " . $node->tag . " for attribute: " . $key . $exp . $val . " where nodes value is: " . $nodeKeyValue);}

                //PaperG - If lowercase is set, do a case insensitive test of the value of the selector.
                if ($lowercase) {
                    $check = $this->match($exp, strtolower($val), strtolower($nodeKeyValue));
                } else {
                    $check = $this->match($exp, $val, $nodeKeyValue);
                }
                if (is_object($debugObject)) {$debugObject->debugLog(2, "after match: " . ($check ? "true" : "false"));}

                // handle multiple class
                if (!$check && strcasecmp($key, 'class')===0) {
                    foreach (explode(' ',$node->attr[$key]) as $k) {
                        // Without this, there were cases where leading, trailing, or double spaces lead to our comparing blanks - bad form.
                        if (!empty($k)) {
                            if ($lowercase) {
                                $check = $this->match($exp, strtolower($val), strtolower($k));
                            } else {
                                $check = $this->match($exp, $val, $k);
                            }
                            if ($check) break;
                        }
                    }
                }
                if (!$check) $pass = false;
            }
            if ($pass) $ret[$i] = 1;
            unset($node);
        }
        // It's passed by reference so this is actually what this function returns.
        if (is_object($debugObject)) {$debugObject->debugLog(1, "EXIT - ret: ", $ret);}
    }

    protected function match($exp, $pattern, $value) {
        global $debugObject;
        if (is_object($debugObject)) {$debugObject->debugLogEntry(1);}

        switch ($exp) {
            case '=':
                return ($value===$pattern);
            case '!=':
                return ($value!==$pattern);
            case '^=':
                return preg_match("/^".preg_quote($pattern,'/')."/", $value);
            case '$=':
                return preg_match("/".preg_quote($pattern,'/')."$/", $value);
            case '*=':
                if ($pattern[0]=='/') {
                    return preg_match($pattern, $value);
                }
                return preg_match("/".$pattern."/i", $value);
        }
        return false;
    }

    protected function parse_selector($selector_string) {
        global $debugObject;
        if (is_object($debugObject)) {$debugObject->debugLogEntry(1);}

        // pattern of CSS selectors, modified from mootools
        // Paperg: Add the colon to the attrbute, so that it properly finds <tag attr:ibute="something" > like google does.
        // Note: if you try to look at this attribute, yo MUST use getAttribute since $dom->x:y will fail the php syntax check.
// Notice the \[ starting the attbute?  and the @? following?  This implies that an attribute can begin with an @ sign that is not captured.
// This implies that an html attribute specifier may start with an @ sign that is NOT captured by the expression.
// farther study is required to determine of this should be documented or removed.
//        $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-:]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        preg_match_all($pattern, trim($selector_string).' ', $matches, PREG_SET_ORDER);
        if (is_object($debugObject)) {$debugObject->debugLog(2, "Matches Array: ", $matches);}

        $selectors = array();
        $result = array();
        //print_r($matches);

        foreach ($matches as $m) {
            $m[0] = trim($m[0]);
            if ($m[0]==='' || $m[0]==='/' || $m[0]==='//') continue;
            // for browser generated xpath
            if ($m[1]==='tbody') continue;

            list($tag, $key, $val, $exp, $no_key) = array($m[1], null, null, '=', false);
            if (!empty($m[2])) {$key='id'; $val=$m[2];}
            if (!empty($m[3])) {$key='class'; $val=$m[3];}
            if (!empty($m[4])) {$key=$m[4];}
            if (!empty($m[5])) {$exp=$m[5];}
            if (!empty($m[6])) {$val=$m[6];}

            // convert to lowercase
            if ($this->dom->lowercase) {$tag=strtolower($tag); $key=strtolower($key);}
            //elements that do NOT have the specified attribute
            if (isset($key[0]) && $key[0]==='!') {$key=substr($key, 1); $no_key=true;}

            $result[] = array($tag, $key, $val, $exp, $no_key);
            if (trim($m[7])===',') {
                $selectors[] = $result;
                $result = array();
            }
        }
        if (count($result)>0)
            $selectors[] = $result;
        return $selectors;
    }

    function __get($name) {
        if (isset($this->attr[$name]))
        {
            return $this->convert_text($this->attr[$name]);
        }
        switch ($name) {
            case 'outertext': return $this->outertext();
            case 'innertext': return $this->innertext();
            case 'plaintext': return $this->text();
            case 'xmltext': return $this->xmltext();
            default: return array_key_exists($name, $this->attr);
        }
    }

    function __set($name, $value) {
        switch ($name) {
            case 'outertext': return $this->_[HDOM_INFO_OUTER] = $value;
            case 'innertext':
                if (isset($this->_[HDOM_INFO_TEXT])) return $this->_[HDOM_INFO_TEXT] = $value;
                return $this->_[HDOM_INFO_INNER] = $value;
        }
        if (!isset($this->attr[$name])) {
            $this->_[HDOM_INFO_SPACE][] = array(' ', '', '');
            $this->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
        }
        $this->attr[$name] = $value;
    }

    function __isset($name) {
        switch ($name) {
            case 'outertext': return true;
            case 'innertext': return true;
            case 'plaintext': return true;
        }
        //no value attr: nowrap, checked selected...
        return (array_key_exists($name, $this->attr)) ? true : isset($this->attr[$name]);
    }

    function __unset($name) {
        if (isset($this->attr[$name]))
            unset($this->attr[$name]);
    }

    // PaperG - Function to convert the text from one character set to another if the two sets are not the same.
    function convert_text($text) {
        global $debugObject;
        if (is_object($debugObject)) {$debugObject->debugLogEntry(1);}

        $converted_text = $text;

        $sourceCharset = "";
        $targetCharset = "";
        if ($this->dom) {
            $sourceCharset = strtoupper($this->dom->_charset);
            $targetCharset = strtoupper($this->dom->_target_charset);
        }
        if (is_object($debugObject)) {$debugObject->debugLog(3, "source charset: " . $sourceCharset . " target charaset: " . $targetCharset);}

        if (!empty($sourceCharset) && !empty($targetCharset) && (strcasecmp($sourceCharset, $targetCharset) != 0))
        {
            // Check if the reported encoding could have been incorrect and the text is actually already UTF-8
            if ((strcasecmp($targetCharset, 'UTF-8') == 0) && ($this->is_utf8($text)))
            {
                $converted_text = $text;
            }
            else
            {
                $converted_text = iconv($sourceCharset, $targetCharset, $text);
            }
        }

        return $converted_text;
    }

    function is_utf8($string)
    {
        return (utf8_encode(utf8_decode($string)) == $string);
    }

    // camel naming conventions
    function getAllAttributes() {return $this->attr;}
    function getAttribute($name) {return $this->__get($name);}
    function setAttribute($name, $value) {$this->__set($name, $value);}
    function hasAttribute($name) {return $this->__isset($name);}
    function removeAttribute($name) {$this->__set($name, null);}
    function getElementById($id) {return $this->find("#$id", 0);}
    function getElementsById($id, $idx=null) {return $this->find("#$id", $idx);}
    function getElementByTagName($name) {return $this->find($name, 0);}
    function getElementsByTagName($name, $idx=null) {return $this->find($name, $idx);}
    function parentNode() {return $this->parent();}
    function childNodes($idx=-1) {return $this->children($idx);}
    function firstChild() {return $this->first_child();}
    function lastChild() {return $this->last_child();}
    function nextSibling() {return $this->next_sibling();}
    function previousSibling() {return $this->prev_sibling();}
}

/**
 * simple html dom parser
 * Paperg - in the find routine: allow us to specify that we want case insensitive testing of the value of the selector.
 * Paperg - change $size from protected to public so we can easily access it
 * Paperg - added ForceTagsClosed in the constructor which tells us whether we trust the html or not.  Default is to NOT trust it.
 *
 * @package PlaceLocalInclude
 */
class simple_html_dom {
    public $root = null;
    public $nodes = array();
    public $callback = null;
    public $lowercase = false;
    public $size;
    protected $pos;
    protected $doc;
    protected $char;
    protected $cursor;
    protected $parent;
    protected $noise = array();
    protected $token_blank = " \t\r\n";
    protected $token_equal = ' =/>';
    protected $token_slash = " />\r\n\t";
    protected $token_attr = ' >';
    protected $_charset = '';
    protected $_target_charset = '';
    protected $default_br_text = "";

    // use isset instead of in_array, performance boost about 30%...
    protected $self_closing_tags = array('img'=>1, 'br'=>1, 'input'=>1, 'meta'=>1, 'link'=>1, 'hr'=>1, 'base'=>1, 'embed'=>1, 'spacer'=>1);
    protected $block_tags = array('root'=>1, 'body'=>1, 'form'=>1, 'div'=>1, 'span'=>1, 'table'=>1);
    // Known sourceforge issue #2977341
    // B tags that are not closed cause us to return everything to the end of the document.
    protected $optional_closing_tags = array(
        'tr'=>array('tr'=>1, 'td'=>1, 'th'=>1),
        'th'=>array('th'=>1),
        'td'=>array('td'=>1),
        'li'=>array('li'=>1),
        'dt'=>array('dt'=>1, 'dd'=>1),
        'dd'=>array('dd'=>1, 'dt'=>1),
        'dl'=>array('dd'=>1, 'dt'=>1),
        'p'=>array('p'=>1),
        'nobr'=>array('nobr'=>1),
        'b'=>array('b'=>1),
    );

    function __construct($str=null, $lowercase=true, $forceTagsClosed=true, $target_charset=DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT) {
        if ($str) {
            if (preg_match("/^http:\/\//i",$str) || is_file($str))
                $this->load_file($str);
            else
                $this->load($str, $lowercase, $stripRN, $defaultBRText);
        }
        // Forcing tags to be closed implies that we don't trust the html, but it can lead to parsing errors if we SHOULD trust the html.
        if (!$forceTagsClosed) {
            $this->optional_closing_array=array();
        }
        $this->_target_charset = $target_charset;
    }

    function __destruct() {
        $this->clear();
    }

    // load html from string
    function load($str, $lowercase=true, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT) {
        global $debugObject;

        // prepare
        $this->prepare($str, $lowercase, $stripRN, $defaultBRText);
        // strip out comments
        $this->remove_noise("'<!--(.*?)-->'is");
        // strip out cdata
        $this->remove_noise("'<!\[CDATA\[(.*?)\]\]>'is", true);
        // Per sourceforge http://sourceforge.net/tracker/?func=detail&aid=2949097&group_id=218559&atid=1044037
        // Script tags removal now preceeds style tag removal.
        // strip out <script> tags
        $this->remove_noise("'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is");
        $this->remove_noise("'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is");
        // strip out <style> tags
        $this->remove_noise("'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is");
        $this->remove_noise("'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is");
        // strip out preformatted tags
        $this->remove_noise("'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is");
        // strip out server side scripts
        $this->remove_noise("'(<\?)(.*?)(\?>)'s", true);
        // strip smarty scripts
        $this->remove_noise("'(\{\w)(.*?)(\})'s", true);

        // parsing
        while ($this->parse());
        // end
        $this->root->_[HDOM_INFO_END] = $this->cursor;
        $this->parse_charset();
    }

    // load html from file
    function load_file() {
        $args = func_get_args();
        $this->load(call_user_func_array('file_get_contents', $args), true);
        // Per the simple_html_dom repositiry this is a planned upgrade to the codebase.
        // Throw an error if we can't properly load the dom.
        if (($error=error_get_last())!==null) {
            $this->clear();
            return false;
        }
    }

    // set callback function
    function set_callback($function_name) {
        $this->callback = $function_name;
    }

    // remove callback function
    function remove_callback() {
        $this->callback = null;
    }

    // save dom as string
    function save($filepath='') {
        $ret = $this->root->innertext();
        if ($filepath!=='') file_put_contents($filepath, $ret, LOCK_EX);
        return $ret;
    }

    // find dom node by css selector
    // Paperg - allow us to specify that we want case insensitive testing of the value of the selector.
    function find($selector, $idx=null, $lowercase=false) {
        return $this->root->find($selector, $idx, $lowercase);
    }

    // clean up memory due to php5 circular references memory leak...
    function clear() {
        foreach ($this->nodes as $n) {$n->clear(); $n = null;}
        // This add next line is documented in the sourceforge repository. 2977248 as a fix for ongoing memory leaks that occur even with the use of clear.
        if (isset($this->children)) foreach ($this->children as $n) {$n->clear(); $n = null;}
        if (isset($this->parent)) {$this->parent->clear(); unset($this->parent);}
        if (isset($this->root)) {$this->root->clear(); unset($this->root);}
        unset($this->doc);
        unset($this->noise);
    }

    function dump($show_attr=true) {
        $this->root->dump($show_attr);
    }

    // prepare HTML data and init everything
    protected function prepare($str, $lowercase=true, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT) {
        $this->clear();

        // set the length of content before we do anything to it.
        $this->size = strlen($str);

        //before we save the string as the doc...  strip out the \r \n's if we are told to.
        if ($stripRN) {
            $str = str_replace("\r", " ", $str);
            $str = str_replace("\n", " ", $str);
        }

        $this->doc = $str;
        $this->pos = 0;
        $this->cursor = 1;
        $this->noise = array();
        $this->nodes = array();
        $this->lowercase = $lowercase;
        $this->default_br_text = $defaultBRText;
        $this->root = new simple_html_dom_node($this);
        $this->root->tag = 'root';
        $this->root->_[HDOM_INFO_BEGIN] = -1;
        $this->root->nodetype = HDOM_TYPE_ROOT;
        $this->parent = $this->root;
        if ($this->size>0) $this->char = $this->doc[0];
    }

    // parse html content
    protected function parse() {
        if (($s = $this->copy_until_char('<'))==='')
            return $this->read_tag();

        // text
        $node = new simple_html_dom_node($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = $s;
        $this->link_nodes($node, false);
        return true;
    }

    // PAPERG - dkchou - added this to try to identify the character set of the page we have just parsed so we know better how to spit it out later.
    // NOTE:  IF you provide a routine called get_last_retrieve_url_contents_content_type which returns the CURLINFO_CONTENT_TYPE fromt he last curl_exec
    // (or the content_type header fromt eh last transfer), we will parse THAT, and if a charset is specified, we will use it over any other mechanism.
    protected function parse_charset()
    {
        global $debugObject;

        $charset = null;

        if (function_exists('get_last_retrieve_url_contents_content_type'))
        {
            $contentTypeHeader = get_last_retrieve_url_contents_content_type();
            $success = preg_match('/charset=(.+)/', $contentTypeHeader, $matches);
            if ($success)
            {
                $charset = $matches[1];
                if (is_object($debugObject)) {$debugObject->debugLog(2, 'header content-type found charset of: ' . $charset);}
            }

        }

        if (empty($charset))
        {
            $el = $this->root->find('meta[http-equiv=Content-Type]',0);
            if (!empty($el))
            {
                $fullvalue = $el->content;
                if (is_object($debugObject)) {$debugObject->debugLog(2, 'meta content-type tag found' . $fullValue);}

                if (!empty($fullvalue))
                {
                    $success = preg_match('/charset=(.+)/', $fullvalue, $matches);
                    if ($success)
                    {
                        $charset = $matches[1];
                    }
                    else
                    {
                        // If there is a meta tag, and they don't specify the character set, research says that it's typically ISO-8859-1
                        if (is_object($debugObject)) {$debugObject->debugLog(2, 'meta content-type tag couldn\'t be parsed. using iso-8859 default.');}
                        $charset = 'ISO-8859-1';
                    }
                }
            }
        }

        // If we couldn't find a charset above, then lets try to detect one based on the text we got...
        if (empty($charset))
        {
            // Have php try to detect the encoding from the text given to us.
            $charset = mb_detect_encoding($this->root->plaintext . "ascii", $encoding_list = array( "UTF-8", "CP1252" ) );
            if (is_object($debugObject)) {$debugObject->debugLog(2, 'mb_detect found: ' . $charset);}

            // and if this doesn't work...  then we need to just wrongheadedly assume it's UTF-8 so that we can move on - cause this will usually give us most of what we need...
            if ($charset === false)
            {
                if (is_object($debugObject)) {$debugObject->debugLog(2, 'since mb_detect failed - using default of utf-8');}
                $charset = 'UTF-8';
            }
        }

        // Since CP1252 is a superset, if we get one of it's subsets, we want it instead.
        if ((strtolower($charset) == strtolower('ISO-8859-1')) || (strtolower($charset) == strtolower('Latin1')) || (strtolower($charset) == strtolower('Latin-1')))
        {
            if (is_object($debugObject)) {$debugObject->debugLog(2, 'replacing ' . $charset . ' with CP1252 as its a superset');}
            $charset = 'CP1252';
        }

        if (is_object($debugObject)) {$debugObject->debugLog(1, 'EXIT - ' . $charset);}

        return $this->_charset = $charset;
    }

    // read tag info
    protected function read_tag() {
        if ($this->char!=='<') {
            $this->root->_[HDOM_INFO_END] = $this->cursor;
            return false;
        }
        $begin_tag_pos = $this->pos;
        $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next

        // end tag
        if ($this->char==='/') {
            $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
            // This represetns the change in the simple_html_dom trunk from revision 180 to 181.
            // $this->skip($this->token_blank_t);
            $this->skip($this->token_blank);
            $tag = $this->copy_until_char('>');

            // skip attributes in end tag
            if (($pos = strpos($tag, ' '))!==false)
                $tag = substr($tag, 0, $pos);

            $parent_lower = strtolower($this->parent->tag);
            $tag_lower = strtolower($tag);

            if ($parent_lower!==$tag_lower) {
                if (isset($this->optional_closing_tags[$parent_lower]) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag)!==$tag_lower)
                        $this->parent = $this->parent->parent;

                    if (strtolower($this->parent->tag)!==$tag_lower) {
                        $this->parent = $org_parent; // restore origonal parent
                        if ($this->parent->parent) $this->parent = $this->parent->parent;
                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->as_text_node($tag);
                    }
                }
                else if (($this->parent->parent) && isset($this->block_tags[$tag_lower])) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $org_parent = $this->parent;

                    while (($this->parent->parent) && strtolower($this->parent->tag)!==$tag_lower)
                        $this->parent = $this->parent->parent;

                    if (strtolower($this->parent->tag)!==$tag_lower) {
                        $this->parent = $org_parent; // restore origonal parent
                        $this->parent->_[HDOM_INFO_END] = $this->cursor;
                        return $this->as_text_node($tag);
                    }
                }
                else if (($this->parent->parent) && strtolower($this->parent->parent->tag)===$tag_lower) {
                    $this->parent->_[HDOM_INFO_END] = 0;
                    $this->parent = $this->parent->parent;
                }
                else
                    return $this->as_text_node($tag);
            }

            $this->parent->_[HDOM_INFO_END] = $this->cursor;
            if ($this->parent->parent) $this->parent = $this->parent->parent;

            $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        $node = new simple_html_dom_node($this);
        $node->_[HDOM_INFO_BEGIN] = $this->cursor;
        ++$this->cursor;
        $tag = $this->copy_until($this->token_slash);
        $node->tag_start = $begin_tag_pos;

        // doctype, cdata & comments...
        if (isset($tag[0]) && $tag[0]==='!') {
            $node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copy_until_char('>');

            if (isset($tag[2]) && $tag[1]==='-' && $tag[2]==='-') {
                $node->nodetype = HDOM_TYPE_COMMENT;
                $node->tag = 'comment';
            } else {
                $node->nodetype = HDOM_TYPE_UNKNOWN;
                $node->tag = 'unknown';
            }
            if ($this->char==='>') $node->_[HDOM_INFO_TEXT].='>';
            $this->link_nodes($node, true);
            $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        // text
        if ($pos=strpos($tag, '<')!==false) {
            $tag = '<' . substr($tag, 0, -1);
            $node->_[HDOM_INFO_TEXT] = $tag;
            $this->link_nodes($node, false);
            $this->char = $this->doc[--$this->pos]; // prev
            return true;
        }

        if (!preg_match("/^[\w-:]+$/", $tag)) {
            $node->_[HDOM_INFO_TEXT] = '<' . $tag . $this->copy_until('<>');
            if ($this->char==='<') {
                $this->link_nodes($node, false);
                return true;
            }

            if ($this->char==='>') $node->_[HDOM_INFO_TEXT].='>';
            $this->link_nodes($node, false);
            $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
            return true;
        }

        // begin tag
        $node->nodetype = HDOM_TYPE_ELEMENT;
        $tag_lower = strtolower($tag);
        $node->tag = ($this->lowercase) ? $tag_lower : $tag;

        // handle optional closing tags
        if (isset($this->optional_closing_tags[$tag_lower]) ) {
            while (isset($this->optional_closing_tags[$tag_lower][strtolower($this->parent->tag)])) {
                $this->parent->_[HDOM_INFO_END] = 0;
                $this->parent = $this->parent->parent;
            }
            $node->parent = $this->parent;
        }

        $guard = 0; // prevent infinity loop
        $space = array($this->copy_skip($this->token_blank), '', '');

        // attributes
        do
        {
            if ($this->char!==null && $space[0]==='') break;
            $name = $this->copy_until($this->token_equal);
            if ($guard===$this->pos) {
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                continue;
            }
            $guard = $this->pos;

            // handle endless '<'
            if ($this->pos>=$this->size-1 && $this->char!=='>') {
                $node->nodetype = HDOM_TYPE_TEXT;
                $node->_[HDOM_INFO_END] = 0;
                $node->_[HDOM_INFO_TEXT] = '<'.$tag . $space[0] . $name;
                $node->tag = 'text';
                $this->link_nodes($node, false);
                return true;
            }

            // handle mismatch '<'
            if ($this->doc[$this->pos-1]=='<') {
                $node->nodetype = HDOM_TYPE_TEXT;
                $node->tag = 'text';
                $node->attr = array();
                $node->_[HDOM_INFO_END] = 0;
                $node->_[HDOM_INFO_TEXT] = substr($this->doc, $begin_tag_pos, $this->pos-$begin_tag_pos-1);
                $this->pos -= 2;
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                $this->link_nodes($node, false);
                return true;
            }

            if ($name!=='/' && $name!=='') {
                $space[1] = $this->copy_skip($this->token_blank);
                $name = $this->restore_noise($name);
                if ($this->lowercase) $name = strtolower($name);
                if ($this->char==='=') {
                    $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                    $this->parse_attr($node, $name, $space);
                }
                else {
                    //no value attr: nowrap, checked selected...
                    $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
                    $node->attr[$name] = true;
                    if ($this->char!='>') $this->char = $this->doc[--$this->pos]; // prev
                }
                $node->_[HDOM_INFO_SPACE][] = $space;
                $space = array($this->copy_skip($this->token_blank), '', '');
            }
            else
                break;
        } while ($this->char!=='>' && $this->char!=='/');

        $this->link_nodes($node, true);
        $node->_[HDOM_INFO_ENDSPACE] = $space[0];

        // check self closing
        if ($this->copy_until_char_escape('>')==='/') {
            $node->_[HDOM_INFO_ENDSPACE] .= '/';
            $node->_[HDOM_INFO_END] = 0;
        }
        else {
            // reset parent
            if (!isset($this->self_closing_tags[strtolower($node->tag)])) $this->parent = $node;
        }
        $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next

        // If it's a BR tag, we need to set it's text to the default text.
        // This way when we see it in plaintext, we can generate formatting that the user wants.
        if ($node->tag == "br") {
            $node->_[HDOM_INFO_INNER] = $this->default_br_text;
        }

        return true;
    }

    // parse attributes
    protected function parse_attr($node, $name, &$space) {
        // Per sourceforge: http://sourceforge.net/tracker/?func=detail&aid=3061408&group_id=218559&atid=1044037
        // If the attribute is already defined inside a tag, only pay atetntion to the first one as opposed to the last one.
        if (isset($node->attr[$name]))
        {
            return;
        }

        $space[2] = $this->copy_skip($this->token_blank);
        switch ($this->char) {
            case '"':
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_DOUBLE;
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                $node->attr[$name] = $this->restore_noise($this->copy_until_char_escape('"'));
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                break;
            case '\'':
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_SINGLE;
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                $node->attr[$name] = $this->restore_noise($this->copy_until_char_escape('\''));
                $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
                break;
            default:
                $node->_[HDOM_INFO_QUOTE][] = HDOM_QUOTE_NO;
                $node->attr[$name] = $this->restore_noise($this->copy_until($this->token_attr));
        }
        // PaperG: Attributes should not have \r or \n in them, that counts as html whitespace.
        $node->attr[$name] = str_replace("\r", "", $node->attr[$name]);
        $node->attr[$name] = str_replace("\n", "", $node->attr[$name]);
        // PaperG: If this is a "class" selector, lets get rid of the preceeding and trailing space since some people leave it in the multi class case.
        if ($name == "class") {
            $node->attr[$name] = trim($node->attr[$name]);
        }
    }

    // link node's parent
    protected function link_nodes(&$node, $is_child) {
        $node->parent = $this->parent;
        $this->parent->nodes[] = $node;
        if ($is_child)
            $this->parent->children[] = $node;
    }

    // as a text node
    protected function as_text_node($tag) {
        $node = new simple_html_dom_node($this);
        ++$this->cursor;
        $node->_[HDOM_INFO_TEXT] = '</' . $tag . '>';
        $this->link_nodes($node, false);
        $this->char = (++$this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
        return true;
    }

    protected function skip($chars) {
        $this->pos += strspn($this->doc, $chars, $this->pos);
        $this->char = ($this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
    }

    protected function copy_skip($chars) {
        $pos = $this->pos;
        $len = strspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
        if ($len===0) return '';
        return substr($this->doc, $pos, $len);
    }

    protected function copy_until($chars) {
        $pos = $this->pos;
        $len = strcspn($this->doc, $chars, $pos);
        $this->pos += $len;
        $this->char = ($this->pos<$this->size) ? $this->doc[$this->pos] : null; // next
        return substr($this->doc, $pos, $len);
    }

    protected function copy_until_char($char) {
        if ($this->char===null) return '';

        if (($pos = strpos($this->doc, $char, $this->pos))===false) {
            $ret = substr($this->doc, $this->pos, $this->size-$this->pos);
            $this->char = null;
            $this->pos = $this->size;
            return $ret;
        }

        if ($pos===$this->pos) return '';
        $pos_old = $this->pos;
        $this->char = $this->doc[$pos];
        $this->pos = $pos;
        return substr($this->doc, $pos_old, $pos-$pos_old);
    }

    protected function copy_until_char_escape($char) {
        if ($this->char===null) return '';

        $start = $this->pos;
        while (1) {
            if (($pos = strpos($this->doc, $char, $start))===false) {
                $ret = substr($this->doc, $this->pos, $this->size-$this->pos);
                $this->char = null;
                $this->pos = $this->size;
                return $ret;
            }

            if ($pos===$this->pos) return '';

            if ($this->doc[$pos-1]==='\\') {
                $start = $pos+1;
                continue;
            }

            $pos_old = $this->pos;
            $this->char = $this->doc[$pos];
            $this->pos = $pos;
            return substr($this->doc, $pos_old, $pos-$pos_old);
        }
    }

    // remove noise from html content
    protected function remove_noise($pattern, $remove_tag=false) {
        $count = preg_match_all($pattern, $this->doc, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

        for ($i=$count-1; $i>-1; --$i) {
            $key = '___noise___'.sprintf('% 3d', count($this->noise)+100);
            $idx = ($remove_tag) ? 0 : 1;
            $this->noise[$key] = $matches[$i][$idx][0];
            $this->doc = substr_replace($this->doc, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
        }

        // reset the length of content
        $this->size = strlen($this->doc);
        if ($this->size>0) $this->char = $this->doc[0];
    }

    // restore noise to html content
    function restore_noise($text) {
        while (($pos=strpos($text, '___noise___'))!==false) {
            $key = '___noise___'.$text[$pos+11].$text[$pos+12].$text[$pos+13];
            if (isset($this->noise[$key]))
                $text = substr($text, 0, $pos).$this->noise[$key].substr($text, $pos+14);
        }
        return $text;
    }

    function __toString() {
        return $this->root->innertext();
    }

    function __get($name) {
        switch ($name) {
            case 'outertext':
                return $this->root->innertext();
            case 'innertext':
                return $this->root->innertext();
            case 'plaintext':
                return $this->root->text();
            case 'charset':
                return $this->_charset;
            case 'target_charset':
                return $this->_target_charset;
        }
    }

    // camel naming conventions
    function childNodes($idx=-1) {return $this->root->childNodes($idx);}
    function firstChild() {return $this->root->first_child();}
    function lastChild() {return $this->root->last_child();}
    function getElementById($id) {return $this->find("#$id", 0);}
    function getElementsById($id, $idx=null) {return $this->find("#$id", $idx);}
    function getElementByTagName($name) {return $this->find($name, 0);}
    function getElementsByTagName($name, $idx=-1) {return $this->find($name, $idx);}
    function loadFile() {$args = func_get_args();$this->load_file($args);}
}

?>
