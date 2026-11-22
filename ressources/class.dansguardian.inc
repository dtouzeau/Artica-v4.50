<?php

if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__)."/class.user.inc");
include_once(dirname(__FILE__)."/class.system.network.inc");
include_once(dirname(__FILE__)."/class.mysql.inc");;



class dansguardian{
	var $hostname;
	var $GlobalConf;
	var $Master_array;
	var $Master_rules_index;
	var $last_errors;
	var $_defaults;
	var $template;
	var $enable_clamav;
	var $DansGuardianEnableUserFrontEnd;
	var $DansGuardianEnableUserArticaIP;
	var $UserCategoryBlackList=array(); 
	var $UserCategoryWeightedPhraseList=array();
	var $RulesList=array();
	
	
	function __construct(){
		$this->LoadMasterRule();
		$this->LoadRules();
		$this->LoadTemplate();
		$socket=new sockets();
		$this->enable_clamav=$socket->GET_INFO('DansGuardianEnableClamav');
		
		$this->DansGuardianEnableUserFrontEnd=$socket->GET_INFO('DansGuardianEnableUserFrontEnd');
		$this->DansGuardianEnableUserArticaIP=$socket->GET_INFO('DansGuardianEnableUserArticaIP');
		if($this->enable_clamav==null){$this->enable_clamav=1;}
		if($this->DansGuardianEnableUserFrontEnd==null){$this->DansGuardianEnableUserFrontEnd=1;}
		if(trim($this->DansGuardianEnableUserArticaIP)==null){$this->DansGuardianEnableUserArticaIP=$this->GetMyLocalIP();}
		$this->Master_array["DansGuardianEnableUserFrontEnd"]=$this->DansGuardianEnableUserFrontEnd;
		$this->Master_array["enable_clamav"]=$this->enable_clamav;
			
	}
	

	
	
	public function ContentScanners(){
	}
						
	
	function LoadRules(){

	}

		
	function GetMyLocalIP(){
		$net=new networking();
			while (list ($num, $ip) = each ($net->array_TCP) ){
				if(trim($ip)<>null){
					return $ip;
					break;
				}
			}
		
	}
		
	function SaveSettings(){
		}
		
		
	function LoadTemplate(){
		$sock=new sockets();
		$this->template=$sock->GET_INFO('DansGuardianHTMLTemplate');
		$this->template=str_replace("\n\n","\n",$this->template);
		
		
	}


	function BuildConfig(){}
			
			private function LoadMasterRule(){
			}
			


		
}



class dansguardian_rules{
	var $error;
	var $hostname;
	var $ruleindex;
	var $array_blacksites=array(
									"porn"=>"{adult}",
									"sex/lingerie"=>"{sex_lingerie}",
									"mixed_adult"=>"{mixed_adult}",
									"sexual_education"=>"{sexual_education}",
									"abortion"=>"{www_abortion}",
									"dating"=>"{dating}",
									"tattooing"=>"{www_tattooing}",
	
							
									"agressive"=>"{agressif}",
									"violence"=>"{violence}",
									"terrorism"=>"{www_terrorism}",

									
									"automobile/bikes"=>"{automobile-bikes}",
									"automobile/boats"=>"{automobile-boats}",
									"automobile/cars"=>"{automobile-cars}",
									"automobile/planes"=>"{automobile-planes}",
									"automobile/carpool"=>"{automobile-carpool}",
									"bicycle"=>"{www_bicycle}",

									
									"publicite"=>"{publicite}",
									
									
									
									"cleaning"=>"{cleaning}",
									"dangerous_material"=>"{dangerous_material}",
									"downloads"=>"{www_downloads}",
									"skype"=>"Microsoft Skype",
									"chat"=>"{www_chat}",
									"passwords"=>"{cat_passwords}",
									
									
									"drugs"=>"{drogue}",
									"dynamic"=>"{www_dynamic}",
									
									"financial"=>"{financial}",
									"stockexchange"=>"{stockexchange}",
									"finance/banking"=>"{finance-banking}",
									"finance/insurance"=>"{finance-insurance}",
									"finance/moneylending"=>"{finance-moneylending}",
									"finance/realestate"=>"{finance-realestate}",
									"finance/other"=>"{finance-other}",

									"forums"=>"{forums}",
									//"forum"=>"{forums} (2)",
									"socialnet"=>"{socialnet}",
									"jobsearch"=>"{jobsearch}",
									"jobtraining"=>"{jobtraining}",
									"learning"=>"{www_learning}",
									"humanitarian"=>"{www_humanitarian_associations}",
									"associations"=>"{www_associations}",
									"microsoft"=>"Microsoft",
									"gamble"=>"{gambling}",

									"hacking"=>"{hacking}",
									"warez"=>"{warez}",
	
									"hobby/cooking"=>"{hobby-cooking}",
									"hobby/fishing"=>"{hobby-fishing}",
									"hobby/arts"=>"{hobby-arts}",
									"hobby/other"=>"{hobby-other}",
									
									"isp"=>"{www_isp}",
									"webmail"=>"{webmail}",
									
									"liste_bu"=>"{liste_bu}",
									"mobile-phone"=>"{mobile-phone}",
									"marketingware"=>"{marketingware}",
									
									"webradio"=>"{webradio}",
									"audio-video"=>"{audio-video}",
									"youtube"=>"Youtube",
									"amazonaws"=>"amazonaws",
									"akamai"=>"Akamai Technologies",
									"yahoo"=>"Yahoo websites",
									"citrix"=>"Citrix, Citrix Online",
			
									"google"=>"Google",
									"apple"=>"Apple",
									"facebook"=>"facebook",
									"webtv"=>"{webtv}",
									"music"=>"{music}",
									"movies"=>"{movies}",
									
									
									
									"blog"=>"{blog}",
									"news"=>"{www_news}",
								
									"society"=>"{www_society}",
									"books"=>"{www_books}",
									"manga"=>"{www_mangua}",
									"dictionaries"=>"{www_dictionaries}",
									"phishing"=>"{phishing}",
				
									"redirector"=>"{redirector}",
									"proxy"=>"{www_proxy}",
									
									"paytosurf"=>"{www_paytosurf}",
									"reaffected"=>"{reaffected}",
									
									"tricheur"=>"{tricheur}",
									
									
									"webphone"=>"{webphone}",
									"weapons"=>"{weapons}",
									
									"games"=>"{games}",
									"hobby/pets"=>"{hobby-pets}",
									"animals"=>"{www_animals}",
									"horses"=>"{www_horses}",
									
									"filehosting"=>"{filehosting}",
									"photo"=>"{www_photo}",
									"pictureslib"=>"{www_pictureslib}",
									"imagehosting"=>"{www_imagehosting}",
									"religion"=>"{religion}",
									"sect"=>"{sect}",
									"genealogy"=>"{www_genealogy}",
									
									
									"recreation/wellness"=>"{recreation_wellness}",
									"recreation/travel"=>"{recreation_travel}",
									"recreation/nightout"=>"{recreation_nightout}",
									"governments"=>"{www_government}",
									"recreation/schools"=>"{www_schools}",
									"housing/doityourself"=>"{www_housingbric}",
									"housing/builders"=>"{www_housingbuilders}",
									"housing/accessories"=>"{housing_accessories}",
									"houseads"=>"{houseads}",
									"smallads"=>"{www_smallads}",
									"electricalapps"=>"{electricalapps}",
									"justice"=>"{www_justice}",
									"police"=>"{www_police}",
									"converters"=>"{www_converters}",
									"meetings"=>"{www_meetings}",
									"getmarried"=>"{www_getmarried}",
									"tobacco"=>"{www_tobacco}",
									
									"recreation/sports"=>"{recreation_sports}",
									"recreation/humor"=>"{recreation_humor}",
									"children"=>"{www_children}",
									"teens"=>"{www_teens}",
									"shopping"=>"{www_shopping}",
									"gifts"=>"{www_gifts}",
									"luxury"=>"{www_luxury}",
									"cosmetics"=>"{www_cosmetics}",
									"clothing"=>"{www_clothing}",
									"electronichouse"=>"{www_electronichouse}",
									"models"=>"{models}",
									"celebrity"=>"{www_celebrity}",
									"womanbrand"=>"{womanbrand}",
									"politic"=>"{www_politic}",
									
									"industry"=>"{www_industry}",
									"science/chemistry"=>"{science_chemistry}",
									"sciences"=>"{www_sciences}",
									"astrology"=>"{astrology}",
									"science/astronomy"=>"{science_astronomy}",
									"science/weather"=>"{science_weather}",
									"nature"=>"{www_nature}",
									"green"=>"{www_green}",
									"browsersplugins"=>"{www_browsersplugins}",
									"webplugins"=>"{webplugins}",
									"maps"=>"{www_maps}",
									"webapps"=>"{www_webapps}",
									"science/computing"=>"{cat_computing}",
									"remote-control"=>"{cat_remote-control}",
									"hospitals"=>"{cat_hospitals}",
									"medical"=>"{cat_medical}",
									"health"=>"{www_health}",
									"handicap"=>"{cat_handicap}",
									"sslsites"=>"{cat_sslsites}",
									"updatesites"=>"{updatesites}",
									"internal"=>"{www_internal}",
									"phishtank"=>"{www_phishtank}",
									"ransomwares"=>"{www_ransomwares}",
									"searchengines"=>"{searchengines}",
									"translators"=>"{www_translators}",
									"spyware"=>"{www_spyware}",
									"malware"=>"{www_malware}",
									"tracker"=>"{www_tracker}",
									"transport"=>"{www_transport}",
									"culture"=>"{www_culture}",
									"wine"=>"{www_wine}",
									"alcohol"=>"{www_alcohol}",
									"astrology"=>"{www_astrology}",
									"literature"=>"{www_literature}",
									"mailing"=>"{www_mailing}",
									"suspicious"=>"{www_suspicious}",
									"google"=>"{www_google}",
									"amazonaws"=>"{www_amazonaws}",
									"akamai"=>"Akamai Technologies",
									"yahoo"=>"Yahoo Web sites",
									"apple"=>"{www_apple}",
									"nrds"=>"{nrds_cat}"
									);
									
		var $arrayGroups=array(
			"{sciences}"=>array("science/computing","science/chemistry","science/weather"),
			"adult"=>array("porn","sex/lingerie","mixed_adult","sexual_education","dating","drugs","gamble","sect","religion"),
			"violence"=>array("agressive","violence","weapons","dangerous_material" ,"hacking","warez","terrorism"),
			"{shopping}"=>array("shopping","clothing","gifts","luxury","cosmetics","smallads","electronichouse","wine"),
			"audio-video / photo / images"=>array("audio-video","webradio","webtv","music","movies","pictureslib","imagehosting","pictures","photo"),
			"{social}"=>array("socialnet","politic","jobsearch","forums","humanitarian","associations","governments"),
			"WWW"=>array("blog","searchengines","filehosting","translators","sslsites","webapps","maps","webplugins","browsersplugins","updatesites"),
			"{health}"=>array("hospitals","medical","health","handicap"),
			"spam"=>array("tracker","malware","publicite","spyware","ransomwares","redirector","marketingware","phishing","phishtank","mailing","suspicious"),
			"{children}"=>array("children","teens","games","liste_bu","recreation/schools","tricheur"),
			"{house}"=>array("finance/realestate","housing/reale_state_office","houseads","housing/doityourself","housing/accessories","electricalapps"),
			"{culture}"=>array("culture","dictionaries","books","literature","news","hobby/arts","jobtraining","learning","science/astronomy"),
			"{money}"=>array("stockexchange","finance/banking","finance/insurance","finance/moneylending","finance/other","financial"),
			"{communication}"=>array("webmail","chat","webphone","skype","mobile-phone","remote-control"),
			"Life"=>array("society","womanbrand","astrology","recreation/wellness","celebrity","models","hobby/fishing"),
			"Production"=>array("industry","justice","transport","sciences","green","converters")
		);

		
						
		
		  var $array_pics=array(
			 "sciences"=>"sciences-20.png",
		  	"reaffected"=>"20-reaffect.png",
		  	"mixed_adult"=>"sex-icon.png",
		  	"sex/lingerie"=>"sex-icon.png",
		  	"porn"=>"sex-icon.png",
		 	"adult"=>"sex-icon.png",
		  	"sexual_education"=>"kiss-icon.png",
		  	"music"=>"fire-guitar-icon.png",
		  	"agressif"=>"icon_fight.png",
		  	"agressive"=>"icon_fight.png",
		  	"violence"=>"icon_fight.png",
		  	"socialnet"=>"tree-groups.gif",
		  	"forum"=>"icon_forum.png",
		  	"forums"=>"icon_forum.png",
		  	"jobsearch"=>"icon_forum.png",
		  	"tracker"=>"icon_eye.png",
		  	"gambling"=>"icon_poker.png",
		  	"gamble"=>"icon_poker.png",
		  	"automobile/bikes"=>"icon-moto.gif",
		  	"publicite"=>"icon-ads.png",
		  	"adv"=>"icon-ads.png",
			"financial"=>"icon-bank.png",
			"finance/banking"=>"icon-bank.png",
			"finance/insurance"=>"icon-bank.png",
			"finance/moneylending"=>"icon-bank.png",
			"finance/other"=>"icon-bank.png",	
			"dangerous_material"=>"icon-bomb.png",
			"drogue"=>"icon-drugs.png",
			"drugs"=>"icon-drugs.png",
		  	"hobby/cooking"=>"icon-food.png",
		  	"astrology"=>"icon-astrologie.png",	
		  	"webmail"=>"icon-webmail.png",	
		  	"mobile-phone"=>"icon-mobile-phone.png", 
		  	"hacking"=>"icon-hacks.png",
		  	"ransomwares"=>"virus-20.png",
		  	"phishing"=>"icon-fish.png",
		  	"phishtank"=>"icon-fish.png",
		  	"automobile/boats"=>"icon-boat.png",
		  	"automobile/cars"=>"icon-car.png",
		  	"automobile/planes"=>"icon-plane.png",
		  	"cleaning"=>"icon-clean.png",
		  	"webtv"=>"icon-tv.png",
		  	"recreation/sports"=>"icon-sport.png",
		  	"recreation/travel"=>"icon-travel.png",
		  	"searchengines"=>"icon-search.png",
		  	"games"=>"icon-games.png",
		  	"hobby/games"=>"icon-games.png",
		 	"hobby/pets"=>"icon-pets.png",
		  	"weapons"=>"icon-armes.png",
		  	"sect"=>"icon-sect.png",
		  	"science/computing"=>"base.gif",
		  	"passwords"=>"lock.gif",
		  	"downloads"=>"icon-download-20.png",
		 	"updatesites"=>"20-cd-scan.png",
		  	"science/astronomy"=>"astronomy-20.png",
		  	"science/weather"=>"20-climat.png",
		  	"recreation/humor"=>"20-humour.png",
			  "nrds"=>"/img/20-news.png",
		  	
		  	"mobile-phone"=>"20-mobile-phone.png",
		  	"tricheur"=>"20-exams.png",
		  	"clothing"=>"20-clothing.png",
		  	"shopping"=>"20-shopping.png",
		  	"filehosting"=>"20-storage.png",
		  	"religion"=>"20-christians.png",
		  	"recreation/wellness"=>"20-heathly.png",
		  	"tobacco"=>"20-tobacco.png",
		  	"science/chemistry"=>"20-chemistery.png",
		  	"spyware"=>"20-setup.png",
		  	"recreation/governments"=>"20-towns.png",
		  	"redirector"=>"20-redirect.png",
		  	"recreation/schools"=>"20-schools.png",
		  	"webradio"=>"20-webradio.png",
		  	"radio"=>"20-webradio.png",
		  	"movies"=>"20-cinema.png",
		  	"dating"=>"20-dating.png",
		  	"webplugins"=>"20-webplugins.png",
		  	"audio-video"=>"20-audio-video.png",
		  	 "youtube"=>"youtube-20.png",
		  	 "google"=>"google-20.png",
		  	"facebook"=>"facebook-20.png",
		  	"blog"=>"20-blog.png",
		  	"models"=>"20-model-agency.png",
		  	"liste_bu"=>"flags/fr.png",
		  	"news"=>"20-news.png",
		  	"marketingware"=>"20-marketing.png",
		  	"warez"=>"20-warez.png",
		  	"finance/realestate"=>"icon-house-20.gif",
		  	"housing/builders"=>"icon-house-20.gif",
		  	"skype"=>"icon-chat-20.png",
			"chat"=>"icon-chat-20.png",
			"hospitals"=>"hospital.gif",
		  	"medical"=>"hospital.gif",
		  	"sslsites"=>"icon-ssl-20.png",
		  	"translators"=>"icon-translation.png",
		  	"humanitarian"=>"humanitarian-20.png",
		  	"hobby/arts"=>"art-20.png",
		  	"remote-control"=>"20-win-nic.png",
			"recreation/nightout"=>"icon_nightclub.png",	
			"imagehosting"=>"picasa_icon.png",
		  	"malware"=>"icon_malwares.png",
		  	"celebrity"=>"celebrity-20.png",
		  	"books"=>"books_20.png",
		  	"internal"=>"network-1-20.gif",
		  	"maps"=>"20-maps.png",
		  	
		  	"nature"=>"nature-20.png",
		  	"gifts"=>"gifts-20.png",
		  	"browsersplugins"=>"20-webplugins.png",
		  	"genealogy"=>"genealogy-20.png",
		  	"children"=>"baby-20.png",
		  	"automobile/carpool"=>"carpool-20.png",
		  	"nature"=>"nature-20.png",
		  	"dictionaries"=>"help-book-20.png",
		  	"stockexchange"=>"stockexchange-20.png",
		  	"handicap"=>"handicap-20.png",
		  	"amazonaws"=>"softwares-20.png",
		  	"microsoft"=>"softwares-20.png",
		  		 
		  	"citrix"=>"softwares-20.png",
		  	"akamai"=>"softwares-20.png",	
		  	"yahoo"=>"softwares-20.png",
		  	"webapps"=>"softwares-20.png",
		  	"health"=>"health-20.png",
		  	"housing/doityourself"=>"bric-20.png",
		  	"housing/accessories"=>"homeaccess-20.png",
		  	"photo"=>"camera-20.png",
		  	"pictureslib"=>"cat_pictures.png",
		  	"bicycle"=>"bicycle-20.png",
		  	"houseads"=>"houseads-20.png",
		  	"politic"=>"20-politic.png",
		  	"electricalapps"=>"electricalapps-20.png",
		  	"luxury"=>"jewel-20.png",
		  	"smallads"=>"smallads-20.png",
		  	"womanbrand"=>"womanbrand-20.png",
		  	"electronichouse"=>"hifi-20.png",
		  	"jobtraining"=>"jobtraining-20.png",
		  	"learning"=>"jobtraining-20.png",
		  	"teens"=>"20-teans.png",
		  	"cosmetics"=>"20-cosmetics.png",
		  	"manga"=>"20-manga.png",
		  	"justice"=>"justice-20.png",
		  	"transport"=>"transport-20.png",
		  	"culture"=>"culture-20.png",
		  	"wine"=>"wine-20.png",
		  	"animals"=>"20-animals.png",
		  	"astrology"=>"astrology-20.png",
			"literature"=>"literature-20.png",
		  	"associations"=>"20-assocs.png",
		  	"mailing"=>"20-mailing.png",
		  	"green"=>"20_green.png",
		  	"alcohol"=>"alcohol-20.png",
		  	"converters"=>"calc-20.png",
		 	"hobby/fishing"=>"fish-20.png",
		  	"suspicious"=>"suspicious-20.png",
		  	"proxy"=>"20-anonymous.png",
		  	"abortion"=>"abortion-20.png",
		  	"hobby/other"=>"hobby-20.png",
		  	"society"=>"society-20.png",
		  	"horses"=>"20_horses.png",
		  	"terrorism"=>"20-terrorisme.png",
		  	"paytosurf"=>"paytosurf-20.png",
		  	"meetings"=>"meetings-20.png",
		  	"getmarried"=>"20_getmarried.png",
		  	"police"=>"20-police.png",
		 	"tattooing"=>"tatoo-20.png",
		   		
		  	
		  		  	);
									
									
			var $array_banned_phrases_list=array("gambling"=>"{gambling}",
												"intolerance"=>"{intolerance}",
												"safelabel"=>"{safelabel}",
												"googlesearches"=>"{googlesearches}",
												"rta"=>"{rta}",
												"pornography"=>"{pornography}",
												"illegaldrugs"=>"{illegaldrugs}");
												
	         
			
							
									
			var $array_weighted_phrases_lists=array("sport"=>"{sport}",
													"news"=>"{news}",
													"personals"=>"{personals}",
													"warezhacking"=>"{warezhacking}",
													"music"=>"{music}",
													"weapons"=>"{weapons}",
													"chat"=>"{chat}",
													"malware"=>"{malware}",
													"peer2peer"=>"{peer2peer}",
													"games"=>"{games}",
													"gambling"=>"{gambling}",
													"secretsocieties"=>"{secretsocieties}",
													"intolerance"=>"{intolerance}",
													"badwords"=>"{badwords}",
													"translation"=>"{translation}",
													"goodphrases"=>"{goodphrases}",
													"gore"=>"{gore}",
													"idtheft"=>"{idtheft}",
													"violence"=>"{violence}",
													"domainsforsale"=>"{domainsforsale}",
													"conspiracy"=>"{conspiracy}",
													"legaldrugs"=>"{legaldrugs}",
													"upstreamfilter"=>"{upstreamfilter}",
													"nudism"=>"{nudism}",
													"pornography"=>"{pornography}",
													"drugadvocacy"=>"{drugadvocacy}",
													"forums"=>"{forums}",
													"webmail"=>"{webmail}",
													"illegaldrugs"=>"{illegaldrugs}",
													"proxies"=>"{proxies}",
													"travel"=>"{travel}");
													
													
	var $array_bannedextensionlist=array(	".ade"=>"Microsoft Access project extension",
											".adp"=>"Microsoft Access project",
											".asx"=>"Windows Media Audio / Video",
											".bas"=>"Microsoft Visual Basic class module",
											".bat"=>"Batch file",
											".cab"=>"Windows setup file",
											".chm"=>"Compiled HTML Help file",
											".cmd"=>"Microsoft Windows NT Command script",
											".com"=>"Microsoft MS-DOS program",
											".cpl"=>"Control Panel extension",
											".crt"=>"Security certificate ",
											".dll"=>"Windows system file",
											".exe"=>"Program",
											".hlp"=>"Help file",
											".ini"=>"Windows system file",
											".hta"=>"HTML program",
											".inf"=>"Setup Information",
											".ins"=>"Internet Naming Service",
											".isp"=>"Internet Communication settings",
											".lnk"=>"Windows Shortcut",
											".mda"=>"Microsoft Access add-in program ",
											".mdb"=>"Microsoft Access program",
											".mde"=>"Microsoft Access MDE database",
											".mdt"=>"Microsoft Access workgroup information ",
											".mdw"=>"Microsoft Access workgroup information ",
											".mdz"=>"Microsoft Access wizard program ",
											".msc"=>"Microsoft Common Console document",
											".msi"=>"Microsoft Windows Installer package",
											".msp"=>"Microsoft Windows Installer patch",
											".mst"=>"Microsoft Visual Test source files",
											".pcd"=>"Photo CD image, Microsoft Visual compiled script",
											".pif"=>"Shortcut to MS-DOS program",
											".prf"=>"Microsoft Outlook profile settings",
											".reg"=>"Windows registry entries",
											".scf"=>"Windows Explorer command",
											".scr"=>"Screen saver",
											".sct"=>"Windows Script Component",
											".sh "=>"Shell script",
											".shs"=>"Shell Scrap object",
											".shb"=>"Shell Scrap object",
											".sys"=>"Windows system file",
											".url"=>"Internet shortcut",
											".vb "=>"VBScript file",
											".vbe"=>"VBScript Encoded script file",
											".vbs"=>"VBScript file",
											".vxd"=>"Windows system file",
											".wsc"=>"Windows Script Component",
											".wsf"=>"Windows Script file",
											".wsh"=>"Windows Script Host Settings file",
											".otf"=>"Font file - can be used to instant reboot 2k and xp",
											".ops"=>"Office XP settings",
											".doc"=>"Word document",
											".xls"=>"Excel document",
											".pps"=>"MicrosoftPower Point presentation",
											".gz "=>"Gziped file",
											".tar"=>"Tape ARchive file",
											".zip"=>"Windows compressed file",
											".tgz"=>"Unix compressed file",
											".bz2"=>"Unix compressed file",
											".cdr"=>"Mac disk image",
											".dmg"=>"Mac disk image",
											".smi"=>"Mac self mounting disk image",
											".sit"=>"Mac compressed file",
											".sea"=>"Mac compressed file, self extracting",
											".bin"=>"Mac binary compressed file",
											".hqx"=>"Mac binhex encoded file",
											".rar"=>"Similar to zip",
											".mp3"=>"Music file",
											".mpeg"=>"Movie file",
											".mpg"=>"Movie file",
											".avi"=>"Movie file",
											".asf"=>"this can also exploit a security hole allowing virus infection",
											".iso"=>"CD ISO image",
											".ogg"=>"Music file",
											".wmf"=>"Movie file",
											".bin"=>"CD ISO image",
											".cue"=>"CD ISO image");
			
	       var $array_default_mimetype=array("audio/mpeg",
											"audio/x-mpeg",
											"audio/x-pn-realaudio",
											"audio/x-wav",
											"video/mpeg",
											"video/x-mpeg2",
											"video/acorn-replay",
											"video/quicktime",
											"video/x-msvideo",
											"video/msvideo",
											"application/gzip",
											"application/x-gzip",
											"application/zip",
											"application/compress",
											"application/x-compress",
											"application/java-vm");										
													
																						
	var $bannedregexpurllist_array=array();
	var $exceptionsitelist_array=array();
	var $bannedextensionlist_array=array();
	var $bannedMimetypelist_array=array();
	var $exceptionfilesitelist=array();
	var $Main_rule;
	var $Main_array=array();
	
	
	function __construct($hostname=null,$RuleID=0){
		

		
		ksort($this->array_weighted_phrases_lists);
		//ksort($this->array_blacksites);
		ksort($this->array_banned_phrases_list);
		if($RuleID>0){
				$this->ruleindex=$RuleID;
				$this->LoadMainRule();
				$this->LoadOtherRules($this->ruleindex);
				$this->ParseMainConfig();
				$this->BuildDefaults();
				writelogs("$RuleID) Parsing end...",__CLASS__.'/'.__FUNCTION__,__FILE__);
				}
			
		
		
	}
	
	private function get_cachedItems($function){
		$filename=PROGRESS_DIR."/$function.cache";
		if(is_file($filename)){return @file_get_contents($filename);}
	
	}	
	
	private function set_cachedItems($function,$data=null){
		$filename=PROGRESS_DIR."/$function.cache";
		@file_put_contents($filename, $data);
	}
	
	function LoadBlackListes($nocache=false){
		$data=$this->get_cachedItems(__FUNCTION__);
		if($data<>null){$data=unserialize($data);
			if(count($data)>20){return $data;}
		}
		
		include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");
		$q=new mysql_squid_builder();

		$sql="SELECT description,categorykey FROM webfilters_categories_caches ORDER BY categorykey";
		$results=$q->QUERY_SQL($sql);
		
		if(!$q->ok){
			if(preg_match("#does.*?exist#", $q->mysql_error)){
				$q->create_webfilters_categories_caches();
				$results=$q->QUERY_SQL($sql);
			}
		}
		
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if($ligne["categorykey"]=="teans"){$ligne["categorykey"]="teens";}
			$array[$ligne["categorykey"]]=$ligne["description"];
		}
		
		reset($this->array_blacksites);
		foreach ($this->array_blacksites as $categoryKey=>$description){
			$array[$categoryKey]=$description;
		}
		$this->set_cachedItems(__FUNCTION__,serialize($array));
		return $array;
	}
	

	private function LoadMainRule(){
		$q=new mysql();
		$sql="SELECT RuleText FROM dansguardian_rules WHERE RuleID=$this->ruleindex";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$this->Main_rule=$ligne["RuleText"];
		writelogs("Rule $this->ruleindex store ". strlen($this->Main_rule). " bytes length (line ".__LINE__.")",__CLASS__.'/'.__FUNCTION__,__FILE__);
		if(trim($this->Main_rule)==null){
			$this->Main_rule=$this->BuildMainRule();
		    $this->SaveRuleID();
		}
		
	}
	
	private function SaveRuleID(){
		$this->Main_rule=$this->BuildMainRule();
		$sql="UPDATE dansguardian_rules SET RuleText='". addslashes($this->Main_rule) ."' WHERE RuleID=$this->ruleindex";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return false;
		}

		$this->RestartFilters();
		
		return true;
	}
	
	
	
	function ParseMainConfig(){
	}

	public function RestartFilters(){
		$sock=new sockets();
		$enable_squidguard=$sock->GET_INFO('squidGuardEnabled');

		
		$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		$UfdbEnabledCentral=$sock->GET_INFO("UfdbEnabledCentral");

		$AntivirusEnabledCentral=$sock->GET_INFO("AntivirusEnabledCentral");
		$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
		if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}		
		if($UnlockWebStats==1){$EnableRemoteStatisticsAppliance=0;}
		
		if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($AntivirusEnabledCentral)){$AntivirusEnabledCentral=1;}
		$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
		if(!is_numeric($UfdbEnabledCentral)){$UfdbEnabledCentral=1;}
		if($EnableRemoteStatisticsAppliance==1){
		$EnableUfdbGuard=$UfdbEnabledCentral;
			
		}
		if($EnableWebProxyStatsAppliance==1){
			$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/compile");
			return;
		}
		
		$users=new usersMenus();
		if($users->C_ICAP_INSTALLED){
			if($enable_squidguard==1){
				$GLOBALS["CLASS_SOCKETS"]->REST_API("/cicap/restart");
				return;
			}
		}
		
		// Les branches DansGuardian et SquidGuard rechargeaient leur filtre via
		// framework/cmd.php?reload-dansguardian, dont la fonction n'existe plus.
		// Toutes deux etaient de toute facon inatteignables: DANSGUARDIAN_INSTALLED
		// et SQUIDGUARD_INSTALLED sont declares false dans usersMenus et ne sont
		// jamais remis a true -- les deux produits ont quitte l'appliance. Leur
		// suppression ne change donc aucun flux: le "return" qu'elles portaient
		// n'a jamais pu s'executer.

		if($users->APP_UFDBGUARD_INSTALLED){
			if($EnableUfdbGuard==1){
				// Passait par framework/cmd.php?reload-ufdbguard, dont la fonction
				// reload_ufdbguard() n'existe plus: contrairement aux branches
				// DansGuardian et SquidGuard ci-dessus, CELLE-CI est atteignable
				// (APP_UFDBGUARD_INSTALLED est bien mis a true), donc le filtre
				// n'etait plus recharge du tout apres un changement de regle.
				// /ufdb/recompile est le chemin utilise par les pages fw.ufdb.*:
				// il enchaine construction puis rechargement, rend la main tout de
				// suite et fusionne les demandes concurrentes cote daemon.
				$GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/recompile");
				return;
			}
		}
		
		
	}

	function LoadOtherRules($ruleindex){
		$sql="SELECT COUNT(ID) AS Tcount FROM dansguardian_files WHERE filename='bannedextensionlist' AND RuleID=$ruleindex";
		$q=new mysql();
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		writelogs("RuleID $ruleindex bannedextensionlist={$ligne["Tcount"]} rows",__CLASS__.'/'.__FUNCTION__,__FILE__);
		
		if($ligne["Tcount"]==0){
			$this->Default_Build_BannedExtensionList($ruleindex);	
		}
		
		$sql="SELECT COUNT(ID) AS Tcount FROM dansguardian_files WHERE filename='bannedmimetypelist' AND RuleID=$ruleindex";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		writelogs("RuleID $ruleindex bannedmimetypelist={$ligne["Tcount"]} rows",__CLASS__.'/'.__FUNCTION__,__FILE__);
		if($ligne["Tcount"]==0){
			$this->Default_Build_BannedMimeTypeList($ruleindex);	
		}
		
		$sql="SELECT COUNT(ID) AS Tcount FROM dansguardian_files WHERE filename='bannedregexpurllist' AND RuleID=$ruleindex";
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		writelogs("RuleID $ruleindex bannedmimetypelist={$ligne["Tcount"]} rows",__CLASS__.'/'.__FUNCTION__,__FILE__);
		if($ligne["Tcount"]==0){
			$this->Default_Build_bannedregexpurllist($ruleindex);	
		}		
		
	}

	
	function Add_bannedregexpurllist($ruleindex,$pattern,$info,$enabled){
		$info=addslashes($info);
		$pattern=addslashes($pattern);
		$sql="INSERT INTO dansguardian_files (RuleID,filename,infos,pattern,enabled) VALUES('$ruleindex','bannedregexpurllist','$info','$pattern','$enabled');";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");		
		if(!$q->ok){
			echo $q->mysql_error."\n";
			writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__);
			writelogs("$sql",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return;
		}
		}
		

		
	function Add_bannedextensionlist($ruleindex,$extension,$info,$enabled){
		if(substr($extension,0,1)<>'.'){$extension='.'.$extension;}
		$extension=str_replace("*",'',$extension);
		writelogs("add new banned extension <info>$info</info><pattern>$extension</pattern><enabled>$enabled</enabled>",__CLASS__.'/'.__FUNCTION__,__FILE__);
		$sql="INSERT INTO dansguardian_files (RuleID,filename,infos,pattern,enabled) VALUES('$ruleindex','bannedextensionlist','$info','$extension','$enabled');";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");		
		if(!$q->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__);}
		}

		
		
		
	function Add_bannedmimetype($ruleindex,$pattern,$info,$enabled){
		$sql="INSERT INTO dansguardian_files (RuleID,filename,infos,pattern,enabled) VALUES('$ruleindex','bannedmimetypelist','$info','$pattern','$enabled');";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");		
		if(!$q->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__);}	
		$sock=new sockets();
		$this->RestartFilters();
		}



	private function Default_Build_BannedExtensionList($ruleindex){
		$arr=$this->array_bannedextensionlist;
		while (list ($num, $val) = each ($arr) ){
			$this->Add_bannedextensionlist($ruleindex,$num,$val,1);

		}

	}

	private function Default_Build_BannedMimeTypeList($ruleindex){
		$arr=$this->array_default_mimetype;
		while (list ($num, $val) = each ($arr) ){
			$this->Add_bannedmimetype($ruleindex,$val,$val,1);
		}

	}

	private function Default_Build_bannedregexpurllist($ruleindex){
		 $this->Add_bannedregexpurllist($ruleindex,
		 '(^|[-\?+=&/_])(big|cyber|hard|huge|mega|small|soft|super|tiny)?(anal|babe|bharath|boob|breast|busen|busty|clit|cum|cunt|dick|fetish|fuck|hooter|lez|lust|naked|nude|oral|orgy|porn|porno|pupper|pussy|rotten|sex|shit|smutpump|teen|tit|topp?les|xxx)s?([-\?+=&/_]|$)',
		 'Pornography, Modelling and Adult Sites These have been taken from www.squidguard.org',
		 0);


		 $this->Add_bannedregexpurllist(
		 $ruleindex,
		 '(^|[\?+=&/])(girls|babes|toples)([\?+=&/]|$)',
		 'Pornography, Modelling and Adult Sites These have been taken from www.squidguard.org',0);

		 $this->Add_bannedregexpurllist(
		 $ruleindex,
		 '(^|[-\?+=&/_])(big|cyber|hard|huge|mega|small|soft|super|tiny|bare|naked|nude|anal|oral|topp?les|sex|phone){1,}.*(anal|babe|bharath|boob|breast|busen|busty|clit|cum|cunt|dick|fetish|fuck|girl|hooter|lez|lust|naked|nude|oral|orgy|penis|porn|porno|pupper|pussy|rotten|sex|shit|smutpump|teen|tit|topp?les|xxx)s?([-\?+=&/_]|$)
(^|[-\?+=&/_])(anal|babe|bharath|boob|breast|busen|busty|clit|cum|cunt|dick|fetish|fuck|girl|hooter|lez|lust|naked|nude|oral|orgy|penis|porn|porno|pupper|pussy|rotten|sex|shit|smutpump|teen|tit|topp?les|xxx){1,}.*(big|cyber|hard|huge|mega|small|soft|super|tiny|bare|naked|nude|anal|oral|topp?les|sex){1,}([-\?+=&/_]|$)',
		'Pornography, Modelling and Adult Sites Philip Pearce 9/11/2004'
		,1);


		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(^|[-\?+=&/_])(anal|babe|bharath|boob|breast|busen|busty|clit|cum|cunt|dick|fetish|fuck|girl|hooter|lez|lust|naked|nude|oral|orgy|penis|porn|porno|pupper|pussy|rotten|sex|shit|smutpump|teen|tit|topp?les|xxx){1,}.*(big|cyber|hard|huge|mega|small|soft|super|tiny|bare|naked|nude|anal|oral|topp?les|sex){1,}([-\?+=&/_]|$)',
				'Pornography, Modelling and Adult Sites Philip Pearce 9/11/2004'
				,1);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(adultsight|adultsite|adultsonly|adultweb|blowjob|bondage|centerfold|cumshot|cyberlust|cybercore|hardcore|masturbat)',
				 'HardCore phrases'
				,1);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(bangbros|pussylip|playmate|pornstar|sexdream|showgirl|softcore|striptease)',
				 'HardCore phrases'
				,1);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(incest|obscene|pedophil|pedofil)',
				 'SoftCore phrases - more likely to overblock - possibly on news sites'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(male|m[ae]n|boy|girl|beaut|agen[ct]|glam){1,}.*(model|talent)',
				 'Photo Modeling - supplied by David Burkholder'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(sex|fuck|boob|cunt|fetish|tits|anal|hooter|asses|shemale|submission|porn|xxx|busty|knockers|slut|nude|naked|pussy){1,}.*(\.jpg|\.wmv|\.mpg|\.mpeg|\.gif|\.mov)',
				 'Block explicit media files (images and video)'
				,1);


		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(girls|babes|bikini|model){1,}.*(\.jpg|\.wmv|\.mpg|\.mpeg|\.gif|\.mov)',
				 'Block explicit media files (images and video)'
				,1);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(naturism|naturist|nude|nudist|nudism|nekkid|nakt|naakt)',
				 'Block Naturism and Nudist sites'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(^|[\?+=&/])(.*\.google\..*/.*\?.*safe=off)([\?+=&/]|$)',
				 'Block unfiltered options on various search engines'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(^|[\?+=&/])(.*\.alltheweb.com/customize\?.*copt_offensive=off)([\?+=&/]|$)',
				 'Block unfiltered options on various search engines'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(yahoo.com\/image\/)',
				 'Block images and video on altavista, alltheweb, yahoo etc - as they are anonomised'
				,0);


		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(yimg.com\/image\/)',
				 'Block images and video on altavista, alltheweb, yahoo etc - as they are anonomised'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(altavista.com\/image\/)',
				 'Block images and video on altavista, alltheweb, yahoo etc - as they are anonomised'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(picsearch.com\/is)',
				 'Block images and video on altavista, alltheweb, yahoo etc - as they are anonomised'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(images.google){1,}.*(\.jpg|\.wmv|\.mpg|\.mpeg|\.gif|\.mov)',
				 'Block images and video on google'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(google.com\/video)',
				 'Block images and video on google (videos)'
				,0);


		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(google.com\/ThumbnailServer)',
				 'Block images and video on google (Thumbnail)'
				,0);


		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(google.com\/videoplay)',
				 'Block images and video on google (playing video)'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(cecid.php|nph-webpr|nph-pro|/dmirror|cgiproxy|phpwebproxy|__proxy_url|proxy.php)',
				 'Block Cgiproxy, Poxy, PHProxy and other Web-based proxies'
				,1);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(anonymizer|proxify|megaproxy)',
				 'Block websites containing proxy lists'
				,1);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(casino|bet(ting|s)|lott(ery|o)|gam(e[rs]|ing|bl(e|ing))|sweepstake|poker)',
				 'Gambling - supplied by David Burkholder'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(bowling|badminton|box(e[dr]|ing)|skat(e[rs]|ing)|hockey|soccer|nascar|wrest|rugby|tennis|sports|cheerlead|rodeo|cricket|badminton|stadium|derby)',
				 'Sport - supplied by David Burkholder'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(speed(st|wa|y)|corvette|rac[eiy]|wrest|harley|motorcycle|nascar)',
				 'Racing - supplied by David Burkholder (1)'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(news(watch|pap|cast)|herald|sentinel|courier|gazet|tribune|chronicle|daily|ning)news)',
				 'News sites - supplied by David Burkholder (2)'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(meet|hook|mailord|latin|(asi|mexic|dominic|russi|kore|colombi|balk)an|brazil|filip|french|chinese|ukrain|thai|tour|foreign|date){1,}.*(dar?[lt]ing|(sing|coup)le|m[ae]n|girl|boy|guy|mat(e|ing)|l[ou]ve?|partner|meet)',
				 'Dating Sites - supplied by David Burkholder (1)'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(marr(y|i[ae])|roman(ce|tic)|fiance|bachelo|dating|affair|personals)',
				 'Dating Sites - supplied by David Burkholder (2)'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(adlog.php|cnt.cgi|count.cgi|count.dat|count.jsp|count.pl|count.php|counter.cgi|counter.js|counter.pl|countlink.cgi|fpcount.exe|logitpro.cgi|rcounter.dll|track.pl|w_counter.js)',
				 'block web counters'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(free|phone|mobile){1,}.*(love|music|movie|dvd|video|stuff|site|arcade|wallpaper|mp3)',
				 'Free stuff - supplied by David Burkholder (1)'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '((ring|real)tone)',
				 'Free stuff - supplied by David Burkholder (2)'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(rock|pop|jazz|rap|punk){1,}.*(cult|roll|geek|drum|music|history|band)',
				 'Music - supplied by David Burkholder'
				,0);

		$this->Add_bannedregexpurllist(
				 $ruleindex,
				 '(Variants/AVTest)',
				 'his is part of the URL for the dansguardian.org sample virus archive.You probably don\'t want to go there unintentionally'
				,1);




		}

	function BuildBranch(){}

	private function BuildDefaults(){
		if($this->Main_array["groupmode"]==null){$this->Main_array["groupmode"]=1;}
		if($this->Main_array["naughtynesslimit"]==null){$this->Main_array["naughtynesslimit"]=50;}
		if($this->Main_array["categorydisplaythreshold"]==null){$this->Main_array["categorydisplaythreshold"]=0;}
		if($this->Main_array["embeddedurlweight"]==null){$this->Main_array["embeddedurlweight"]=0;}
		if($this->Main_array["deepurlanalysis"]==null){$this->Main_array["deepurlanalysis"]="off";}


	}


	function BuildMainRule(){


	}





	
	
	
	
	
	
}




?>
