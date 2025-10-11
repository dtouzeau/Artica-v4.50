<?php
require_once(dirname(__FILE__)."/OAuth.php");

// http://www.brightcloud.com/support/catdescription.php
$GLOBALS["ayBrightCloudCats"]= array(
	'0'		=> 999,
	'1'		=> 60,
	'2'		=> 38,
	'3'		=> 31,
	'4'		=> 21,
	'5'		=> 38,
	'6'		=> 59,
	'7'		=> 58,
	'8'		=> 22,
	'9'		=> 66,
	'10'	=> 25,
	'11'	=> 3,
	'12'	=> 58,
	'13'	=> 35,
	'14'	=> 55,
	'15'	=> 998,
	'16'	=> 32,
	'17'	=> 27,
	'18'	=> 47,
	'19'	=> 4,
	'20'	=> 54,
	'21'	=> 20,
	'22'	=> 63,
	'23'	=> 34,
	'24'	=> 66,
	'25'	=> 84,
	'26'	=> 45,
	'27'	=> 11,
	'28'	=> 95,
	'29'	=> 49,
	'30'	=> 71,
	'31'	=> 83,
	'32'	=> 25,
	'33'	=> 17,
	'34'	=> 33,
	'35'	=> 36,
	'36'	=> 15,
	'37'	=> 72,
	'38'	=> 65,
	'39'	=> 61,
	'40'	=> 27,
	'41'	=> 106,
	'42'	=> 65,
	'43'	=> 5,
	'44'	=> 9,
	'45'	=> 87,
	'46'	=> 16,
	'47'	=> 56,
	'48'	=> 16,
	'49'	=> 43,
	'50'	=> 40,
	'51'	=> 40,
	'52'	=> 88,
	'53'	=> 51,
	'54'	=> 110,
	'55'	=> 52,
	'56'	=> 43,
	'57'	=> 18,
	'58'	=> 86,
	'59'	=> 43,
	'60'	=> 20,
	'61'	=> 34,
	'62'	=> 6,
	'63'	=> 46,
	'64'	=> 38,
	'65'	=> 97,
	'66'	=> 110,
	'67'	=> 43,
	'68'	=> 16,
	'69'	=> 37,
	'70'	=> 101,
	'71'	=> 101,
	'72'	=> 92,
	'73'	=> 86,
	'74'	=> 103,
	'75'	=> 98,
	'76'	=> 25,
	'77'	=> 505,
	'78'	=> 602,
	'79'	=> 506,
	'80'	=> 65,
	'81'	=> 67,
	'82'	=> 89,
);

function UBoxBrightcloudGetiCat($szCat)
{
	$ayBrightCloudCats = $GLOBALS["ayBrightCloudCats"];

	return (isset($ayBrightCloudCats[$szCat]) ? $ayBrightCloudCats[$szCat] : 0);
}

function UBoxBrightcloudGetCurl($endpoint, $oauth_header, $bUseProxy = TRUE){
		if(!isset($GLOBALS["PROXY"])){
			$GLOBALS["PROXY"]=array();
			$ini=new Bs_IniHandler();
			$sock=new sockets();
			$datas=$sock->GET_INFO("ArticaProxySettings");
			if(trim($datas)<>null){$ini->loadString($datas);}
			if(isset($ini->_params["PROXY"])){
				$GLOBALS["PROXY"]=$ini->_params["PROXY"];
			}
		}
		if(!isset($GLOBALS["PROXY"]["ArticaProxyServerEnabled"])){
			$GLOBALS["PROXY"]["ArticaProxyServerEnabled"]="no";
			$GLOBALS["PROXY"]["ArticaProxyServerName"]=null;
			$GLOBALS["PROXY"]["ArticaProxyServerPort"]=null;
			$GLOBALS["PROXY"]["ArticaProxyServerUsername"]=null;
			$GLOBALS["PROXY"]["ArticaProxyServerUserPassword"]=null;
		}
		
		$ArticaProxyServerEnabled=$GLOBALS["PROXY"]["ArticaProxyServerEnabled"];
		$ArticaProxyServerName=$GLOBALS["PROXY"]["ArticaProxyServerName"];
		$ArticaProxyServerPort=$GLOBALS["PROXY"]["ArticaProxyServerPort"];
		$ArticaProxyServerUsername=trim($GLOBALS["PROXY"]["ArticaProxyServerUsername"]);
		$ArticaProxyServerUserPassword=$GLOBALS["PROXY"]["ArticaProxyServerUserPassword"];
		
		
		
		$curl = curl_init($endpoint);    
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_FAILONERROR, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($curl, CURLOPT_SSLVERSION,'all');curl_setopt($curl, CURLOPT_SSLVERSION,'all');
		curl_setopt($curl, CURLOPT_SSLVERSION,'all');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array($oauth_header));
		
		if($ArticaProxyServerEnabled==1){$ArticaProxyServerEnabled="yes";}if($ArticaProxyServerEnabled=="yes"){
			curl_setopt($curl,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
			curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			curl_setopt($curl, CURLOPT_PROXY, $ArticaProxyServerName);
			curl_setopt($curl, CURLOPT_PROXYPORT, $ArticaProxyServerPort);
			if($ArticaProxyServerUsername<>null){
				curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
				curl_setopt($curl, CURLOPT_PROXYUSERPWD, $ArticaProxyServerUsername.':'.$ArticaProxyServerUserPassword);
			}
		}		
		


		$response = curl_exec($curl);    
		curl_close($curl);
		return $response;
	}

function UBoxBrightcloudIsDrug($iCat)
{
	switch ($iCat) {
		case 23:
		case 24:
		case 25:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxBrightcloudIsGambling($iCat)
{
	switch ($iCat) {
		case 11:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxBrightcloudIsHacking($iCat)
{
	switch ($iCat) {
		case 17:
		case 18:
		case 43:
		case 44:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxBrightcloudIsPorn($iCat)
{
	switch ($iCat) {
		case 1:
		case 3:
		case 4:
		case 6:
		case 92:
		case 93:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxBrightcloudIsShopping($iCat)
{
	switch ($iCat) {
		case 58:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxBrightcloudIsStreaming($iCat)
{
	switch ($iCat) {
		case 84:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxBrightcloudIsViolence($iCat)
{
	switch ($iCat) {
		case 7:
		case 14:
		case 15:
		case 35:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxBrightcloudGetRawCatCode($szUrl, $bUseProxy = TRUE)
{
	$consumer_key = "4nnDdHEuySd7zkqKsqXdA";  
	$consumer_secret = "Me1vrf4STIKadoXyQ6ZIKOEEwhSnYW33ysPaxADZaeI";
	$rest_endpoint = "http://thor.brightcloud.com:80/rest";  
	$uri_info_path = "uris";  
	$http_method = "GET";

	$endpoint = $rest_endpoint."/".$uri_info_path."/".urlencode($szUrl /*, "UTF-8"*/);

	$consumer = new OAuthConsumer($consumer_key, $consumer_secret, NULL);
	$request = OAuthRequest::from_consumer_and_token($consumer, NULL, $http_method, $endpoint, NULL);
	$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL);
	$oauth_header = $request->to_header();

	$szBody = UBoxBrightcloudGetCurl($endpoint, $oauth_header, $bUseProxy);
	return $szBody;
}
function UBoxBrightcloudCreateGuid(){
	if (function_exists('com_create_guid')) {
		return com_create_guid();
	}
	else {
		mt_srand((double)microtime()*10000);
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);
		$uuid = chr(123).substr($charid, 0, 8).$hyphen.substr($charid, 8, 4).$hyphen.substr($charid,12, 4).$hyphen.substr($charid,16, 4).$hyphen.substr($charid,20,12).chr(125);
		return $uuid;
	}
}

function UBoxBrightcloudGetCurlHttpsToolbar($szUrl, $bUseProxy = TRUE){
	
	if($GLOBALS["DEBUG_EXTERN"]){echo "Bright:";}
	$iProxyLoop = 0;
	$bStat = false;
	$response = "";

	$szXml = '<?xml version="1.0"?><publicapi><request><type>tb-geturi</type><uri>'.$szUrl.'</uri></request></publicapi>';

	

		$ayHeaders = array(
				'Content-Type', 'text/xml',
				'X-BCAPAPI-GUID', UBoxBrightcloudCreateGuid(),
				'X-BCAPAPI-IDENTIFIER', 'Firefox',
				'pragma', 'no-cache',
				'Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate'
		);

		$t=time();
		$curl = curl_init('https://toolbar.brightcloud.com/toolbaruri');
		
		
		curl_setopt($curl,  CURLOPT_HEADER, true);
		curl_setopt($curl,  CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl,  CURLOPT_FAILONERROR, true);
		curl_setopt($curl,  CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSLVERSION,'all');
		curl_setopt($curl,  CURLOPT_HTTPHEADER, $ayHeaders);
		curl_setopt($curl,  CURLOPT_POST, 1);
		curl_setopt($curl,  CURLOPT_POSTFIELDS, $szXml);
		/*curl_setopt($curl,  CURLOPT_HTTPPROXYTUNNEL,TRUE);
		curl_setopt ($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt ($curl, CURLOPT_PROXY,"127.0.0.1:60846");
		*/

		unset($ayHeaders);

		$response = curl_exec($curl);
		$Infos= curl_getinfo($curl);
		
		if($GLOBALS["DEBUG_BRIGHT"]){
			echo "UBoxBrightcloudGetCurlHttpsToolbar()\n$response\n";
			print_r($Infos);
		}
		
		curl_close($curl);

		$bStat = (!$response ? false : true);
		$distanceInSeconds = round(abs(time() - $t));
	
		if($GLOBALS["DEBUG_EXTERN"]){echo " Done {$distanceInSeconds}s";}
	return $response;
}

function UBoxBrightcloudGetCatCode($szUrl, $bUseProxy = TRUE){


	$szBody = UBoxBrightcloudGetCurlHttpsToolbar($szUrl, $bUseProxy);
	if(isset($GLOBALS["DEBUG_BRIGHT"])){
		echo "$szUrl\n$szBody\n";
	}

	preg_match("#<statusmsg>(.+?)</statusmsg>#is", $szBody, $re);
	$OK=strtoupper($re[1]);
	if($OK<>"OK"){
		if($GLOBALS["VERBOSE"]){echo $szBody."\n";}
	}
	
	preg_match("#<catid>(.+?)</catid>#is", $szBody, $re);
	$szCat = (isset($re[1]) ? $re[1] : 999);
	unset($re);

	preg_match("#<bcri>(.+?)</bcri>#is", $szBody, $re);
	$iScore = (isset($re[1]) ? (1-($re[1]/100)) : 0);
	unset($re);

	$iCat = UBoxBrightcloudGetiCat($szCat);
	

	$result = array('icat' => $iCat, 'score' => $iScore);
	return (count($result) > 0 ? $result : FALSE);
}

?>
