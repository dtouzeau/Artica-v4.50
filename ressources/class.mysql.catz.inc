<?php

// SP217, 4.40 SP29
if(!isset($GLOBALS["AS_ROOT"])) {
    if (function_exists("posix_getuid")) {
        if (posix_getuid() == 0) {
            $GLOBALS["AS_ROOT"] = true;
        }
    }
}
if(!isset($GLOBALS["NOCACHE"])){$GLOBALS["NOCACHE"]=false;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.tcpip.inc");
include_once(dirname(__FILE__)."/class.sqlite.inc");
include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
include_once(dirname(__FILE__)."/class.memcached.inc");
include_once(dirname(__FILE__)."/class.postgres.inc");
include_once(dirname(__FILE__)."/class.openssl.aes.inc");
include_once(dirname(__FILE__)."/externals/Net/DNS2.inc");

class mysql_catz{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="catz";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	public $CategoryTime=0;
	public $CategoriesList=array();
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	var $last_id;
	var $affected_rows=0;
	var $SocketPath=null;
	var $TryTCP=true;	
	var $arrayConfig=array();
	var $TimeExec=0;
	var $evArray=array();
	public $CategoryName=null;
	private $RemoteUfdbCat=0;
	private $USE_CACHE=true;
	private $Disabled=false;
	private $QueryOnlyLocal=0;
	public $CategoriesCount=0;
	public $CategoryNumbers=0;
	private $CloudArticaCategoriesOutgoing=null;
	private $prevents=false;
	private $DNSremotes=array();
	public $UfdbCatEnabled=0;
	public $FinalUsedServer;
	public $LocalUfdbCatEnabled=0;
	public $category_id=0;
	public $events=array();
	public $memcached;
	private $WLOGExists=1;
	private $SaveNotCategorized=True;
	private $EnableSyslog=false;
	private $HardCategoriesRemove=0;
    public $THESHIELD_MAIN=array();
    private $fixedregex=array();
    private $fixedhosts=array();

	private $CategoryIndex=array();
	private $UFDBCAT_INSTALLED=0;
    private $local_interfaces=array();
    private $EnableLocalUfdbCatService=0;
	public $NoNegativeCache=false;
	
	
	function __construct($noload=false){
		
			$this->memcached=new lib_memcached();
            $this->local_interfaces=array();
		    if(!$noload){
			
			$config["TOKEN_INSTALLED"]="UFDBCAT_INSTALLED";
			$config["TITLE"]="{APP_UFDBCAT}";
			$config["EXPLAIN"]="{APP_UFDBCAT_EXPLAIN}";
			$config["TOKEN"]="EnableLocalUfdbCatService";

			if(!isset($GLOBALS["CHECKLIC"])){
                $GLOBALS["CHECKLIC"]=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
            }
			
			

			$NET_IPADDRS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NET_IPADDRS"));

			if(is_array($NET_IPADDRS)) {
                foreach ($NET_IPADDRS as $nicname => $ipaddr) {
                    $ff = explode("/", $ipaddr);
                    $this->local_interfaces[$nicname] = $ff[0];
                }
            }
			
            $this->HardCategoriesRemove=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HardCategoriesRemove"));
			$this->EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
			$this->CloudArticaCategoriesOutgoing=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CloudArticaCategoriesOutgoing"));
			$this->CategoryIndex=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesIndex"));
			$this->DNSremotes=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CATEGORIES_SERVICES"));
			if(!is_array($this->DNSremotes)){$this->DNSremotes=array();}
			
			$this->Disabled=false;
			$this->LoadParams();
			$this->CHECK_SQLITEDB();
		}
	}

	public function BuildDnsRemotes(){
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $results=$q->QUERY_SQL("SELECT * FROM categories_services WHERE enabled=1 ORDER BY zorder ");
        $CATEGORIES_SERVICES=array();
        foreach ($results as $index=>$ligne) {
            $ID = intval($ligne["ID"]);
            if ($ID == 0) {
                continue;
            }
            $PARAMS=array();
            $PARAMS["port"] = $ligne["port"];
            $PARAMS["hostname"] = $ligne["hostname"];
            $PARAMS["domain"] = $ligne["domain"];
            $PARAMS["decrypt"] = $ligne["crypt"];
            $PARAMS["passphrase"] = $ligne["passphrase"];
            $CATEGORIES_SERVICES[$ID]=$PARAMS;
        }

        $this->DNSremotes=$CATEGORIES_SERVICES;
        if(!is_array($this->DNSremotes)){$this->DNSremotes=array();$CATEGORIES_SERVICES=array();}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CATEGORIES_SERVICES",serialize($CATEGORIES_SERVICES));
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("dnscat.php?synchronize-remote=yes");
    }
	
	private function GET_INFO($key){
		return trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
		
	}
	
	public function OnlyQuery(){
		$this->Disabled=true;
	}
	
	public function NoCache(){
		$this->USE_CACHE=true;
	}

	private function categories_descriptions_memory(){
        include_once(dirname(__FILE__)."/class.categories.mem.inc");
        return $this->fill_cguarddescs(FILL_CATEGORIES_MEM());

    }

	public function CategoryIntToStr($category_id){
        $category_id=intval($category_id);
		if($category_id==0){return "Unknown";}
        if($category_id==1){return "Facebook";}
        if($category_id==2){return "Microsoft";}
        if($category_id==3){return "Society";}
        if($category_id==5){return "Advertising";}
        if($category_id==6){return "Phishtank";}
        if($category_id==7){return "Ransomwares";}
        if($category_id==8){return "Shopping";}
        if($category_id==9){return "Abortion";}
        if($category_id==10){return "Agressive";}
        if($category_id==11){return "Alcohol";}
        if($category_id==12){return "Animals";}
        if($category_id==13){return "Associations";}
        if($category_id==14){return "Astrology";}
        if($category_id==15){return "Audio-video";}
        if($category_id==16){return "Youtube";}
        if($category_id==17){return "Google";}
        if($category_id==18){return "Apple";}
        if($category_id==19){return "Amazonaws";}
        if($category_id==20){return "Akamai";}
        if($category_id==21){return "Yahoo";}
        if($category_id==22){return "Skype";}
        if($category_id==23){return "Citrix";}
        if($category_id==24){return "Bikes";}
        if($category_id==25){return "Boats";}
        if($category_id==26){return "Carpool";}
        if($category_id==27){return "Cars";}
        if($category_id==28){return "Planes";}
        if($category_id==29){return "Bicycles";}
        if($category_id==30){return "Blogs";}
        if($category_id==31){return "Books";}
        if($category_id==32){return "Browsers plugins";}
        if($category_id==33){return "Celebrities";}
        if($category_id==34){return "Chat";}
        if($category_id==35){return "Children";}
        if($category_id==36){return "Cleaning";}
        if($category_id==37){return "Clothing";}
        if($category_id==38){return "Converters";}
        if($category_id==39){return "Cosmetics";}
        if($category_id==40){return "Culture";}
        if($category_id==41){return "Dangerous materials";}
        if($category_id==42){return "Dating";}
        if($category_id==43){return "Dictionaries";}
        if($category_id==44){return "Downloads";}
        if($category_id==45){return "Drugs";}
        if($category_id==46){return "Dynamic DHCP ISP";}
        if($category_id==47){return "Electrical appliances";}
        if($category_id==48){return "Electronic house";}
        if($category_id==49){return "Filehosting";}
        if($category_id==50){return "Banking";}
        if($category_id==51){return "Insurances";}
        if($category_id==52){return "Moneylending";}
        if($category_id==53){return "Finance/other";}
        if($category_id==54){return "Realestate";}
        if($category_id==55){return "Financial";}
        if($category_id==56){return "Forums";}
        if($category_id==57){return "Gambling";}
        if($category_id==58){return "Games";}
        if($category_id==59){return "Genealogy";}
        if($category_id==60){return "Gifts";}
        if($category_id==62){return "Governments";}
        if($category_id==63){return "Green";}
        if($category_id==64){return "Hacking";}
        if($category_id==65){return "Handicap";}
        if($category_id==66){return "Health";}
        if($category_id==67){return "Arts";}
        if($category_id==68){return "Cooking";}
        if($category_id==69){return "Hobby Other";}
        if($category_id==70){return "Pets";}
        if($category_id==71){return "Paytosurf";}
        if($category_id==72){return "Terrorism";}
        if($category_id==73){return "Fishing";}
        if($category_id==74){return "Hospitals";}
        if($category_id==75){return "Houseads";}
        if($category_id==76){return "Housing accessories";}
        if($category_id==77){return "Housing doityourself";}
        if($category_id==78){return "Housing builders";}
        if($category_id==79){return "Humanitarian";}
        if($category_id==80){return "Images hosting";}
        if($category_id==81){return "Industry";}
        if($category_id==82){return "Internal";}
        if($category_id==83){return "Internet Providers";}
        if($category_id==85){return "Jobsearch";}
        if($category_id==86){return "Jobtraining";}
        if($category_id==87){return "Justice";}
        if($category_id==88){return "Learning";}
        if($category_id==89){return "Global White List";}
        if($category_id==90){return "Luxury";}
        if($category_id==91){return "Mailing";}
        if($category_id==92){return "Malwares";}
        if($category_id==93){return "Mangas";}
        if($category_id==94){return "Maps";}
        if($category_id==95){return "Marketingware";}
        if($category_id==96){return "Medical";}
        if($category_id==97){return "Mixed adult";}
        if($category_id==98){return "Smartphones";}
        if($category_id==99){return "Models";}
        if($category_id==100){return "Movie and Streaming";}
        if($category_id==101){return "Music";}
        if($category_id==102){return "Nature";}
        if($category_id==103){return "News and press";}
        if($category_id==104){return "Passwords";}
        if($category_id==105){return "Phishing";}
        if($category_id==106){return "Photo";}
        if($category_id==107){return "Pictures libraries";}
        if($category_id==108){return "Politic";}
        if($category_id==109){return "Porn";}
        if($category_id==111){return "Proxies";}
        if($category_id==112){return "Reaffected";}
        if($category_id==113){return "Humor";}
        if($category_id==114){return "Nightout";}
        if($category_id==115){return "Schools Educational";}
        if($category_id==116){return "Sports";}
        if($category_id==117){return "Get Married";}
        if($category_id==118){return "Police";}
        if($category_id==119){return "Travel";}
        if($category_id==120){return "Wellness";}
        if($category_id==121){return "Redirector";}
        if($category_id==122){return "Religion";}
        if($category_id==123){return "Remote-control";}
        if($category_id==124){return "Sciences";}
        if($category_id==125){return "Astronomy";}
        if($category_id==126){return "Science computing";}
        if($category_id==127){return "Weather";}
        if($category_id==128){return "Chemistry";}
        if($category_id==129){return "Searchengines";}
        if($category_id==130){return "Sect";}
        if($category_id==131){return "Sexual Education";}
        if($category_id==132){return "Lingerie (sex)";}
        if($category_id==133){return "Smallads";}
        if($category_id==134){return "Social Networks";}
        if($category_id==135){return "Spywares";}
        if($category_id==136){return "SSL certificates";}
        if($category_id==137){return "Stockexchange";}
        if($category_id==139){return "Redirector";}
        if($category_id==140){return "Suspicious";}
        if($category_id==141){return "Teens";}
        if($category_id==142){return "Tobacco";}
        if($category_id==143){return "Trackers";}
        if($category_id==144){return "Translators";}
        if($category_id==145){return "Transport";}
        if($category_id==146){return "Cheater";}
        if($category_id==147){return "Updatesites";}
        if($category_id==148){return "Violence";}
        if($category_id==149){return "Warez";}
        if($category_id==150){return "Weapons";}
        if($category_id==151){return "Web applications";}
        if($category_id==152){return "Webmails";}
        if($category_id==153){return "Web phones";}
        if($category_id==154){return "Web plugins";}
        if($category_id==155){return "Web radio";}
        if($category_id==156){return "Web TV";}
        if($category_id==157){return "Wine";}
        if($category_id==158){return "Womanbrand";}
        if($category_id==159){return "Horses";}
        if($category_id==160){return "Meetings";}
        if($category_id==161){return "Tattooing";}
        if($category_id==163){return "Literature";}
        if($category_id==166){return "Advertising";}
        if($category_id==167){return "Porn";}
        if($category_id==168){return "Agressive";}
        if($category_id==169){return "Arjel";}
        if($category_id==170){return "Religion";}
        if($category_id==171){return "Astrology";}
        if($category_id==172){return "Audio-video";}
        if($category_id==173){return "Banking";}
        if($category_id==174){return "Bitcoin";}
        if($category_id==175){return "Blogs";}
        if($category_id==176){return "Celebrities";}
        if($category_id==177){return "Chat";}
        if($category_id==178){return "Children";}
        if($category_id==179){return "Cleaning";}
        if($category_id==180){return "Cooking";}
        if($category_id==181){return "Cryptojacking";}
        if($category_id==182){return "Dangerous materials";}
        if($category_id==183){return "Dating";}
        if($category_id==184){return "D-DOS";}
        if($category_id==185){return "Dialers";}
        if($category_id==186){return "Downloads";}
        if($category_id==187){return "Drugs";}
        if($category_id==188){return "Schools Educational";}
        if($category_id==189){return "filehosting";}
        if($category_id==190){return "Financial";}
        if($category_id==191){return "Forums";}
        if($category_id==192){return "Gambling";}
        if($category_id==193){return "Games";}
        if($category_id==194){return "Proxies";}
        if($category_id==195){return "Hacking";}
        if($category_id==196){return "Jobsearch";}
        if($category_id==197){return "Sex Lingerie";}
        if($category_id==198){return "White list";}
        if($category_id==199){return "Educational White list";}
        if($category_id==200){return "Malwares";}
        if($category_id==201){return "Manga";}
        if($category_id==202){return "Marketingware";}
        if($category_id==203){return "Mixed Adult";}
        if($category_id==204){return "Smartphones";}
        if($category_id==205){return "Phishing";}
        if($category_id==207){return "Press";}
        if($category_id==208){return "Redirector";}
        if($category_id==209){return "Advertising";}
        if($category_id==210){return "Radio";}
        if($category_id==211){return "Reaffected";}
        if($category_id==212){return "Redirector";}
        if($category_id==213){return "Remote-control";}
        if($category_id==214){return "Sect";}
        if($category_id==215){return "Sexual Education";}
        if($category_id==216){return "Shopping";}
        if($category_id==217){return "Shorteners";}
        if($category_id==218){return "Social Networks";}
        if($category_id==219){return "Special";}
        if($category_id==220){return "Sports";}
        if($category_id==221){return "Redirector";}
        if($category_id==222){return "Redirector";}
        if($category_id==223){return "Translation";}
        if($category_id==224){return "Cheater";}
        if($category_id==225){return "Update";}
        if($category_id==226){return "Warez";}
        if($category_id==227){return "Webmail";}
        if($category_id==228){return "Poneytelecom";}
        if($category_id==229){return "OVH";}
        if($category_id==230){return "DigitalOcean";}
        if($category_id==231){return "Orange SA";}
        if($category_id==232){return "SoftLayer Technologies";}
        if($category_id==233){return "Linode";}
        if($category_id==234){return "Cloudflare";}
        if($category_id==235){return "cryptomining";}
        if($category_id==236){return "Hetzner Online";}
        if($category_id==237){return "DOH DNS";}
        if($category_id==238){return "Newly Registered Domains";}
        if($category_id==249){return "DNS Black Listing";}
        if($category_id==5001) { return "lem.adult.porn";}
        if($category_id==5002) { return "lem.notlegal.appz";}
        if($category_id==5003) { return "lem.notlegal.hardware";}
        if($category_id==5004) { return "lem.games.online";}
        if($category_id==5005) { return "lem.notlegal.generic";}
        if($category_id==5006) { return "lem.health.prevention.drugs";}
        if($category_id==5007) { return "lem.gov.justice";}
        if($category_id==5008) { return "lem.gov.health";}
        if($category_id==5009) { return "lem.dating";}
        if($category_id==5010) { return "lem.scam";}
        if($category_id==5011) { return "x.cd06.wl";}
        if($category_id==5012) { return "lem.games.app.apple";}
        if($category_id==5013) { return "lem.games.news";}
        if($category_id==5014) { return "lem.health.prevention.accident";}
        if($category_id==5015) { return "lem.finance.banks";}
        if($category_id==5016) { return "lem.hobby.manga";}
        if($category_id==5017) { return "lem.gamble";}
        if($category_id==5018) { return "lem.games.app.pc";}
        if($category_id==5019) { return "lem.health.risk.drugs";}
        if($category_id==5020) { return "lem.companies.insurance";}
        if($category_id==5021) { return "lem.games.app.android";}
        if($category_id==5022) { return "lem.health.prevention.riskypractice";}
        if($category_id==5023) { return "lem.gov.country.fr";}
        if($category_id==5024) { return "lem.infected";}
        if($category_id==5025) { return "lem.chat";}
        if($category_id==5026) { return "lem.tracker";}
        if($category_id==5027) { return "lem.malware";}
        if($category_id==5028) { return "lem.gov.edu";}
        if($category_id==5029) { return "lem.notlegal.moviez";}
        if($category_id==5030) { return "lem.notlegal.p2p";}
        if($category_id==5031) { return "lem.certificate";}
        if($category_id==5032) { return "lem.search.safe-engines";}
        if($category_id==5033) { return "lem.fake.news";}
        if($category_id==5034) { return "lem.fake.joke";}
        if($category_id==5035) { return "lem.fake.health";}
        if($category_id==5036) { return "lem.notlegal.downloadz";}
        if($category_id==5037) { return "lem.gov.rescue";}
        if($category_id==5038) { return "lem.gov.towns";}
        if($category_id==5039) { return "lem.gov.regions";}
        if($category_id==5040) { return "lem.gov.country";}
        if($category_id==5041) { return "lem.health.prevention.sects";}
        if($category_id==5042) { return "lem.notlegal.extractor";}
        if($category_id==5043) { return "lem.health.risk.sects";}
        if($category_id==5044) { return "lem.gov.police";}
        if($category_id==5045) { return "lem.finance.fr.notlegal";}
        if($category_id==5046) { return "lem.gov.generic";}
        if($category_id==5047) { return "lem.companies.industries";}
        if($category_id==5048) { return "lem.adult.underwears";}
        if($category_id==5049) { return "lem.health.products";}
        if($category_id==5050) { return "lem.network.monitoring";}
        if($category_id==5051) { return "lem.hobby.sport";}
        if($category_id==5052) { return "x.cd06.bl";}
        if($category_id==5053) { return "lem.computing";}
        if($category_id==5054) { return "lem.shop";}
        if($category_id==5055) { return "lem.companies.itservices";}
        if($category_id==5056) { return "lem.computing.pentesting";}
        if($category_id==5057) { return "lem.computing.freedns";}
        if($category_id==5058) { return "lem.adult.sexshop";}
        if($category_id==5059) { return "lem.learning";}
        if($category_id==5060) { return "lem.computing.update";}
        if($category_id==5061) { return "lem.hobby.art";}
        if($category_id==5062) { return "lem.cooking";}
        if($category_id==5063) { return "lem.companies.buildings";}
        if($category_id==5064) { return "lem.hobby.genealogy";}
        if($category_id==5065) { return "lem.hobby.astrology";}
        if($category_id==5066) { return "lem.ads";}
        if($category_id==5067) { return "lem.news.tv";}
        if($category_id==5068) { return "lem.news.mag";}
        if($category_id==5069) { return "lem.computing.webdesign";}
        if($category_id==5070) { return "lem.learning.schools";}
        if($category_id==5071) { return "lem.health.hospitals";}
        if($category_id==5072) { return "lem.hobby.travel";}
        if($category_id==5073) { return "lem.learning.manuals";}
        if($category_id==5074) { return "lem.learning.languages";}
        if($category_id==5075) { return "lem.learning.tools";}
        if($category_id==5076) { return "lem.hobby.animals";}
        if($category_id==5077) { return "lem.hobby.music";}
        if($category_id==5078) { return "lem.hobby.vehicles";}
        if($category_id==5079) { return "lem.health.disease";}
        if($category_id==5080) { return "lem.app.productivity";}
        if($category_id==5081) { return "lem.hobby.books";}
        if($category_id==5082) { return "lem.hobby.photo";}
        if($category_id==5083) { return "lem.sciences";}
        if($category_id==5084) { return "lem.companies.realestate";}
        if($category_id==5085) { return "lem.blogs.design";}
        if($category_id==5086) { return "lem.blogs.geek";}
        if($category_id==5087) { return "lem.socialnet";}
        if($category_id==5088) { return "lem.blogs.persdev";}
        if($category_id==5089) { return "lem.companies.models";}
        if($category_id==5090) { return "lem.politics";}
        if($category_id==5091) { return "lem.blogs";}
        if($category_id==5092) { return "lem.ecology";}
        if($category_id==5093) { return "lem.proxy";}
        if($category_id==5094) { return "lem.phishing";}
        if($category_id==5095) { return "lem.finance.cryptocoins";}
        if($category_id==5096) { return "lem.health.risk";}
        if($category_id==5097) { return "lem.pictures";}
        if($category_id==5098) { return "lem.blogs.celebrity";}
        if($category_id==5099) { return "lem.blogs.fashion";}
        if($category_id==5100) { return "lem.blogs.diy";}
        if($category_id==5101) { return "lem.violence";}
        if($category_id==5102) { return "lem.blogs.history";}
        if($category_id==5103) { return "lem.hobby.nature";}
        if($category_id==5104) { return "lem.notmoderated";}
        if($category_id==5105) { return "lem.blogs.lifestyle";}
        if($category_id==5106) { return "lem.videos";}
        if($category_id==5107) { return "lem.pwned";}
        if($category_id==5108) { return "lem.religious";}
        if($category_id==5109) { return "lem.tattouing";}
        if($category_id==5110) { return "lem.companies.events";}
        if($category_id==5111) { return "lem.anonymous";}
        if($category_id==5112) { return "lem.visio";}
        if($category_id==5113) { return "lem.adult.generic";}
        if($category_id==5114) { return "lem.weapons";}
        if($category_id==5115) { return "lem.job";}
        if($category_id==5116) { return "lem.blogs.lgbt";}
        if($category_id==5117) { return "lem.apple";}
        if($category_id==5118) { return "lem.timeloose";}
        if($category_id==5119) { return "lem.learning.cheat";}
        if($category_id==5120) { return "lem.health.prevention";}
        if($category_id==5121) { return "lem.computing.antivirus";}
        if($category_id==5122) { return "lem.videos.youtube";}
        if($category_id==5123) { return "lem.videos.tv";}
        if($category_id==5124) { return "lem.notused";}
        if($category_id==5125) { return "lem.search.nosafe-engines";}
        if($category_id==5126) { return "lem.meteo";}
        if($category_id==5127) { return "lem.finance.loan";}
        if($category_id==5128) { return "lem.companies.printing";}

        if(!isset($GLOBALS["useCGuardCategories"])) {
            $GLOBALS["useCGuardCategories"] = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCGuardCategories"));
        }
        if($GLOBALS["useCGuardCategories"]==1) {
            if (!isset($GLOBALS["CguardCatzData"])){
                    $GLOBALS["CguardCatzData"] = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CguardCatzData")));
            }
            if(isset($GLOBALS["CguardCatzData"][$category_id]["NAME"])){return $GLOBALS["CguardCatzData"][$category_id]["NAME"];
            }

        }
        $categories_descriptions=$this->categories_descriptions();
		if(isset($categories_descriptions[$category_id])){
		    if(isset($categories_descriptions[$category_id]["categoryname"])) {
                return $categories_descriptions[$category_id]["categoryname"];
            }
		    return null;
		}
        $mem=new lib_memcached();
		if($this->CategoryName<>null){
            $categories_descriptions[$category_id]=$this->CategoryName;
            $mem->saveKey("categories_descriptions", serialize($categories_descriptions),1600);
            return $this->CategoryName;
        }

		$CategoryName=$mem->getKey("CATEGORY-NAME:$category_id");
		if($mem->MemCachedFound){
            $categories_descriptions[$category_id]=$CategoryName;
            $mem->saveKey("categories_descriptions", serialize($categories_descriptions),1600);
            return $CategoryName;
        }

        if($category_id>249) {
            $qp=new postgres_sql();
            $ligne2 = $qp->mysqli_fetch_array("SELECT categoryname FROM personal_categories WHERE category_id = $category_id");
                if (!isset($ligne2["categoryname"])) {
                    return "";
                }
            if(!$ligne2["categoryname"]){return "";}
            $categoryname=trim($ligne2["categoryname"]);
            $mem->saveKey("CATEGORY-NAME:$category_id", $categoryname);
            $mem->saveKey("categories_descriptions", serialize($categories_descriptions), 1600);
            $categories_descriptions[$category_id] = $categoryname;
            return $categoryname;

        }

	}

	public function categories_descriptions_clean():bool{
        $mem=new lib_memcached();
        $mem->Delkey("categories_descriptions");
        return true;
    }

    private function fill_cguarddescs($array){

	    if(!isset($GLOBALS["CguardCatzData"])) {
            VERBOSE("Open CguardCatzData",__LINE__);
            $GLOBALS["CguardCatzData"] = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CguardCatzData")));
            if(!is_array($GLOBALS["CguardCatzData"])){$GLOBALS["CguardCatzData"]=array();}
        }

        if(count($GLOBALS["CguardCatzData"])==0){
            VERBOSE("CguardCatzData Count==0 return an array of ".count($array)." items",__LINE__);
            return $array;

        }
        foreach ($GLOBALS["CguardCatzData"] as $category_id=>$dataarray){
            if(!isset($dataarray["ITEMS"])){continue;}
            $array[$category_id]["categorykey"]=$dataarray["NAME"];
            $array[$category_id]["categoryname"]=$dataarray["NAME"];
            $array[$category_id]["category_description"]=$dataarray["DESC"];
            $array[$category_id]["category_icon"]="/img/20-categories-personnal.png";
            $array[$category_id]["master_category"]=999991;
            $array[$category_id]["enabled"]=1;
            $array[$category_id]["publicmode"]=0;
            $array[$category_id]["items"]=$dataarray["ITEMS"];
            $array[$category_id]["free_category"]=0;
            $array[$category_id]["compiledate"]=$GLOBALS["CguardCatzData"]["99999999"]["TIME"];
            $array[$category_id]["compilerows"]=null;

        }

        return $array;

    }
	
	public function categories_descriptions(){
        $mem=new lib_memcached();
        $categories_descriptions=$mem->getKey("categories_descriptions");
        if(!isset($GLOBALS["useCGuardCategories"])) {$GLOBALS["useCGuardCategories"] = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCGuardCategories"));}
        if($GLOBALS["useCGuardCategories"]==1) {if (!isset($GLOBALS["CguardCatzData"])) {$GLOBALS["CguardCatzData"] = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CguardCatzData")));}}

        if($mem->MemCachedFound){
			$array=unserialize($categories_descriptions);
            if(!$array){
                $array=array();
            }
            VERBOSE("categories_descriptions MemCachedFound Array == ".count($array)." ->(MemCachedFound)",__LINE__);
			if(count($array)>200){
			    return $this->fill_cguarddescs($array);
			}
		}else{
            VERBOSE("categories_descriptions MemCachedFound = False",__LINE__);
        }
		
	
		include_once(dirname(__FILE__)."/class.categories.inc");

		$q=new postgres_sql();
		if(!$q->TABLE_EXISTS("personal_categories")){
            VERBOSE("categories_descriptions personal_categories No such table",__LINE__);
		    return $this->categories_descriptions_memory();
		}
		$sql="SELECT * FROM personal_categories";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){
            VERBOSE("categories_descriptions $q->mysql_error",__LINE__);
		    return $this->categories_descriptions_memory();
		}
        $total=0;
        if($results) {
            $total = pg_num_rows($results);
        }
		if($total==0){
            VERBOSE("TOTAL == 0 ??",__LINE__);
			$catz=new categories();
            $total=0;
			$catz->initialize();
			$sql="SELECT category_id,categoryname FROM personal_categories order by category_id";
			$results=$q->QUERY_SQL($sql);
            if($results) {
                $total = pg_num_rows($results);
            }
		}

		if($total==0){
            VERBOSE("TOTAL == 0 ??",__LINE__);
            return $this->categories_descriptions_memory();
        }

        $categories_descriptions=array();
		while ($ligne = pg_fetch_assoc($results)) {
			$category_id=$ligne["category_id"];

			foreach ($ligne as $columns=>$data){
				if(is_numeric($columns)){continue;}
				$categories_descriptions[$category_id][$columns]=$data;

			}
		}

		$count_categories_descriptions=count($categories_descriptions);
		if($count_categories_descriptions<150){
            return $this->categories_descriptions_memory();
        }

        $mem->saveKey("categories_descriptions", serialize($categories_descriptions),1600);
		return $this->fill_cguarddescs($categories_descriptions);


		
	}
	
	private function LoadParams(){
		
		
		
		
		
		if(!isset($GLOBALS["AsCategoriesAppliance"])){$GLOBALS["AsCategoriesAppliance"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AsCategoriesAppliance")); }
		if(!isset($GLOBALS["UfdbgclientSockTimeOut"])){$GLOBALS["UfdbgclientSockTimeOut"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbgclientSockTimeOut"));}
		if($GLOBALS["UfdbgclientSockTimeOut"]==0){$GLOBALS["UfdbgclientSockTimeOut"]=2;}
		if(!isset($GLOBALS["SquidPerformance"])){$GLOBALS["SquidPerformance"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));}
		
		if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
			$this->LocalUfdbCatEnabled=1;
			$this->UfdbCatEnabled=1;
			return;
			
		}
		
		
		if($GLOBALS["AsCategoriesAppliance"]==1){
			$this->LocalUfdbCatEnabled=1;
			$this->UfdbCatEnabled=1;
			return;
		}
		
	}
	function isRemoteSockets(){
		$EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));

	
		if($EnableLocalUfdbCatService==1){
			$ufdbCatInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbCatInterface");
			if($ufdbCatInterface<>null){return true;}
		}
		return false;
	
	}

	function category_transform_name($category){
			if($category=="teans"){$category="teens";}
			if($category=="forum"){$category="forums";}
			if($category=="gambling"){$category="gamble";}
			if($category=="radio"){$category="webradio";}
			$category=str_replace('/',"_",$category);
			$category=str_replace('-',"_",$category);
			return $category;	
	}
	
	private function ToSyslog($text){
		$LOG_SEV=LOG_INFO;
		$this->WLOGS($text);
		if(function_exists("openlog")){openlog("artica-categorize", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}

	public function microtime_float(){
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	private function LoadMyDNS(){
        if(isset($GLOBALS["MY_DNSS"])) {return $GLOBALS["MY_DNSS"];}
        $f = explode("\n", @file_get_contents("/etc/resolv.conf"));
        foreach ($f as $line) {
            $line = trim($line);
            if ($line == null) {
                continue;
            }
            if (!preg_match("#nameserver\s+([0-9\.]+)#", $line, $re)) {
                continue;
            }

            $GLOBALS["MY_DNSS"][] = $re[1];
        }


        return $GLOBALS["MY_DNSS"];
    }

    public function ufdbcat_dns_infos(){
        $DNS=$this->LoadMyDNS();
        $DNS[]="46.105.178.150";
        $Config["nameservers"]=$GLOBALS["MY_DNSS"];
        $Config["timeout"]=2;

        $UFDBCAT_DNS_COUNT=intval($this->memcached->getKey("UFDBCAT_DNS_COUNT"));
        $UFDBCAT_DNS_COUNT=$UFDBCAT_DNS_COUNT+1;
        $this->memcached->saveKey("UFDBCAT_DNS_COUNT",$UFDBCAT_DNS_COUNT,36000);
        $r = new Net_DNS2_Resolver(array('nameservers' => $GLOBALS["MY_DNSS"], "timeout" => 1));

        try {
            $result = $r->query("articatechinfos.main.filter.artica.center", 'TXT');

        } catch(Net_DNS2_Exception $e) {
            $message=$e->getMessage();
            $this->memcached->saveKey("UFDBCAT_DNS_ERROR",$message,3600);
            if(preg_match("#timeout on read#",$message)){return 0;}
            echo $this->ToSyslog("$message In line ".__LINE__);
            return 0;
        }
        $this->CategoryTime=0;
        $this->CategoryNumbers=0;

        if(!property_exists($result,"answer")){
            return 0;
        }

        foreach ($result->answer as $index=>$rr) {
            $data = $rr->text[0];
           if(preg_match("#([0-9]+):([0-9]+)#",$data,$re)){
               $this->CategoryTime=intval($re[1]);
               $this->CategoryNumbers=intval($re[2]);
           }

        }

    }

    private function srnquery($sitename=null){
        $MAIN=array();

        if(is_array($sitename)){
            $fname=PROGRESS_DIR."/srnquery.bulk";
            @file_put_contents($fname,@implode("\n",$sitename));
            exec("/usr/share/artica-postfix/bin/srnquery --bulk $fname 2>&1",$results);
        }else {
            exec("/usr/share/artica-postfix/bin/srnquery $sitename 2>&1", $results);
        }
        $this->ok=true;
        foreach ($results as $line){
            VERBOSE("-->srnquery: $line", __LINE__);
            if(preg_match("#<serialized>(.+?)</serialized>#i",$line,$re)){
                $MAIN=unserialize($re[1]);
            }
        }
        if(is_array($sitename)){
            return $MAIN;
        }

        $this->THESHIELD_MAIN["error"]=null;
        $this->THESHIELD_MAIN["ACTION"]="PASS";
        return $MAIN["category"];
    }


    public function ufdbcat_bulk($Dict=array()):array{
        $KSRNRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KSRNRemote"));
        if($KSRNRemote==0){
            VERBOSE("-->srnquery", __LINE__);
            return $this->srnquery($Dict);
        }

        $array=$GLOBALS["CLASS_SOCKETS"]->ksrn_sockets($Dict);
        return $array;
    }

	public function ufdbcat_dns($sitename){

        if(function_exists("debug_backtrace")){
            $trace=@debug_backtrace();
            foreach ($trace as $ligne){
                    $file=$ligne["file"];
                    $function=$ligne["function"];
                    $line=$ligne["line"];
                    $this->ToSyslog("By $file {$function}() in line $line");
            }
        }

        $this->ok=true;
        $this->THESHIELD_MAIN=null;
        $array=$GLOBALS["CLASS_SOCKETS"]->ksrn_sockets($sitename);

        if(!$array["STATUS"]) {
            VERBOSE("$sitename {$array["ERROR"]}", __LINE__);
            $this->ok=false;
            return false;
        }
        $response=$array["RESPONSE"];
        //$main=unserialize($response);
        $main=$response;
        $this->THESHIELD_MAIN=$main;

        $json = json_decode($main,true);
        if(!isset($json["category_id"])){return 0;}
        return intval($json["category_id"]);
    }


	private function setevents($line){
		$this->WLOGS($line);
		if($GLOBALS["VERBOSE"]){echo "$line<br>\n";}
		if(count($this->evArray)>500){$this->evArray=array();}
		$this->evArray[]=$line;
		
	}
	
	public function limited_categorize($sitename){
		if(isset($GLOBALS["DSHIELD"])){return 0;}
		if($this->HardCategoriesRemove==1){return 0;}

        if(preg_match("#(^|\.)evernote\.(com|com.co|com.tr|com.ua|net|fr|pl|de|ch|es)$#", $sitename)){return 151;}
        if(preg_match("#\.(corp|home|mail|tld|int|intra)$#", $sitename,$re)){return 82;}
		if(preg_match("#^(.+?)\?#", $sitename,$re)){$sitename=$re[1];}
		if(preg_match("#^[0-9\.]+$#", $sitename)){return $this->is_local_net($sitename);}

		if(preg_match("#(^|\.)(msftncsi|maxmind)\.com$#", $sitename)){return 147;}
		if(preg_match("#\.goog$#", $sitename)){return 17;}
		if(preg_match("#(^|\.)(yahoo|yimg)\.[a-z]+$#", $sitename)){return 21;}
		if(preg_match("#(^|\.)(google|googleapis|gstatic|ampproject|appspot|gmail|googlecode|ggpht|googleusercontent|gvt[0-9])\.[a-z]+$#", $sitename)){return 17;}
		if(preg_match("#(^|\.)(youtube|ytimg|googlevideo)\.[a-z]+$#", $sitename)){return 16;}
		if(preg_match("#(^|\.)(microsoft|onedrive|qualifytring|live|s-microsoft|windows|outlook|windowsupdate|msftconnecttest|edgesuite|jcloudms|aspnetcdn|s-msn)\.[a-z]+$#", $sitename)){return 2;}
        if(preg_match("#(^|\.)(microsoftonline\.com|microsoftonline-p\.com|msauth\.net|msauthimages\.net|msecnd\.net|msftauth\.net|msftauthimages\.net|phonefactor\.net|windows\.net|azure\.com|msft\.net|office\.com|msappproxy\.net|msftidentity\.com|msidentity\.com|windowsazure\.com|sharepoint\.com)$#", $sitename)){return 2;}
		if(preg_match("#-msn-com\.akamaized\.net#", $sitename)){return 2;}

		if(preg_match("#(^|\.)(facebook|fbcdn|fbsbx)\.[a-z]+$#", $sitename)){return 1;}
		if(preg_match("#(^|\.)(apple|icloud|icloud-content|appleiphonecell|thinkdifferent|ibook)\.[a-z]+$#", $sitename)){return 18;}
		if(preg_match("#(^|\.)snapchat\.[a-z]+$#", $sitename)){return 34;}
		if(preg_match("#(^|\.)(skype|skypeassets)\.[a-z]+$#", $sitename)){return 22;}
		if(preg_match("#\.(skype|skypeassets)\.com#", $sitename)){return 22;}
		if(preg_match("#(^|\.)(samsung|samsungapps|samqaicongen|samsungnyc|samsungrs)\.[a-z]+$#", $sitename)){return 98;}
		if(preg_match("#(^|\.)(nflxvideo|netflix|allocine|plex)\.[a-z]+$#", $sitename)){return 100;}
		if(preg_match("#(^|\.)virtualearth\.net$#", $sitename)){return 94;}
		if(preg_match("#(^|\.)(twittre|twimg|t|scope|twtr|twtt|twitter|tweetdashboard|bytwitter)\.(in|id|co|jp|com|biz|info|net)+$#", $sitename)){return 134;}
		if(preg_match("#(^|\.)(msn|onedrive|office365|office|onenote|sfx|live|msedge|azureedge)\.[a-z]+$#", $sitename)){return 2;}
		if(preg_match("#(^|\.)(articatech|artica|proxy-appliance|python|ibm|bluecoat|squid-cache|synology|mozilla|githubapp|githubusercontent|atolcd|ubuntu|domainbigdata|db-ip|mon-ip|debian|github|sourceforge|clubic|adobe|rainmeter)\.(com|net|fr|org)$#", $sitename)){return 126;}
		if(preg_match("#(^|\.)(symcd|comodoca|digicert|globalsign|identrust|letsencrypt|verisign)\.com$#", $sitename)){return 136;}
        if(preg_match("#(^|\.)(seagate|atos)\.(com|com.co|com.tr|com.ua|net|fr|pl|de|ch|es)$#", $sitename)){return 126;}
        if(preg_match("#(^|\.)artica\.center$#", $sitename)){return 126;}

		//sslsites == 136
         if(preg_match("#(^|\.)(usertrust)\.com$#", $sitename)){return 136;}
        //protonmail.com == 152
        if(preg_match("#(^|\.)(protonmail)\.(com|fr)$#", $sitename)){return 152;}
		//webtv==156
		if(preg_match("#(^|\.)(bfmtv|tf1|cnn)\.(com|fr)$#", $sitename)){return 156;}
		//searchEngines == 129
        if(preg_match("#(^|\.)(yandex)\.(com|ru)$#", $sitename)){return 129;}
        //Web Applications
        if(preg_match("#(^|\.)(trello|appdynamics|service-now)\.(com|ru|net|jp|tw)$#", $sitename)){return 151;}
        // Governements
        if(preg_match("#(^|\.)(gouv)\.fr$#", $sitename)){return 62;}
        //Internal
        if(preg_match("#(^|\.)company\.tld$#", $sitename)){return 82;}
        //Passwords
        if(preg_match("#(^|\.)lastpass\.com$#", $sitename)){return 104;}
		//Advertising.
		if(preg_match("#(^|\.)(adnxs|doubleclick|2mdn|camakaroda|accengage|googleadservices|leadsmonitor|hawifallon|adalyser|adjust|amazon-adsystem|seeawhale|itsup|adsafeprotected|googlesyndication|adglare|sc-cdn|carbonads|adcolony|ldanet|beopinion)\.(net|com|io)$#", $sitename)){return 5;}
		if(preg_match("#(^|\.)(servedby-buysellads|stickyadstv|popupdomination)\.com$#", $sitename)){return 5;}
		if(preg_match("#(^|\.)(chartboost|advizon|bytedance|taboola|consensu|rfihub|googletagservices|smartadserver|rubiconproject|outbrainimg|casalemedia|openx|pubmatic)\.(net|com|io|org)$#", $sitename)){return 5;}
		//justice
        if(preg_match("#(^|\.)(elnet)\.fr$#", $sitename)){return 87;}

        //Weather.
        if(preg_match("#(^|\.)(meteociel|meteofrance|weenat)\.(fr|com)$#",$sitename)){return 127;}

        //Gambling
        if(preg_match("#(^|\.)winamax\.com$#",$sitename)){return 57;}

        //Schools
        if(preg_match("#(^|\.)education\.(fr|com)$#",$sitename)){return 115;}
        if(preg_match("#(^|\.)edx\.org$#",$sitename)){return 115;}

        //Associations
        if(preg_match("#(^|\.)asso\.fr$#",$sitename)){return 13;}

        // Web Plugins
        if(preg_match("#(^|\.)fontawesome\.com$#",$sitename)){return 154;}
		
		//trackers
		if(preg_match("#msmtrakk.*?\.[a-z]+$#", $sitename)){return 143;}
		if(preg_match("#(^|\.)(mkt[0-9]+|crashlytics|app-measurement|voluumtrk|appnexus|flurry|mediarithmics|cedexis|gigya|amplitude|weborama|helpshift|outbrain|zemanta|teads|newrelic|acpm|mediego|googletagmanager|onesignal|walkme)\.(com|fr|tv)$#", $sitename)){return 143;}
		if(preg_match("#(^|\.)ew3\.io$#", $sitename)){return 143;}
		
		if(preg_match("#(^|\.)akamaihd\.net$#", $sitename)){return 20;}
		if(preg_match("#(^|\.)(playbattlegrounds|playstation|ubi|king|xboxlive)\.(com|net)$#", $sitename)){return 58;}
		if(preg_match("#(^|\.)(amazonaws|amazon)\.[a-z]+$#", $sitename)){return 19;}
        if(preg_match("#(^|\.)(amazon)\.(com|co)\.[a-z]+$#", $sitename)){return 19;}

		if(preg_match("#(^|\.)(google-analytics|appsflyer|criteo|estat|alexametrics|omnitagjs|adlooxtracking)\.(com|io|net|org)$#", $sitename)){return 143;}
		
		if(preg_match("#(^|\.)(firefox|mozilla|apache|abuseipdb|envato|trendmicro|mcafee|tplinkcloud|techsmith|ftcdn|oracle|citrix)\.[a-z]+#", $sitename)){return 126;}
		if(preg_match("#(^|\.)artica\.center$#", $sitename)){return 126;}
		if(preg_match("#(^|\.)(quickconnect|ookla|speedtest)\.[a-z]+$#", $sitename)){return 126;}

        if(preg_match("#(^|\.)(weather)\.[a-z]+$#", $sitename)){return 127;}


        //transport

        if(preg_match("#(^|\.)truckstop\.com$#", $sitename)){return 145;}

        //music
        if(preg_match("#(^|\.)(spotify)\.[a-z]+$#", $sitename)){return 101;}

        if(preg_match("#(^|\.)ecosia\.[a-z]+$#", $sitename)){return 129;}

        // New / Press
        if(preg_match("#(^|\.)(lemonde|lemde)\.[a-z]+$#", $sitename)){return 103;}
		
		if(preg_match("#(^|\.)(grouponcdn|groupondata|groupon|fnac)\.[a-z]+$#", $sitename)){return 8;}
		if(preg_match("#(^|\.)(linkedin|licdn|instagram|qzone\.qq|myspace|weibo|reddit|pinterest|flickr|staticflickr|slideflickr)\.[a-z]+$#", $sitename)){return 134;}

		if(preg_match("#(^|\.)(orange|akamaized)\.[a-z]+$#", $sitename)){return 83;}
		if(preg_match("#(^|\.)samsungcloudsolution\.[a-z]+$#", $sitename)){return 98;}
		if(preg_match("#(^|\.)(kaspersky|malwarebytes|symantec|kaspersky-labs)\.[a-z]+$#", $sitename)){return 36;}

        //remote control
        if(preg_match("#(^|\.)(anydesk|teamviewer)\.[a-z]+$#", $sitename)){return 123;}

		//80 imagehosting
		if(preg_match("#(^|\.)(firefoxusercontent|woopic)\.com$#", $sitename)){return 80;}

		if(preg_match("#(^|\.)(roblox|nintendo)\.(com|net)$#", $sitename)){return 58;}
		if(preg_match("#(^|\.)snapchat\.[a-z]+$#", $sitename)){return 34;}
		if(preg_match("#(^|\.)(musical|vimeo|dailymotion)\.(ly|com)$#", $sitename)){return 15;}
		if(preg_match("#(^|\.)mywot\.com$#", $sitename)){return 32;}
		if(preg_match("#(^|\.)(tumblr|wordpress|wp)\.(com|net)$#", $sitename)){return 30;}
		if(preg_match("#\.(localdomain|local|localhost)$#", $sitename)){return 82;}
		if(preg_match("#(^|\.)(bing|duckduckgo)\.(com|net)$#", $sitename)){return 129;}
        if(preg_match("#(^|\.)joinhoney\.(com|net)$#", $sitename)){return 32;}
        if(preg_match("#(^|\.)photobox\.[a-z]+$#", $sitename)){return 106;}

		// ISP
		if(preg_match("#(^|\.)(socgen|bnpparisbas|stripe|paypal)\.[a-z]+$#", $sitename)){return 50;}

		if(preg_match("#(^|\.)lastpass\.com$#", $sitename)){return 104;}
		if(preg_match("#(^|\.)bootstrapcdn\.com$#", $sitename)){return 154;}
		if(preg_match("#(^|\.)teamviewer\.com$#", $sitename)){return 123;}
		if(preg_match("#(^|\.)rabbits\.webcam$#", $sitename)){return 109;}

        //porn
        if(preg_match("#(^|\.)(icfcdn|ypncdn|youporn|pornhub|playboy|xvideos|xnxx|xhamster|porn)\.(com|net)$#", $sitename)){return 109;}
		if(preg_match("#(^|\.)(sex|mmoframes|newsensations|porndoe|lit-erotica|tubepleasure)\.(com|net)$#", $sitename)){return 109;}
		if(preg_match("#(^|\.)(wikipedia|wikimedia)\.(com|org)$#", $sitename)){return 43;}
		if(preg_match("#(^|\.)gravatar\.(com|org)$#", $sitename)){return 107;}
		if(preg_match("#(^|\.)sante\.fr$#", $sitename)){return 66;}
		//MAPS
		if(preg_match("#(^|\.)virtualearth\.net$#", $sitename)){return 94;}
		if(preg_match("#(^|\.)(dropbox|getdropbox)\.com$#", $sitename)){return 49;}
		if(preg_match("#(^|\.)e-i\.com$#", $sitename)){return 55;}
        if(preg_match("#(^|\.)sapo\.pt$#", $sitename)){return 3;}
        if(preg_match("#(^|\.)(digitaloceanspaces|digitaloceanspace|digitaloceanmirrors|digitalocean|digitaloceanllc|zerointellect)\.(com|net)$#",$sitename)){return 230;}
        if(preg_match("#(^|\.)do\.co$#",$sitename)){return 230;}

	}
	
	
	private function CreateRemoteCategory($CategoryName,$category_id,$SERVICE_ID){
	    $category=new categories();
	    if(!$category->create_category($CategoryName,null,$SERVICE_ID,$category_id)){
            $this->WLOGS("<strong>$category_id/$CategoryName</strong> Error while creating the category $category->mysql_error");
        }
    }

	public function ufdbcat($sitename){
        $sitename=strtolower($sitename);
        return $this->ufdbcat_dns($sitename);
    }
	
	private function is_local_net($ipaddr){
		if($this->ip_in_range($ipaddr, "10.0.0.0/8")){return 82;}
		if($this->ip_in_range($ipaddr, "172.16.0.0/12")){return 82;}
		if($this->ip_in_range($ipaddr, "192.168.0.0/16")){return 82;}
        if($this->ip_in_range($ipaddr, "169.254.0.0/16")){return 82;}
		$category=$this->is_microsoft($ipaddr);
		if($category>0){return $category;}
        $category=$this->is_cloudlfare($ipaddr);
		return $category;
		
	}
	
	private function is_microsoft($ipaddr){
		$this->WLOGS("$ipaddr: Checks Microsoft Networks");
		$pp=new products_ip_ranges();
		$mic=$pp->microsoft_networks();
		foreach ($mic as $cdir=>$none){
			if($this->ip_in_range($ipaddr, $cdir)){return 2;}
		}
	
		return $this->is_amazon($ipaddr);
	
	}

	private function ufdbcatDeCompose($answer){
        $content=array();



        foreach ($answer as $index=>$rr){

            $data=$rr->text[0];
            if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = [$data]",__LINE__);}

            if(preg_match("#^[0-9]+\s+99999::99999:([0-9]+):([0-9]+):([0-9]+):([0-9\|]+)#",$data,$re)){
                $category_id = 99999;
                $categoryname = "Null";
                $this->CategoryTime = intval($re[3]);
                $this->CategoryName = $categoryname;
                $this->CategoryNumbers=$re[2];
                $content[]="CATZLIST:".trim($re[4]);
                continue;
            }else{
                if($GLOBALS["VERBOSE"]){
                    echo "NO MATCHES #^[0-9]+\s+9999::99999:([0-9]+):([0-9]+):([0-9]+):([0-9\|]+)# \n\n\"$data\"\n\n\n";
                }
            }


            if(preg_match("#^[0-9]+\s+99999:(.+)#",$data,$re)){
                    $content[]=trim($re[1]);
                    continue;
                }
        }
        $phrase=@implode("",$content);

        if(preg_match("#^CATZLIST:(.+)#",trim($phrase),$re)){
            $Orders=explode("|",$re[1]);
            $this->CategoriesList=$Orders;
            return;
        }



        if(!preg_match("#^99999:([0-9]+):([0-9+]):([0-9]+):([0-9\|]+)$#",trim($phrase),$re)) {
            VERBOSE("$phrase No matches", __LINE__);
        }
            $category_id = 99999;
            $categoryname = "Null";
            $this->CategoryTime = intval($re[3]);
            $this->CategoryName = $categoryname;

            $Orders=explode("|",$re[4]);
            $this->CategoriesCount=$Orders[0];

            if ($GLOBALS["VERBOSE"]) {
                VERBOSE("data[$index] = Category ID: $category_id", __LINE__);
            }
             if ($GLOBALS["VERBOSE"]) {
                VERBOSE("data[$index] = Time.......: {$re[3]}", __LINE__);
            }
            $this->CategoriesList=explode("|",$re[4]);
            if($GLOBALS["VERBOSE"]){
                VERBOSE("data[$index] = CategoriesList.....: ".count($this->CategoriesList),__LINE__);
                foreach ($this->CategoriesList as $index=>$value){
                    VERBOSE("data[$index] = CategoriesList($index).....: $value",__LINE__);

                }

            }

            return $category_id;

    }

    public function synchronizeCatz($sitename,$ID){
        $PARAMS=$this->DNSremotes[$ID];

        $DnsCatzCrypt       =   intval($PARAMS["decrypt"]);
        $DnsCatzPassPharse  =   trim($PARAMS["passphrase"]);
        $DnsCatzHost        =   trim($PARAMS["hostname"]);
        $DnsCatzPort        =   intval($PARAMS["port"]);
        $DnscatzDomain      =   trim($PARAMS["domain"]);
        if($DnscatzDomain==null){$DnscatzDomain="categories.tld";}
        if ($DnsCatzPort == 0) {$DnsCatzPort = 3477;}
        if(!preg_match("#^[0-9\.]+#",$DnsCatzHost)){
            if($GLOBALS["VERBOSE"]){echo "$DnsCatzHost -> gethostbyname [".__LINE__."]\n";}
            $DnsCatzHost=gethostbyname($DnsCatzHost);
            if($GLOBALS["VERBOSE"]){echo "gethostbyname -> $DnsCatzHost [".__LINE__."]\n";}
        }

        if($DnsCatzHost==null){
            $DnsCatzPort=53;
        }

        $f[]="/usr/bin/dig";

        if($DnsCatzHost<>null){
            $f[]="@$DnsCatzHost";
        }

        if($DnsCatzPort<>53){
            $f[]="-p3477";
        }

        $f[]="$sitename.$DnscatzDomain";

        $f[]="TXT";
        $f[]="2>&1";

        exec(@implode(" ",$f),$results);
        $List=array();
        foreach ($results as $line){

            if(preg_match("#IN TXT\s+\"(.+?)\"#",$line,$re)){
                $line=$re[1];
                if(preg_match("#[0-9]+\s+99999::99999:([0-9]+):([0-9]+):([0-9]+):(.+)#",$line,$re)){
                    $CountOfCategories=$re[1];
                    $NumberOfItems=$re[2];
                    $DateOfCompile=$re[3];
                    $List[]=$re[4];
                    continue;
                }

                if(preg_match("#[0-9]+\s+99999:\|(.+)#",$line,$re)){
                    $List[]=$re[1];
                }
            }
        }


        if(count($List)==0){
            $this->ok=false;
            return;
        }

        $this->CategoryNumbers=$CountOfCategories;
        $this->lastUpdate=intval($DateOfCompile);
        $Merged=@implode("|",$List);
        $this->CategoriesList=@explode("|",$Merged);


        if($GLOBALS["VERBOSE"]){
            echo "Number Of categories: $CountOfCategories\n";
            echo "Number of items: $NumberOfItems\n";
            echo "Date...........: $DateOfCompile\n";
            echo "List...........: ".@implode("|",$List);
        }

        $this->ok=true;
        return true;

    }

    private function FillCguardCatzData():bool{
        if (!isset($GLOBALS["CguardCatzData"])){
            $GLOBALS["CguardCatzData"] = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CguardCatzData")));
        }
        if(!isset($GLOBALS["CguardCatzData"]["99999999"])) { return false;}
        if(!isset($GLOBALS["CguardCatzData"]["99999999"]["DOMAIN"])) { return false;}
        $this->DNSremotes[999991]["hostname"]=null;
        $this->DNSremotes[999991]["port"]=3477;
        $this->DNSremotes[999991]["passphrase"]=null;
        $this->DNSremotes[999991]["decrypt"]=0;
        $this->DNSremotes[999991]["domain"]=null;

        if(isset($GLOBALS["CguardCatzData"]["99999999"]["HOST"])) {
            $this->DNSremotes[999991]["hostname"] = $GLOBALS["CguardCatzData"]["99999999"]["HOST"];
        }
        if(isset($GLOBALS["CguardCatzData"]["99999999"]["PORT"])) {
            $this->DNSremotes[999991]["port"] = $GLOBALS["CguardCatzData"]["99999999"]["PORT"];
        }
        if(isset($GLOBALS["CguardCatzData"]["99999999"]["PASS"])) {
            $this->DNSremotes[999991]["passphrase"] = $GLOBALS["CguardCatzData"]["99999999"]["PASS"];
        }
        if(isset($GLOBALS["CguardCatzData"]["99999999"]["CRYPT"])) {
            $this->DNSremotes[999991]["decrypt"] = $GLOBALS["CguardCatzData"]["99999999"]["CRYPT"];
        }

        $this->DNSremotes[999991]["domain"] = $GLOBALS["CguardCatzData"]["99999999"]["DOMAIN"];

        return true;

	}

	public function ufdbcat_remote($sitename,$ID){
        $this->CategoryNumbers=0;
        $this->CategoryTime=0;
        $this->CategoriesCount=0;
        $this->CategoriesList=array();
        $this->CategoryName=null;
        $this->ok=false;
        $this->WLOGS("$sitename: Checking connection id $ID");
        if($ID==999991){ $this->FillCguardCatzData(); }


        if(!isset($this->DNSremotes[$ID])){
            $this->WLOGS("$sitename: $ID no such configuration");
            $this->mysql_error="$ID no such configuration";
            $this->ok=false;
            return 0;
        }

        $PARAMS=$this->DNSremotes[$ID];
        $DnsCatzCrypt       =   intval($PARAMS["decrypt"]);
        $DnsCatzPassPharse  =   trim($PARAMS["passphrase"]);
        $DnsCatzHost        =   trim($PARAMS["hostname"]);
        $DnsCatzPort        =   intval($PARAMS["port"]);
        $DnscatzDomain      =   trim($PARAMS["domain"]);
        if($DnscatzDomain==null){$DnscatzDomain="categories.tld";}
        if ($DnsCatzPort == 0) {$DnsCatzPort = 3477;}
        if(!preg_match("#^[0-9\.]+#",$DnsCatzHost)){
            if($GLOBALS["VERBOSE"]){echo "$DnsCatzHost -> gethostbyname [".__LINE__."]\n";}
            $DnsCatzHost=gethostbyname($DnsCatzHost);
            if($GLOBALS["VERBOSE"]){echo "gethostbyname -> $DnsCatzHost [".__LINE__."]\n";}
        }

        if($DnsCatzHost==null){
            $nameservers=$this->LoadMyDNS();
            $DnsCatzPort=53;
        }else{
            $nameservers=array($DnsCatzHost);

        }
        if($GLOBALS["VERBOSE"]){VERBOSE("Loading Net_DNS2_Resolver()",__LINE__);}
        $r = new Net_DNS2_Resolver(array('nameservers' => $nameservers, "timeout" => 1));
        $r->dns_port=$DnsCatzPort;

        try {
            if($GLOBALS["VERBOSE"]){echo "TXT: $sitename.$DnscatzDomain [".__LINE__."]\n";}
            $result = $r->query("$sitename.$DnscatzDomain", 'TXT');

        } catch(Net_DNS2_Exception $e) {
            $message=$e->getMessage();
            $this->mysql_error=$message;
            $this->ok=false;
            $this->WLOGS("$sitename: Query: Error $message");
            if($GLOBALS["VERBOSE"]){VERBOSE("DNSremotes[$ID] Query: Error $message",__LINE__);}
            if(stripos("  $message","referenced in the query does not exist")>0){return 0;}

            return 0;
        }



        $this->ok=true;
        foreach ($result->answer as $index=>$rr){
            $data=$rr->text[0];


            if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = [$data]",__LINE__);}

            if(preg_match("#^[0-9]+\s+99999:#",$data)){
                if($GLOBALS["VERBOSE"]){VERBOSE("this->ufdbcatDeCompose(result->answer...",__LINE__);}
                return $this->ufdbcatDeCompose($result->answer);
            }

            if($DnsCatzCrypt==1){
                $crypt=new OpenSSLAES();
                if($GLOBALS["VERBOSE"]){VERBOSE("Uncrypt $data with [$DnsCatzPassPharse]",__LINE__);}
                $data=$crypt->decrypt($data,$DnsCatzPassPharse);
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = [$data]",__LINE__);}
            }

            if(preg_match("#([0-9]+):([0-9]+):([0-9]+):([0-9]+):([0-9\|]+)$#",trim($data),$re)){
                $category_id=intval($re[1]);
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = Category ID: $category_id",__LINE__);}
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = Items......: {$re[2]}",__LINE__);}
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = Categories.: {$re[3]}",__LINE__);}
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = Time.......: {$re[4]}",__LINE__);}
                $this->CategoriesCount=$re[2];
                $this->CategoryNumbers=$re[3];
                $this->CategoryTime=$re[4];
                $this->CategoriesList=explode("|",$re[5]);
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = CategoriesList.....: ".count($this->CategoriesList),__LINE__);}
                return $category_id;
            }else{
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = NO MATCHES",__LINE__);}
            }

            if(preg_match("#([0-9]+):([0-9]+):([0-9]+):([0-9]+)$#",trim($data),$re)){
                $category_id=intval($re[1]);
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = Category ID: $category_id",__LINE__);}
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = Items......: {$re[2]}",__LINE__);}
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = Categories.: {$re[3]}",__LINE__);}
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = Time.......: {$re[4]}",__LINE__);}
                $this->CategoriesCount=$re[2];
                $this->CategoryNumbers=$re[3];
                $this->CategoryTime=$re[4];
                return $category_id;
            }else{
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = NO MATCHES",__LINE__);}
            }

            if(preg_match("#^([0-9]+):(.+):([0-9]+):([0-9\|]+)$#",trim($data),$re)) {
                VERBOSE("data[$index] = Found category with orders", __LINE__);
                $category_id = intval($re[1]);
                $categoryname = trim($re[2]);
                $this->CategoryTime = intval($re[3]);
                $this->CategoryName = $categoryname;

                $Orders=explode("|",$re[4]);
                $this->CategoriesCount=$Orders[0];

                if ($GLOBALS["VERBOSE"]) {
                    VERBOSE("data[$index] = Category ID: $category_id", __LINE__);
                }
                if ($GLOBALS["VERBOSE"]) {
                    VERBOSE("data[$index] = Name ......: {$re[2]}", __LINE__);
                }
                if ($GLOBALS["VERBOSE"]) {
                    VERBOSE("data[$index] = Time.......: {$re[3]}", __LINE__);
                }
                $this->CategoriesList=explode("|",$re[4]);
                if($GLOBALS["VERBOSE"]){
                    VERBOSE("data[$index] = CategoriesList.....: ".count($this->CategoriesList),__LINE__);
                    foreach ($this->CategoriesList as $index=>$value){
                        VERBOSE("data[$index] = CategoriesList($index).....: $value",__LINE__);

                    }

                }

                return $category_id;
            }else{
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = NO MATCHES",__LINE__);}
            }

            if(preg_match("#([0-9]+):(.+):([0-9]+)#",trim($data),$re)) {
                $category_id = intval($re[1]);
                $categoryname = trim($re[2]);
                $this->CategoryTime = intval($re[3]);
                $this->CategoryName = $categoryname;
                if ($GLOBALS["VERBOSE"]) {
                    VERBOSE("data[$index] = Category ID: $category_id", __LINE__);
                }
                if ($GLOBALS["VERBOSE"]) {
                    VERBOSE("data[$index] = Name ......: {$re[2]}", __LINE__);
                }
                if ($GLOBALS["VERBOSE"]) {
                    VERBOSE("data[$index] = Time.......: {$re[3]}", __LINE__);
                }
                return $category_id;
            }else{
                if($GLOBALS["VERBOSE"]){VERBOSE("data[$index] = NO MATCHES",__LINE__);}
                }


            if(preg_match("#([0-9]+):(.+)#",trim($data),$re)){
                $category_id=intval($re[1]);
                $categoryname=trim($re[2]);
                if($category_id==89){continue;}
                if($category_id==199){continue;}
                $this->CategoryName=$categoryname;
                return $category_id;
            }

        }

        return 0;

    }
	
	

	
	
	private function is_amazon($ipaddr){
		$this->WLOGS("$ipaddr: Checks Amazon Networks");


		for($i=128;$i<256;$i++){ if($this->ip_in_range($ipaddr, "184.169.$i.0/24")){return 19;}	}
		for($i=200;$i<238;$i++){ if($this->ip_in_range($ipaddr, "18.$i.0.0/16")){return 19;}	}
		

		
		for($i=192;$i<256;$i++){ if($this->ip_in_range($ipaddr, "34.$i.0.0/16")){return 19;} }
		for($i=153;$i<183;$i++){ if($this->ip_in_range($ipaddr, "35.153.$i.0/24")){return 19;}	}
		for($i=152;$i<183;$i++){ if($this->ip_in_range($ipaddr, "35.$i.0.0/16")){return 19;} }

		if($this->ip_in_range($ipaddr, "40.70.0.0/16")){return 19;}
		for($i=192;$i<256;$i++){
            if($this->ip_in_range($ipaddr, "44.$i.0.0/16")){return 19;}
        }


		
		for($i=0;$i<80;$i++){ if($this->ip_in_range($ipaddr, "52.$i.0.0/16")){return 19;}	}



		for($i=69;$i<100;$i++){ if($this->ip_in_range($ipaddr, "54.$i.0.0/16")){return 19;}	}
		for($i=144;$i<256;$i++){ if($this->ip_in_range($ipaddr, "54.$i.0.0/16")){return 19;}	}


        for($i=128;$i<256;$i++){ if($this->ip_in_range($ipaddr, "75.101.$i.0/24")){return 19;}	}
		for($i=32;$i<64;$i++){ if($this->ip_in_range($ipaddr, "216.137.$i.0/24")){return 19;}	}








		
		$this->WLOGS("->->is_akamai($ipaddr)");
		return $this->is_akamai($ipaddr);
	}

	private function is_cloudlfare($ipaddr):int{
        //CloudFlare


        return 0;
    }
	
	private function is_akamai($ipaddr){
		
		for($i=1;$i<16;$i++){if($this->ip_in_range($ipaddr, "23.$i.0.0/16")){return 20;}	}
        for($i=1;$i<256;$i++){if($this->ip_in_range($ipaddr, "2.22.$i.0/24")){return 20;}	}
		for($i=43;$i<68;$i++){if($this->ip_in_range($ipaddr, "23.$i.0.0/16")){return 20;}}
        for($i=192;$i<222;$i++){if($this->ip_in_range($ipaddr, "23.$i.0.0/16")){return 20;}}
        for($i=64;$i<128;$i++){ if($this->ip_in_range($ipaddr, "104.$i.0.0/16")){return 20;}}



		$resutls=intval($this->is_google($ipaddr));
        $this->WLOGS("->->is_google($ipaddr) -> $resutls");
        if($resutls>0){return $resutls;}

        $resutls=intval($this->is_in_yahoo($ipaddr));
        $this->WLOGS("->->is_yahoo($ipaddr) -> $resutls");
        if($resutls>0){return $resutls;}

        $resutls=$this->is_ovh_sas($ipaddr);
        $this->WLOGS("->->is_ovh_sass($ipaddr) -> $resutls");
        if($resutls>0){return $resutls;}

        $resutls=$this->is_Hangzhou_Alibaba_Advertising($ipaddr);
        $this->WLOGS("->->is_Hangzhou_Alibaba_Advertising($ipaddr) -> $resutls");
        if($resutls>0){return $resutls;}

        $resutls=$this->is_university($ipaddr);
        $this->WLOGS("->->is_university($ipaddr) -> $resutls");
        if($resutls>0){return $resutls;}

        $this->WLOGS("->->is_poneytelecom($ipaddr)");
		return $this->is_poneytelecom($ipaddr);
	}
	
	private function is_poneytelecom($ipaddr){
		if($this->ip_in_range($ipaddr, "62.210.0.0/16")){return 228;}
		if($this->ip_in_range($ipaddr, "195.154.0.0/16")){return 228;}
		if($this->ip_in_range($ipaddr, "163.172.0.0/16")){return 228;}
		if($this->ip_in_range($ipaddr, "51.15.0.0/16")){return 228;}
		if($this->ip_in_range($ipaddr, "151.115.0.0/16")){return 228;}
		if($this->ip_in_range($ipaddr, "212.129.0.0/18")){return 228;}
		if($this->ip_in_range($ipaddr, "62.4.0.0/19")){return 228;}
		if($this->ip_in_range($ipaddr, "212.83.128.0/19")){return 228;}
		if($this->ip_in_range($ipaddr, "212.83.160.0/19")){return 228;}
		if($this->ip_in_range($ipaddr, "212.47.224.0/19")){return 228;}
		
		if($this->ip_in_range($ipaddr, "152.195.0.0/16")){return 83;}
		
		// VErizon


		//  Incapsula
        if($this->ip_in_range($ipaddr, "107.154.0.0/16")){return 83;}


        //Joe's Datacenter,
		if($this->ip_in_range($ipaddr, "69.195.159.0/24")){return 83;}

		//NetActuate
		if($this->ip_in_range($ipaddr, "185.40.235.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "176.58.88.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "176.58.89.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "176.58.90.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "176.58.91.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "176.58.92.0/24")){return 83;}
		
		//Oxalide:
		if($this->ip_in_range($ipaddr, "95.131.137.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "95.131.138.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "95.131.139.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "95.131.140.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "95.131.141.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "95.131.142.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "95.131.143.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "146.185.42.0/24")){return 83;}
		
		// ip-pool.com - Germany - Host Europe GmbH
        if($this->ip_in_range($ipaddr, "62.138.14.0/24")){return 83;} //Host Europe GmbH
        if($this->ip_in_range($ipaddr, "85.93.89.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "85.93.90.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "85.93.91.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "85.93.92.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "85.93.93.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "85.93.94.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "85.93.95.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "83.169.16.0/24")){return 83;} //Host Europe GmbH
		if($this->ip_in_range($ipaddr, "91.250.80.0/24")){return 83;} //Host Europe GmbH

		
		// Jisc Services Limited
		if($this->ip_in_range($ipaddr, "193.63.75.0/24")){return 83;}
		
		// CDN77
		if($this->ip_in_range($ipaddr, "185.93.1.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "185.93.2.0/24")){return 83;}
		
		if($this->ip_in_range($ipaddr, "185.75.143.0/24")){return 83;} //Avenir Telematique S.A.S.
		


		if($this->ip_in_range($ipaddr, "37.59.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "37.187.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "46.105.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "54.37.0.0/16")){return 83;}
		
		// ODISO
		if($this->ip_in_range($ipaddr, "89.248.208.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "89.248.209.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "89.248.210.0/24")){return 83;}

		// netDNA
		if($this->ip_in_range($ipaddr, "23.111.8.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "23.111.9.0/24")){return 83;} 
		if($this->ip_in_range($ipaddr, "23.111.10.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "23.111.11.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "146.88.138.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "151.139.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "108.161.188.0/24")){return 83;}
		
		
		
		for($i=0;$i<64;$i++){ if($this->ip_in_range($ipaddr, "5.153.$i.0/24")){return 232;}	} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "5.10.88.0/24")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "108.168.176.0/24")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "108.168.180.0/24")){return 232;} // SoftLayer Technologies Inc.
		for($i=38;$i<64;$i++){ if($this->ip_in_range($ipaddr, "169.$i.0.0/16")){return 232;}	} // SoftLayer Technologies Inc.
		
		if($this->ip_in_range($ipaddr, "37.58.85.0/24")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "50.23.0.0/16")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "74.86.208.0/24")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "75.126.0.0/16")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "77.104.177.0/24")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "173.192.0.0/16")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "173.193.0.0/16")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "158.85.0.0/16")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "159.8.0.0/16")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "184.173.147.0/24")){return 232;} // SoftLayer Technologies Inc.
		if($this->ip_in_range($ipaddr, "192.155.212.0/24")){return 232;} // SoftLayer Technologies Inc.
		
		
		if($this->ip_in_range($ipaddr, "185.151.188.0/24")){return 83;} // ODISO.
		if($this->ip_in_range($ipaddr, "185.151.189.0/24")){return 83;} // ODISO.
		if($this->ip_in_range($ipaddr, "185.151.190.0/24")){return 83;} // ODISO.
		
		if($this->ip_in_range($ipaddr, "80.252.91.0/24")){return 83;} // TELECITYGROUP INTERNATIONAL LIMITED
		if($this->ip_in_range($ipaddr, "212.31.129.0/24")){return 83;} // Telehouse International Corporation of Europe Ltd
		if($this->ip_in_range($ipaddr, "110.49.174.0/24")){return 83;} // Thailand - ADVANCED WIRELESS NETWORK COMPANY LIMITED
		
		for($i=0;$i<90;$i++){ if($this->ip_in_range($ipaddr, "66.155.$i.0/24")){return 83;}} // Cogeco Peer 1
		if($this->ip_in_range($ipaddr, "76.74.234.0/24")){return 83;} // Cogeco Peer 1

		
		if($this->ip_in_range($ipaddr, "46.19.122.0/24")){return 83;} // ITS INTEGRA SAS
		if($this->ip_in_range($ipaddr, "176.74.38.0/24")){return 83;} // ADD-ON MULTIMEDIA SAS
		if($this->ip_in_range($ipaddr, "23.92.190.0/24")){return 83;} // Voxel Dot Net, Inc.
		
		if($this->ip_in_range($ipaddr, "91.215.100.0/24")){return 83;} // INFOnline GmbH
		if($this->ip_in_range($ipaddr, "91.215.101.0/24")){return 83;} // INFOnline GmbH
		if($this->ip_in_range($ipaddr, "91.215.102.0/24")){return 83;} // INFOnline GmbH
		if($this->ip_in_range($ipaddr, "91.215.103.0/24")){return 83;} // INFOnline GmbH

        if($this->ip_in_range($ipaddr, "158.255.72.0/21")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "185.241.204.0/24")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "185.241.205.0/24")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "185.241.206.0/24")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "185.241.207.0/24")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "78.41.128.0/21")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "78.41.128.0/24")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "78.41.129.0/24")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "78.41.130.0/24")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "78.41.131.0/24")){return 83;} // Zayo France
        if($this->ip_in_range($ipaddr, "78.41.135.0/24")){return 83;} // Zayo France

		
		if($this->ip_in_range($ipaddr, "77.91.237.0/24")){return 83;} // Host Europe GmbH
		if($this->ip_in_range($ipaddr, "78.138.112.0/24")){return 83;} // PlusServer GmbH
		if($this->ip_in_range($ipaddr, "206.72.192.0/24")){return 83;} // NEW JERSEY INTERNATIONAL INTERNET EXCHANGE LLC
		if($this->ip_in_range($ipaddr, "205.196.136.0/24")){return 83;} // Windstream Communications LLC
		if($this->ip_in_range($ipaddr, "185.105.223.0/24")){return 83;} //ServiHosting Networks S.L.
		if($this->ip_in_range($ipaddr, "74.117.181.0/24")){return 83;} //WZ Communications Inc.
		if($this->ip_in_range($ipaddr, "185.2.4.0/24")){return 83;} // REGISTER.IT SPA
		
		if($this->ip_in_range($ipaddr, "188.72.202.0/24")){return 83;} // Webzilla B.V.
		if($this->ip_in_range($ipaddr, "78.140.191.0/24")){return 83;} // Webzilla B.V.
		if($this->ip_in_range($ipaddr, "78.140.180.0/24")){return 83;} // Webzilla B.V.
		
		if($this->ip_in_range($ipaddr, "188.130.45.0/24")){return 83;} // SAS CTS
		if($this->ip_in_range($ipaddr, "185.158.44.0/24")){return 83;} // SAS CTS
		if($this->ip_in_range($ipaddr, "185.158.47.0/24")){return 83;} // SAS CTS
		if($this->ip_in_range($ipaddr, "185.88.68.0/24")){return 83;} // SAS CTS
		if($this->ip_in_range($ipaddr, "185.88.69.0/24")){return 83;} // SAS CTS
		if($this->ip_in_range($ipaddr, "185.88.70.0/24")){return 83;} // SAS CTS
		if($this->ip_in_range($ipaddr, "185.88.71.0/24")){return 83;} // SAS CTS
		
		if($this->ip_in_range($ipaddr, "149.14.152.0/24")){return 83;} // Cogent Communications
        if($this->ip_in_range($ipaddr, "154.59.0.0/16")){return 83;} // Cogent Communications

		if($this->ip_in_range($ipaddr, "91.207.255.0/24")){return 83;} //PHPNET FRANCE SARL
		if($this->ip_in_range($ipaddr, "195.8.111.0/24")){return 83;} // UTEL SAS

		if($this->ip_in_range($ipaddr, "94.237.48.0/24")){return 83;} // Upcloud
        if($this->ip_in_range($ipaddr, "94.237.2.0/24")){return 83;} // Upcloud

		if($this->ip_in_range($ipaddr, "185.28.92.0/24")){return 83;} // ThePlatform UK Limited
		if($this->ip_in_range($ipaddr, "185.94.140.0/24")){return 83;} // ACORUS NETWORKS SAS
		if($this->ip_in_range($ipaddr, "216.105.42.0/24")){return 83;} // American Internet Services, LLC.
		if($this->ip_in_range($ipaddr, "83.169.16.0/24")){return 83;} // Host Europe GmbH
		if($this->ip_in_range($ipaddr, "67.202.94.0/24")){return 83;} // Steadfast
		if($this->ip_in_range($ipaddr, "172.56.0.0/16")){return 83;} //  T-Mobile USA, Inc.
		if($this->ip_in_range($ipaddr, "176.62.164.0/24")){return 83;} // Combell NV
		if($this->ip_in_range($ipaddr, "185.57.150.0/24")){return 83;}    // ALTIMA SAS
		if($this->ip_in_range($ipaddr, "208.52.184.0/24")){return 83;}    // Virtual Citadel Inc.
		if($this->ip_in_range($ipaddr, "49.44.92.0/24")){return 83;}   	  // Reliance Jio Infocomm Limited
		if($this->ip_in_range($ipaddr, "5.200.14.0/24")){return 83;}   	  // i3D.net B.V

        if($this->ip_in_range($ipaddr, "92.223.20.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "92.223.21.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "141.101.148.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "146.185.215.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "146.185.218.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "146.185.219.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "146.185.220.0/23")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "146.185.220.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.213.60.0/22")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.213.60.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.213.61.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.213.62.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.213.63.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.216.228.0/22")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.216.228.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.216.229.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.216.230.0/24")){return 83;}   	  // G-Core Labs S.A.
        if($this->ip_in_range($ipaddr, "162.216.231.0/24")){return 83;}   	  // G-Core Labs S.A.


        if($this->ip_in_range($ipaddr, "23.90.187.0/24")){return 83;}    // Zenlayer
		
		if($this->ip_in_range($ipaddr, "109.69.186.0/24")){return 83;}    // Datacampus SARL
		if($this->ip_in_range($ipaddr, "109.69.187.0/24")){return 83;}    // Datacampus SARL
        if($this->ip_in_range($ipaddr, "143.244.56.0/24")){return 83;}    // Datacampus SARL
		
		if($this->ip_in_range($ipaddr, "14.102.148.0/24")){return 83;}    // Malaysia - IP ServerOne ipserverone
		if($this->ip_in_range($ipaddr, "103.10.156.0/24")){return 83;}    // Malaysia - IP ServerOne
		if($this->ip_in_range($ipaddr, "103.10.157.0/24")){return 83;}    // Malaysia - IP ServerOne
		if($this->ip_in_range($ipaddr, "103.10.158.0/24")){return 83;}    // Malaysia - IP ServerOne
		if($this->ip_in_range($ipaddr, "103.10.159.0/24")){return 83;}    // Malaysia - IP ServerOne
		if($this->ip_in_range($ipaddr, "103.21.180.0/24")){return 83;}    // Malaysia - IP ServerOne
		if($this->ip_in_range($ipaddr, "103.21.181.0/24")){return 83;}    // Malaysia - IP ServerOne
		
		if($this->ip_in_range($ipaddr, "185.75.252.0/24")){return 83;}    // Viking Host B.V.
		if($this->ip_in_range($ipaddr, "94.199.253.0/24")){return 83;}    // Viking Host B.V.
		
		if($this->ip_in_range($ipaddr, "31.193.142.0/24")){return 83;}    // Simply Transit Ltd
		if($this->ip_in_range($ipaddr, "46.166.134.0/24")){return 83;}    // NForce Entertainment B.V.
		if($this->ip_in_range($ipaddr, "79.107.104.0/24")){return 83;}    // WIND HELLAS TELECOMMUNICATIONS SA
		if($this->ip_in_range($ipaddr, "81.16.155.0/24")){return 83;}    //  yelster digital gmbh
		if($this->ip_in_range($ipaddr, "82.195.75.0/24")){return 83;}    //  Man-da.de GmbH
		if($this->ip_in_range($ipaddr, "83.164.148.0/24")){return 83;}   //  lagis Internet Service Provider GmbH
		if($this->ip_in_range($ipaddr, "85.116.38.0/24")){return 83;}   //   LAGARDERE ACTIVE DIGITAL SAS
		
		if($this->ip_in_range($ipaddr, "87.252.12.0/24")){return 83;}   //   Ecritel SARL
		if($this->ip_in_range($ipaddr, "91.188.68.0/24")){return 83;}   //   Ecritel SARL
		if($this->ip_in_range($ipaddr, "128.204.213.0/24")){return 83;} //   Ecritel SARL
		
		if($this->ip_in_range($ipaddr, "147.75.44.0/24")){return 83;}   //   Netherlands - Packet Host, Inc.
		if($this->ip_in_range($ipaddr, "67.20.95.0/24")){return 83;}   //    Unified Layer
		
		if($this->ip_in_range($ipaddr, "193.84.73.0/24")){return 83;}   //   FullSave
		
		
		if($this->ip_in_range($ipaddr, "109.234.160.0/24")){return 83;}   // o2switch SARL
		if($this->ip_in_range($ipaddr, "109.234.161.0/24")){return 83;}   // o2switch SARL
		if($this->ip_in_range($ipaddr, "109.234.162.0/24")){return 83;}   // o2switch SARL
		if($this->ip_in_range($ipaddr, "109.234.163.0/24")){return 83;}   // o2switch SARL
		if($this->ip_in_range($ipaddr, "109.234.164.0/24")){return 83;}   // o2switch SARL
		if($this->ip_in_range($ipaddr, "185.154.136.0/22")){return 83;}   // o2switch SARL
		
		if($this->ip_in_range($ipaddr, "95.128.73.0/24")){return 83;}   // 	 Celeonet SAS
		if($this->ip_in_range($ipaddr, "185.66.195.0/24")){return 83;}   //  Freifunk Rheinland e.V.
		if($this->ip_in_range($ipaddr, "81.7.11.0/24")){return 83;}   //     ISPpro Internet KG
		if($this->ip_in_range($ipaddr, "89.30.106.0/24")){return 83;}   //   VINC SAS
		if($this->ip_in_range($ipaddr, "90.85.36.0/24")){return 83;}   //    VSC Technologies SAS
		if($this->ip_in_range($ipaddr, "198.145.13.0/24")){return 83;}   //  Infinity Internet, Inc.
		if($this->ip_in_range($ipaddr, "46.20.171.0/24")){return 83;}   //   ADISTA SAS
		if($this->ip_in_range($ipaddr, "78.110.61.0/24")){return 83;}   //   ZAO "Hosting Telesystems"
		if($this->ip_in_range($ipaddr, "31.170.8.0/24")){return 83;}   //    Evolix SARL
		if($this->ip_in_range($ipaddr, "185.151.28.0/24")){return 83;}   //  Node4 Limited
		if($this->ip_in_range($ipaddr, "185.16.44.0/22")){return 83;} 	  // Planet Service SARL
		if($this->ip_in_range($ipaddr, "64.207.186.0/24")){return 83;}   //  Media Temple, Inc.
		if($this->ip_in_range($ipaddr, "67.222.101.0/24")){return 83;}   //  IHNetworks, LLC
		if($this->ip_in_range($ipaddr, "79.143.244.0/24")){return 83;}   //  Absolight
		if($this->ip_in_range($ipaddr, "79.99.165.0/24")){return 83;}   //   Planet-Work
		if($this->ip_in_range($ipaddr, "91.194.60.0/24")){return 83;}   //   Octopuce s.a.r.l.
		if($this->ip_in_range($ipaddr, "93.184.219.0/24")){return 83;}   //  MCI Communication
		if($this->ip_in_range($ipaddr, "185.42.223.0/24")){return 83;}   //  Clouvider 
		if($this->ip_in_range($ipaddr, "185.66.233.0/24")){return 83;}   //  Aqua Ray SAS
		if($this->ip_in_range($ipaddr, "195.181.160.0/24")){return 83;}  //  Datacamp Limited
		if($this->ip_in_range($ipaddr, "212.37.223.0/24")){return 83;}  //   PROSODIE SAS
		if($this->ip_in_range($ipaddr, "213.196.39.0/24")){return 83;}  //   Servers.com, Inc.
		if($this->ip_in_range($ipaddr, "216.240.132.0/24")){return 83;}  //  MULTACOM CORPORATION
		if($this->ip_in_range($ipaddr, "46.17.66.0/24")){return 83;}     //  PICTIME SAS
		if($this->ip_in_range($ipaddr, "206.125.164.0/24")){return 83;}  //  Technologie Optic.ca
        if($this->ip_in_range($ipaddr, "149.154.153.0/24")){return 83;}  //  EDIS GmbH
        if($this->ip_in_range($ipaddr, "146.255.56.0/24")){return 83;}  //   Nessus GmbH

        if($this->ip_in_range($ipaddr, "149.112.112.0/24")){return 83;}     //  Quad9
        if($this->ip_in_range($ipaddr, "149.112.149.0/24")){return 83;}     //  Quad9
        if($this->ip_in_range($ipaddr, "199.249.255.0/24")){return 83;}     //  Quad9
        if($this->ip_in_range($ipaddr, "9.9.9.0/24")){return 83;}           //  Quad9


		
		if($this->ip_in_range($ipaddr, "159.253.63.0/24")){return 83;}   //  Nuco Technologies
		if($this->ip_in_range($ipaddr, "185.143.75.0/24")){return 83;}   //  Nuco Technologies
		if($this->ip_in_range($ipaddr, "185.184.236.0/22")){return 83;}   //  Nuco Technologies
		if($this->ip_in_range($ipaddr, "217.172.135.0/24")){return 83;}   //  Nuco Technologies
		if($this->ip_in_range($ipaddr, "217.172.136.0/22")){return 83;}   //  Nuco Technologies
		
		if($this->ip_in_range($ipaddr, "31.44.210.0/24")){return 83;} //Docapost BPO SAS
		if($this->ip_in_range($ipaddr, "31.44.212.0/24")){return 83;} //Docapost BPO SAS
		if($this->ip_in_range($ipaddr, "31.44.213.0/24")){return 83;} //Docapost BPO SAS
		if($this->ip_in_range($ipaddr, "194.5.111.0/24")){return 83;} //Docapost BPO SAS

        if($this->ip_in_range($ipaddr, "103.224.182.0/24")){return 83;} // Trellian
		
		if($this->ip_in_range($ipaddr, "142.169.3.0/24")){return 83;}   //  TELUS Communications Inc.
		if($this->ip_in_range($ipaddr, "104.157.0.0/17")){return 83;}   //  TELUS Communications Inc.
		if($this->ip_in_range($ipaddr, "104.205.0.0/16")){return 83;}   //  TELUS Communications Inc.
		if($this->ip_in_range($ipaddr, "108.172.0.0/16")){return 83;}   //  TELUS Communications Inc.
		if($this->ip_in_range($ipaddr, "108.173.0.0/16")){return 83;}   //  TELUS Communications Inc.
		if($this->ip_in_range($ipaddr, "108.180.0.0/16")){return 83;}   //  TELUS Communications Inc.
		if($this->ip_in_range($ipaddr, "108.181.0.0/16")){return 83;}   //  TELUS Communications Inc.
		
		if($this->ip_in_range($ipaddr, "58.218.215.0/24")){return 83;} // China - No.31,Jin-rong Street
		if($this->ip_in_range($ipaddr, "218.90.0.0/16")){return 83;} //China - No.31,Jin-rong Street
		
		if($this->ip_in_range($ipaddr, "14.17.112.0/24")){return 83;} //China Telecom (83 --> ISP)
		if($this->ip_in_range($ipaddr, "123.58.0.0/16")){return 83;} //China - IDC, China Telecommunications Corporation
		if($this->ip_in_range($ipaddr, "59.124.61.0/24")){return 83;} // Chunghwa Telecom Co., Ltd.
		if($this->ip_in_range($ipaddr, "182.254.0.0/16")){return 83;} // Shenzhen Tencent Computer Systems Company Limited
		if($this->ip_in_range($ipaddr, "49.51.129.0/24")){return 83;} // Shenzhen Tencent Computer Systems Company Limited
		if($this->ip_in_range($ipaddr, "52.80.0.0/16")){return 83;} //  Beijing Guanghuan
		if($this->ip_in_range($ipaddr, "42.159.0.0/16")){return 83;} //  Shanghai Blue Cloud Technology Co.,Ltd
		if($this->ip_in_range($ipaddr, "120.92.22.0/24")){return 83;} //  China - Beijing Kingsoft Cloud Internet Technology Co., Ltd
		if($this->ip_in_range($ipaddr, "101.99.69.0/24")){return 83;} // Malaysia - Shinjiru Technology Sdn Bhd
		
		
		// CHINANET Guangdong province network
		if($this->ip_in_range($ipaddr, "116.31.73.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "121.14.24.0/24")){return 83;}
		
		//United States - Limelight Networks, Inc.
        if($this->ip_in_range($ipaddr, "87.248.204.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "87.248.222.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "95.140.239.0/24")){return 83;}
		
		if($this->ip_in_range($ipaddr, "140.143.48.0/24")){return 83;}   // Shenzhen Tencent Computer Systems Company Limited
		if($this->ip_in_range($ipaddr, "140.143.216.0/24")){return 83;}   // Shenzhen Tencent Computer Systems Company Limited
		if($this->ip_in_range($ipaddr, "140.143.254.0/24")){return 83;}   // Shenzhen Tencent Computer Systems Company Limited
		
		//United States - Exponential Interactive, Inc.
		if($this->ip_in_range($ipaddr, "204.11.109.0/24")){return 83;}
		
		//unbelievable-machine.com
		if($this->ip_in_range($ipaddr, "94.198.63.0/24")){return 83;}
		
		//WISTEE SAS
		if($this->ip_in_range($ipaddr, "94.124.81.0/24")){return 83;}

		// Nitronet Sp. z o.o
        if($this->ip_in_range($ipaddr, "79.98.145.0/24")){return 83;}
        //Transip B.V
        if($this->ip_in_range($ipaddr, "149.210.233.0/24")){return 83;} //Transip B.V
        if($this->ip_in_range($ipaddr, "136.144.215.0/24")){return 83;} //Transip B.V
		

		
		if($this->ip_in_range($ipaddr, "5.153.231.0/24")){return 83;} // debian.org  Bytemark Limited
		
		
		if($this->ip_in_range($ipaddr, "193.172.0.0/16")){return 83;} // KPN BV
		
		//
        if($this->ip_in_range($ipaddr, "220.242.1810/24")){return 83;} // QUANTIL NETWORKS INC

		for($i=128;$i<192;$i++){if($this->ip_in_range($ipaddr, "157.185.$i.0/24")){return 83;} }// QUANTIL NETWORKS INC
		for($i=129;$i<194;$i++){if($this->ip_in_range($ipaddr, "163.171.$i.0/24")){return 83;} }// QUANTIL NETWORKS INC
		for($i=128;$i<132;$i++){if($this->ip_in_range($ipaddr, "190 157.$i.0/24")){return 83;} } //QUANTIL NETWORKS INC
		if($this->ip_in_range($ipaddr, "163.171.0/24")){return 83;} // QUANTIL NETWORKS INC


        // Webtv (156) E-TF1
        if($this->ip_in_range($ipaddr, "185.22.116.0/22")){return 156;} 	//Fr.etf1 Lir
        if($this->ip_in_range($ipaddr, "193.169.66.0/23")){return 156;} 	//E-tf1 SAS
		
		// NetActuate, Inc
		if($this->ip_in_range($ipaddr, "185.40.234.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "176.58.89.0/24")){return 83;}
		if($this->ip_in_range($ipaddr,"192.73.241.0/24")){return 83;}
		
		//Iguane Solutions
		if($this->ip_in_range($ipaddr, "158.58.176.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "158.58.177.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "158.58.178.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "158.58.179.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "158.58.180.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "158.58.181.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "158.58.182.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "158.58.183.0/24")){return 83;}
		
		//Mediamath
		if($this->ip_in_range($ipaddr, "185.29.132.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "185.29.133.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "185.29.134.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "185.29.135.0/24")){return 83;}

		//Switch.ch
        if($this->ip_in_range($ipaddr, "130.59.0.0/16")){return 83;}


		
		
		if($this->ip_in_range($ipaddr,"91.208.180.0/24")){return 83;} //Switzerland - Sunrise Communications AG
		
		
		//nielsen -- >TRacker
		if($this->ip_in_range($ipaddr, "138.108.0.0/16")){return 143;}
		if($this->ip_in_range($ipaddr, "89.207.16.0/24")){return 143;} //conversant inc
		//Outbrain -->Tracker
        if($this->ip_in_range($ipaddr, "64.74.236.0/24")){return 143;} //Outbrain inc

		
		//Eleurian
		if($this->ip_in_range($ipaddr, "109.232.192.0/24")){return 143;}
		if($this->ip_in_range($ipaddr, "109.232.193.0/24")){return 143;}
		if($this->ip_in_range($ipaddr, "109.232.194.0/24")){return 143;}
		if($this->ip_in_range($ipaddr, "109.232.195.0/24")){return 143;}
		if($this->ip_in_range($ipaddr, "109.232.196.0/24")){return 143;}
		if($this->ip_in_range($ipaddr, "109.232.197.0/24")){return 143;}
		if($this->ip_in_range($ipaddr, "109.232.198.0/24")){return 143;}
		if($this->ip_in_range($ipaddr, "109.232.199.0/24")){return 143;}

		//alibaba
        if($this->ip_in_range($ipaddr, "47.254.0.0/16")){return 83;} //Alibaba
        if($this->ip_in_range($ipaddr, "8.211.0.0/16")){return 83;} //Alibaba
		
		// Leaseweb

		
		
		
		for($i=128;$i<192;$i++){if($this->ip_in_range($ipaddr, "209.58.$i.0/24")){return 83;}}
		if($this->ip_in_range($ipaddr, "212.98.178.0/24")){return 83;} //Belarus - Business Network Ltd
		



		
		
		//LImeLight
		for($i=200;$i<256;$i++){if($this->ip_in_range($ipaddr, "178.79.$i.0/24")){return 83;}}
		
		
		//Myrepublic
		for($i=128;$i<256;$i++){if($this->ip_in_range($ipaddr, "158.140.$i.0/24")){return 83;}}
		
		//1and1
		if($this->ip_in_range($ipaddr, "212.227.0.0/16")){return 83;} // 1and1
		if($this->ip_in_range($ipaddr, "217.160.0.0/16")){return 83;} // 1and1
		if($this->ip_in_range($ipaddr, "82.223.0.0/16")){return 83;} // 1and1
		if($this->ip_in_range($ipaddr, "195.20.250.0/24")){return 83;} 
		if($this->ip_in_range($ipaddr, "213.165.79.0/24")){return 83;} 
		if($this->ip_in_range($ipaddr, "162.222.200.0/21")){return 83;} // 1and1
		if($this->ip_in_range($ipaddr, "162.252.156.0/22")){return 83;} // 1and1
		if($this->ip_in_range($ipaddr, "162.255.84.0/22")){return 83;} // 1and1
		if($this->ip_in_range($ipaddr, "185.132.36.0/22")){return 83;} // 1and1
		if($this->ip_in_range($ipaddr, "185.132.40.0/22")){return 83;} // 1and1
		if($this->ip_in_range($ipaddr, "185.235.232.0/22")){return 83;} // 1and1
		if($this->ip_in_range($ipaddr, "192.195.77.0/24")){return 83;} // 1and1
		
		

		
		if($this->ip_in_range($ipaddr, "74.201.0.0/16")){return 83;} // Internap Network Services Corporation
		if($this->ip_in_range($ipaddr, "66.151.25.0/24")){return 83;} // Internap Network Services Corporation
		
		if($this->ip_in_range($ipaddr, "98.158.97.0/24")){return 83;} //IPTP LTD
		if($this->ip_in_range($ipaddr, "85.25.83.0/24")){return 83;} //PlusServer GmbH
		if($this->ip_in_range($ipaddr, "89.30.109.0/24")){return 83;} //BSO Network Solutions SAS
		if($this->ip_in_range($ipaddr, "89.30.122.0/24")){return 83;} //BSO Network Solutions SAS



		for($i=0;$i<128;$i++){if($this->ip_in_range($ipaddr, "45.33.$i.0/24")){return 233;}} //Linode, LLC
		

		
		
		if($this->ip_in_range($ipaddr, "213.190.91.0/24")){return 83;} //SAEM Intermediasud
		if($this->ip_in_range($ipaddr, "69.167.0.0/24")){return 83;} //Paradise Networks LLC
		if($this->ip_in_range($ipaddr, "109.232.0.0/24")){return 83;} //Iran - Imen Sanat Novin Alighapou
		
		if($this->ip_in_range($ipaddr, "38.88.0.0/16")){return 83;} //Cogent Communications
		if($this->ip_in_range($ipaddr, "130.117.190.0/24")){return 83;} //Cogent Communications
		
		
		if($this->ip_in_range($ipaddr, "185.216.26.0/24")){return 83;} //Netrix SAS

        if($this->ip_in_range($ipaddr, "185.134.196.0/24")){return 83;} //Faelix NET
		




		
		//Vodaphone



		//Qwest
		if($this->ip_in_range($ipaddr, "65.153.0.0/16")){return 83;}
		
		if($this->ip_in_range($ipaddr, "80.74.79.0/24")){return 83;} //Oceanet Technology SAS
		if($this->ip_in_range($ipaddr, "173.248.191.0/24")){return 83;} //Handy Networks, LLC

        if($this->ip_in_range($ipaddr, "95.143.68.0/24")){return 83;}   //Jaguar Network
        if($this->ip_in_range($ipaddr, "37.26.0.0/16")){return 83;}     //Jaguar Network
		
		if($this->ip_in_range($ipaddr, "45.60.0.0/16")){return 83;} //Incapsula Inc
		if($this->ip_in_range($ipaddr, "192.230.70.0/24")){return 83;} //Incapsula Inc
		if($this->ip_in_range($ipaddr, "107.154.115.0/24")){return 83;} //Incapsula Inc
		if($this->ip_in_range($ipaddr, "185.11.125.0/24")){return 83;} //Incapsula Inc
		
		if($this->ip_in_range($ipaddr, "62.97.140.0/24")){return 83;} //PROSODIE IBERICA SL
		
		if($this->ip_in_range($ipaddr, "64.135.100.0/24")){return 83;} //BroadbandONE, Inc.
		
		if($this->ip_in_range($ipaddr, "63.251.28.0/24")){return 83;} //Freewheel Media Inc.
		if($this->ip_in_range($ipaddr, "75.98.70.0/24")){return 83;} // Freewheel Media Inc.
		
		if($this->ip_in_range($ipaddr, "85.236.157.0/24")){return 83;} //PlanetHoster
		if($this->ip_in_range($ipaddr, "85.236.158.0/24")){return 83;} //PlanetHoster
		if($this->ip_in_range($ipaddr, "146.88.233.0/24")){return 83;} //PlanetHoster
		if($this->ip_in_range($ipaddr, "146.88.234.0/24")){return 83;} //PlanetHoster
		if($this->ip_in_range($ipaddr, "185.22.110.0/24")){return 83;} //PlanetHoster



        if($this->ip_in_range($ipaddr, "46.148.125.0/24")){return 83;} //LLHost Inc
        if($this->ip_in_range($ipaddr, "46.148.123.0/24")){return 83;} //LLHost Inc
        if($this->ip_in_range($ipaddr, "46.148.126.0/24")){return 83;} //LLHost Inc
        if($this->ip_in_range($ipaddr, "5.188.201.0/24")){return 83;} //LLHost Inc
        if($this->ip_in_range($ipaddr, "5.188.202.0/24")){return 83;} //LLHost Inc
        if($this->ip_in_range($ipaddr, "5.189.255.0/24")){return 83;} //LLHost Inc
        if($this->ip_in_range($ipaddr, "91.243.40.0/24")){return 83;} //LLHost Inc
        if($this->ip_in_range($ipaddr, "91.243.43.0/24")){return 83;} //LLHost Inc
		
		if($this->ip_in_range($ipaddr, "78.153.242.0/24")){return 83;} // Jaguar Network SAS
		if($this->ip_in_range($ipaddr, "82.98.110.0/24")){return 83;} // Plus.line AG
		if($this->ip_in_range($ipaddr, "190.107.177.0/24")){return 83;} //Gtd Internet S.A.
		if($this->ip_in_range($ipaddr, "69.43.132.0/24")){return 83;} //Castle Access Inc
		
		if($this->ip_in_range($ipaddr, "212.95.70.0/24")){return 83;} // SdV-Plurimedia
		if($this->ip_in_range($ipaddr, "212.95.71.0/24")){return 83;} // SdV-Plurimedia
		if($this->ip_in_range($ipaddr, "212.95.72.0/24")){return 83;} // SdV-Plurimedia
		if($this->ip_in_range($ipaddr, "212.95.73.0/24")){return 83;} // SdV-Plurimedia
		if($this->ip_in_range($ipaddr, "212.95.74.0/24")){return 83;} // SdV-Plurimedia
		
		if($this->ip_in_range($ipaddr, "62.240.232.0/24")){return 83;} // ClaraNET LTD
		if($this->ip_in_range($ipaddr, "89.185.35.0/24")){return 83;} // ClaraNET LTD
		if($this->ip_in_range($ipaddr, "89.185.37.0/24")){return 83;} // ClaraNET LTD
		if($this->ip_in_range($ipaddr, "89.185.38.0/24")){return 83;} // ClaraNET LTD
		if($this->ip_in_range($ipaddr, "89.185.39.0/24")){return 83;} // ClaraNET LTD
		if($this->ip_in_range($ipaddr, "89.185.40.0/24")){return 83;} // ClaraNET LTD
		if($this->ip_in_range($ipaddr, "89.185.41.0/24")){return 83;} // ClaraNET LTD
		if($this->ip_in_range($ipaddr, "78.109.84.0/24")){return 83;} // ClaraNET LTD
		if($this->ip_in_range($ipaddr, "78.109.93.0/24")){return 83;} // ClaraNET LTD
		
		if($this->ip_in_range($ipaddr, "185.7.38.0/24")){return 83;} // ClaraNET LTD
		
		
		if($this->ip_in_range($ipaddr, "62.129.173.0/24")){return 83;} //SFR
        if($this->ip_in_range($ipaddr, "37.71.0.0/16")){return 83;}    //SFR

		
		if($this->ip_in_range($ipaddr, "88.191.0.0/16")){return 83;} // Free SAS
		

		
		if($this->ip_in_range($ipaddr, "31.15.27.0/24")){return 83;} //Alter Way
		if($this->ip_in_range($ipaddr, "31.15.28.0/24")){return 83;} //Alter Way
		if($this->ip_in_range($ipaddr, "31.15.29.0/24")){return 83;} //Alter Way
		if($this->ip_in_range($ipaddr, "31.15.30.0/24")){return 83;} //Alter Way
		if($this->ip_in_range($ipaddr, "31.15.31.0/24")){return 83;} //Alter Way
		
		for($i=80;$i<96;$i++){ if($this->ip_in_range($ipaddr, "80.93.$i.0/24")){return 83;} }//Ikoula Net SAS
		if($this->ip_in_range($ipaddr, "178.170.113.0/24")){return 83;} //Ikoula Net SAS
		if($this->ip_in_range($ipaddr, "178.170.61.0/24")){return 83;} //Ikoula Net SAS
		if($this->ip_in_range($ipaddr, "91.236.153.0/24")){return 83;} //DIRECT INFO SERVICE SARL
		
		
		for($i=0;$i<192;$i++){ if($this->ip_in_range($ipaddr, "95.163.$i.0/24")){return 83;} }//JSC Digital Network
		if($this->ip_in_range($ipaddr, "213.159.210.0/24")){return 83;} //JSC \"ISPsystem"
		
		if($this->ip_in_range($ipaddr, "178.132.201.0/24")){return 83;} // OOO "Network of data-centers "Selectel"
		if($this->ip_in_range($ipaddr, "95.213.236.0/24")){return 83;}  // OOO "Network of data-centers "Selectel"
		
		if($this->ip_in_range($ipaddr, "85.114.159.0/24")){return 83;} // Germany - myLoc managed IT AG
		
		if($this->ip_in_range($ipaddr, "185.31.40.0/24")){return 83;} // ALWAYSDATA SARL
		
		if($this->ip_in_range($ipaddr, "185.104.45.0/24")){return 83;} //InHosted
		if($this->ip_in_range($ipaddr, "195.60.188.0/24")){return 83;} //Oxeva
		if($this->ip_in_range($ipaddr, "185.2.54.0/24")){return 83;} //Nucleus NV
		if($this->ip_in_range($ipaddr, "77.95.65.0/24")){return 83;} //Rezopole
		if($this->ip_in_range($ipaddr, "81.18.18.0/24")){return 83;} //Quickline AG
		if($this->ip_in_range($ipaddr, "157.166.238.0/24")){return 83;} // Turner Broadcasting
		if($this->ip_in_range($ipaddr, "209.141.51.0/24")){return 83;} // FranTech Solutions
		
		
		if($this->ip_in_range($ipaddr, "128.65.195.0/24")){return 83;} // Infomaniak Network SA
		if($this->ip_in_range($ipaddr, "93.88.243.0/24")){return 83;} // Infomaniak Network SA



        if($this->ip_in_range($ipaddr, "95.179.152.0/24")){return 83;} // Choopa LCC
		
		if($this->ip_in_range($ipaddr, "195.68.0.0/16")){return 83;}  // Colt Telecom
		if($this->ip_in_range($ipaddr, "84.14.0.0/16")){return 83;}   // Colt Telecom
		if($this->ip_in_range($ipaddr, "213.41.75.0/24")){return 83;} // Colt Telecom
        if($this->ip_in_range($ipaddr, "176.116.118.0/24")){return 83;} // Colt Telecom
		
		if($this->ip_in_range($ipaddr, "162.220.223.0/24")){return 83;} //ANEXIA Internetdienstleistungs GmbH
		if($this->ip_in_range($ipaddr, "80.92.65.0/24")){return 83;} //Datacenter Luxembourg S.A.
		
		if($this->ip_in_range($ipaddr, "45.76.0.0/16")){return 83;} // Choopa
		if($this->ip_in_range($ipaddr, "199.247.20.0/24")){return 83;} // Choopa
		if($this->ip_in_range($ipaddr, "103.43.72.0/22")){return 83;} // Choopa
		if($this->ip_in_range($ipaddr, "104.207.131.0/24")){return 83;} //Choopa, LLC
		if($this->ip_in_range($ipaddr, "104.156.240.0/21")){return 83;} //Choopa, LLC
        if($this->ip_in_range($ipaddr, "136.244.97.0/24")){return 83;} //Choopa, LLC
		
		if($this->ip_in_range($ipaddr, "213.230.210.0/24")){return 83;} //Netcalibre Ltd
		if($this->ip_in_range($ipaddr, "198.252.98.0/24")){return 83;} // TierPoint, LLC

        if($this->ip_in_range($ipaddr, "185.233.106.0/24")){return 83;} // netcup GMBH
        if($this->ip_in_range($ipaddr, "185.233.107.0/24")){return 83;} // netcup GMBH



		
		
		if($this->ip_in_range($ipaddr, "77.87.106.0/24")){return 83;} // "PROCEAU" s.a.r.l.
		if($this->ip_in_range($ipaddr, "77.104.160.0/24")){return 83;} // SingleHop LLC
		if($this->ip_in_range($ipaddr, "198.143.164.0/24")){return 83;} // SingleHop LLC
		if($this->ip_in_range($ipaddr, "205.234.175.0/24")){return 83;} // CacheNetworks, Inc.
		if($this->ip_in_range($ipaddr, "185.99.9.0/24")){return 83;} // Dataline Ltd
		if($this->ip_in_range($ipaddr, "213.196.34.0/24")){return 83;} // Servers.com, Inc.
		if($this->ip_in_range($ipaddr, "72.52.91.0/24")){return 83;} // Hurricane Electric LLC
		if($this->ip_in_range($ipaddr, "217.16.2.0/24")){return 83;} // HOSTEUR SARL
		if($this->ip_in_range($ipaddr, "104.207.237.0/24")){return 83;} // VPS Datacenter, LLC
		if($this->ip_in_range($ipaddr, "164.138.209.0/24")){return 83;} // Cyberneticos Hosting SL
		if($this->ip_in_range($ipaddr, "173.248.157.0/24")){return 83;} // Handy Networks, LLC
		if($this->ip_in_range($ipaddr, "185.172.149.0/24")){return 83;} // proinity GmbH
		if($this->ip_in_range($ipaddr, "195.20.15.0/24")){return 83;}  // "PROCEAU" s.a.r.l.
		if($this->ip_in_range($ipaddr, "191.96.165.0/24")){return 83;}  // ColoCrossing
		if($this->ip_in_range($ipaddr, "195.81.225.0/24")){return 83;}  // Interoute Communications Limited
		if($this->ip_in_range($ipaddr, "217.146.26.0/24")){return 83;}  // ANEXIA Internetdienstleistungs GmbH
		if($this->ip_in_range($ipaddr, "50.31.185.0/24")){return 83;}  // Server Central Network
		if($this->ip_in_range($ipaddr, "212.99.82.0/24")){return 83;}  // COMPLETEL SAS
		if($this->ip_in_range($ipaddr, "207.189.124.0/24")){return 83;}// ViaWest
		
		
		if($this->ip_in_range($ipaddr, "192.69.222.0/24")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "100.42.220.0/22")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "100.42.216.0/22")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "103.211.1.0/24")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "104.237.48.0/23")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "104.237.54.0/24")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "104.237.55.0/24")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "104.237.56.0/21")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "107.182.160.0/24")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "107.182.161.0/24")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "107.182.162.0/23")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "107.182.164.0/22")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "107.182.168.0/21")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "108.171.192.0/22")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "108.171.196.0/23")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "108.171.198.0/23")){return 83;} // WebNX
		if($this->ip_in_range($ipaddr, "108.171.200.0/23")){return 83;} // WebNX




		if($this->ip_in_range($ipaddr, "192.185.153.0/24")){return 83;}  //CyrusOne LLC
		if($this->ip_in_range($ipaddr, "192.185.0.0/24")){return 83;}  //CyrusOne LLC
		
		if($this->ip_in_range($ipaddr, "72.172.95.0/24")){return 83;}  //NV Next LLC
		if($this->ip_in_range($ipaddr, "69.89.69.0/24")){return 83;}  // NV Next LLC
		
		if($this->ip_in_range($ipaddr, "216.18.168.0/24")){return 83;}  // Reflected Networks, Inc.
		if($this->ip_in_range($ipaddr, "67.22.48.0/24")){return 83;}    // Reflected Networks, Inc.
		
		
		if($this->ip_in_range($ipaddr, "194.73.0.0/16")){return 83;} //  British Telecommunications PLC
		if($this->ip_in_range($ipaddr, "194.74.0.0/16")){return 83;} //  British Telecommunications PLC
		if($this->ip_in_range($ipaddr, "194.75.0.0/16")){return 83;} //  British Telecommunications PLC
		
		
		if($this->ip_in_range($ipaddr, "217.70.180.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.181.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.182.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.183.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.184.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.185.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.186.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.187.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.188.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.189.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "217.70.190.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "95.142.164.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "155.133.131.0/24")){return 83;} //Gandi
		if($this->ip_in_range($ipaddr, "92.243.28.0/24")){return 83;} //Gandi
		
		if($this->ip_in_range($ipaddr, "47.89.0.0/16")){return 83;} //Alibaba.com LLC
		
		if($this->ip_in_range($ipaddr, "173.239.46.0/24")){return 83;} //  Webair Internet Development Company Inc.
		if($this->ip_in_range($ipaddr, "173.239.53.0/24")){return 83;} //  Webair Internet Development Company Inc.

        //Highwinds Network Group, Inc.
		for($i=195;$i<224;$i++){ if($this->ip_in_range($ipaddr, "205.185.$i.0/24")){return 83;} }
        if($this->ip_in_range($ipaddr, "209.197.3.0/24")){return 83;}



		if($this->ip_in_range($ipaddr, "213.44.0.0/16")){return 83;} // Bouygues telecom
		for($i=96;$i<128;$i++){ if($this->ip_in_range($ipaddr, "194.158.$i.0/24")){return 83;}	}

		
		if($this->ip_in_range($ipaddr, "82.199.68.0/24")){return 83;} // TELECITYGROUP INTERNATIONAL
		if($this->ip_in_range($ipaddr, "81.18.187.0/24")){return 83;} // LASOTEL SAS

		for($i=128;$i<192;$i++){ if($this->ip_in_range($ipaddr, "69.167.$i.0/24")){return 83;}	} // LIQUID WEB
	
		// Internap
		if($this->ip_in_range($ipaddr, "74.217.0.0/16")){return 83;}
		
		if($this->ip_in_range($ipaddr, "160.92.0.0/16")){return 83;} // Worldline SA
		
		
		//CDNetworks Inc.
		if($this->ip_in_range($ipaddr, "148.253.233.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.224.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.225.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.226.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.227.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.228.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.229.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.230.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.231.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.232.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.233.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.234.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.235.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.236.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.237.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.238.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.239.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.240.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.241.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.242.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.243.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.244.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.245.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.246.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.247.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.248.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.249.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.250.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.251.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.252.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.253.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.254.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "148.253.255.0/24")){return 83;}
		
		
		//sendgrid.net / 91 /mailing
		for($i=0;$i<128;$i++){if($this->ip_in_range($ipaddr, "167.89.$i.0/24")){return 91;}}
		
		if($this->ip_in_range($ipaddr, "78.109.92.0/24")){return 83;} // - United Kingdom - Claranet France

        for ($i = 0; $i < 24; $i++) {
            if ($this->ip_in_range($ipaddr, "44.$i.0.0/16")) {
                return 115;
            } // University of California, San Diego
        }

        if(preg_match("#^140\.211\.#",$ipaddr)){
            // Schools  Network for Education and Research in Oregon
            for ($i = 0; $i < 200; $i++) {
                if ($this->ip_in_range($ipaddr, "140.211.0.0/16")) {
                    return 115;
                }
            }
            for ($i = 200; $i < 256; $i++) {
                if ($this->ip_in_range($ipaddr, "140.211.0.0/16")) {
                    return 19;
                }
            }
        }

        // Freie Netze Muenchen e.V.
        if($this->ip_in_range($ipaddr, "5.1.66.0/24")){return 83;}




        if($this->ip_in_range($ipaddr, "45.63.110.0/24")){return 83;} //  The Constant Company, LLC

    	//WikiFu
        if($this->ip_in_range($ipaddr, "116.0.2.0/24")){return 83;}

        //MVPS LTD
        if($this->ip_in_range($ipaddr, "45.153.187.0/24")){return 83;}

        // CommunityRack.org
        if($this->ip_in_range($ipaddr, "185.95.218.0/24")){return 83;}

        // M247 Ltd
        if($this->ip_in_range($ipaddr, "89.44.9.0/24")){return 83;}
        if($this->ip_in_range($ipaddr, "194.242.2.0/24")){return 83;}

        //ab stract
        if($this->ip_in_range($ipaddr, "95.215.19.0/24")){return 83;}
		
		//RackSpace
		if($this->ip_in_range($ipaddr, "74.205.41.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "94.236.6.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "134.213.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "159.135.138.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "104.130.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "162.13.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "162.242.140.0/24")){return 83;}
		if($this->ip_in_range($ipaddr, "162.209.114.0/24")){return 83;}
		
		for($i=127;$i<256;$i++){if($this->ip_in_range($ipaddr, "198.61.$i.0/24")){return 83;}}
		
		//Godaddy.com
		if($this->ip_in_range($ipaddr, "160.153.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "184.168.0.0/16")){return 83;} //GoDaddy.com, LLC
		
		
		//SFR
		if($this->ip_in_range($ipaddr, "109.2.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "109.3.128.0/18")){return 83;}
		if($this->ip_in_range($ipaddr, "109.3.192.0/20")){return 83;}
		
		//Thales
		if($this->ip_in_range($ipaddr, "37.35.6.0/24")){return 83;}
		
		if($this->ip_in_range($ipaddr, "192.54.200.0/24")){return 83;}
		
		//Realestate
		if($this->ip_in_range($ipaddr, "217.151.0.0/24")){return 54;}
		if($this->ip_in_range($ipaddr, "217.151.1.0/24")){return 54;}

		//Take-two Interactive Software - games

        if($this->ip_in_range($ipaddr, "192.81.240/24")){return 58;}
        if($this->ip_in_range($ipaddr, "192.81.241/24")){return 58;}
        if($this->ip_in_range($ipaddr, "192.81.242/24")){return 58;}
        if($this->ip_in_range($ipaddr, "192.81.243/24")){return 58;}
        if($this->ip_in_range($ipaddr, "192.81.244/24")){return 58;}
        if($this->ip_in_range($ipaddr, "192.81.245/24")){return 58;}
        if($this->ip_in_range($ipaddr, "192.81.246/24")){return 58;}
        if($this->ip_in_range($ipaddr, "192.81.247/24")){return 58;}
        if($this->ip_in_range($ipaddr, "104.255.104.0/22")){return 58;}
        if($this->ip_in_range($ipaddr, "104.255.104.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "104.255.105.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "104.255.106.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "104.255.107.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "198.133.210.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.224.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.225.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.226.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.227.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.228.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.229.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.231.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.232.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.233.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.234.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.235.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.236.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.237.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.238.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "139.138.239.0/24")){return 58;}




        // Facebook



		
		//games - Roblox
		if($this->ip_in_range($ipaddr, "8.42.96.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "209.206.41.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.0.0/17")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.0.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.1.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.11.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.2.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.3.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.4.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.5.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.6.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.7.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.8.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "128.116.9.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "141.193.3.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "205.201.62.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "209.206.40.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "209.206.42.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "209.206.43.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "209.206.44.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "209.206.45.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "209.206.46.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "209.206.47.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "216.200.230.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "64.125.149.0/24")){return 58;}
		if($this->ip_in_range($ipaddr, "8.42.100.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.100.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.101.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.102.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.104.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.105.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.11.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.112.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.113.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.114.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.115.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.116.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.117.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.118.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.119.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.120.0/24")){return 58;}
        if($this->ip_in_range($ipaddr, "128.116.121.0/24")){return 58;}






		
		
		
		if($this->ip_in_range($ipaddr, "217.18.19.0/24")){return 58;} // Sony Intercative
		
		if($this->ip_in_range($ipaddr, "208.78.164.0/24")){return 58;} //Valve Corporation
		if($this->ip_in_range($ipaddr, "146.66.152.0/24")){return 58;} //Valve Corporation
		if($this->ip_in_range($ipaddr, "162.254.195.0/24")){return 58;} //Valve Corporation
		
		if($this->ip_in_range($ipaddr, "155.133.229.0/24")){return 58;} //Valve Corporation
		if($this->ip_in_range($ipaddr, "155.133.254.0/24")){return 58;} //Valve Corporation
		
		if($this->ip_in_range($ipaddr, "162.254.193.0/24")){return 58;} //Valve Corporation
		if($this->ip_in_range($ipaddr, "185.25.180.0/24")){return 58;} //Valve Corporation
        if($this->ip_in_range($ipaddr, "185.25.181.0/24")){return 58;} //Valve Corporation
        if($this->ip_in_range($ipaddr, "185.25.182.0/24")){return 58;} //Valve Corporation
        if($this->ip_in_range($ipaddr, "185.25.183.0/24")){return 58;} //Valve Corporation


		
		if($this->ip_in_range($ipaddr, "161.170.0.0/16")){return 8;} // Wal-Mart Stores Inc. shopping
		if($this->ip_in_range($ipaddr, "47.252.0.0/16")){return 8;} // Alibaba shopping
		if($this->ip_in_range($ipaddr, "50.115.217.0/24")){return 8;} //Groupon, Inc.
		
		
		//DailyMontion Audio/Video = 15
		if($this->ip_in_range($ipaddr, "188.65.120.0/24")){return 15;}
		if($this->ip_in_range($ipaddr, "188.65.121.0/24")){return 15;}
		if($this->ip_in_range($ipaddr, "188.65.122.0/24")){return 15;}
		if($this->ip_in_range($ipaddr, "188.65.123.0/24")){return 15;}
		if($this->ip_in_range($ipaddr, "188.65.124.0/24")){return 15;}
		if($this->ip_in_range($ipaddr, "188.65.125.0/24")){return 15;}
		if($this->ip_in_range($ipaddr, "188.65.126.0/24")){return 15;}
		if($this->ip_in_range($ipaddr, "188.65.127.0/24")){return 15;}
		
		if($this->ip_in_range($ipaddr, "192.108.239.0/24")){return 15;} //Twitch Interactive Inc.
		if($this->ip_in_range($ipaddr, "23.160.0.0/24")){return 15;} //Twitch Interactive Inc.
		
		if($this->ip_in_range($ipaddr, "91.209.25.0/24")){return 51;} //CTRE REG Assurancdes
		
		//Societe generale -> 50
		for($i=0;$i<128;$i++){if($this->ip_in_range($ipaddr, "194.119.$i.0/24")){return 50;}}
		for($i=16;$i<24;$i++){if($this->ip_in_range($ipaddr, "5.57.$i.0/24")){return 119;}}
		
		//PPR SA -> 8 Shopping
		if($this->ip_in_range($ipaddr, "193.104.231.0/24")){return 8;}
		
		if($this->ip_in_range($ipaddr, "178.251.204.0/24")){return 8;} // Rue du Commerce
		if($this->ip_in_range($ipaddr, "178.251.205.0/24")){return 8;} // Rue du Commerce
		if($this->ip_in_range($ipaddr, "178.251.206.0/24")){return 8;} // Rue du Commerce
		if($this->ip_in_range($ipaddr, "178.251.207.0/24")){return 8;} // Rue du Commerce
		
		//CD Discount
		if($this->ip_in_range($ipaddr, "62.122.9.0/24")){return 8;}
		if($this->ip_in_range($ipaddr, "62.122.15.0/24")){return 8;}
		
		//Airfrance - Travel - 119
		if($this->ip_in_range($ipaddr, "193.57.247.0/24")){return 119;}
		if($this->ip_in_range($ipaddr, "193.57.244.0/24")){return 119;}

		// Web radio 155
        if($this->ip_in_range($ipaddr, "31.12.71.0/24")){return 155;} //  Radionomy SA
		
		//Booking.com
		if($this->ip_in_range($ipaddr, "185.28.220.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "185.28.221.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "185.28.222.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "185.28.223.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.0.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.1.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.32.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.36.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.37.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.38.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.56.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.57.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.58.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.60.0/24")){return 119;} //Booking.com BV
		if($this->ip_in_range($ipaddr, "37.10.63.0/24")){return 119;} //Booking.com BV
		
		
		//WebApps = 151
		
		if($this->ip_in_range($ipaddr, "185.166.140.0/24")){return 151;} //Atlassian Network Services
		if($this->ip_in_range($ipaddr, "103.233.242.0/24")){return 151;} //Atlassian Network Services
		if($this->ip_in_range($ipaddr, "104.192.136.0/23")){return 151;} //Atlassian Network Services
		if($this->ip_in_range($ipaddr, "104.192.138.0/24")){return 151;} //Atlassian Network Services
		if($this->ip_in_range($ipaddr, "104.192.139.0/24")){return 151;} //Atlassian Network Services
		if($this->ip_in_range($ipaddr, "104.192.140.0/23")){return 151;} //Atlassian Network Services
		if($this->ip_in_range($ipaddr, "104.192.142.0/24")){return 151;} //Atlassian Network Services
		if($this->ip_in_range($ipaddr, "104.192.143.0/24")){return 151;} //Atlassian Network Services
		
		
		//Cars 27
        for($i=31;$i<64;$i++){
            if($this->ip_in_range($ipaddr, "84.21.$i.0/24")){return 24;} // Porsche
        }

		// Daily montion
		if($this->ip_in_range($ipaddr, "188.65.124.0/24")){return 15;}
		
		//Netflix
		if($this->ip_in_range($ipaddr, "23.246.2.0/24")){return 100;}
		
		//Suiss gov - 62
		if($this->ip_in_range($ipaddr, "162.23.128.0/24")){return 62;}
		if($this->ip_in_range($ipaddr, "131.102.0.0/16")){return 62;}
		if($this->ip_in_range($ipaddr, "91.198.125.0/24")){return 62;} //CNFPT
		

		 //ADIFY CORPORATION -> adversting


        if($this->ip_in_range($ipaddr, "91.228.74.0/24")){return 5;} // Quantcast Corporation
		


		if($this->ip_in_range($ipaddr, "192.48.236.0/24")){return 5;} // United States - MoPub, Inc. twiter ads
		if($this->ip_in_range($ipaddr, "31.24.80.0/24")){return 5;} // Vibrant Media Ltd
		if($this->ip_in_range($ipaddr, "46.228.164.0/24")){return 5;} // Turn Europe (UK) Ltd.
		if($this->ip_in_range($ipaddr, "159.127.187.0/24")){return 5;} // Epsilon Intercative
		
		if($this->ip_in_range($ipaddr, "178.249.101.0/24")){return 5;} // LivePerson
		
		
		if($this->ip_in_range($ipaddr, "104.193.83.0/24")){return 5;} // IgnitionOne, Inc.
		

		
		
		if($this->ip_in_range($ipaddr, "185.64.189.0/24")){return 5;} // PubMatic, Inc.
		if($this->ip_in_range($ipaddr, "198.47.127.0/24")){return 5;} // PubMatic, Inc.
		
		if($this->ip_in_range($ipaddr, "64.158.223.0/24")){return 5;} // ValueClick, Inc.
		if($this->ip_in_range($ipaddr, "37.157.4.0/24")){return 5;} // Adform A/S
		if($this->ip_in_range($ipaddr, "173.241.240.0/24")){return 5;} // OPENX TECHNOLOGIES, INC.
		
		
		if($this->ip_in_range($ipaddr, "185.148.48.0/24")){return 5;} // TubeMogul, Inc.
		if($this->ip_in_range($ipaddr, "213.19.147.0/24")){return 5;} // RhythmOne, LLC
		
		if($this->ip_in_range($ipaddr, "185.31.128.0/24")){return 5;} // Rocket Fuel Inc.
		if($this->ip_in_range($ipaddr, "193.0.160.0/24")){return 5;} // Rocket Fuel Inc.
		
		if($this->ip_in_range($ipaddr, "37.157.2.0/24")){return 5;} //  Adform A/S
		if($this->ip_in_range($ipaddr, "37.157.6.0/24")){return 5;} //  Adform A/S
		
		
		// AppNexus -> adversting
		if($this->ip_in_range($ipaddr, "37.252.161.0/24")){return 5;}
		if($this->ip_in_range($ipaddr, "37.252.172.0/24")){return 5;}
		if($this->ip_in_range($ipaddr, "185.33.223.0/24")){return 5;}
		if($this->ip_in_range($ipaddr, "213.19.162.0/24")){return 5;} 		//Rubicon project Advertising
		if($this->ip_in_range($ipaddr, "62.67.193.0/24")){return 5;} 		//Rubicon project Advertising
		if($this->ip_in_range($ipaddr, "64.95.202.0/24")){return 5;} 		//Quantcast Corporation Advertising
		if($this->ip_in_range($ipaddr, "199.166.0.0/24")){return 5;} 		//Integral Ad Science, Inc.
		



        if($this->ip_in_range($ipaddr, "185.228.168.0/24")){return 36;} 	//Cleaning Daniel CID


        // 153 - Web phone



        // Industry 81
        if($this->ip_in_range($ipaddr, "195.248.251.0/24")){return 81;} 	//CCM Benchmark

		
		//MAPPY -> maps
		if($this->ip_in_range($ipaddr, "193.203.32.0/24")){return 94;}
		if($this->ip_in_range($ipaddr, "193.203.34.0/24")){return 94;}
		
		//Socialnet -> 134
		if($this->ip_in_range($ipaddr, "185.63.145.0/24")){return 134;} //Ireland - LinkedIn Corporation
        if($this->ip_in_range($ipaddr, "103.136.221.0/24")){return 134;} //TIKTOK PTE. LTD
        if($this->ip_in_range($ipaddr, "103.136.220.0/24")){return 134;} //TIKTOK PTE. LTD
		

		
		
		
		//ebay -> 133 Smallads
		if($this->ip_in_range($ipaddr, "66.211.179.0/24")){return 133;}
		if($this->ip_in_range($ipaddr, "66.211.160.0/24")){return 133;}
		for($i=92;$i<225;$i++){if($this->ip_in_range($ipaddr, "66.135.$i.0/24")){return 133;}}
		
		//Edu -> 115 schools
		if($this->ip_in_range($ipaddr, "128.31.0.0/16")){return 115;} // Massachusetts Institute of Technology
		if($this->ip_in_range($ipaddr, "195.83.0.0/16")){return 115;} // Renater
        if($this->ip_in_range($ipaddr, "193.51.0.0/16")){return 115;} // Renater
		
		
		//Webex.com -> 123 remote-control




		if($this->ip_in_range($ipaddr, "194.187.168.0/24")){return 129;} //QWANT SAS, Searchengine

        if($this->ip_in_range($ipaddr, "209.126.36.0/24")){return 126;} // HAProxy Technologies
        if($this->ip_in_range($ipaddr, "103.10.4.0/24")){return 126;} // ASUSTek COMPUTER INC.

        if($this->ip_in_range($ipaddr, "192.146.154.0/24")){return 126;} // Nutanix
        if($this->ip_in_range($ipaddr, "192.146.155.0/24")){return 126;} // Nutanix
		
		if($this->ip_in_range($ipaddr, "13.50.0.0/16")){return 126;} // Xerox 126 -> Science Computing
		if($this->ip_in_range($ipaddr, "91.189.94.0/24")){return 126;} // Canonical Group Limited
		if($this->ip_in_range($ipaddr, "198.252.206.0/24")){return 126;} // Stack Exchange, Inc.
		if($this->ip_in_range($ipaddr, "15.0.0.0/16")){return 126;} // HP 126 -> Science Computing
		if($this->ip_in_range($ipaddr, "15.59.0.0/16")){return 126;} // HP 126 -> Science Computing
		if($this->ip_in_range($ipaddr, "15.73.0.0/16")){return 126;} // HP 126 -> Science Computing

        if($this->ip_in_range($ipaddr, "45.90.28.0/24")){return 126;}   //  NextDns-> Science Computing
        if($this->ip_in_range($ipaddr, "45.90.29.0/24")){return 126;}   //  NextDns-> Science Computing
        if($this->ip_in_range($ipaddr, "45.90.30.0/24")){return 126;}   //  NextDns-> Science Computing
        if($this->ip_in_range($ipaddr, "45.90.31.0/24")){return 126;}   //  NextDns-> Science Computing

        if($this->ip_in_range($ipaddr, "91.189.88.0/24")){return 126;} //  Canonical
        if($this->ip_in_range($ipaddr, "91.189.89.0/24")){return 126;} //  Canonical
        if($this->ip_in_range($ipaddr, "91.189.90.0/24")){return 126;} //  Canonical
        if($this->ip_in_range($ipaddr, "91.189.91.0/24")){return 126;} //  Canonical
        if($this->ip_in_range($ipaddr, "91.189.92.0/24")){return 126;} //  Canonical
        if($this->ip_in_range($ipaddr, "91.189.93.0/24")){return 126;} //  Canonical
        if($this->ip_in_range($ipaddr, "91.189.94.0/24")){return 126;} //  Canonical
        if($this->ip_in_range($ipaddr, "91.189.95.0/24")){return 126;} //  Canonical

        if($this->ip_in_range($ipaddr, "82.145.216.0/24")){return 126;} //  Opera Software -> Science Computing
		
		if($this->ip_in_range($ipaddr, "194.126.158.0/24")){return 126;} // Florian Kirstein
		
		if($this->ip_in_range($ipaddr, "204.93.89.0/24")){return 126;} // Jive Software Inc.
		if($this->ip_in_range($ipaddr, "209.167.231.0/24")){return 126;} // Oracle Corporation
		if($this->ip_in_range($ipaddr, "195.135.221.0/24")){return 126;} // SUSE LINUX GmbH
		

		
		//INtel
		if($this->ip_in_range($ipaddr, "192.198.164.0/24")){return 126;} //Intel Corporation
		
		if($this->ip_in_range($ipaddr, "213.162.55.0/24")){return 126;} //Smile S.A.
		if($this->ip_in_range($ipaddr, "91.235.134.0/24")){return 126;} //ThreatMetrix Inc.
		if($this->ip_in_range($ipaddr, "208.74.205.0/24")){return 126;} //Lithium Technologies, Inc.
		
		
		if($this->ip_in_range($ipaddr, "89.145.176.0/22")){return 126;} //Premaccess - ILD
		if($this->ip_in_range($ipaddr, "89.145.180.0/22")){return 126;} //Premaccess - ILD
		if($this->ip_in_range($ipaddr, "91.216.84.0/23")){return 126;} //Premaccess - ILD

		
		//Adobe

		for($i=16;$i<31;$i++){if($this->ip_in_range($ipaddr, "66.117.$i.0/24")){return 126;}}

		for($i=224;$i<256;$i++){if($this->ip_in_range($ipaddr, "192.243.$i.0/24")){return 126;}}



		//Nvidia
		if($this->ip_in_range($ipaddr, "8.36.112.0/24")){return 126;}
		
		//Dell
		if($this->ip_in_range($ipaddr, "143.166.0.0/16")){return 126;}
		
		if($this->ip_in_range($ipaddr, "195.248.250.0/24")){return 126;} //France - CCM Benchmark Group SAS
		
	
		
		//SalesForce --> 151

		
		// 101 - > Music
		if($this->ip_in_range($ipaddr, "212.124.124.0/24")){return 101;} //True Records Inc.
		

	}
	
	function is_google($ipaddr){
		// Google

        $this->WLOGS("is_google($ipaddr) ?");
        for($i=192;$i<256;$i++){if($this->ip_in_range($ipaddr, "72.14.$i.0/24")){return 17;} }
        for($i=0;$i<128;$i++){if($this->ip_in_range($ipaddr, "108.177.$i.0/24")){return 17;} }
        
		for($i=128;$i<256;$i++){if($this->ip_in_range($ipaddr, "209.85.$i.0/24")){return 17;}}

		

	}
	
	

	
	function is_in_yahoo($ipaddr){
		if($this->ip_in_range($ipaddr, "188.125.66.0/24")){return 21;}
		
		for($i=64;$i<96;$i++){if($this->ip_in_range($ipaddr, "188.128.$i.0/24")){return 21;}}
		for($i=96;$i<128;$i++){if($this->ip_in_range($ipaddr, "87.248.$i.0/24")){return 21;}}
		for($i=100;$i<128;$i++){if($this->ip_in_range($ipaddr, "212.82.$i.0/24")){return 21;}}
		for($i=160;$i<191;$i++){if($this->ip_in_range($ipaddr, "77.238.$i.0/24")){return 21;}}
		if($this->ip_in_range($ipaddr, "217.12.13.0/24")){return 21;}


	}
	
	function is_ovh_sas($ipaddr){



		for($i=33;$i<64;$i++){ if($this->ip_in_range($ipaddr, "213.186.$i.0/24")){return 229;}}
		for($i=128;$i<256;$i++){ if($this->ip_in_range($ipaddr, "87.98.$i.0/24")){return 229;}}
		for($i=0;$i<101;$i++){ if($this->ip_in_range($ipaddr, "5.39.$i.0/24")){return 229;}}
		for($i=128;$i<256;$i++){ if($this->ip_in_range($ipaddr, "198.50.$i.0/24")){return 229;}}
		for($i=0;$i<128;$i++){ if($this->ip_in_range($ipaddr, "193.70.$i.0/24")){return 229;}}

		if($this->ip_in_range($ipaddr, "51.38.0.0/16")){return 229;}
        for($i=1;$i<128;$i++){if($this->ip_in_range($ipaddr, "5.39.119.$i/24")){return 229;}}
		if($this->ip_in_range($ipaddr, "5.196.0.0/16")){return 229;}
		

	}

	function is_university($ipaddr){


    }
	
	function is_Hangzhou_Alibaba_Advertising($ipaddr){
		if($this->ip_in_range($ipaddr, "101.132.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "101.133.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "101.200.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "101.201.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "101.37.0.0/17")){return 83;}
		if($this->ip_in_range($ipaddr, "101.37.128.0/17")){return 83;}
		if($this->ip_in_range($ipaddr, "103.52.196.0/23")){return 83;}
		if($this->ip_in_range($ipaddr, "103.52.198.0/23")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.128.0/20")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.144.0/20")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.16.0/20")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.160.0/20")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.176.0/21")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.184.0/21")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.192.0/23")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.194.0/23")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.196.0/23")){return 83;}
		if($this->ip_in_range($ipaddr, "106.11.198.0/24")){return 83;}
		
		if($this->ip_in_range($ipaddr, "106.14.0.0/16")){return 83;}
		
		
		if($this->ip_in_range($ipaddr, "47.100.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "47.101.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "47.102.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "47.103.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "47.104.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "47.105.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "47.106.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "47.107.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "47.108.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "47.109.0.0/16")){return 83;}
		
		if($this->ip_in_range($ipaddr, "139.224.0.0/16")){return 83;}
		if($this->ip_in_range($ipaddr, "106.15.0.0/16")){return 83;}
		return 0;
	}
    private function ip_in_range( $ip, $range ) {
		if ( strpos( $range, '/' ) == false ) { $range .= '/32';}

		list( $range, $netmask ) = explode( '/', $range, 2 );
		$range_decimal = ip2long( $range );
		$ip_decimal = ip2long( $ip );
		$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
		$netmask_decimal = ~ $wildcard_decimal;
		return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
	}	
	
	private function CHECK_SQLITEDB():bool{
		if(isset($GLOBALS["CHECK_SQLITEDB"])){return true;}
		
		$q=new postgres_sql();

        if (!$q->TABLE_EXISTS("categories_caches")) {
            try {
                $q->CREATE_TABLES();
            }catch (Exception $e) {
                $this->mysql_error = $e->getMessage();
            }
        }
		$GLOBALS["CHECK_SQLITEDB"]=true;
        return true;
	}

	private function SAVE_NOT_CATEGORIZED($sitename){

	    if(!preg_match("#.+?\..+#",$sitename)){return;}
	    if(strpos($sitename,",")>0){return;}

        $ADD_NOT_CAT=true;
        $sitename=strtolower($sitename);
        if(strpos($sitename,".")==0){$ADD_NOT_CAT=false;}
        if(strpos($sitename,",")>0){$ADD_NOT_CAT=false;}
        if($ADD_NOT_CAT) {
            $libmemcache = new lib_memcached();
            $value = $libmemcache->getKey("notcategorized.$sitename");
            if (!$libmemcache->MemCachedFound) {
                $value = 0;
            }
            $value++;
            $libmemcache->saveKey("notcategorized.$sitename", $value);
        }


	}
	
	public function OnlyLocal(){
		$this->QueryOnlyLocal=true;
	}
	
	public function LogTosyslog(){
		$this->EnableSyslog=true;
	}
	
	public function SaveNotCategorizedToDatabase(){
		$this->SaveNotCategorized=True;
	}

    public function LoadFixedCategories(){
        $f=explode("\n",@file_get_contents(dirname(__FILE__) . "/databases/categories.org"));
        foreach ($f as $line){
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#(regex|fixed)\/(.+?)\/([0-9]+)$#",$line,$re)){
                if($re[1]=="regex"){
                    $this->fixedregex[$re[2]]=$re[3];
                    continue;
                }
                if($re[1]=="fixed"){
                    $this->fixedhosts[$re[2]]=$re[3];
                }

            }
        }

    }
	public function GET_CATEGORIES($sitename,$morespeed=false){
        $SelfDisableHardCoded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SelfDisableHardCoded"));
        if ($SelfDisableHardCoded == 0){
            if(count($this->fixedhosts)==0){$this->LoadFixedCategories();}
        }

        $this->CategoryName=null;
	    $this->CategoriesList=array();
        if(preg_match("#^(.+?):#i", $sitename,$re)){$sitename=$re[1];}
        $sitename=trim(strtolower($sitename));
        if($sitename==null){return 0;}

        if(preg_match("#^(.+?)(\?|\s+)(verbose|debug)#i", $sitename,$re)){
            $GLOBALS["VERBOSE"]=true;
            $sitename=$re[1];
        }
		$this->events=array();
		$this->prevents=$morespeed;
        $this->WLOGS(__LINE__."]: $sitename");
		$category=intval($this->ufdbcat($sitename));
		if($this->EnableSyslog){$this->Tosyslog("$sitename: $category");}
			
		if(!$this->Disabled){
			if($this->EnableSyslog){$this->Tosyslog("$sitename: SAVE_VISITED");}
			$this->SAVE_VISITED($sitename);
		}

			$FREE_TO_OFFICAL[167]=109; //porn
            $FREE_TO_OFFICAL[166]=5; //pub
            $FREE_TO_OFFICAL[170]=122; //religion
            $FREE_TO_OFFICAL[183]=42; //dating
            $FREE_TO_OFFICAL[187]=45; // frugs
            $FREE_TO_OFFICAL[189]=49; // filehosting
            $FREE_TO_OFFICAL[190]=55; // financial
            $FREE_TO_OFFICAL[191]=56; //forum
            $FREE_TO_OFFICAL[192]=57; // gamble
            $FREE_TO_OFFICAL[193]=58; // games
            $FREE_TO_OFFICAL[200]=92; // malwares
            $FREE_TO_OFFICAL[205]=105; // phishing
            $FREE_TO_OFFICAL[226]=149; // warez
			if($category>0){
                if(isset($FREE_TO_OFFICAL[$category])){$category=$FREE_TO_OFFICAL[$category];}
                $this->ok=true;
				if($this->EnableSyslog){$this->Tosyslog("$sitename: <$category>");}
				return $category;
			}
			
			if(!$this->Disabled){
				if($this->EnableSyslog){$this->Tosyslog("$sitename: SAVE_NOT_CATEGORIZED :".__LINE__);}
				$this->SAVE_NOT_CATEGORIZED($sitename);
				$this->SaveNotCategorized=false;
			}
			
			if($this->SaveNotCategorized){
				if($this->EnableSyslog){$this->Tosyslog("$sitename: SAVE_NOT_CATEGORIZED");}
				$this->SAVE_NOT_CATEGORIZED($sitename);
			}
			
			return 0;
	}
	private function events($text){

	    if(isset($GLOBALS["DEBUG_LEVEL"])){
	        if($GLOBALS["DEBUG_LEVEL"]==0){return;}
        }

		if($GLOBALS["VERBOSE"]){echo $text."\n";}
		if($this->WLOGExists==1){
			if(!function_exists("WLOG")){$this->WLOGExists=0;return;}
			$this->WLOGExists=3;
		}
		if($this->WLOGExists==3){
			WLOG("mysql_catz: $text",true);
		}
	}
	
	private function SAVE_VISITED($sitename){
		if(isset($GLOBALS["SAVE_VISITED"][$sitename])){return;}
		$q=new postgres_sql();
		$q->QUERY_SQL("INSERT INTO visits (domain) VALUES ('$sitename') ON CONFLICT DO NOTHING");
		$GLOBALS["SAVE_VISITED"][$sitename]=true;
	}
	
	
	private function WLOGS($event){
		$this->events[]=$event;
		if(isset($GLOBALS["VERBOSE"])){
			if(function_exists("VERBOSE")){VERBOSE($event, 0);}
		}
		
		if(isset($GLOBALS["DEBUG_LEVEL"])){if($GLOBALS["DEBUG_LEVEL"]>1){$GLOBALS["DEBUG"]=true;}}

		if($GLOBALS["DEBUG"]) {
            if (!isset($GLOBALS["SQUID_AS_PLUGIN"])) {
                echo "$event\n";
            }
        }
		if(!$GLOBALS["DEBUG"]){return;}
		
		if(!function_exists("WLOG")){return;}
		WLOG($event);
	}
	
	public function GetMemoryCache($sitename,$categorize=false){
		$fill=false;
		
		if(!isset($GLOBALS["CATZ"])){$GLOBALS["CATZ"]=array();}
		if(count($GLOBALS["CATZ"])>65000){unset($GLOBALS["CATZ"]);}
		if(isset($GLOBALS["CATZ"][$sitename])){
			if(strlen($GLOBALS["CATZ"][$sitename])>3){
				return $GLOBALS["CATZ"][$sitename];
			}
		}
		
		$orgsitename=$sitename;
		
		$ipClass=new IP();
		if($ipClass->isValid($sitename)){
			if($GLOBALS["VERBOSE"]){echo "->GetMemoryCache($sitename) ->  IPADDR<br>\n";}
			if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#", $sitename,$re)){
				$cdir="{$re[1]}-{$re[2]}-{$re[3]}.cdir";
				if($GLOBALS["VERBOSE"]){echo "->GetMemoryCache($sitename) ->  $cdir ?<br>\n";}
				$cat=trim($this->ufdbcat($cdir));
				if($cat<>null){
					$GLOBALS["CATZ"][$orgsitename]=$cat;
					return $cat;
				}
			}
			$sitename=ip2long($sitename).".addr";
		}		
		
		
		
		if($GLOBALS["VERBOSE"]){echo "GetMemoryCache() ->->ufdbcat($sitename,true)<br>\n";}
		$catz=$this->ufdbcat($sitename,true);
		if($catz==null){$catz="NULL";}
		
		
		$this->setevents("GET_CATEGORIES:: ufdbcat($sitename,true) return $catz");
		$GLOBALS["CATZ"][$sitename]=$catz;
		return $catz;
	}

	public function LIST_TABLES_CATEGORIES($nocache=false){
		if(!$nocache){
			
			if(isset($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"])){
				if(strlen($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"])>2){
					return unserialize($GLOBALS["LIST_TABLES_CATEGORIES_CATZ"]);
				}
			}
		}
		
		$f=$this->TransArray();
        foreach ($f as $table=>$none){
			if($this->TABLE_EXISTS($table)){
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: `$table` exists...\n";}
				$array[$table]=$table;
			}else{
				
				if($GLOBALS["VERBOSE"]){echo "GET_CATEGORIES: `$table` no such table...\n";}
			}
		}
		
		$array=array();
		
		foreach (glob("/home/artica/categories_databases/*.db") as $filename) {
			$file=basename($filename);
			$file=str_replace(".db", "", $file);
			$array[$file]=$file;
		
		}

		
		$GLOBALS["LIST_TABLES_CATEGORIES_CATZ"]=serialize($array);
		return $array;
		
	}	
	

	
	function COUNT_CATEGORIES(){
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return $GLOBALS[__CLASS__.__FUNCTION__];}
		$c=0;
		$array=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
		if(!is_array($array)){return 0;}
		unset($array["TIME"]);
        foreach ($array as $table=>$items){
			$items=intval($items);
			$c=$c+$items;
		}
		$GLOBALS[__CLASS__.__FUNCTION__]=$c;
		return $c;
	}
	
	FUNCTION DELETE_TABLE($table){

	}		
	
	private function distanceOfTimeInWords($fromTime, $toTime = 0, $showLessThanAMinute = true) {
		$distanceInSeconds = round(abs($toTime - $fromTime));
		$distanceInMinutes = round($distanceInSeconds / 60);
	
		if ( $distanceInMinutes <= 1 ) {
			if ( !$showLessThanAMinute ) {
				return ($distanceInMinutes == 0) ? 'less than a minute' : '1 {minute}';
			} else {
				if ( $distanceInSeconds < 5 ) {
					return '{lessthan} 5 {seconds} ('.$distanceInSeconds.'s)';
				}
				if ( $distanceInSeconds < 10 ) {
					return '{lessthan} 10 {seconds} ('.$distanceInSeconds.'s)';
				}
				if ( $distanceInSeconds < 20 ) {
					return '{lessthan} 20 {seconds} ('.$distanceInSeconds.'s) ';
				}
				if ( $distanceInSeconds < 40 ) {
					return '{abouttime} {halfaminute} ('.$distanceInSeconds.'s)';
				}
				if ( $distanceInSeconds < 60 ) {
					return '{lessthanaminute}';
				}
	
				return '1 minute';
			}
		}
		if ( $distanceInMinutes < 45 ) {
			return $distanceInMinutes . ' {minutes}';
		}
		if ( $distanceInMinutes < 90 ) {
			return '{abouttime} 1 {hour}';
		}
		if ( $distanceInMinutes < 1440 ) {
			return '{abouttime} ' . round(floatval($distanceInMinutes) / 60.0) . ' {hours}';
		}
		if ( $distanceInMinutes < 2880 ) {
			return '1 {day}';
		}
		if ( $distanceInMinutes < 43200 ) {
			return '{abouttime} ' . round(floatval($distanceInMinutes) / 1440) . ' {days}';
		}
		if ( $distanceInMinutes < 86400 ) {
			return '{abouttime} 1 {month}';
		}
		if ( $distanceInMinutes < 525600 ) {
			return round(floatval($distanceInMinutes) / 43200) . ' {months}';
		}
		if ( $distanceInMinutes < 1051199 ) {
			return '{abouttime} 1 {year}';
		}
	
		return 'over ' . round(floatval($distanceInMinutes) / 525600) . ' {years}';
		 
	}	

    function TransArray(){
			$trans["category_facebook"]="facebook";
			$trans["category_microsoft"]="microsoft";
			$trans["category_society"]="society";
			$trans["category_association"]="associations";
			$trans["category_publicite"]="publicite";
			$trans["category_phishtank"]="phishtank";
			$trans["category_ransomwares"]="ransomwares";
			$trans["category_shopping"]="shopping";
			$trans["category_abortion"]="abortion";
			$trans["category_agressive"]="agressive";
			$trans["category_alcohol"]="alcohol";
			$trans["category_animals"]="animals";
			$trans["category_associations"]="associations";
			$trans["category_astrology"]="astrology";
			$trans["category_audio_video"]="audio-video";
			$trans["category_youtube"]="youtube";
			$trans["category_google"]="google";
			$trans["category_apple"]="apple";
			$trans["category_amazonaws"]="amazonaws";
			$trans["category_akamai"]="akamai";
			$trans["category_yahoo"]="yahoo";
			$trans["category_skype"]="skype";
			$trans["category_citrix"]="citrix";
			$trans["category_automobile_bikes"]="automobile/bikes";
			$trans["category_automobile_boats"]="automobile/boats";
			$trans["category_automobile_carpool"]="automobile/carpool";
			$trans["category_automobile_cars"]="automobile/cars";
			$trans["category_automobile_planes"]="automobile/planes";
			$trans["category_bicycle"]="bicycle";
			$trans["category_blog"]="blog";
			$trans["category_books"]="books";
			$trans["category_browsersplugins"]="browsersplugins";
			$trans["category_celebrity"]="celebrity";
			$trans["category_chat"]="chat";
			$trans["category_children"]="children";
			$trans["category_cleaning"]="cleaning";
			$trans["category_clothing"]="clothing";
			$trans["category_converters"]="converters";
			$trans["category_cosmetics"]="cosmetics";
			$trans["category_culture"]="culture";
			$trans["category_dangerous_material"]="dangerous_material";
			$trans["category_dating"]="dating";
			$trans["category_dictionaries"]="dictionaries";
			$trans["category_downloads"]="downloads";
			$trans["category_drugs"]="drugs";
			$trans["category_dynamic"]="dynamic";
			$trans["category_electricalapps"]="electricalapps";
			$trans["category_electronichouse"]="electronichouse";
			$trans["category_filehosting"]="filehosting";
			$trans["category_finance_banking"]="finance/banking";
			$trans["category_finance_insurance"]="finance/insurance";
			$trans["category_finance_moneylending"]="finance/moneylending";
			$trans["category_finance_other"]="finance/other";
			$trans["category_finance_realestate"]="finance/realestate";
			$trans["category_financial"]="financial";
			$trans["category_forums"]="forums";
			$trans["category_gamble"]="gamble";
			$trans["category_games"]="games";
			$trans["category_genealogy"]="genealogy";
			$trans["category_gifts"]="gifts";
			$trans["category_governements"]="governements";
			$trans["category_governments"]="governments";
			$trans["category_green"]="green";
			$trans["category_hacking"]="hacking";
			$trans["category_handicap"]="handicap";
			$trans["category_health"]="health";
			$trans["category_hobby_arts"]="hobby/arts";
			$trans["category_hobby_cooking"]="hobby/cooking";
			$trans["category_hobby_other"]="hobby/other";
			$trans["category_hobby_pets"]="hobby/pets";
			$trans["category_paytosurf"]="paytosurf";
			$trans["category_terrorism"]="terrorism";
			$trans["category_hobby_fishing"]="hobby/fishing";
			$trans["category_hospitals"]="hospitals";
			$trans["category_houseads"]="houseads";
			$trans["category_housing_accessories"]="housing/accessories";
			$trans["category_housing_doityourself"]="housing/doityourself";
			$trans["category_housing_builders"]="housing/builders";
			$trans["category_humanitarian"]="humanitarian";
			$trans["category_imagehosting"]="imagehosting";
			$trans["category_industry"]="industry";
			$trans["category_internal"]="internal";
			$trans["category_isp"]="isp";
			$trans["category_smalladds"]="smalladds";
			$trans["category_jobsearch"]="jobsearch";
			$trans["category_jobtraining"]="jobtraining";
			$trans["category_justice"]="justice";
			$trans["category_learning"]="learning";
			$trans["category_liste_bu"]="liste_bu";
			$trans["category_luxury"]="luxury";
			$trans["category_mailing"]="mailing";
			$trans["category_malware"]="malware";
			$trans["category_manga"]="manga";
			$trans["category_maps"]="maps";
			$trans["category_marketingware"]="marketingware";
			$trans["category_medical"]="medical";
			$trans["category_mixed_adult"]="mixed_adult";
			$trans["category_mobile_phone"]="mobile-phone";
			$trans["category_models"]="models";
			$trans["category_movies"]="movies";
			$trans["category_music"]="music";
			$trans["category_nature"]="nature";
			$trans["category_news"]="news";
			
			$trans["category_passwords"]="passwords";
			$trans["category_phishing"]="phishing";
			$trans["category_photo"]="photo";
			$trans["category_pictureslib"]="pictureslib";
			$trans["category_politic"]="politic";
			$trans["category_porn"]="porn";
			$trans["category_press"]="news";
			$trans["category_proxy"]="proxy";
			$trans["category_reaffected"]="reaffected";
			$trans["category_recreation_humor"]="recreation/humor";
			$trans["category_recreation_nightout"]="recreation/nightout";
			$trans["category_recreation_schools"]="recreation/schools";
			$trans["category_recreation_sports"]="recreation/sports";
			$trans["category_recreation_travel"]="recreation/travel";
			$trans["category_recreation_wellness"]="recreation/wellness";
			$trans["category_redirector"]="redirector";
			$trans["category_religion"]="religion";
			$trans["category_remote_control"]="remote-control";
            $trans["category_nrds"]="nrds";
			$trans["category_sciences"]="sciences";
			$trans["category_science_astronomy"]="science/astronomy";
			$trans["category_science_computing"]="science/computing";
			$trans["category_science_weather"]="science/weather";
			$trans["category_science_chemistry"]="science/chemistry";
			$trans["category_searchengines"]="searchengines";
			$trans["category_sect"]="sect";
			$trans["category_sexual_education"]="sexual_education";
			$trans["category_sex_lingerie"]="sex/lingerie";
			$trans["category_smallads"]="smallads";
            $trans["category_ai"]="Artificial Intelligence";
			$trans["category_socialnet"]="socialnet";
			$trans["category_spyware"]="spyware";
			$trans["category_sslsites"]="sslsites";
			$trans["category_stockexchange"]="stockexchange";
			$trans["category_strict_redirector"]="strict_redirector";
			$trans["category_strong_redirector"]="strong_redirector";
			$trans["category_suspicious"]="suspicious";
			$trans["category_teens"]="teens";
			$trans["category_tobacco"]="tobacco";
			$trans["category_tracker"]="tracker";
			$trans["category_translators"]="translators";
			$trans["category_transport"]="transport";
			$trans["category_tricheur"]="tricheur";
			$trans["category_updatesites"]="updatesites";
			$trans["category_violence"]="violence";
			$trans["category_warez"]="warez";
			$trans["category_weapons"]="weapons";
			$trans["category_webapps"]="webapps";
			$trans["category_webmail"]="webmail";
			$trans["category_webphone"]="webphone";
			$trans["category_webplugins"]="webplugins";
			$trans["category_webradio"]="webradio";
			$trans["category_webtv"]="webtv";
			$trans["category_wine"]="wine";
			$trans["category_womanbrand"]="womanbrand";	
			$trans["category_horses"]="horses";	
			$trans["category_meetings"]="meetings";	
			$trans["category_tattooing"]="tattooing";	
			$trans["category_advertising"]="publicite";	
			$trans["category_getmarried"]="getmarried";	
			$trans["category_literature"]="literature";
			$trans["category_police"]="police";
			$trans["category_search"]="searchengines";
			
			return $trans;		
		
	}

    public function ufdbKat_explains($category_id){
    $index=99770;
    $IntoExplain[1]="Adult content. Pornographic and erotic content, nudity.";
    $IntoExplain[3]="Content related to alcohol, tobacco, narcotics.";
    $IntoExplain[4]="Physical or psychological violence, cruel treatment of animals.";
    $IntoExplain[5]="Content with profane language.";
    $IntoExplain[6]="Content related to weapons, explosives, and pyrotechnical products.";
    $IntoExplain[7]="Content related to gambling.";
    $IntoExplain[8]="Web chats, forums, instant messaging.";
    $IntoExplain[9]="Web-based email services.";
    $IntoExplain[10]="Online shops and auctions. Includes websites of online stores, and stores that accept online payments.";
    $IntoExplain[11]="Social networks.";
    $IntoExplain[12]="Recruitment, hiring and employment sites. Related social networks. Employer websites with job listings.";
    $IntoExplain[13]="Web anonymizers and proxy servers.";
    $IntoExplain[14]="Online payment and online banking services.";
    $IntoExplain[15]="Casual games.";
    $IntoExplain[16]="Online payment systems.";
    $IntoExplain[17]="Bank websites.";
    $IntoExplain[20]="Online shops with own payment systems.";
    $IntoExplain[21]="Blocked by police decision. Use this category only for Japan.";
    $IntoExplain[22]="Downloading of software, audio or video content.";
    $IntoExplain[23]="Content related to finance and economics.";
    $IntoExplain[24]="Content related to business. Corporate websites, business associations, enterprise-oriented services.";
    $IntoExplain[25]="Content related to consumer electronics. Websites of manufacturers, news, reviews, informational websites, and online retailers.";
    $IntoExplain[26]="Content related to information security, including websites of cybersecurity companies. This category does not include websites that provide information on how to hack systems.";
    $IntoExplain[27]="Content and services related to gambling, lotteries, sweepstakes.";
    $IntoExplain[28]="Content and services related to communication.";
    $IntoExplain[29]="Spam websites.";
    $IntoExplain[30]="Content related to motor vehicles. Reviews, informational websites, and online retailers.";
    $IntoExplain[31]="Content related to arts. Websites of galleries and museums, informational  websites.";
    $IntoExplain[34]="Content related to online transactions and commerce.";
    $IntoExplain[35]="Content related to games. Shops, downloads, discussion, casual games, flash games, official sites of mmorpg and game development companies.";
    $IntoExplain[36]="Content related to religious public movements, religious associations, organizations with a religious ideology, cults and sects.";
    $IntoExplain[37]="News and media websites.";
    $IntoExplain[38]="Pornographic and erotic content, nudity. Receiving responses from KWF Service";
    $IntoExplain[39]="Nudism and exhibitionism, related communities.";
    $IntoExplain[40]="Content depicting underwear and lingerie. Lingerie shops, fashion, exposed underwear.";
    $IntoExplain[41]="Content related to sexual education. Sex education portals, sex encyclopedias, related forums. Articles about sexuality, including medical sites.";
    $IntoExplain[42]="Adult dating sites.";
    $IntoExplain[43]="Lesbian, gay, bisexual and transgender (LGBT) content.";
    $IntoExplain[44]="Sex shops and sex toys.";
    $IntoExplain[45]="Narcotics. Sale, manufacturing, discussion. Related articles. Sites that promote drug abstinence.";
    $IntoExplain[46]="Alcohol sale, discussion, related articles.";
    $IntoExplain[47]="Tobacco sale, discussion. Related articles. Shops that sell smoking accessories.";
    $IntoExplain[48]="Content related to culture and society.";
    $IntoExplain[49]="Government websites, political parties, laws and legal companies.";
    $IntoExplain[50]="Content related to home and family. Related shops.";
    $IntoExplain[51]="Content related to military or military equipment. Military-related government sites.";
    $IntoExplain[52]="Content related to food. Food shops, food delivery services. Restaurant and cafe websites.";
    $IntoExplain[53]="Content related to esoterica, astrology, horoscopes, divination, chiromancy, ufology, magic.";
    $IntoExplain[54]="Torrent trackers and forums, including protocol specifications and software.";
    $IntoExplain[55]="File sharing websites and services. Receiving responses from KWF Service";
    $IntoExplain[18]="Identifier Description";
    $IntoExplain[56]="Video and audio hosting, downloads, streaming.";
    $IntoExplain[57]="Content related to information technologies.";
    $IntoExplain[58]="Search engines and services.";
    $IntoExplain[59]="Hosting and domain registration services, whois services, domain sellers.";
    $IntoExplain[60]="Teaser network sites, ad services, context advertisement services, advertising agencies.";
    $IntoExplain[61]="Bank websites.";
    $IntoExplain[62]="Real estate rental services.";
    $IntoExplain[63]="Phishing websites.";
    $IntoExplain[64]="Malicious resources or malicious software download URLs.";
    $IntoExplain[65]="Content related to suicide, self-harm and self-injury.";
    $IntoExplain[66]="Blogs and blog hosting.";
    $IntoExplain[67]="Dating services. Does not include adult dating services.";
    $IntoExplain[68]="Content related to education.";
    $IntoExplain[69]="Websites of schools and universities.";
    $IntoExplain[70]="Online libraries, book shops, book reviews. Related discussions.";
    $IntoExplain[71]="Education portals, knowledge bases, encyclopedias.";
    $IntoExplain[72]="Content related to hobbies and entertainment.";
    $IntoExplain[73]="Content related to fishing and hunting. Shops that sell fishing and hunting equipment.";
    $IntoExplain[74]="Content related to traveling. Travel reviews, tourist agencies, hotel and ticket Receiving responses from KWF Service";
    $IntoExplain[19]="Identifier Description booking services.";
    $IntoExplain[75]="Content related to television and radio. Websites of television and radio stations, online radio stations.";
    $IntoExplain[76]="Content related to pets and animals. Articles and discussions.";
    $IntoExplain[77]="Humorous content.";
    $IntoExplain[78]="Content related to music. Song and song lyrics downloads. Official websites of musicians and bands.";
    $IntoExplain[79]="Content related to beauty, health and healthy lifestyle.";
    $IntoExplain[80]="Content related to sport and bodybuilding. Discussions, articles, official sites of sports teams, fan clubs.";
    $IntoExplain[81]="Content related to health, medicine, healthy lifestyle, diets, vegetarians.";
    $IntoExplain[82]="Content related to fashion and style.";
    $IntoExplain[83]="Content related to pharmacy, legal drugs, health supplements.";
    $IntoExplain[84]="Online lotteries.";
    $IntoExplain[85]="Content related to casino and card games. Online casinos, flash card games.  Articles and rules.";
    $IntoExplain[86]="Online betting services. Racing and sport betting.";
    $IntoExplain[87]="Content related to hatred and discrimination.";
    $IntoExplain[88]="Extremist or racist content.";
    $IntoExplain[89]="This category is reserved for future use. You can ignore this category.";
    $IntoExplain[90]="Content related to anorexia and promotion of anorexia.";
    $IntoExplain[91]="Content related to abortions, including sites of medical organizations with Receiving responses from KWF Service";
    $IntoExplain[20]="Identifier Description information about abortions.";
    $IntoExplain[92]="Content for children. Includes educational content, entertainment and chats.";
    $IntoExplain[93]="Websites of global and local internet service providers.";
    $IntoExplain[94]="Content is banned by Russian Federation laws.";
    $IntoExplain[95]="Content is banned by Federal Law No. 436-FZ of the Russian Federation (On Protection of Children from Information Harmful to Their Health and Development).";
    $IntoExplain[96]="Content is from the Federal List of Extremist Materials, and is banned by Russian Federation laws.";
    $IntoExplain[97]="Blacklisted by Roskomnadzor. The resource is in the unified register of the domain names, website references and network addresses that allow identifying websites containing information circulation of which is forbidden in the Russian Federation.";
    $IntoExplain[113]="Content related to software that downloads or displays unwanted ads when a user is online, collects marketing data and other information without the user's knowledge or redirects search requests to certain advertising websites.";
    $IntoExplain[114]="Content related to legitimate programs that can cause damage if they are exploited by malicious users (in order to delete, block, modify, or copy data, and disrupt the performance of computers or networks).";
    $IntoExplain[122]="Resource is a Tor node.";
    $IntoExplain[123]="Resource is provided by a dynamic DNS service.";
    $IntoExplain[124]="Resource hosts Remote Admin Tools software.";

    foreach ($IntoExplain as $index=>$explain){
        $num=$index+99770;
        $MAIN[$num]=$explain;

    }

    return $MAIN[$category_id];

    }

}