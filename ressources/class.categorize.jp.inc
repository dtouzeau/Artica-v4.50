<?php
require_once("simple_html_dom.php");

$ayTrendMicroCats = array(
	'Adult / Mature Content'		=> 1,
	'Pornography'					=> 3,
	'Sex Education'					=> 4,
	'Illegal / Questionable'		=> 9,
	'Gambling'						=> 11,
	'Violence / Hate / Racism'		=> 14,
	'Weapons'						=> 15,
	'Hacking'						=> 17,
	'Phishing'						=> 18,
	'Entertainment'					=> 20,
	'Business / Economy'			=> 21,
	'Alcohol / Tobacco'				=> 23,
	'Alcohol / Tobacco'				=> 24,
	'Illegal Drugs'					=> 25,
	'Cultural Institutions'			=> 29,
	'Financial Services'			=> 31,
	'Games'							=> 33,
	'Government / Legal'			=> 34,
	'Military'						=> 35,
	'Activist Groups'				=> 36,
	'Health'						=> 37,
	'Computers / Internet'			=> 38,
	'Internet Infrastructure'		=> 38,
	'Search Engines / Portals'		=> 40,
	'Disease Vector'				=> 43,
	'Job Search / Careers'			=> 45,
	'News / Media'					=> 46,
	'Personals / Dating'			=> 47,
	'Reference'						=> 49,
	'Chat / Instant Messaging'		=> 51,
	'Email'							=> 52,
	'Newsgroups'					=> 53,
	'Social Networking'				=> 55,
	'Personal Network Storage / File Download Servers'	=> 56,
	'Shopping'						=> 58,
	'Auctions'						=> 59,
	'Real Estate'					=> 60,
	'Society / Lifestyle'			=> 61,
	'Blogs / Web Communications'	=> 63,
	'Restaurants / Food'			=> 64,
	'Sports'						=> 65,
	'Travel'						=> 66,
	'Vehicles'						=> 67,
	'Humor'							=> 68,
	'Software Downloads'			=> 71,
	'Pay to Surf'					=> 72,
	'Peer-to-peer'					=> 83,
	'Streaming Media / MP3'			=> 84,
	'Internet Radio and TV'			=> 84,
	'Proxy Avoidance'				=> 86,
	'Web Advertisement'				=> 88,
	'Web Hosting'					=> 89,
	'Untested'						=> 90,
	'Spam'							=> 101,
	'Internet Telephony'			=> 110,

	'Photo Searches'				=> 602,
);

function UBoxTrendmicroGetiCat($szCat)
{
	$ayTrendMicroCats = $GLOBALS["ayTrendMicroCats"];

	return (isset($ayTrendMicroCats[$szCat]) ? $ayTrendMicroCats[$szCat] : 999);
}

function UBoxTrendmicroGetCurl($szUrl){

	$postdata = array('urlname' => $szUrl);
	$ckfile = tempnam("/tmp", "tmcook-");

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://global.sitesafety.trendmicro.com/result.php");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
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
	$data = curl_exec($ch);
	curl_close($ch);

	unlink($ckfile);

	return $data;
}

function UBoxTrendmicroIsDrug($iCat)
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

function UBoxTrendmicroIsGambling($iCat)
{
	switch ($iCat) {
		case 11:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxTrendmicroIsHacking($iCat)
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

function UBoxTrendmicroIsPorn($iCat)
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

function UBoxTrendmicroIsShopping($iCat)
{
	switch ($iCat) {
		case 58:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxTrendmicroIsStreaming($iCat)
{
	switch ($iCat) {
		case 84:
			return TRUE;
		default:
			return FALSE;
	}
}

function UBoxTrendmicroIsViolence($iCat)
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

function UBoxTrendmicroGetCatCode($szUrl)
{
	$szBody = UBoxTrendmicroGetCurl($szUrl);
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
			$iCat = UBoxTrendmicroGetiCat($szCat);
			$result = array('icat' => $iCat);
//			$result = array('icat' => $iCat, 'scat' => $szCat);
			break;
		}
	}
	return (count($result) > 0 ? $result : FALSE);
}

?>
