<?php 
include_once(dirname(__FILE__)."/class.tcpip.inc");

class squid_familysite{

	public function filldomains($MAIN,$www){
		$array["com"]=true;
		$array["at"]=true;
		$array["es"]=true;
		$array["pt"]=true;
		$array["ua"]=true;
		$array["fr"]=true;
		$array["ru"]=true;
		$array["ph"]=true;
		$array["hu"]=true;
		$array["com.ar"]=true;
		$array["com.au"]=true;
		$array["com.br"]=true;
		$array["com.sg"]=true;
		$array["com.mx"]=true;
		$array["com.hk"]=true;
		$array["com.cn"]=true;
		$array["com.tr"]=true;
		$array["de.nu"]=true;
		$array["co.nz"]=true;
		$array["tn"]=true;
		$array["co.tz"]=true;
		$array["co.zw"]=true;
		$array["com.fj"]=true;
		$array["com.ly"]=true;
		$array["com.pe"]=true;
		$array["dz"]=true;
		$array["bf"]=true;
		$array["be"]=true;
		$array["si"]=true;
		$array["ht"]=true;
		$array["st"]=true;
		$array["cl"]=true;
		$array["com.sa"]=True;
		$array["com.sv"]=True;
		$array["com.bo"]=True;
		$array["com.cu"]=True;
		$array["com.pk"]=True;
		$array["com.do"]=true;
		$array["com.ni"]=true;
		$array["com.jm"]=true;
		$array["id"]=True;
		$array["co.jp"]=true;
		$array["co.za"]=true;
		$array["co.id"]=true;
		$array["co.uk"]=true;
		$array["co.mz"]=true;
		$array["co.in"]=true;
		$array["co.zm"]=true;
		$array["co.cr"]=true;
		$array["co.ve"]=true;
		
		$array["tj"]=true;
		$array["tt"]=true;
		$array["de"]=true;
		$array["de.tf"]=true;
		$array["de.vu"]=true;
		$array["in"]=true;
		$array["iq"]=true;
		$array["it"]=true;
		$array["ro"]=true;
		$array["pl"]=true;
		$array["dk"]=true;
		$array["tr"]=true;
		$array["kz"]=true;
		$array["ma"]=true;
		$array["net"]=true;
		$array["by"]=true;
		$array["com.my"]=true;
		$array["se"]=true;
		$array["lan"]=true;
		$array["ro"]=true;
		$array["cl"]=true;
		$array["com.ph"]=true;
		$array["de"]=true;
		$array["ca"]=true;
		$array["jp"]=true;

        foreach ($array as $ext=>$in){
            $MAIN["{$www}.$ext"]=true;
		
		}
		return $MAIN;
	}
	
	public function GetFamilySites($sitename){
		if(!isset($GLOBALS["TCP_CLASS"])){$GLOBALS["TCP_CLASS"]=new IP();}
		if(!isset($GLOBALS["DEBUG_FAMILIY"])){$GLOBALS["DEBUG_FAMILIY"]=false;}
		$sitename=str_replace("'", "", $sitename);
		if(isset($GLOBALS["GetFamilySites"][$sitename])){return $GLOBALS["GetFamilySites"][$sitename];}
		if(strpos(" $sitename", ".")==0){return $sitename;}
		if($GLOBALS["TCP_CLASS"]->isIPAddress($sitename)){return $sitename;}
	
	
		if(function_exists("idn_to_ascii")){
			$sitename = @idn_to_ascii($sitename, "UTF-8");
		}
	
		$tmp=trim($this->getRegisteredDomain($sitename));
		if($GLOBALS["DEBUG_FAMILIY"]){echo "getRegisteredDomain($sitename) -> $tmp\n";}
	
		$tt=$this->GetFamilySitestt("",true);
		if($tmp<>null){
			if(isset($tt[$tmp])){
				if($GLOBALS["DEBUG_FAMILIY"]){echo "{$tt[$tmp]} tt[$tmp] -> $sitename\n";}
				$GLOBALS["GetFamilySites"][$sitename]=$sitename;
				return $sitename;
			}
				
			$exp=explode(".",$tmp);
			if(count($exp)>1){
				$tmp=null;
			}else{
				$GLOBALS["GetFamilySites"][$sitename]=$tmp;
				return $tmp;
			}
	
				
				
		}
	
		
	
		$tmp=$this->GetFamilySitestt($sitename);
		if(strpos($tmp, ".")>0){
			$GLOBALS["GetFamilySites"][$sitename]=$tmp;
			return $tmp;
		}
	
		
		if($GLOBALS["VERBOSE"]){echo "GetFamilySitestt -> ?? $sitename\n";}
		$bits = explode('.', $sitename);
		$idz=count($bits);
		$idz-=3;
		
		if( $idz+2 == -1){
			if($GLOBALS["VERBOSE"]){echo "?? $sitename -> $idz +2 = -1\n";}
		}
		
		if (strlen($bits[($idz+2)])==2) {
			if($idz==-1){echo "?? $sitename -> $idz +2 = -1\n";}
			$url=$bits[$idz].'.'.$bits[($idz+1)].'.'.$bits[($idz+2)];
		} else if (strlen($bits[($idz+2)])==0) {
			$url=$bits[($idz)].'.'.$bits[($idz+1)];
		} else {
			$url=$bits[($idz+1)].'.'.$bits[($idz+2)];
		}
		if(substr($url, 0,1)=="."){$url=substr($url, 1,strlen($url));}
		$GLOBALS["GetFamilySites"][$sitename]=$url;
		return $url;
	}
	
	private function getRegisteredDomain($signingDomain) {
		if(!isset($GLOBALS["tldTree"])){
			$GLOBALS["tldTree"]=file_get_contents(dirname(__FILE__)."/tldree.db");
		}
	
		$signingDomainParts = explode('.', $signingDomain);
		$tldTree=unserialize($GLOBALS["tldTree"]);
		$result = $this->findRegisteredDomain($signingDomainParts, $tldTree);
	
		if ($result===NULL || $result=="") {
			// this is an invalid domain name
			return NULL;
		}
	
		// assure there is at least 1 TLD in the stripped signing domain
		if (!strpos($result, '.')) {
			$cnt = count($signingDomainParts);
			if ($cnt==1 || $signingDomainParts[$cnt-2]=="") return NULL;
			return $signingDomainParts[$cnt-2].'.'.$signingDomainParts[$cnt-1];
		}
		return $result;
	}

	private function findRegisteredDomain($remainingSigningDomainParts, &$treeNode) {
	
		$sub = array_pop($remainingSigningDomainParts);
	
		$result = "";
		if (isset($treeNode['!'])) {
			return '#';
		} else if (is_array($treeNode) && array_key_exists($sub, $treeNode)) {
			$result = $this->findRegisteredDomain($remainingSigningDomainParts, $treeNode[$sub]);
		} else if (is_array($treeNode) && array_key_exists('*', $treeNode)) {
			$result = $this->findRegisteredDomain($remainingSigningDomainParts, $treeNode['*']);
		} else {
			return $sub;
		}
        if(is_null($result)){
            return NULL;
        }
		if ($result == '#') {
            return $sub;
        }
		if (strlen($result)>0) {
			return $result.'.'.$sub;
		}
		return NULL;
	}

	public function tld_countries($tld){
	    $array["an"]="Netherlands Antilles";
        $array["ao"]="Angola";
        $array["aq"]="Antarctica";
	    $array["ax"]="Åland Islands, Finland";
	    $array["aw"]="Aruba";
	    $array["cw"]="Curaçao";
	    $array["et"]="Ethiopia";
	    $array["fj"]="Fiji";
        $array["gf"]="Wallis and Futuna Islands";
        $array["gh"]="Ghana";
        $array["gov"]="Governements";
        $array["hm"]="Heard and McDonald Islands";
        $array["pr"]="Puerto Rico";
        $array["sd"]="South Sudan";
        $array["ss"]="South Sudan";
        $array["jm"]="Jamaica";
        $array["ke"]="Kenya";
        $array["kw"]="Kuwait";
        $array["ky"]="Cayman Islands";
        $array["ki"]="Kiribati";
        $array["kh"]="Cambodia";
        $array["km"]="Comoros";
        $array["kg"]="Kyrgyzstan";
        $array["lr"]="Liberia";
        $array["tt"]="Trinidad and Tobago";
        $array["tz"]="Tanzania";
        $array["coop"]="Cooperatives";
        $array["pro"]="Professional";
        $array["pw"]="Palau";
        $array["cat"]="Catalan";
        $array["mo"]="Macao";
        $array["cy"]="Cyprus";
        $array["mz"]="Mozambique";
        $array["mc"]="Monaco";
        $array["bn"]="Jabatan Telekom Brunei";
        $array["sz"]="Eswatini";
        $array["zm"]="Zambia";
        $array["mp"]="Northern Mariana Islands";
        $array["sl"]="Sierra Leone";
        $array["sc"]="Seychelles";
        $array["ua"]="Ukraine";

    }
	
	public function GetFamilySitestt($domain,$getpartOnly=false){

 	$tlds = array(
				'com'=>array('cn'=>true,'us'=>true,'ya'=>true,"com"=>true,"gr"=>true,"org"=>true,"uk"=>true,"eu"=>true,"gb"=>true,"kr"=>true,'symbolics'=>true,'bbn'=>true,'think'=>true,'mcc'=>true,'dec'=>true,'northrop'=>true,'xerox'=>true,'sri'=>true,'hp'=>true,'bellcore'=>true,'ibm'=>true,'sun'=>true,'intel'=>true,'ti'=>true,'att'=>true,'gmr'=>true,'tek'=>true,'fmc'=>true,'ub'=>true,'bell-atl'=>true,'ge'=>true,'grebyn'=>true,'isc'=>true,'nsc'=>true,'stargate'=>true,'boeing'=>true,'itcorp'=>true,'siemens'=>true,'pyramid'=>true,'alphacdc'=>true,'bdm'=>true,'fluke'=>true,'inmet'=>true,'kesmai'=>true,'mentor'=>true,'nec'=>true,'ray'=>true,'rosemount'=>true,'vortex'=>true,'alcoa'=>true,'gte'=>true,'adobe'=>true,'amd'=>true,'das'=>true,'data-io'=>true,'octopus'=>true,'portal'=>true,'teltone'=>true,'3com'=>true,'amdahl'=>true,'ccur'=>true,'ci'=>true,'convergent'=>true,'dg'=>true,'peregrine'=>true,'quad'=>true,'sq'=>true,'tandy'=>true,'tti'=>true,'unisys'=>true,'cgi'=>true,'cts'=>true,'spdcc'=>true,'apple'=>true,'nma'=>true,'prime'=>true,'philips'=>true,'datacube'=>true,'kai'=>true,'tic'=>true,'vine'=>true,'ncr'=>true,'cisco'=>true,'rdl'=>true,'slb'=>true,'parcplace'=>true,'utc'=>true,'ide'=>true,'trw'=>true,'unipress'=>true,'dupont'=>true,'lockheed'=>true,'rosetta'=>true,'toad'=>true,'quick'=>true,'allied'=>true,'dsc'=>true,'sco'=>true,'gene'=>true,'kccs'=>true,'spectra'=>true,'wlk'=>true,'mentat'=>true,'wyse'=>true,'cfg'=>true,'marble'=>true,'cayman'=>true,'entity'=>true,'ksr'=>true,'nynexst'=>true,"google"=>true),
            'academy'=>true,
            'accountants'=>true,
            'actor'=>true,
            'ai.net'=>true,
            'airforce'=>true,
            'apartments'=>true,
            'app'=>true,
            'archi'=>true,
            'army'=>true,
            'art'=>true,
            'associates'=>true,
            'audio'=>true,
            'auto'=>true,
            'autos'=>true,
            'aws'=>true,
            'baby'=>true,
            'band'=>true,
            'bar'=>true,
            'bargains'=>true,
            'bayern'=>true,
            'best'=>true,
            'bet'=>true,
            'bike'=>true,
            'bingo'=>true,
            'bio'=>true,
            'blue'=>true,
            'boats'=>true,
            'boston'=>true,
            'boutique'=>true,
            'brown'=>true,
            'cam'=>true,
            'camera'=>true,
            'camp'=>true,
            'capital'=>true,
            'car'=>true,
            'cards'=>true,
            'care'=>true,
            'careers'=>true,
            'cars'=>true,
            'casa'=>true,
            'casino'=>true,
            'catering'=>true,
            'ceo'=>true,
            'charity'=>true,
            'chat'=>true,
            'cheap'=>true,
            'christmas'=>true,
            'city'=>true,
            'clothing'=>true,
            'computer'=>true,
            'condos'=>true,
            'construction'=>true,
            'contractors'=>true,
            'cooking'=>true,
            'cool'=>true,
            'coupons'=>true,
            'courses'=>true,
            'credit'=>true,
            'creditcard'=>true,
            'cruises'=>true,
            'dance'=>true,
            'design'=>true,
            'diet'=>true,
            'direct'=>true,
            'discount'=>true,
            'doctor'=>true,
            'dog'=>true,
            'dom'=>true,
            'domains'=>true,
            'eco'=>true,
            'education'=>true,
            'energy'=>true,
            'engineer'=>true,
            'engineering'=>true,
            'enterprises'=>true,
            'equipment'=>true,
            'estate'=>true,
            'film'=>true,
            'finance'=>true,
            'financial'=>true,
            'fish'=>true,
            'fishing'=>true,
            'fit'=>true,
            'fitness'=>true,
            'flights'=>true,
            'florist'=>true,
            'flowers'=>true,
            'football'=>true,
            'forsale'=>true,
            'foundation'=>true,
            'fun'=>true,
            'fund'=>true,
            'furniture'=>true,
            'futbol'=>true,
            'fyi'=>true,
            'gallery'=>true,
            'game'=>true,
            'games'=>true,
            'gift'=>true,
            'gifts'=>true,
            'gives'=>true,
            'glass'=>true,
            'gmbh'=>true,
            'gold'=>true,
            'golf'=>true,
            'graphics'=>true,
            'gratis'=>true,
            'green'=>true,
            'gripe'=>true,
            'guitars'=>true,
            'haus'=>true,
            'health'=>true,
            'healthcare'=>true,
            'hiphop'=>true,
            'hiv'=>true,
            'html'=>true,
            'icu'=>true,
            'immo'=>true,
            'immobilien'=>true,
            'imrworld'=>true,
            'ink'=>true,
            'institute'=>true,
            'insure'=>true,
            'international'=>true,
            'investments'=>true,
            'irish'=>true,
            'jewelry'=>true,
            'kitchen'=>true,
            'kiwi'=>true,
            'lawyer'=>true,
            'lease'=>true,
            'legal'=>true,
            'lgbt'=>true,
            'lighting'=>true,
            'limited'=>true,
            'limo'=>true,
            'llc'=>true,
            'loans'=>true,
            'love'=>true,
            'ltd'=>true,
            'ltda'=>true,
            'luxe'=>true,
            'luxury'=>true,
            'maison'=>true,
            'management'=>true,
            'market'=>true,
            'marketing'=>true,
            'mba'=>true,
            'melbourne'=>true,
            'memorial'=>true,
            'menu'=>true,
            'miami'=>true,
            'mortgage'=>true,
            'motorcycles'=>true,
            'movie'=>true,
            'nagoya'=>true,
            'navy'=>true,
            'observer'=>true,
            'okinawa'=>true,
            'one'=>true,
            'onl'=>true,
            'organic'=>true,
            'parts'=>true,
            'php'=>true,
            'pizza'=>true,
            'plumbing'=>true,
            'plus'=>true,
            'poker'=>true,
            'productions'=>true,
            'properties'=>true,
            'property'=>true,
            'protection'=>true,
            'pub'=>true,
            'qpon'=>true,
            'realestate'=>true,
            'realty'=>true,
            'recipes'=>true,
            'rehab'=>true,
            'reise'=>true,
            'reisen'=>true,
            'ren'=>true,
            'rent'=>true,
            'rentals'=>true,
            'repair'=>true,
            'report'=>true,
            'republican'=>true,
            'rest'=>true,
            'restaurant'=>true,
            'rich'=>true,
            'rip'=>true,
            'rodeo'=>true,
            'run'=>true,
            'ryukyu'=>true,
            'sale'=>true,
            'salon'=>true,
            'sarl'=>true,
            'school'=>true,
            'schule'=>true,
            'security'=>true,
            'shoes'=>true,
            'shop'=>true,
            'shopping'=>true,
            'show'=>true,
            'singles'=>true,
            'size'=>true,
            'ski'=>true,
            'soccer'=>true,
            'social'=>true,
            'software'=>true,
            'solar'=>true,
            'soy'=>true,
            'srl'=>true,
            'storage'=>true,
            'store'=>true,
            'studio'=>true,
            'study'=>true,
            'style'=>true,
            'sucks'=>true,
            'supplies'=>true,
            'supply'=>true,
            'support'=>true,
            'surf'=>true,
            'surgery'=>true,
            'systems'=>true,
            'tattoo'=>true,
            'tax'=>true,
            'taxi'=>true,
            'tennis'=>true,
            'theater'=>true,
            'theatre'=>true,
            'tickets'=>true,
            'tienda'=>true,
            'tips'=>true,
            'tires'=>true,
            'tours'=>true,
            'town'=>true,
            'toys'=>true,
            'training'=>true,
            'tube'=>true,
            'university'=>true,
            'vacations'=>true,
            'vegas'=>true,
            'ventures'=>true,
            'vet'=>true,
            'viajes'=>true,
            'video'=>true,
            'villas'=>true,
            'vin'=>true,
            'vote'=>true,
            'voto'=>true,
            'voyage'=>true,
            'watch'=>true,
            'wedding'=>true,
            'wiki'=>true,
            'wine'=>true,
            'world'=>true,
            'wtf'=>true,
            'xin'=>true,
            'yachts'=>true,
            'yoga'=>true,
            'yokohama'=>true,
            'zip'=>true,

				'ac'=>array('gob' => true,"com"=>true,"org"=>true,"co"=>true),
				'ad'=>array('gob' => true,"com"=>true,"org"=>true,"co"=>true),
				'aq'=>true,
				'adult'=>true,
				'ai'=>array('gob' => true,"com"=>true,"org"=>true,"co"=>true),
				'al'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ar' => array('net'=>true,'gob' => true,"com"=>true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'as'=>array("com"=>true,"org"=>true),
				'am'=>array("com"=>true,"org"=>true),
                'an'=>array("com"=>true,"org"=>true),
				'ae'=>array("com"=>true,"org"=>true,"ac"=>true),
				'ag'=>array("com"=>true,"org"=>true),
                'ao'=>true,
				'at'=>array('co'=>true,"com"=>true,"org"=>true,"ua"=>true,"gv"=>true,'or'=>true),
				'aw'=>true,
				'ax'=>true,
				'az'=>array("com"=>true,"org"=>true,"edu"=>true),
				'name'=>array("com"=>true,"org"=>true),
                'ba'=>array("com"=>true,"org"=>true),
                'bb'=>array("com"=>true,"org"=>true),
				'bd'=>array("com"=>true,"org"=>true,"net"=>true),
				'bg'=>array("com"=>true,"org"=>true),
				
				'be'=>array("com"=>true,"org"=>true,'de'=>true,'ac'=>true),
				'bf'=>array("com"=>true,"org"=>true,'de'=>true),
				'bh'=>array("com"=>true,"org"=>true),
                'bi'=>array("com"=>true,"org"=>true,'de'=>true),
				'bj'=>array("com"=>true,"org"=>true,'de'=>true),
                'bl'=>true,
                'bm'=>array("com"=>true,"org"=>true,"gob"=>true),
                'bn'=>array('com'=>true,'edu'=>true,'gov'=>true,'net'=>true,'org'=>true),
                'bo'=>array("com"=>true,"org"=>true,"gob"=>true),


				'biz'=>array("com"=>true,"www1"=>true),
				'br'=>array('com' => true,"org"=>true,"tv"=>true,"inf"=>true,'net'=>true,'gov'=>true,'ind'=>true),
				'bs'=>array('com' => true,"org"=>true,"tv"=>true,"inf"=>true,'net'=>true),
				'bt'=>array('com' => true,"org"=>true,"tv"=>true,"inf"=>true,'net'=>true),
				'by'=>array("com"=>true,"org"=>true),
				'bz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'bzh'=>true,
				'bw'=>true,
				'cat'=>true,
				'ca'=>array('ab'=>true,'bc'=>true,'on'=>true,'qc'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'cc'=>array("za"=>true,"com"=>true,"org"=>true,'co'=>true,'cx'=>true,'on'=>true,   'cz'=>true,"net"=>true,"biz"=>true,"usa"=>true),
				'cd'=>array('gouv'=>true,"com"=>true,"org"=>true),
				'cf'=>array('gouv'=>true,"com"=>true,"org"=>true),
				'cg'=>array('gouv'=>true,"com"=>true,"org"=>true),
				'ci'=>array('gouv'=>true,"com"=>true,"org"=>true),
				'cn'=>array('sh'=>true,'com'=>true,'net'=>true,"org"=>true,"za"=>true),
				'cm'=>array('com'=>true,'net'=>true,"org"=>true,"za"=>true),
				'cx'=>array('com'=>true,'net'=>true,"org"=>true,"za"=>true),
				'co' => array('mz'=>true,'net'=>true,"za"=>true,"com"=>true,"org"=>true,'cc'=>true,"gov"=>true),
				'cr' => array('mz'=>true,'net'=>true,"za"=>true,"com"=>true,"org"=>true,'cc'=>true,"gov"=>true),
				'cw'=>true,
				'cu' => array('mz'=>true,'net'=>true,"za"=>true,'cc'=>true,"gov"=>true,'co'=>true,'com'=>true,"org"=>true,"biz"=>true),
				'cv' => array('mz'=>true,'net'=>true,"za"=>true,"com"=>true,"org"=>true,'cc'=>true,"gov"=>true),
                'cy'=>array('ac'=>true,'net'=>true,'gov'=>true,'org'=>true,'pro'=>true,'name'=>true,'ekloges'=>true,'tm'=>true,'ltd'=>true,'biz'=>true,'press'=>true,'parliament'=>true,'com'=>true,'centralbank'=>true),
				'dj'=>array('com' => true,"org"=>true,"tv"=>true,"inf"=>true,'net'=>true),
				'dev'=>array('com' => true,"org"=>true,"tv"=>true,"inf"=>true,'net'=>true),
				'dm'=>array('com' => true,"org"=>true,"tv"=>true,"inf"=>true,'net'=>true),
				'gb'=>true,
				'gdn'=>true,
				'kim'=>true,
				'pics'=>true,
				
				'ffr'=>array("com"=>true,"org"=>true),
				're'=>array("com"=>true,"org"=>true),
				'ru'=>array('msk'=>true,'da'=>true,"com"=>true,"org"=>true,'ac'=>true),
				'ws'=>array("com"=>true,"org"=>true),

				'org'=>array("com"=>true,'us'=>true,"eu"=>true),
				'net'=>array('uk'=>true,"com"=>true,"org"=>true),
				'do'=>array("com"=>true,"gob"=>true,"org"=>true),
				'de'=>array('tf'=>true,"com"=>true,"org"=>true),
				'dk'=>array("com"=>true,"org"=>true),
				'dz'=>true,
				'edu'=>array("com"=>true,"org"=>true),
				'arpa'=>array("in-addr"=>true),

				'ec'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"gob"=>true),
				'eg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ee' => array('com' => true,"uc"=>true,"ua"=>true,"org"=>true,"net"=>true,"biz"=>true),
				'es'=>array("com"=>true,"org"=>true,"nom"=>true,"gob"=>true),
                'et'=>array('com'=>true,'gov'=>true,'org'=>true,'edu'=>true,'net'=>true),
                'fr'=>array("org"=>true,"nom"=>true,"bl"=>true,"gf"=>true,"gp"=>true,"mq"=>true,"pm"=>true),
				'ug'=>array('ac'=>true),
				'af'=>true,
				'accountant'=>true,
				'bid'=>true,
				'racing'=>true,
				'cricket'=>true,
				'priv'=>true,
				'date'=>true,
				'trade'=>true,
				'ip'=>true,
				'top'=>true,
				'clu'=>true,
				'lol'=>true,
				'buzz'=>true,
				'party'=>true,
				'cash'=>true,
				'rocks'=>true,
				'vip'=>true,
				'ooo'=>true,
				'photo'=>true,
				'pink'=>true,
				'garden'=>true,
				'media'=>true,
				'cloud'=>true,
				'land'=>true,
				'network'=>true,
				'press'=>true,
				'click'=>true,
				'agency'=>true,
				'work'=>true,
				'link'=>true,
				'country'=>true,
				'digital'=>true,
				'hosting'=>true,
				'help'=>true,
				'host'=>true,
				'solutions'=>true,
				"services"=>true,
				"online"=>true,

				"asia"=>true,
				"zone"=>true,
				"news"=>true,
				'today'=>true,
				'photography'=>true,
				'club'=>true,
				'guru'=>true,
				'email'=>true,
				'church'=>true,
				'expert'=>true,
                'coop'=>true,
                'stream'=>true,
                'directory'=>true,
				'webcam'=>true,
				'company'=>true,
				'uno'=>true,
				'photos'=>true,
				'global'=>true,
				'technology'=>true,
				'paris'=>true,
				'wales'=>true,
				'group'=>true,
				'porn'=>true,
				'pf'=>true,
				'xxx'=>true,
				'localdomain'=>true,
				'local'=>true,
				'tel'=>true,
				'center'=>true,
				'goog'=>true,
				'win'=>true,
				'xyz'=>true,
				'download'=>true,
				'review'=>true,
                'reviews'=>true,
				'barclays'=>true,
				'bnpparisbas'=>true,
				'bradesco'=>true,
				'citic'=>true,
				'fage'=>true,
				'dnp'=>true,
				'jcb'=>true,
				'leclerc'=>true,
				'seat'=>true,
				'sener'=>true,
				'lab'=>true,
				'tld'=>true,
				'int'=>true,
				'weir'=>true,
				'tools'=>true,
				'life'=>true,
				'live'=>true,
				'red'=>true,
				'pet'=>true,
                'exchange'=>true,
                'loan'=>true,
                'earth'=>true,
                'partners'=>true,
                'london'=>true,'house'=>true,"ninja"=>true,"men"=>true,"website"=>true,'kerastase'=>true,'kerryhotels'=>true,'kerrylogisitics'=>true,'kerrylogistics'=>true,'kerryproperties'=>true,'ketchup'=>true,'kfh'=>true,'krd'=>true,'mobile'=>true,'mobily'=>true,'moda'=>true,'moe'=>true,'moi'=>true,'mom'=>true,'monash'=>true,'money'=>true,'nokia'=>true,'northlandinsurance'=>true,'northwesternmutual'=>true,'norton'=>true,'now'=>true,'place'=>true,'vodka'=>true,'volkswagen'=>true,'vuelos'=>true,'jpmorgan'=>true,'jpmorganchase'=>true,'jprs'=>true,'juegos'=>true,'juniper'=>true,'justforu'=>true,'kaufen'=>true,'kddi'=>true,

				'au' => array('net' => true,"com"=>true,"org"=>true),
				
				'pt' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true,"gov"=>true),
				'ga' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
                'pr'=>array('biz'=>true,'com'=>true,'edu'=>true,'gov'=>true,'info'=>true,'isla'=>true,'name'=>true,'net'=>true,'org'=>true,'pro'=>true,'est'=>true,'prof'=>true,'ac'=>true),
				'gr' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'lk' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'lu' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'np' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'jo' => array('com' => true,"uc"=>true,"ua"=>true,"co"=>true,"org"=>true),
				'ph'=> array('com'=>true,"gov"=>true),
				'gi' =>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gq' =>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'la'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ch'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'cl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"gob"=>true),

				'cz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),


				'eu'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'eus'=>true,


                'fj'=>array('ac'=>true,'biz'=>true,'com'=>true,'info'=>true,'mil'=>true),
                'fo'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'gf'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'gd'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gp'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gm'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gs'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ge'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'gh'=>array('com'=>true,'edu'=>true,'gov'=>true,'mil'=>true,'net'=>true,'org'=>true,'biz'=>true,'info'=>true,
                            'mobi'=>true,'web'=>true,'media'=>true,'ac'=>true,'store'=>true,'tm'=>true),
                'gov'=>array('alaska'=>true,'as'=>true,'americansamoa'=>true,'az'=>true,'arizona'=>true,'ar'=>true,
                    'arkansas'=>true,'ca'=>true,'co'=>true,'colorado'=>true,'ct'=>true,'de'=>true,'delaware'=>true,
                    'dc'=>true,'florida'=>true,'fl'=>true,'ga'=>true,'georgia'=>true,'guam'=>true,'hawaii'=>true,
                    'ehawaii'=>true,'idaho'=>true,'il'=>true,'illinois'=>true,'in'=>true,'indiana'=>true,'ia'=>true,
                    'iowa'=>true,'ks'=>true,'kansas'=>true,'ky'=>true,'kentucky'=>true,'la'=>true,'louisiana'=>true,
                    'maine'=>true,'md'=>true,'maryland'=>true,'ma'=>true,'mass'=>true,'massachusetts'=>true,'mi'=>true,
                    'michigan'=>true,'mn'=>true,'minnesota'=>true,'ms'=>true,'mississippi'=>true,'mo'=>true,'missouri'=>true,'mt'=>true,'montana'=>true,'ne'=>true,'nebraska'=>true,'nv'=>true,'nevada'=>true,'nh'=>true,'nj'=>true,'newjersey'=>true,'newmexico'=>true,'ny'=>true,'nc'=>true,'northcarolina'=>true,'nd'=>true,'northdakota'=>true,'ohio'=>true,'ok'=>true,'oklahoma'=>true,
                    'oregon'=>true,
                    'pa'=>array('com'=>true),
                    'pennsylvania'=>true,'pr'=>true,'ri'=>true,'rhodeisland'=>true,'sc'=>true,'sd'=>true,'tn'=>true,'tennessee'=>true,'texas'=>true,'utah'=>true,'vt'=>true,'vermont'=>true,'vi'=>true,'virginia'=>true,'wa'=>true,'washington'=>true,'wv'=>true,'wi'=>true,'wisconsin'=>true,'wy'=>true,'wyoming'=>true),
				'gy'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'hk'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'hm'=>true,
				'hu'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'hr'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'hn'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ht'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'il'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ir'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,'ac'=>true),
				'it'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,'pisa'=>true),
				'im'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'in'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"ind"=>true,'ua'=>true,'res'=>true,'ac'=>true),
				'id'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"go"=>true,'sch'=>true,'ac'=>true,"or"=>true),
				'io'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'is'=>array('co'=>true,'com'=>true,"biz"=>true,"web"=>true,"or"=>true,"org"=>true),
				'iq' => array('net' => true,"web"=>true,"co"=>true,"or"=>true,"org"=>true),
				'ie' => array('net' => true,"web"=>true,"co"=>true,"or"=>true,"org"=>true),

				
				'info'=>array('com' => true,"net"=>true,"org"=>true),
				'iy'=>true,
				'js'=>true,
				'jm'=>true,
				'lb'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'je'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'jw'=>true,
				'li'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'lr'=>array('com'=>true,'edu'=>true,'gov'=>true,'org'=>true,'net'=>true),
                'ke'=>array('co'=>true,'or'=>true,'ne'=>true,'go'=>true,'ac'=>true,'sc'=>true,'me'=>true,'mobi'=>true,'info'=>true),
                'kg'=>array('org'=>true,'net'=>true,'com'=>true,'edu'=>true,'gov'=>true,'mil'=>true),
                'kh'=>array('per'=>true,'com'=>true,'edu'=>true,'gov'=>true,'mil'=>true,'net'=>true,'org'=>true),
				'ki'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'km'=>array('com'=>true,'coop'=>true,'asso'=>true,'nom'=>true,'presse'=>true,'tm'=>true,'medecin'=>true,'notaires'=>true,   'pharmaciens'=>true,'veterinaire'=>true,'edu'=>true,'gouv'=>true,'mil'=>true),
                'kw'=>array('com'=>true,'ind'=>true,'net'=>true,'org'=>true,'gov'=>true,'edu'=>true),
                'ky'=>array('com'=>true,'org'=>true,'net'=>true,'edu'=>true,'gov'=>true),
                'kn'=>array('com'=>true,'org'=>true,'net'=>true,'edu'=>true,'gov'=>true),
				'lc'=>array('gov'=>true,"co"=>true,"net"=>true,"nul"=>true,"org"=>true,"sc"=>true,"ac"=>true,"edu"=>true, "l"=>true,"p"=>true,"com"=>true),
                'ls'=>array('gov'=>true,"co"=>true,"net"=>true,"nul"=>true,"org"=>true,"sc"=>true,"ac"=>true),
				'ly'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'lv'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'lt'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'jp'=>array('ed'=>true,'gr'=>true,'or'=>true,'org' => true,"co"=>true,'ac'=>true,'ne'=>true,"go"=>true),
				'na'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'nc'=>true,
				'ne'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'nf'=>array('store'=>true,'web'=>true,"org"=>true,"firm"=>true,"info"=>true,"other"=>true,"per"=>true,"rec"=>true),
				'ni'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ng'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'nl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'nr'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'mc'=>array('tm'=>true,'asso'=>true),
				'mn'=>array('fr'=>true,'it'=>true),
                'mo'=>array('com'=>true,'edu'=>true,'gov'=>true,'net'=>true,'org'=>true),
                'mp'=>array('gov'=>true,'co'=>true),
                'mq'=>array('com'=>true,'edu'=>true,'gov'=>true,'net'=>true,'org'=>true),
				'ml'=>array('gov'=>true,'presse'=>true),
                'mm'=>array('com'=>true,'edu'=>true,'gov'=>true,'net'=>true,'org'=>true),
				'mr'=>true,
				'mg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'mk'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'mu'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'mt'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'mv'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'mw'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'mz'=>array('co'=>true,'net'=>true,'org'=>true,'ac'=>true,'gov'=>true,'edu'=>true),
				'jobs'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'invalid'=>true,
				'no'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'nu'=>array('de'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'nz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"mil"=>true,"ac"=>true),
				'or'=>true,
				'fi'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'fm'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				

				'md'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'team'=>true,
				'tech'=>true,
				'travel'=>true,
				'science'=>true,
				'promo'=>true,
				'vision'=>true,
				'diamonds'=>true,	
				'consulting'=>true,	
				'site'=>true,
				'ovh'=>true,
				'tj'=>true,'mil'=>true,
				'capetown'=>true,
				'space'=>true,
				'on'=>true,
				
				'aero'=>array("com"=>true,"org"=>true),
				'ma'=>array('co'=>true,'cu'=>true,'com'=>true,"org"=>true,"net"=>true,'gov'=>true),
				'ms'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'me'=>array('co'=>true,'com'=>true,"org"=>true,"biz"=>true,'net'=>true),
				'mobi'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'gbl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'mx'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"tv"=>true,"gob"=>true),
				'my'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,'mil'=>true),

				'om'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'pe'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"gob"=>true),
				'pl'=>array('info'=>true,'za'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,'wwp'=>true,'int'=>true),
				'pm'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'pn'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'pg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'pk'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'qa'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'pa'=>array('med'=>true,'nom'=>true,'com'=>true),
				'ps'=>array('com'=>true,'net'=>true,"edu"=>true,"gov"=>true,"plo"=>true,"sec"=>true),
				'pro'=>true,
				'pw'=>true,
				'gt'=>array('com'=>true,'net'=>true,"edu"=>true,"gob"=>true,"plo"=>true,"org"=>true,"mil"=>true,"ind"=>true),
                'py'=>array('coop'=>true,'com'=>true,"org"=>true,"net"=>true,"mil"=>true,'edu'=>true,"una"=>true,"gov"=>true),
				'kz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'kr'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"or"=>true),
				'vn'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'za'=>array('pl'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,'ac'=>true),
				'zw'=>array('ac'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'rs'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'ro'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'rw'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				
				'sa'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'sb'=>true,
                'sc'=>array('com','net','edu','gov','org'),
                'sd'=>array('com'=>true,'net'=>true,'org'=>true,'edu'=>true,'med'=>true,'tv'=>true,'gov'=>true,'info'=>true),
				'se'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sq'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sh'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'si'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sk'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'sl'=>array('com'=>true,'net'=>true,'org'=>true,'edu'=>true,'gov'=>true),
				'sm'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sn'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sr'=>array('fr'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'ss'=>array('edu'=>true,'com'=>true,'net'=>true,'biz'=>true,'gov'=>true,'org'=>true),
				'st'=>array('fr'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'so'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'su'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sv'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sy'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'sx'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'sz'=>array('co'=>true,'ac'=>true,'org'=>true,'gov'=>true),
				'td'=>array('de'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tf'=>array('de'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tg'=>array('de'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tl'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tm'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tn'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tc'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,'de'=>true),
				'tk'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'tw' => array('co' => true,"go"=>true,"in"=>true,"com"=>true,"net"=>true,"org"=>true),
				'th' =>array('go'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true,"or"=>true,"ac"=>true),
				'tr' =>array('info'=>true,'tv'=>true,'av'=>true,'bel'=>true,'dr'=>true,"bbs"=>true, 'gen'=>true,'name'=>true,'nc'=>true,'pol'=>true,'tel'=>true,'tsk'=>true,'web'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'to' =>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),				
				'tt'=>array('co'=>true),
				'tv'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'tz'=>array('co'=>true,'ac'=>true,'go'=>true,'or'=>true,'mil'=>true,'sc'=>true,'ne'=>true,
                    'hotel'=>true,'mobi'=>true,   'tv'=>true,'info'=>true,'me'=>true),
				'u'=>true,
            'ua'=>array('if'=>true,'kh'=>true,'ks'=>true,'km'=>true,'kv'=>true,'kr'=>true,'lg'=>true,'vl'=>true,
                'lv'=>true,'mk'=>true,'od'=>true,'pl'=>true,'rv'=>true,'sm'=>true,'te'=>true,'uz'=>true,'vn'=>true,
                'zp'=>true,'zt'=>true,'ck'=>true,'cn'=>true,'cr'=>true,'dp'=>true,'dn'=>true,'com'=>true,'edu'=>true,
                'gov'=>true,'net'=>true,'in'=>true,'pp'=>true,'org'=>true,'cherkasy'=>true,'chernigov'=>true,
                'chernivtsi'=>true,'crimea'=>true,'dnipropetrovsk'=>true,'donetsk'=>true,'ivano-frankivsk'=>true,
                'kharkiv'=>true,'kherson'=>true,'khmelnitskiy'=>true,'kyiv'=>true,'kirovograd'=>true,'lugansk'=>true,
                'lutsk'=>true,'lviv'=>true,'nikolaev'=>true,'odesa'=>true,'poltava'=>true,'rivne'=>true,
                'sevastopol'=>true,'yalta'=>true,'sumy'=>true,'ternopil'=>true,'uzhgorod'=>true,'vinnica'=>true,
                'zaporizhzhe'=>true,'zhitomir'=>true),
				'uk' => array('ac'=>true,'co'=>true,'gov'=>true,'ltd'=>true,'me'=>true,'mod'=>true,'mil'=>true,'net'=>true,'nhs'=>true,'nic'=>true,'org'=>true,'parliament'=>true,'plc'=>true,'sch'=>true),
				'us'=>array('ca'=>true,'wi'=>true,"com"=>true,"org"=>true,'sd'=>true),
				'uz'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'uy'=>array("biz"=>true,'co'=>true,'com'=>true,'gub'=>true,"org"=>true,"net"=>true),
				'vc'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'vi'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'vg'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'va'=>array('de'=>true,'co'=>true,'com'=>true,"ch"=>true,"org"=>true,"net"=>true),
                'vu'=>array('de'=>true,'co'=>true,'com'=>true,"ch"=>true,"org"=>true,"net"=>true),
				've'=>array('info'=>true,'co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'yu'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
                'ye'=>array('co'=>true,'com'=>true,"org"=>true,"net"=>true,"biz"=>true),
				'yt'=>true,
				'wf'=>true,
                'xn'=>true,
                'zm'=>array('ac'=>true,'co'=>true,'com'=>true,'edu'=>true,'gov'=>true,'net'=>'true','org'=>true,'sch'=>true),
            'athleta'=>true,'attorney'=>true,'auction'=>true,'beats'=>true,'beauty'=>true,'beer'=>true,'beknown'=>true,'bentley'=>true,'berlin'=>true,'black'=>true,'blackfriday'=>true,'blanco'=>true,'budapest'=>true,'bugatti'=>true,'buick'=>true,'build'=>true,'builders'=>true,'business'=>true,'cab'=>true,'cadillac'=>true,'cafe'=>true,'chanel'=>true,'changiairport'=>true,'claims'=>true,'cleaning'=>true,'clinic'=>true,'coach'=>true,'codes'=>true,'coffee'=>true,'college'=>true,'cologne'=>true,'comcast'=>true,'commbank'=>true,'community'=>true,'deal'=>true,'dealer'=>true,'deals'=>true,'degree'=>true,'delivery'=>true,'dell'=>true,'delmonte'=>true,'deloitte'=>true,'delta'=>true,'democrat'=>true,'dental'=>true,'dentist'=>true,'desi'=>true,'eurovision'=>true,'events'=>true,'everbank'=>true,'exposed'=>true,'express'=>true,'extraspace'=>true,'fail'=>true,'fairwinds'=>true,'faith'=>true,'family'=>true,'fan'=>true,'fans'=>true,'farm'=>true,'farmers'=>true,'fashion'=>true,'fast'=>true,'foo'=>true,'hair'=>true,'halal'=>true,'hockey'=>true,'holdings'=>true,'holiday'=>true,'home'=>true,'homedepot'=>true,'homegoods'=>true,'homes'=>true,'homesense'=>true,'honda'=>true,'honeywell'=>true,'horse'=>true,'hospital'=>true,'hot'=>true,'hoteis'=>true,'hotel'=>true,'hoteles'=>true,'hotels'=>true,'hotmail'=>true,'how'=>true,'hsbc'=>true,'inc'=>true,'indians'=>true,'industries'=>true,'infiniti'=>true,'infosys'=>true,'infy'=>true,'itau'=>true,'itv'=>true,'pictures'=>true,

				
	
	
		);
		if($getpartOnly){
			foreach ($tlds as $key => $part) {
				$doms[$key]=true;
				if(is_array($part)){
                    foreach ($part as $a => $b) {
						$doms["$a.$key"]=true;
					}
					continue;
				}
			}
			return $doms;
		}
	
		$parts = explode('.', $domain);
		$tmp = $tlds;
		// travers the tree in reverse order, from right to left
		foreach (array_reverse($parts) as $key => $part) {
			if (isset($tmp[$part])) {
				$tmp = $tmp[$part];
			} else {
				break;
			}
		}
		if($getpartOnly){return $tmp;}
	
		$get=implode('.', array_slice($parts, - $key - 1));
		if(substr($get, 0,1)=="."){$get=substr($get, 1,strlen($get)); }
		return $get;
	}	
	
}

?>