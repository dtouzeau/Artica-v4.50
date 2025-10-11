#!/usr/bin/php -q
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($argv[1]=="--domain"){$GLOBALS["VERBOSE"]=true;GetResults2($argv[2]);exit();}



$GLOBALS["Q"]=new mysql_squid_builder();
$sock=new sockets();
$sql="SELECT sitename FROM visited_sites WHERE length(category)=0";	
$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
$results=$GLOBALS["Q"]->QUERY_SQL($sql);
$num_rows = mysqli_num_rows($results);	
$CountUpdatedTables=0;
$c=0;

while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){	
		$category=null;
		$c++;
		$category=GetResults2($ligne["sitename"]);
		if($category==null){continue;}
		
		$newmd5=md5("$category{$ligne["sitename"]}");
				
		$category_table="category_".$GLOBALS["Q"]->category_transform_name("$category");
		WriteMyLogs("$c/$num_rows: {$ligne["sitename"]}= $category ($category_table)");	
		if(!$GLOBALS["Q"]->TABLE_EXISTS("$category_table")){WriteMyLogs("$c/$num_rows: {$ligne["sitename"]}= $category_table no such table");continue;}
		
		$GLOBALS["Q"]->QUERY_SQL("INSERT IGNORE INTO categorize_changes (zmd5,sitename,category) VALUES('$newmd5','{$ligne["sitename"]}','$category')");
		$GLOBALS["Q"]->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$newmd5',NOW(),'$category','{$ligne["sitename"]}','$uuid')");
		$sql="UPDATE visited_sites SET category='$category' WHERE sitename='{$ligne["sitename"]}'";
		$GLOBALS["Q"]->QUERY_SQL($sql);
		
	}

function WriteMyLogs($text){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	writelogs($text,"non",__FILE__,0);
	$logFile="/var/log/artica-postfix/".basename(__FILE__).".log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n");
	@fclose($f);
}

function GetResults($website){
	
if($GLOBALS["VERBOSE"]){echo "GET $website\n";}
	$parms["url"]="$website";
	$parms["jscheck"]="validated";
	while (list ($num, $ligne) = each ($parms)){$curlPost .='&'.$num.'=' . urlencode($ligne);}
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://sitereview.cwfservice.net/sitereview.jsp");
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($ch, CURLOPT_SSLVERSION,'all');
            curl_setopt($ch, CURLOPT_SSLVERSION,'all');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache", "Cache-Control: no-cache"));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
			if($GLOBALS["VERBOSE"]){echo "curl_exec $website\n";}
			$data=curl_exec($ch);
			$error=curl_errno($ch);	
			curl_close($ch);
			if(preg_match("#currently categorized as <a.+?>(.+?)</a>#is", $data,$re)){
				return $re[1];
			}
}

function GetResults2($domain){

	$array=UBoxBluecoatGetCatCode($domain);
	
	//if($GLOBALS["VERBOSE"]){print_r($array);}
	
	$category=$array["scat"];
	//echo "$domain: {$array["scat"]}\n";
	return $category;
}





function GetBlueCats(){

// Achats
$conf["Shopping"]="shopping";
$conf["News/Media"]="news";
$conf["Business/Economy"]="finance/other";
$conf["Greeting Cards"]="gifts";
$conf["Alcohol"]="";
$conf["Web Applications"]="webapps";
$conf["Weapons"]="weapons";
$conf["Art/Culture"]="culture";
$conf["Blogs/Personal Pages"]="blog";
// Avortement
$conf["Abortion"]="";




// Clips audio/vidéo
$conf["Audio/Video Clips"]="audio-video";

// Contenu adulte/mature
$conf["Adult/Mature Content"]="";

// Contenu ouvert/mixte
$conf["Open/Mixed Content"]="";

// Conversation/Messagerie instantanée
$conf["Chat/Instant Messaging"]="chat";

// Courtage/Négoce
$conf["Brokerage/Trading"]="";

// Données En Partance Malveillantes
$conf["Malicious Outbound Data/Botnets"]="";

// Drogues illégales
$conf["Illegal Drugs"]="drugs";

// Éducation sexuelle
$conf["Sex Education"]="sexual_education";

// E-mail
$conf["Email"]="webmail";

// Enseignement
$conf["Education"]="recreation/schools";

// Escroquerie/Douteux/Illicit
$conf["Scam/Questionable/Illegal"]="";

// Espaces réservés
$conf["Placeholders"]="";

// Évitement de proxy
$conf["Proxy Avoidance"]="";

// Gouvernement/Juridique
$conf["Government/Legal"]="governments";

// Groupes de discussion/Forums
$conf["Newsgroups/Forums"]="forums";

// Groupes politiques/activistes
$conf["Political/Activist Groups"]="politic";

// Hameçonnage
$conf["Phishing"]="phishing";

// Hébergement Web
$conf["Web Hosting"]="isp";

// Hôte de DNS dynamique
$conf["Dynamic DNS Host"]="";

// Humour/Blagues
$conf["Humor/Jokes"]="recreation/humor";

// Immobilier
$conf["Real Estate"]="finance/realestate";

// Informationnel
$conf["Informational"]="";

// Jeux
$conf["Games"]="games";

// Jeux d'argent
$conf["Gambling"]="gamble";

// LGBT
$conf["LGBT"]="";

// Lingerie/Maillots de bain
$conf["Intimate Apparel/Swimsuit"]="";

// Logiciel potentiellement indésirable
$conf["Potentially Unwanted Software"]="spyware";

// Militaire
$conf["Military"]="";

// Moteurs/Portails de recherche
$conf["Search Engines/Portals"]="searchengines";

// Non consultable
$conf["Non-viewable"]="";

// Nudité
$conf["Nudity"]="";

// Ordinateurs/Internet
$conf["Computers/Internet"]="science/computing";

// Organismes caritatifs
$conf["Charitable Organizations"]="humanitarian";

// Outils d'accès à distance
$conf["Remote Access Tools"]="remote-control";

// Pair à pair
$conf["Peer-to-Peer (P2P)"]="";

// Partage de fichiers multimédia
$conf["Media Sharing"]="filehosting";

// Payer pour naviguer
$conf["Pay to Surf"]="";

// Piratage
$conf["Hacking"]="hacking";

// Pornographie
$conf["Pornography"]="porn";

// Pornographie d'Enfant/pornographie juvenile
$conf["Child Pornography"]="porn";

// Pour les enfants
$conf["For Kids"]="children";

// Publicités Web
$conf["Web Advertisements"]="publicite";

// Recherche d'emploi/Carrières
$conf["Job Search/Careers"]="jobsearch";

// Référence
$conf["Reference"]="";

// Religion
$conf["Religion"]="";

// Rencontres/Rendez-vous
$conf["Personals/Dating"]="";

// Réseaux sociaux
$conf["Social Networking"]="";

// Restaurants/Restauration/Nourriture
$conf["Restaurants/Dining/Food"]="";

// Réunions en ligne
$conf["Online Meetings"]="";

// Santé
$conf["Health"]="health";

// Serveurs de contenu
$conf["Content Servers"]="";

// Services financiers
$conf["Financial Services"]="financial";

// Sexualité/Modes de vie alternatifs
$conf["Alternative Sexuality/Lifestyles"]="";

// Sites choquants
$conf["Extreme"]="violence";

// Société/Vie de tous les jours
$conf["Society/Daily Living"]="";

// Sources Malveillantes
$conf["Malicious Sources"]="";

// Spam
$conf["Spam"]="marketingware";

// Spectacles et divertissements
$conf["Entertainment"]="recreation/nightout";

// Spiritualité/Croyances alternatives
$conf["Alternative Spirituality/Belief"]="";

// Sports/Loisirs
$conf["Sports/Recreation"]="recreation/sports";

// Stockage en ligne
$conf["Online Storage"]="filehosting";

// Suspect
$conf["Suspicious"]="spyware";

// Tabac
$conf["Tobacco"]="tobacco";

// Téléchargements de logiciels
$conf["Software Downloads"]="downloads";

// Téléphonie par Internet
$conf["Internet Telephony"]="webphone";

// Traduction
$conf["Translation"]="translator";

// Transmission audio/radio en continu
$conf["Radio/Audio Streams"]="webradio";

// Transmission TV/Vidéo en continu
$conf["TV/Video Streams"]="webtv";

// Véhicules
$conf["Vehicles"]="automobile/cars";

// Ventes aux enchères
//$conf["Auctions"]="auctions";

// Violence/Haine/Racisme
$conf["Violence/Hate/Racism"]="violence";
$conf["Travel"]="recreation/travel";
return $conf; 
}