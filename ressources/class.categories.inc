<?php
include_once(dirname(__FILE__)."/class.postgres.inc");
include_once(dirname(__FILE__)."/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/class.dansguardian.inc");
include_once(dirname(__FILE__)."/class.html.tools.inc");

class categories{
    public $mysql_error=null;
    public $last_id=0;
    public $output=false;
    public $cached=true;

    public function __construct(){


    }


    public function initialize():bool{
        $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
        if($DisablePostGres==1){
            return false;
        }
        if(isset($GLOBALS["categories_initialize"])){return true;}
        $GLOBALS["categories_initialize"]=true;
        $q=new postgres_sql();

        if($GLOBALS["VERBOSE"]){VERBOSE("INIT initialize();", __LINE__);}
        if($q->TABLE_EXISTS("personal_categories")) {
            $q->QUERY_SQL("UPDATE personal_categories SET remotecatz=0 WHERE serviceid=0");
            if ($q->COUNT_ROWS("personal_categories") > 100) {
                VERBOSE("personal_categories > 100, return...", __LINE__);
                return true;
            }
        }
        if(!$q->create_personal_categories()) {
            if (function_exists("writelogs")) {
                writelogs($q->mysql_error, __FUNCTION__, __FILE__, __LINE__);
            }
        }

        if(!$q->TABLE_EXISTS("personal_categories")){
            $q->create_personal_categories();
        }

        if(!$q->TABLE_EXISTS("personal_categories")){
            if (function_exists("writelogs")) {
                writelogs("Unable to create personal_categories table !!!", __FUNCTION__, __FILE__, __LINE__);
                writelogs($q->mysql_error, __FUNCTION__, __FILE__, __LINE__);
            }
            return false;

        }
        if(!$q->FIELD_EXISTS("personal_categories", "compiledate")){
            $q->QUERY_SQL("alter table personal_categories add column if not exists compiledate bigint;");
            if(!$q->ok){echo $q->mysql_error;}
        }
        if(!$q->FIELD_EXISTS("personal_categories", "remotecatz")){
            $q->QUERY_SQL("alter table personal_categories add column if not exists remotecatz smallint;");
            if(!$q->ok){echo $q->mysql_error;}
        }

        $f[]="(1,'facebook','Facebook','category_facebook','facebook','/img/facebook-20.png','',1,1,0,0)";
        $f[]="(2,'microsoft','Microsoft','category_microsoft','Microsoft','/img/softwares-20.png','',1,1,0,0)";
        $f[]="(3,'society','Society','category_society','{www_society}','/img/society-20.png','',1,1,0,0)";
        $f[]="(5,'publicite','Advertising','category_publicite','{publicite}','/img/icon-ads.png','',1,1,0,0)";
        $f[]="(6,'phishtank','Phishtank','category_phishtank','{www_phishtank}','/img/icon-fish.png','',1,1,0,0)";
        $f[]="(7,'ransomwares','Ransomwares','category_ransomwares','{www_ransomwares}','/img/virus-20.png','',1,1,0,0)";
        $f[]="(8,'shopping','Shopping','category_shopping','{www_shopping}','/img/20-shopping.png','',1,1,0,0)";
        $f[]="(9,'abortion','Abortion','category_abortion','{www_abortion}','/img/abortion-20.png','',1,1,0,0)";
        $f[]="(10,'agressive','Agressive','category_agressive','{agressif}','/img/icon_fight.png','',1,1,0,0)";
        $f[]="(11,'alcohol','Alcohol','category_alcohol','{www_alcohol}','/img/alcohol-20.png','',1,1,0,0)";
        $f[]="(12,'animals','Animals','category_animals','{www_animals}','/img/20-animals.png','',1,1,0,0)";
        $f[]="(13,'associations','Associations','category_associations','{www_associations}','/img/20-assocs.png','',1,1,0,0)";
        $f[]="(14,'astrology','Astrology','category_astrology','{www_astrology}','/img/astrology-20.png','',1,1,0,0)";
        $f[]="(15,'audio_video','Audio-video','category_audio_video','{audio-video}','/img/20-audio-video.png','',1,1,0,0)";
        $f[]="(16,'youtube','Youtube','category_youtube','Youtube','/img/youtube-20.png','',1,1,0,0)";
        $f[]="(17,'google','Google','category_google','{www_google}','/img/google-20.png','',1,1,0,0)";
        $f[]="(18,'apple','Apple','category_apple','{www_apple}','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(19,'amazonaws','Amazonaws','category_amazonaws','{www_amazonaws}','/img/softwares-20.png','',1,1,0,0)";
        $f[]="(20,'akamai','Akamai','category_akamai','Akamai Technologies','/img/softwares-20.png','',1,1,0,0)";
        $f[]="(21,'yahoo','Yahoo','category_yahoo','Yahoo Web sites','/img/softwares-20.png','',1,1,0,0)";
        $f[]="(22,'skype','Skype','category_skype','Microsoft Skype','/img/icon-chat-20.png','',1,1,0,0)";
        $f[]="(23,'citrix','Citrix','category_citrix','Citrix, Citrix Online','/img/softwares-20.png','',1,1,0,0)";
        $f[]="(24,'automobile_bikes','Bikes','category_automobile_bikes','{automobile-bikes}','/img/icon-moto.gif','',1,1,0,0)";
        $f[]="(25,'automobile_boats','Boats','category_automobile_boats','{automobile-boats}','/img/icon-boat.png','',1,1,0,0)";
        $f[]="(26,'automobile_carpool','Carpool','category_automobile_carpool','{automobile-carpool}','/img/carpool-20.png','',1,1,0,0)";
        $f[]="(27,'automobile_cars','Cars','category_automobile_cars','{automobile-cars}','/img/icon-car.png','',1,1,0,0)";
        $f[]="(28,'automobile_planes','Planes','category_automobile_planes','{automobile-planes}','/img/icon-plane.png','',1,1,0,0)";
        $f[]="(29,'bicycle','Bicycles','category_bicycle','{www_bicycle}','/img/bicycle-20.png','',1,1,0,0)";
        $f[]="(30,'blog','Blogs','category_blog','{blog}','/img/20-blog.png','',1,1,0,0)";
        $f[]="(31,'books','Books','category_books','{www_books}','/img/books_20.png','',1,1,0,0)";
        $f[]="(32,'browsersplugins','Browsers plugins','category_browsersplugins','{www_browsersplugins}','/img/20-webplugins.png','',1,1,0,0)";
        $f[]="(33,'celebrity','Celebrities','category_celebrity','{www_celebrity}','/img/celebrity-20.png','',1,1,0,0)";
        $f[]="(34,'chat','Chat','category_chat','{www_chat}','/img/icon-chat-20.png','',1,1,0,0)";
        $f[]="(35,'children','Children','category_children','{www_children}','/img/baby-20.png','',1,1,0,0)";
        $f[]="(36,'cleaning','Cleaning','category_cleaning','{cleaning}','/img/icon-clean.png','',1,1,0,0)";
        $f[]="(37,'clothing','Clothing','category_clothing','{www_clothing}','/img/20-clothing.png','',1,1,0,0)";
        $f[]="(38,'converters','Converters','category_converters','{www_converters}','/img/calc-20.png','',1,1,0,0)";
        $f[]="(39,'cosmetics','Cosmetics','category_cosmetics','{www_cosmetics}','/img/20-cosmetics.png','',1,1,0,0)";
        $f[]="(40,'culture','Culture','category_culture','{www_culture}','/img/culture-20.png','',1,1,0,0)";
        $f[]="(41,'dangerous_material','Dangerous materials','category_dangerous_material','{dangerous_material}','/img/icon-bomb.png','',1,1,0,0)";
        $f[]="(42,'dating','Dating','category_dating','{dating}','/img/20-dating.png','',1,1,0,0)";
        $f[]="(43,'dictionaries','Dictionaries','category_dictionaries','{www_dictionaries}','/img/help-book-20.png','',1,1,0,0)";
        $f[]="(44,'downloads','Downloads','category_downloads','{www_downloads}','/img/icon-download-20.png','',1,1,0,0)";
        $f[]="(45,'drugs','Drugs','category_drugs','{drogue}','/img/icon-drugs.png','',1,1,0,0)";
        $f[]="(46,'dynamic','Dynamic DHCP ISP','category_dynamic','{www_dynamic}','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(47,'electricalapps','Electrical appliances','category_electricalapps','{electricalapps}','/img/electricalapps-20.png','',1,1,0,0)";
        $f[]="(48,'electronichouse','Electronic house','category_electronichouse','{www_electronichouse}','/img/hifi-20.png','',1,1,0,0)";
        $f[]="(49,'filehosting','Filehosting','category_filehosting','{filehosting}','/img/20-storage.png','',1,1,0,0)";
        $f[]="(50,'finance_banking','Banking','category_finance_banking','{finance-banking}','/img/icon-bank.png','',1,1,0,0)";
        $f[]="(51,'finance_insurance','Insurances','category_finance_insurance','{finance-insurance}','/img/icon-bank.png','',1,1,0,0)";
        $f[]="(52,'finance_moneylending','Moneylending','category_finance_moneylending','{finance-moneylending}','/img/icon-bank.png','',1,1,0,0)";
        $f[]="(53,'finance_other','Finance/other','category_finance_other','{finance-other}','/img/icon-bank.png','',1,1,0,0)";
        $f[]="(54,'finance_realestate','Realestate','category_finance_realestate','{finance-realestate}','/img/icon-house-20.gif','',1,1,0,0)";
        $f[]="(55,'financial','Financial','category_financial','{financial}','/img/icon-bank.png','',1,1,0,0)";
        $f[]="(56,'forums','Forums','category_forums','{forums}','/img/icon_forum.png','',1,1,0,0)";
        $f[]="(57,'gamble','Gambling','category_gamble','{gambling}','/img/icon_poker.png','',1,1,0,0)";
        $f[]="(58,'games','Games','category_games','{games}','/img/icon-games.png','',1,1,0,0)";
        $f[]="(59,'genealogy','Genealogy','category_genealogy','{www_genealogy}','/img/genealogy-20.png','',1,1,0,0)";
        $f[]="(60,'gifts','Gifts','category_gifts','{www_gifts}','/img/gifts-20.png','',1,1,0,0)";
        $f[]="(62,'governments','Governments','category_governments','{www_government}','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(63,'green','Green','category_green','{www_green}','/img/20_green.png','',1,1,0,0)";
        $f[]="(64,'hacking','Hacking','category_hacking','{hacking}','/img/icon-hacks.png','',1,1,0,0)";
        $f[]="(65,'handicap','Handicap','category_handicap','{cat_handicap}','/img/handicap-20.png','',1,1,0,0)";
        $f[]="(66,'health','Health','category_health','{www_health}','/img/health-20.png','',1,1,0,0)";
        $f[]="(67,'hobby_arts','Arts','category_hobby_arts','{hobby-arts}','/img/art-20.png','',1,1,0,0)";
        $f[]="(68,'hobby_cooking','Cooking','category_hobby_cooking','{hobby-cooking}','/img/icon-food.png','',1,1,0,0)";
        $f[]="(69,'hobby_other','Hobby Other','category_hobby_other','{hobby-other}','/img/hobby-20.png','',1,1,0,0)";
        $f[]="(70,'hobby_pets','Pets','category_hobby_pets','{hobby-pets}','/img/icon-pets.png','',1,1,0,0)";
        $f[]="(71,'paytosurf','Paytosurf','category_paytosurf','{www_paytosurf}','/img/paytosurf-20.png','',1,1,0,0)";
        $f[]="(72,'terrorism','Terrorism','category_terrorism','{www_terrorism}','/img/20-terrorisme.png','',1,1,0,0)";
        $f[]="(73,'hobby_fishing','Fishing','category_hobby_fishing','{hobby-fishing}','/img/fish-20.png','',1,1,0,0)";
        $f[]="(74,'hospitals','Hospitals','category_hospitals','{cat_hospitals}','/img/hospital.gif','',1,1,0,0)";
        $f[]="(75,'houseads','Houseads','category_houseads','{houseads}','/img/houseads-20.png','',1,1,0,0)";
        $f[]="(76,'housing_accessories','Housing accessories','category_housing_accessories','{housing_accessories}','/img/homeaccess-20.png','',1,1,0,0)";
        $f[]="(77,'housing_doityourself','Housing doityourself','category_housing_doityourself','{www_housingbric}','/img/bric-20.png','',1,1,0,0)";
        $f[]="(78,'housing_builders','Housing builders','category_housing_builders','{www_housingbuilders}','/img/icon-house-20.gif','',1,1,0,0)";
        $f[]="(79,'humanitarian','Humanitarian','category_humanitarian','{www_humanitarian_associations}','/img/humanitarian-20.png','',1,1,0,0)";
        $f[]="(80,'imagehosting','Images hosting','category_imagehosting','{www_imagehosting}','/img/picasa_icon.png','',1,1,0,0)";
        $f[]="(81,'industry','Industry','category_industry','{www_industry}','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(82,'internal','Internal','category_internal','{www_internal}','/img/network-1-20.gif','',1,1,0,0)";
        $f[]="(83,'isp','Internet Providers','category_isp','{www_isp}','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(85,'jobsearch','Jobsearch','category_jobsearch','{jobsearch}','/img/icon_forum.png','',1,1,0,0)";
        $f[]="(86,'jobtraining','Jobtraining','category_jobtraining','{jobtraining}','/img/jobtraining-20.png','',1,1,0,0)";
        $f[]="(87,'justice','Justice','category_justice','{www_justice}','/img/justice-20.png','',1,1,0,0)";
        $f[]="(88,'learning','Learning','category_learning','{www_learning}','/img/jobtraining-20.png','',1,1,0,0)";
        $f[]="(89,'liste_bu','Global White List','category_liste_bu','{liste_bu}','/img/flags/fr.png','',1,1,0,0)";
        $f[]="(90,'luxury','Luxury','category_luxury','{www_luxury}','/img/jewel-20.png','',1,1,0,0)";
        $f[]="(91,'mailing','Mailing','category_mailing','{www_mailing}','/img/20-mailing.png','',1,1,0,0)";
        $f[]="(92,'malware','Malwares','category_malware','{www_malware}','/img/icon_malwares.png','',1,1,0,0)";
        $f[]="(93,'manga','Mangas','category_manga','{www_mangua}','/img/20-manga.png','',1,1,0,0)";
        $f[]="(94,'maps','Maps','category_maps','{www_maps}','/img/20-maps.png','',1,1,0,0)";
        $f[]="(95,'marketingware','Marketingware','category_marketingware','{marketingware}','/img/20-marketing.png','',1,1,0,0)";
        $f[]="(96,'medical','Medical','category_medical','{cat_medical}','/img/hospital.gif','',1,1,0,0)";
        $f[]="(97,'mixed_adult','Mixed adult','category_mixed_adult','{mixed_adult}','/img/sex-icon.png','',1,1,0,0)";
        $f[]="(98,'mobile_phone','Smartphones','category_mobile_phone','{mobile-phone}','/img/20-mobile-phone.png','',1,1,0,0)";
        $f[]="(99,'models','Models','category_models','{models}','/img/20-model-agency.png','',1,1,0,0)";
        $f[]="(100,'movies','Movie and Streaming','category_movies','{movies}','/img/20-cinema.png','',1,1,0,0)";
        $f[]="(101,'music','Music','category_music','{music}','/img/fire-guitar-icon.png','',1,1,0,0)";
        $f[]="(102,'nature','Nature','category_nature','{www_nature}','/img/nature-20.png','',1,1,0,0)";
        $f[]="(103,'news','News and press','category_news','{www_news}','/img/20-news.png','',1,1,0,0)";
        $f[]="(104,'passwords','Passwords','category_passwords','{cat_passwords}','/img/lock.gif','',1,1,0,0)";
        $f[]="(105,'phishing','Phishing','category_phishing','{phishing}','/img/icon-fish.png','',1,1,0,0)";
        $f[]="(106,'photo','Photo','category_photo','{www_photo}','/img/camera-20.png','',1,1,0,0)";
        $f[]="(107,'pictureslib','Pictures libraries','category_pictureslib','{www_pictureslib}','/img/cat_pictures.png','',1,1,0,0)";
        $f[]="(108,'politic','Politic','category_politic','{www_politic}','/img/20-politic.png','',1,1,0,0)";
        $f[]="(109,'porn','Porn','category_porn','{adult}','/img/sex-icon.png','',1,1,0,0)";
        $f[]="(111,'proxy','Proxies','category_proxy','{www_proxy}','/img/20-anonymous.png','',1,1,0,0)";
        $f[]="(112,'reaffected','Reaffected','category_reaffected','{reaffected}','/img/20-reaffect.png','',1,1,0,0)";
        $f[]="(113,'recreation_humor','Humor','category_recreation_humor','{recreation_humor}','/img/20-humour.png','',1,1,0,0)";
        $f[]="(114,'recreation_nightout','Nightout','category_recreation_nightout','{recreation_nightout}','/img/icon_nightclub.png','',1,1,0,0)";
        $f[]="(115,'recreation_schools','Schools Educational','category_recreation_schools','{www_schools}','/img/20-schools.png','',1,1,0,0)";
        $f[]="(116,'recreation_sports','Sports','category_recreation_sports','{recreation_sports}','/img/icon-sport.png','',1,1,0,0)";
        $f[]="(117,'getmarried','Get Married','category_getmarried','{www_getmarried}','/img/20_getmarried.png','',1,1,0,0)";
        $f[]="(118,'police','Police','category_police','{www_police}','/img/20-police.png','',1,1,0,0)";
        $f[]="(119,'recreation_travel','Travel','category_recreation_travel','{recreation_travel}','/img/icon-travel.png','',1,1,0,0)";
        $f[]="(120,'recreation_wellness','Wellness','category_recreation_wellness','{recreation_wellness}','/img/20-heathly.png','',1,1,0,0)";
        $f[]="(121,'redirector','Redirector','category_redirector','{redirector}','/img/20-redirect.png','',1,1,0,0)";
        $f[]="(122,'religion','Religion','category_religion','{religion}','/img/20-christians.png','',1,1,0,0)";
        $f[]="(123,'remote_control','Remote-control','category_remote_control','{cat_remote-control}','/img/20-win-nic.png','',1,1,0,0)";
        $f[]="(124,'sciences','Sciences','category_sciences','{www_sciences}','/img/sciences-20.png','',1,1,0,0)";
        $f[]="(125,'science_astronomy','Astronomy','category_science_astronomy','{science_astronomy}','/img/astronomy-20.png','',1,1,0,0)";
        $f[]="(126,'science_computing','Science computing','category_science_computing','{cat_computing}','/img/base.gif','',1,1,0,0)";
        $f[]="(127,'science_weather','Weather','category_science_weather','{science_weather}','/img/20-climat.png','',1,1,0,0)";
        $f[]="(128,'science_chemistry','Chemistry','category_science_chemistry','{science_chemistry}','/img/20-chemistery.png','',1,1,0,0)";
        $f[]="(129,'searchengines','Searchengines','category_searchengines','{searchengines}','/img/icon-search.png','',1,1,0,0)";
        $f[]="(130,'sect','Sect','category_sect','{sect}','/img/icon-sect.png','',1,1,0,0)";
        $f[]="(131,'sexual_education','Sexual Education','category_sexual_education','{sexual_education}','/img/kiss-icon.png','',1,1,0,0)";
        $f[]="(132,'sex_lingerie','Lingerie (sex)','category_sex_lingerie','{sex_lingerie}','/img/sex-icon.png','',1,1,0,0)";
        $f[]="(133,'smallads','Smallads','category_smallads','{www_smallads}','/img/smallads-20.png','',1,1,0,0)";
        $f[]="(134,'socialnet','Social Networks','category_socialnet','{socialnet}','/img/tree-groups.gif','',1,1,0,0)";
        $f[]="(135,'spyware','Spywares','category_spyware','{www_spyware}','/img/20-setup.png','',1,1,0,0)";
        $f[]="(136,'sslsites','SSL certificates','category_sslsites','{cat_sslsites}','/img/icon-ssl-20.png','',1,1,0,0)";
        $f[]="(137,'stockexchange','Stockexchange','category_stockexchange','{stockexchange}','/img/stockexchange-20.png','',1,1,0,0)";
        $f[]="(139,'strong_redirector','Redirector','category_redirector','','/img/20-redirect.png','',1,1,0,0)";
        $f[]="(140,'suspicious','Suspicious','category_suspicious','{www_suspicious}','/img/suspicious-20.png','',1,1,0,0)";
        $f[]="(141,'teens','Teens','category_teens','{www_teens}','/img/20-teans.png','',1,1,0,0)";
        $f[]="(142,'tobacco','Tobacco','category_tobacco','{www_tobacco}','/img/20-tobacco.png','',1,1,0,0)";
        $f[]="(143,'tracker','Trackers','category_tracker','{www_tracker}','/img/icon_eye.png','',1,1,0,0)";
        $f[]="(144,'translators','Translators','category_translators','{www_translators}','/img/icon-translation.png','',1,1,0,0)";
        $f[]="(145,'transport','Transport','category_transport','{www_transport}','/img/transport-20.png','',1,1,0,0)";
        $f[]="(146,'tricheur','Cheater','category_tricheur','{tricheur}','/img/20-exams.png','',1,1,0,0)";
        $f[]="(147,'updatesites','Updatesites','category_updatesites','{updatesites}','/img/20-cd-scan.png','',1,1,0,0)";
        $f[]="(148,'violence','Violence','category_violence','{violence}','/img/icon_fight.png','',1,1,0,0)";
        $f[]="(149,'warez','Warez','category_warez','{warez}','/img/20-warez.png','',1,1,0,0)";
        $f[]="(150,'weapons','Weapons','category_weapons','{weapons}','/img/icon-armes.png','',1,1,0,0)";
        $f[]="(151,'webapps','Web applications','category_webapps','{www_webapps}','/img/softwares-20.png','',1,1,0,0)";
        $f[]="(152,'webmail','Webmails','category_webmail','{webmail}','/img/icon-webmail.png','',1,1,0,0)";
        $f[]="(153,'webphone','Web phones','category_webphone','{webphone}','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(154,'webplugins','Web plugins','category_webplugins','{webplugins}','/img/20-webplugins.png','',1,1,0,0)";
        $f[]="(155,'webradio','Web radio','category_webradio','{webradio}','/img/20-webradio.png','',1,1,0,0)";
        $f[]="(156,'webtv','Web TV','category_webtv','{webtv}','/img/icon-tv.png','',1,1,0,0)";
        $f[]="(157,'wine','Wine','category_wine','{www_wine}','/img/wine-20.png','',1,1,0,0)";
        $f[]="(158,'womanbrand','Womanbrand','category_womanbrand','{womanbrand}','/img/womanbrand-20.png','',1,1,0,0)";
        $f[]="(159,'horses','Horses','category_horses','{www_horses}','/img/20_horses.png','',1,1,0,0)";
        $f[]="(160,'meetings','Meetings','category_meetings','{www_meetings}','/img/meetings-20.png','',1,1,0,0)";
        $f[]="(161,'tattooing','Tattooing','category_tattooing','{www_tattooing}','/img/tatoo-20.png','',1,1,0,0)";
        $f[]="(163,'literature','Literature','category_literature','{www_literature}','/img/literature-20.png','',1,1,0,0)";
        $f[]="(228,'Poneytelecom','Poneytelecom','category_228','Poneytelecom ISP - ONLINE S.A.S','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(229,'ovh','OVH','category_229','OVH Telecom','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(230,'digitalocean','DigitalOcean','category_230','DigitalOcean ISP IP ranges','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(231,'orange','Orange SA','category_231','Orange ISP IP ranges','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(232,'softlayer','SoftLayer Technologies','category_232','SoftLayer Technologies IP ranges','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(233,'linode','Linode, LLC','category_233','Linode, LLC IP ranges','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="('181','uts_cryptojacking','cryptojacking','{cryptojacking}','category_utscryptojacking','/img/icon-hacks.png','',1,1,0,0)";
        $f[]="(234,'cloudflare','cloudflare','','CloudFlare','/img/20-categories-personnal.png','',1,0,0,0)";
        $f[]="(236,'hetzner_online','hetzner_online','','Hetzner Online','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(237,'doh_dns','doh_dns','','DOH DNS','/img/20-categories-personnal.png','',1,1,0,0)";
        $f[]="(238,'nrds','category_nrds','','{nrds_cat}','/img/20-news.png','',1,1,0,0)";
        $f[]="(248,'science_ai','Artificial Intelligence','category_ai','{category_ai}','/img/ai-20.png','',1,1,0,0)";

        if(!$q->FIELD_EXISTS("personal_categories", "free_category")){
            $q->QUERY_SQL("alter table personal_categories add column if not exists free_category smallint DEFAULT 0;");
            if(!$q->ok){return false;}
        }




        foreach ($f as $sql_suffix) {
            $sql = "INSERT INTO personal_categories (category_id,categorykey,categoryname,categorytable,category_description,category_icon,master_category,official_category,enabled,publicmode,meta) VALUES
		$sql_suffix ON CONFLICT DO NOTHING";
            VERBOSE($sql, __LINE__);
            $q->QUERY_SQL($sql);
            if (!$q->ok) { continue; }
        }
        $f=array();
        $f[]="(239,'reserved11','reserved11','','{reserved}','/img/20-categories-personnal.png','',1,0,0,0)";
        $f[]="(240,'reserved12','reserved12','','{reserved}','/img/20-categories-personnal.png','',1,0,0,0)";
        $f[]="(241,'reserved13','reserved13','','{reserved}','/img/20-categories-personnal.png','',1,0,0,0)";
        $f[]="(242,'reserved14','reserved14','','{reserved}','/img/20-categories-personnal.png','',1,0,0,0)";
        $f[]="(243,'reserved15','reserved15','','{reserved}','/img/20-categories-personnal.png','',1,0,0,0)";
        $f[]="(244,'reserved16','reserved16','','{reserved}','/img/20-categories-personnal.png','',1,0,0,0)";
        $f[]="(245,'reserved17','reserved17','','{reserved}','/img/20-categories-personnal.png','',1,0,0,0)";
        $f[]="(246,'reserved18','reserved18','','{reserved}','/img/20-categories-personnal.png','',1,0,0,0)";
        $f[]="(247,'reserved19','reserved19','','{reserved}','/img/20-categories-personnal.png','',1,0,0,0)";

        $q->QUERY_SQL("update personal_categories  SET categorykey='science_ai',categoryname='Artificial Intelligence',categorytable='category_ai',category_description='{category_ai}',category_icon='img/ai-20.png' WHERE category_id='248'");

        $sql="INSERT INTO personal_categories (category_id,categorykey,categoryname,categorytable,category_description,category_icon,master_category,official_category,enabled,publicmode,meta) VALUES
		".@implode(",", $f). " ON CONFLICT DO NOTHING";
        $q->QUERY_SQL($sql);
        if(!$q->ok){return false;}
        return true;
    }

   public function patches_categories(){
       $q=new postgres_sql();
        $q->QUERY_SQL("UPDATE personal_categories SET categoryname='cryptojacking',category_description='{cryptojacking}',official_category=1,free_category=0,enabled=1 WHERE category_id=181");

        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_228', categoryname='poneytelecom',categorykey='poneytelecom',category_description='Poneytelecom - ONLINE S.A.S IPS ranges',official_category=1,enabled=1 WHERE category_id=228");
        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_229', categoryname='OVH',categorykey='ovh',category_description='OVH Telecom IPS ranges',official_category=1,enabled=1 WHERE category_id=229");
        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_230', categoryname='DigitalOcean',categorykey='digitalocean',category_description='DigitalOcean ISP IP ranges',official_category=1,enabled=1 WHERE category_id=230");
        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_231', categoryname='Orange SA',categorykey='orange',category_description='Orange ISP IP ranges',official_category=1,enabled=1 WHERE category_id=231");
        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_232', categoryname='SoftLayer Technologies',categorykey='softlayer',category_description='SoftLayer ISP IP ranges',official_category=1,enabled=1 WHERE category_id=232");
        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_233', categoryname='Linode, LLC',categorykey='linode',category_description='Linode, LLC IP ranges',official_category=1,enabled=1 WHERE category_id=233");
        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_234', categoryname='Cloudflare',categorykey='linode',category_description='Cloudflare, Inc',official_category=1,enabled=1 WHERE category_id=234");
        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='hetzner_online', categoryname='Hetzner Online',categorykey='hetzner_online',category_description='Hetzner Online, Inc',official_category=1,enabled=1 WHERE category_id=236");
        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='hetzner_online', categoryname='DOH DNS',categorykey='doh_dns',category_description='DNS Over HTTPs',official_category=1,enabled=1 WHERE category_id=237");

        $q->QUERY_SQL("UPDATE personal_categories SET categorytable='category_nrds', categoryname='{nrds_cat}',categorykey='nrds',category_description='{nrds_cat_explain}',official_category=1,enabled=1, category_icon='/img/20-news.png' WHERE category_id=238");

}

    public function delete_category($category_id){
        $q=new postgres_sql();
        $ligne=$q->mysqli_fetch_array("SELECT categorytable FROM personal_categories WHERE category_id=$category_id");
        $categorytable=$ligne["categorytable"];
        $q->QUERY_SQL("DROP TABLE $categorytable");
        $q->QUERY_SQL("DELETE FROM personal_categories WHERE category_id=$category_id");

    }

    public function update_category_items($category_id,$items){
        $q=new postgres_sql();
        $q->QUERY_SQL("UPDATE personal_categories SET items='$items' WHERE category_id=$category_id");
    }

    public function FreeIndex(){
        $PersonalCategoriesIndexFrom=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PersonalCategoriesIndexFrom"));
        if($PersonalCategoriesIndexFrom==0){$PersonalCategoriesIndexFrom=249;}

        $sql="SELECT category_id FROM personal_categories WHERE category_id>$PersonalCategoriesIndexFrom ORDER BY category_id ASC";
        $q=new postgres_sql();
        $results=$q->QUERY_SQL($sql);

        while ($ligne = pg_fetch_assoc($results)) {
            $id=$ligne["category_id"];
            if($GLOBALS["VERBOSE"]){echo "Reserved ID $id\n";}
            $reserved[$id]=true;
        }

        $PersonalCategoriesIndexFrom=$PersonalCategoriesIndexFrom+1;
        $PersonalCategoriesIndexTO=$PersonalCategoriesIndexFrom+3000;
        for($i=$PersonalCategoriesIndexFrom;$i<$PersonalCategoriesIndexTO;$i++){
            if(!isset($reserved[$i])){
                return $i;
            }

        }

        return $PersonalCategoriesIndexFrom;

    }

    public function PatchTables(){

        $q=new postgres_sql();

        if(!$q->FIELD_EXISTS("personal_categories","remotecatz")) {
            $q->QUERY_SQL("ALTER TABLE personal_categories ADD remotecatz smallint NOT NULL DEFAULT 0");
            $q->QUERY_SQL("ALTER TABLE personal_categories ADD serviceid bigint NOT NULL DEFAULT 0");
            $q->create_index("personal_categories","idx_serviceid",array("serviceid"));
            $q->create_index("personal_categories", "idx_rcatz", array("remotecatz"));
        }

        if(!$q->FIELD_EXISTS("personal_categories","created")) {
            $q->QUERY_SQL("ALTER TABLE personal_categories ADD created bigint NOT NULL DEFAULT 0");
        }
    }

    public function create_category($categoryname,$description=null,$publicmode=0,$ID=0){
        $q=new postgres_sql();
        $time=time();
        $this->PatchTables();


        if($ID>0){
            $SERVICE_ID=$publicmode;
            $libmem=new lib_memcached();
            $q=new postgres_sql();
            $categorykey=str_replace([':', '.','\\', '/', '*',' ','-','_','(',')','{','}','[',']','%','$',';',',','?','=','#','~','&','`'], '', $categoryname);

            if(strlen($categorykey)+strlen($ID)>24){
                $categorykey=substr($categorykey,0,24-strlen($ID));
            }

            $categorykey=$categorykey."$ID";

            $sql="INSERT INTO personal_categories (category_id,categorykey,categoryname,categorytable,category_description,category_icon,master_category,official_category,enabled,publicmode,meta,remotecatz,serviceid,created) VALUES ($ID,'$categorykey','$categoryname','','From remote server','',0,0,1,0,0,1,$SERVICE_ID,$time) ON CONFLICT DO NOTHING";
            if($this->output){
                echo "INSERT INTO personal_categories.... ($ID,'$categorykey','$categoryname','','From remote server','',0,0,1,0,0,1,$SERVICE_ID)\n";
            }
            $q->QUERY_SQL($sql);

            if(!$q->ok){
                if($this->output){
                    echo "$q->mysql_error\n";
                }

                if(preg_match("#column.*?remotecatz#",$q->mysql_error)){
                    $q->QUERY_SQL("ALTER TABLE personal_categories ADD remotecatz smallint NOT NULL DEFAULT 0");
                    $q->create_index("personal_categories","idx_rcatz",array("remotecatz"));
                    $q->QUERY_SQL($sql);
                }
            }
            if(!$q->ok){
                if(preg_match("#column.*?serviceid#",$q->mysql_error)){
                    $q->QUERY_SQL("ALTER TABLE personal_categories ADD serviceid bigint NOT NULL DEFAULT 0");
                    $q->create_index("personal_categories","idx_serviceid",array("serviceid"));
                    $q->QUERY_SQL($sql);
                }
            }


            if(!$q->ok){
                $this->mysql_error=$q->mysql_error;
                return false;
            }
            $libmem->saveKey("categories_descriptions", serialize(array()),1600);
            return true;
        }


        $html=new htmltools_inc();
        $categorykey=strtolower($html->StripSpecialsChars($categoryname));
        $categorykey=str_replace([':', '.','\\', '/', '*',' ','-','_','(',')','{','}','[',']','%','$',';',',','?','=','#','~','&','`'], '', $categorykey);
        $publicmode=intval($publicmode);
        $MyNewLastID=$this->FreeIndex();
        $this->last_id=$MyNewLastID;

        $categorytable="category_{$categorykey}";
        $category_description=str_replace("'", "`", $description);
        $categoryname=$html->replace_accents($categoryname);
        $categoryname=str_replace("'","",$categoryname);

        $f[]=$MyNewLastID;
        $f[]="'$categorykey'";
        $f[]="'$categoryname'";
        $f[]="'$categorytable'";
        $f[]="'$category_description'";
        $f[]="'/img/20-categories-personnal.png'";
        $f[]="''";
        $f[]="'0'";
        $f[]="'1'"; // enabled
        $f[]="'$publicmode'";
        $f[]="'0'"; // meta
        $f[]="'0'"; // remotecatz
        $f[]="'$time'"; //created

        $sql="INSERT INTO personal_categories (category_id,categorykey,categoryname,categorytable,category_description,category_icon,master_category,official_category,enabled,publicmode,meta,remotecatz,created) VALUES
		(".@implode(",", $f). ") ON CONFLICT DO NOTHING";

        $q=new postgres_sql();
        $q->QUERY_SQL($sql);
        if(!$q->ok){$this->mysql_error=$q->mysql_error."<br><small>$sql</small>";return false;}
        $q->CREATE_CATEGORY_TABLE($categorytable);
        return true;
    }

    public function remote_categories_server_del($index){
        $q=new postgres_sql();
        $q->QUERY_SQL("DELETE FROM personal_categories WHERE serviceid=$index");
        $q->QUERY_SQL("UPDATE personal_categories SET remotecatz=0 WHERE serviceid=0");
    }

    public function CGuard_categories(){
        $lemCatz[5001] = "lem.adult.porn";
        $lemCatz[5002] = "lem.notlegal.appz";
        $lemCatz[5003] = "lem.notlegal.hardware";
        $lemCatz[5004] = "lem.games.online";
        $lemCatz[5005] = "lem.notlegal.generic";
        $lemCatz[5006] = "lem.health.prevention.drugs";
        $lemCatz[5007] = "lem.gov.justice";
        $lemCatz[5008] = "lem.gov.health";
        $lemCatz[5009] = "lem.dating";
        $lemCatz[5010] = "lem.scam";
        $lemCatz[5011] = "x.cd06.wl";
        $lemCatz[5012] = "lem.games.app.apple";
        $lemCatz[5013] = "lem.games.news";
        $lemCatz[5014] = "lem.health.prevention.accident";
        $lemCatz[5015] = "lem.finance.banks";
        $lemCatz[5016] = "lem.hobby.manga";
        $lemCatz[5017] = "lem.gamble";
        $lemCatz[5018] = "lem.games.app.pc";
        $lemCatz[5019] = "lem.health.risk.drugs";
        $lemCatz[5020] = "lem.companies.insurance";
        $lemCatz[5021] = "lem.games.app.android";
        $lemCatz[5022] = "lem.health.prevention.riskypractice";
        $lemCatz[5023] = "lem.gov.country.fr";
        $lemCatz[5024] = "lem.infected";
        $lemCatz[5025] = "lem.chat";
        $lemCatz[5026] = "lem.tracker";
        $lemCatz[5027] = "lem.malware";
        $lemCatz[5028] = "lem.gov.edu";
        $lemCatz[5029] = "lem.notlegal.moviez";
        $lemCatz[5030] = "lem.notlegal.p2p";
        $lemCatz[5031] = "lem.certificate";
        $lemCatz[5032] = "lem.search.safe-engines";
        $lemCatz[5033] = "lem.fake.news";
        $lemCatz[5034] = "lem.fake.joke";
        $lemCatz[5035] = "lem.fake.health";
        $lemCatz[5036] = "lem.notlegal.downloadz";
        $lemCatz[5037] = "lem.gov.rescue";
        $lemCatz[5038] = "lem.gov.towns";
        $lemCatz[5039] = "lem.gov.regions";
        $lemCatz[5040] = "lem.gov.country";
        $lemCatz[5041] = "lem.health.prevention.sects";
        $lemCatz[5042] = "lem.notlegal.extractor";
        $lemCatz[5043] = "lem.health.risk.sects";
        $lemCatz[5044] = "lem.gov.police";
        $lemCatz[5045] = "lem.finance.fr.notlegal";
        $lemCatz[5046] = "lem.gov.generic";
        $lemCatz[5047] = "lem.companies.industries";
        $lemCatz[5048] = "lem.adult.underwears";
        $lemCatz[5049] = "lem.health.products";
        $lemCatz[5050] = "lem.network.monitoring";
        $lemCatz[5051] = "lem.hobby.sport";
        $lemCatz[5052] = "x.cd06.bl";
        $lemCatz[5053] = "lem.computing";
        $lemCatz[5054] = "lem.shop";
        $lemCatz[5055] = "lem.companies.itservices";
        $lemCatz[5056] = "lem.computing.pentesting";
        $lemCatz[5057] = "lem.computing.freedns";
        $lemCatz[5058] = "lem.adult.sexshop";
        $lemCatz[5059] = "lem.learning";
        $lemCatz[5060] = "lem.computing.update";
        $lemCatz[5061] = "lem.hobby.art";
        $lemCatz[5062] = "lem.cooking";
        $lemCatz[5063] = "lem.companies.buildings";
        $lemCatz[5064] = "lem.hobby.genealogy";
        $lemCatz[5065] = "lem.hobby.astrology";
        $lemCatz[5066] = "lem.ads";
        $lemCatz[5067] = "lem.news.tv";
        $lemCatz[5068] = "lem.news.mag";
        $lemCatz[5069] = "lem.computing.webdesign";
        $lemCatz[5070] = "lem.learning.schools";
        $lemCatz[5071] = "lem.health.hospitals";
        $lemCatz[5072] = "lem.hobby.travel";
        $lemCatz[5073] = "lem.learning.manuals";
        $lemCatz[5074] = "lem.learning.languages";
        $lemCatz[5075] = "lem.learning.tools";
        $lemCatz[5076] = "lem.hobby.animals";
        $lemCatz[5077] = "lem.hobby.music";
        $lemCatz[5078] = "lem.hobby.vehicles";
        $lemCatz[5079] = "lem.health.disease";
        $lemCatz[5080] = "lem.app.productivity";
        $lemCatz[5081] = "lem.hobby.books";
        $lemCatz[5082] = "lem.hobby.photo";
        $lemCatz[5083] = "lem.sciences";
        $lemCatz[5084] = "lem.companies.realestate";
        $lemCatz[5085] = "lem.blogs.design";
        $lemCatz[5086] = "lem.blogs.geek";
        $lemCatz[5087] = "lem.socialnet";
        $lemCatz[5088] = "lem.blogs.persdev";
        $lemCatz[5089] = "lem.companies.models";
        $lemCatz[5090] = "lem.politics";
        $lemCatz[5091] = "lem.blogs";
        $lemCatz[5092] = "lem.ecology";
        $lemCatz[5093] = "lem.proxy";
        $lemCatz[5094] = "lem.phishing";
        $lemCatz[5095] = "lem.finance.cryptocoins";
        $lemCatz[5096] = "lem.health.risk";
        $lemCatz[5097] = "lem.pictures";
        $lemCatz[5098] = "lem.blogs.celebrity";
        $lemCatz[5099] = "lem.blogs.fashion";
        $lemCatz[5100] = "lem.blogs.diy";
        $lemCatz[5101] = "lem.violence";
        $lemCatz[5102] = "lem.blogs.history";
        $lemCatz[5103] = "lem.hobby.nature";
        $lemCatz[5104] = "lem.notmoderated";
        $lemCatz[5105] = "lem.blogs.lifestyle";
        $lemCatz[5106] = "lem.videos";
        $lemCatz[5107] = "lem.pwned";
        $lemCatz[5108] = "lem.religious";
        $lemCatz[5109] = "lem.tattouing";
        $lemCatz[5110] = "lem.companies.events";
        $lemCatz[5111] = "lem.anonymous";
        $lemCatz[5112] = "lem.visio";
        $lemCatz[5113] = "lem.adult.generic";
        $lemCatz[5114] = "lem.weapons";
        $lemCatz[5115] = "lem.job";
        $lemCatz[5116] = "lem.blogs.lgbt";
        $lemCatz[5117] = "lem.apple";
        $lemCatz[5118] = "lem.timeloose";
        $lemCatz[5119] = "lem.learning.cheat";
        $lemCatz[5120] = "lem.health.prevention";
        $lemCatz[5121] = "lem.computing.antivirus";
        $lemCatz[5122] = "lem.videos.youtube";
        $lemCatz[5123] = "lem.videos.tv";
        $lemCatz[5124] = "lem.notused";
        $lemCatz[5125] = "lem.search.nosafe-engines";
        $lemCatz[5126] = "lem.meteo";
        $lemCatz[5127] = "lem.finance.loan";
        $lemCatz[5128] = "lem.companies.printing";
        return $lemCatz;
    }

    public function remote_categories_server_md5($index){
        $q=new postgres_sql();
        $ARRAY=array();
        $results=$q->QUERY_SQL("SELECT * FROM personal_categories WHERE serviceid=$index");
        if(!$q->ok){
            if($this->output) {
                echo $q->mysql_error."\n";
            }
        }
        while ($ligne = pg_fetch_assoc($results)) {
            if($this->output) {
                echo "serviceid ($index): {$ligne["category_id"]} == {$ligne["categoryname"]}\n";
            }
            $ARRAY[$ligne["category_id"]]=$ligne["categoryname"];
        }

        return md5(serialize($ARRAY));

    }


    public function CreateCategoryTable($category,$fulltablename=null){
        $mysql_catz=new mysql_catz(true);
        if($fulltablename==null){
            if($category==null){return false;}
            if($category=="drogue"){$category="drugs";}
            if($category=="gambling"){$category="gamble";}
            if($category=="hobby/games"){$category="games";}
            if($category=="forum"){$category="forums";}
            if($category=="spywmare"){$category="spyware";}
            if($category=="association"){$category="associations";}
            $category=$mysql_catz->category_transform_name($category);
            $tablename=strtolower("category_$category");
        }else{
            $tablename=$fulltablename;
        }
        $tablename=strtolower($tablename);
        $tablename=str_replace("category_category_","category_",$tablename);
        if($tablename=="category_teans"){$tablename="category_teens";}
        if($tablename=="category_drogue"){$tablename="category_drugs";}
        if($tablename=="category_gambling"){$tablename="category_gamble";}
        if($tablename=="category_hobby_games"){$tablename="category_games";}
        if($tablename=="category_forum"){$tablename="category_forums";}
        if($tablename=="category_spywmare"){$tablename="category_spyware";}
        if($tablename=="category_association"){$tablename="category_associations";}
        $tablename=strtolower($tablename);
        if($GLOBALS["VERBOSE"]){echo "CREATE CATEGORY TABLE `$tablename`\n";}

        $postgres=new postgres_sql();
        if(!$postgres->CREATE_CATEGORY_TABLE($tablename)){
            $this->mysql_error=$postgres->mysql_error;
            return false;
        }
        $postgres->create_personal_categories();
        return true;


    }



}