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

function UBoxBrightcloudGetCurl($endpoint, $oauth_header, $bUseProxy = TRUE)
{
	$ayProxies = array(
		0 => array('ip' => '91.121.167.143', 'port' => '443'),
		//1 => array('ip' => '188.165.242.213', 'port' => '3128'),
		1 => array('ip' => '90.150.144.46', 'port' => '8080'),
		
	);

	$iProxyLoop = 0;
	$bStat = false;

	$nProxy = ($bUseProxy == TRUE ? count($ayProxies) : 1);
	while ($iProxyLoop < $nProxy) {
		if ($bUseProxy == TRUE) {
			$irand = rand(0, $nProxy - 1);
			$szProxy = $ayProxies[$irand]['ip'];
			$szPort = $ayProxies[$irand]['port'];
			$szTmp = $szProxy.":".$szPort;
			//echo "\nBrightcloud proxy: ".$szTmp."\n";
		}

		$curl = curl_init($endpoint);    
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_FAILONERROR, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($curl, CURLOPT_SSLVERSION,'all');curl_setopt($curl, CURLOPT_SSLVERSION,'all');
		curl_setopt($curl, CURLOPT_SSLVERSION,'all');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array($oauth_header));
		if ($bUseProxy == TRUE) {
			curl_setopt($curl, CURLOPT_PROXYTYPE, 'HTTP');
			curl_setopt($curl, CURLOPT_PROXY, $szProxy);
			curl_setopt($curl, CURLOPT_PROXYPORT, $szPort);
		}

		$response = curl_exec($curl);    
		curl_close($curl);

		$bStat = (!$response ? false : true);
		if ($bStat == true) {
			break;
		}
/*		else {
			echo "iProxyLoop: ".$iProxyLoop."\n";
		}*/
		$iProxyLoop++;
	}
	
	unset($ayProxies);

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

function UBoxBrightcloudGetCatCode($szUrl, $bUseProxy = TRUE)
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
	//echo $szBody."\n";

	preg_match("#<catid>(.+?)</catid>#is", $szBody, $re);
	$szCat = (isset($re[1]) ? $re[1] : 999);
	unset($re);

	preg_match("#<bcri>(.+?)</bcri>#is", $szBody, $re);
	$iScore = (isset($re[1]) ? (1-($re[1]/100)) : 0);
	unset($re);

	$iCat = UBoxBrightcloudGetiCat($szCat);
	//echo $szUrl." -> szCat: ".$szCat." - iCat: ".$iCat."          \n";

	$result = array('icat' => $iCat, 'score' => $iScore);
	return (count($result) > 0 ? $result : FALSE);
}

?>
