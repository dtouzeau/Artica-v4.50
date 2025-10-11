<?php // UTF8 à è

/** GeoIP.php
*
* Copyright (C) 2011 Alejandro Lapeyre
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License, or (at your option) any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/
xGeoIP::__static();

class xGeoIP {

	/**
	 * enum GeoIPOptions
	 */
	const GEOIP_STANDARD = 0;
	const GEOIP_MEMORY_CACHE = 1;
	const GEOIP_CHECK_CACHE = 2;
	const GEOIP_INDEX_CACHE = 4;
	const GEOIP_MMAP_CACHE = 8;

	/**
	 * enum DdatabaseType
	 */
	const GEOIP_COUNTRY_EDITION = 1;
	const GEOIP_CITY_EDITION_REV1 = 2;
	const GEOIP_REGION_EDITION_REV1 = 3;
	const GEOIP_ISP_EDITION = 4;
	const GEOIP_ORG_EDITION = 5;
	const GEOIP_CITY_EDITION_REV0 = 6;
	const GEOIP_REGION_EDITION_REV0 = 7;
	const GEOIP_PROXY_EDITION = 8;
	const GEOIP_ASNUM_EDITION = 9;
	const GEOIP_NETSPEED_EDITION = 10;
	const GEOIP_DOMAIN_EDITION = 11;
	const GEOIP_COUNTRY_EDITION_V6 = 12;
	const GEOIP_LOCATIONA_EDITION = 13;
	const GEOIP_ACCURACYRADIUS_EDITION = 14;
	const GEOIP_CITYCONFIDENCE_EDITION = 15;
	const GEOIP_CITYCONFIDENCEDIST_EDITION = 16;
	const GEOIP_LARGE_COUNTRY_EDITION = 17;
	const GEOIP_LARGE_COUNTRY_EDITION_V6 = 18;

	const GEOIP_ASNUM_EDITION_V6 = 21;
	const GEOIP_ISP_EDITION_V6 = 22;
	const GEOIP_ORG_EDITION_V6 = 23;
	const GEOIP_DOMAIN_EDITION_V6 = 24;
	const GEOIP_LOCATIONA_EDITION_V6 = 25;
	const GEOIP_REGISTRAR_EDITION = 26;
	const GEOIP_REGISTRAR_EDITION_V6 = 27;
	const GEOIP_USERTYPE_EDITION = 28;
	const GEOIP_USERTYPE_EDITION_V6 = 29;
	const GEOIP_CITY_EDITION_REV1_V6 = 30;
	const GEOIP_CITY_EDITION_REV0_V6 = 31;
	const GEOIP_NETSPEED_EDITION_REV1 = 32;
	const GEOIP_NETSPEED_EDITION_REV1_V6 = 33;

	const GEOIP_UNKNOWN_CONF = 0x7f;
	const GEOIP_UNKNOWN_ACCURACY_RADIUS = 0x3ff;

	const PACKAGE_VERSION = '1.4.8';

	const DB_ID = 0x01;
	const DB_COUNTRY = 0x02;
	const DB_REGION = 0x04;
	const DB_CITY = 0x08;
	const DB_NAME = 0x10;
	const DB_V6 = 0x80;

	public static $GeoIP_country_code;
	public static $GeoIP_country_name;
	public static $GeoIP_country_code_to_number;
	public static $GeoIP_country_continent;
	public static $GeoIP_country_code3;
	public static $DBInfo;
	public static $custom_directory;

	public $databaseInfo;
	public $databaseDescription;
	public $databaseEdition;

	public $charset = 'UTF-8';
	public $filename;

	protected $flags;
	protected $cache;
	protected $index_cache;
	protected $databaseType;
	protected $databaseSegments;
	protected $record_length;
	protected $filesize;
	protected $filehandle;
	protected $dyn_seg_size;
	protected $seek_record_v4_function;
	protected $seek_record_v6_function;
	protected $record_cache;
	protected $seek_record_offset = 0;
	protected $DBFlags;


	function debugInfo()
	{
		return array(
			'filename' => $this->filename,
			'databaseInfo' => $this->databaseInfo,
			'databaseDescription' => $this->databaseDescription,
			'flags' => $this->flags,
			'databaseType' => $this->databaseType,
			'cache' => isset($this->cache) ? (strlen($this->cache) . " bytes") : "null",
			'index_cache' => isset($this->index_cache) ? (strlen($this->index_cache) . " bytes") : "null",
			'databaseSegments' => $this->databaseSegments,
			'record_length' => $this->record_length,
			'filesize' => $this->filesize,
			'filehandle' => (empty($this->filehandle)) ? 'false' : 'yes',
			'dyn_seg_size' => $this->dyn_seg_size,
			'seek_record_offset' => $this->seek_record_offset,
			'DBFlags' => "0x" . dechex($this->DBFlags),
			'seek_record_v4_function' => $this->seek_record_v4_function,
			'seek_record_v6_function' => $this->seek_record_v6_function,
			'PACKAGE_VERSION' => self::PACKAGE_VERSION,
			);
	}


	// =====================================================
	// PUBLIC STATIC METHODS
	// =====================================================

	static function __static()
	{
		if (isset(self::$GeoIP_country_code)) return;

		self::$GeoIP_country_code = array(
			'--',"AP","EU","AD","AE","AF","AG","AI","AL","AM","CW",
			"AO","AQ","AR","AS","AT","AU","AW","AZ","BA","BB",
			"BD","BE","BF","BG","BH","BI","BJ","BM","BN","BO",
			"BR","BS","BT","BV","BW","BY","BZ","CA","CC","CD",
			"CF","CG","CH","CI","CK","CL","CM","CN","CO","CR",
			"CU","CV","CX","CY","CZ","DE","DJ","DK","DM","DO",
			"DZ","EC","EE","EG","EH","ER","ES","ET","FI","FJ",
			"FK","FM","FO","FR","SX","GA","GB","GD","GE","GF",
			"GH","GI","GL","GM","GN","GP","GQ","GR","GS","GT",
			"GU","GW","GY","HK","HM","HN","HR","HT","HU","ID",
			"IE","IL","IN","IO","IQ","IR","IS","IT","JM","JO",
			"JP","KE","KG","KH","KI","KM","KN","KP","KR","KW",
			"KY","KZ","LA","LB","LC","LI","LK","LR","LS","LT",
			"LU","LV","LY","MA","MC","MD","MG","MH","MK","ML",
			"MM","MN","MO","MP","MQ","MR","MS","MT","MU","MV",
			"MW","MX","MY","MZ","NA","NC","NE","NF","NG","NI",
			"NL","NO","NP","NR","NU","NZ","OM","PA","PE","PF",
			"PG","PH","PK","PL","PM","PN","PR","PS","PT","PW",
			"PY","QA","RE","RO","RU","RW","SA","SB","SC","SD",
			"SE","SG","SH","SI","SJ","SK","SL","SM","SN","SO",
			"SR","ST","SV","SY","SZ","TC","TD","TF","TG","TH",
			"TJ","TK","TM","TN","TO","TL","TR","TT","TV","TW",
			"TZ","UA","UG","UM","US","UY","UZ","VA","VC","VE",
			"VG","VI","VN","VU","WF","WS","YE","YT","RS","ZA",
			"ZM","ME","ZW","A1","A2","O1","AX","GG","IM","JE",
			"BL","MF", "BQ",
			);


		self::$GeoIP_country_code_to_number = array_flip(self::$GeoIP_country_code);

		self::$GeoIP_country_name = array(
'N/A', "Asia/Pacific Region","Europe","Andorra","United Arab Emirates","Afghanistan","Antigua and Barbuda","Anguilla","Albania","Armenia","Curaçao",
	"Angola","Antarctica","Argentina","American Samoa","Austria","Australia","Aruba","Azerbaijan","Bosnia and Herzegovina","Barbados",
	"Bangladesh","Belgium","Burkina Faso","Bulgaria","Bahrain","Burundi","Benin","Bermuda","Brunei Darussalam","Bolivia",
	"Brazil","Bahamas","Bhutan","Bouvet Island","Botswana","Belarus","Belize","Canada","Cocos (Keeling) Islands","Congo, The Democratic Republic of the",
	"Central African Republic","Congo","Switzerland","Cote D'Ivoire","Cook Islands","Chile","Cameroon","China","Colombia","Costa Rica",
	"Cuba","Cape Verde","Christmas Island","Cyprus","Czech Republic","Germany","Djibouti","Denmark","Dominica","Dominican Republic",
	"Algeria","Ecuador","Estonia","Egypt","Western Sahara","Eritrea","Spain","Ethiopia","Finland","Fiji",
	"Falkland Islands (Malvinas)","Micronesia, Federated States of","Faroe Islands","France","Sint Maarten (Dutch part)","Gabon","United Kingdom","Grenada","Georgia","French Guiana",
	"Ghana","Gibraltar","Greenland","Gambia","Guinea","Guadeloupe","Equatorial Guinea","Greece","South Georgia and the South Sandwich Islands","Guatemala",
	"Guam","Guinea-Bissau","Guyana","Hong Kong","Heard Island and McDonald Islands","Honduras","Croatia","Haiti","Hungary","Indonesia",
	"Ireland","Israel","India","British Indian Ocean Territory","Iraq","Iran, Islamic Republic of","Iceland","Italy","Jamaica","Jordan",
	"Japan","Kenya","Kyrgyzstan","Cambodia","Kiribati","Comoros","Saint Kitts and Nevis","Korea, Democratic People's Republic of","Korea, Republic of","Kuwait",
	"Cayman Islands","Kazakhstan","Lao People's Democratic Republic","Lebanon","Saint Lucia","Liechtenstein","Sri Lanka","Liberia","Lesotho","Lithuania",
	"Luxembourg","Latvia","Libyan Arab Jamahiriya","Morocco","Monaco","Moldova, Republic of","Madagascar","Marshall Islands","Macedonia","Mali",
	"Myanmar","Mongolia","Macau","Northern Mariana Islands","Martinique","Mauritania","Montserrat","Malta","Mauritius","Maldives",
	"Malawi","Mexico","Malaysia","Mozambique","Namibia","New Caledonia","Niger","Norfolk Island","Nigeria","Nicaragua",
	"Netherlands","Norway","Nepal","Nauru","Niue","New Zealand","Oman","Panama","Peru","French Polynesia",
	"Papua New Guinea","Philippines","Pakistan","Poland","Saint Pierre and Miquelon","Pitcairn Islands","Puerto Rico","Palestinian Territory","Portugal","Palau",
	"Paraguay","Qatar","Reunion","Romania","Russian Federation","Rwanda","Saudi Arabia","Solomon Islands","Seychelles","Sudan",
	"Sweden","Singapore","Saint Helena","Slovenia","Svalbard and Jan Mayen","Slovakia","Sierra Leone","San Marino","Senegal","Somalia","Suriname",
	"Sao Tome and Principe","El Salvador","Syrian Arab Republic","Swaziland","Turks and Caicos Islands","Chad","French Southern Territories","Togo","Thailand",
	"Tajikistan","Tokelau","Turkmenistan","Tunisia","Tonga","Timor-Leste","Turkey","Trinidad and Tobago","Tuvalu","Taiwan",
	"Tanzania, United Republic of","Ukraine","Uganda","United States Minor Outlying Islands","United States","Uruguay","Uzbekistan","Holy See (Vatican City State)","Saint Vincent and the Grenadines","Venezuela",
	"Virgin Islands, British","Virgin Islands, U.S.","Vietnam","Vanuatu","Wallis and Futuna","Samoa","Yemen","Mayotte","Serbia","South Africa",
	"Zambia","Montenegro","Zimbabwe","Anonymous Proxy","Satellite Provider","Other","Aland Islands","Guernsey","Isle of Man","Jersey",
  "Saint Barthelemy","Saint Martin", "Bonaire, Saint Eustatius and Saba"
		);

		self::$GeoIP_country_continent = array(
  "--", "AS","EU","EU","AS","AS","NA","NA","EU","AS","NA",
        "AF","AN","SA","OC","EU","OC","NA","AS","EU","NA",
        "AS","EU","AF","EU","AS","AF","AF","NA","AS","SA",
        "SA","NA","AS","AN","AF","EU","NA","NA","AS","AF",
        "AF","AF","EU","AF","OC","SA","AF","AS","SA","NA",
        "NA","AF","AS","AS","EU","EU","AF","EU","NA","NA",
        "AF","SA","EU","AF","AF","AF","EU","AF","EU","OC",
        "SA","OC","EU","EU","NA","AF","EU","NA","AS","SA",
        "AF","EU","NA","AF","AF","NA","AF","EU","AN","NA",
        "OC","AF","SA","AS","AN","NA","EU","NA","EU","AS",
        "EU","AS","AS","AS","AS","AS","EU","EU","NA","AS",
        "AS","AF","AS","AS","OC","AF","NA","AS","AS","AS",
        "NA","AS","AS","AS","NA","EU","AS","AF","AF","EU",
        "EU","EU","AF","AF","EU","EU","AF","OC","EU","AF",
        "AS","AS","AS","OC","NA","AF","NA","EU","AF","AS",
        "AF","NA","AS","AF","AF","OC","AF","OC","AF","NA",
        "EU","EU","AS","OC","OC","OC","AS","NA","SA","OC",
        "OC","AS","AS","EU","NA","OC","NA","AS","EU","OC",
        "SA","AS","AF","EU","EU","AF","AS","OC","AF","AF",
        "EU","AS","AF","EU","EU","EU","AF","EU","AF","AF",
        "SA","AF","NA","AS","AF","NA","AF","AN","AF","AS",
        "AS","OC","AS","AF","OC","AS","EU","NA","OC","AS",
        "AF","EU","AF","OC","NA","SA","AS","EU","NA","SA",
        "NA","NA","AS","OC","OC","OC","AS","AF","EU","AF",
        "AF","EU","AF","--","--","--","EU","EU","EU","EU",
        "NA","NA","NA",
        );

		self::$GeoIP_country_code3 = array(
"--","AP","EU","AND","ARE","AFG","ATG","AIA","ALB","ARM","CUW",
	"AGO","ATA","ARG","ASM","AUT","AUS","ABW","AZE","BIH","BRB",
	"BGD","BEL","BFA","BGR","BHR","BDI","BEN","BMU","BRN","BOL",
	"BRA","BHS","BTN","BVT","BWA","BLR","BLZ","CAN","CCK","COD",
	"CAF","COG","CHE","CIV","COK","CHL","CMR","CHN","COL","CRI",
	"CUB","CPV","CXR","CYP","CZE","DEU","DJI","DNK","DMA","DOM",
	"DZA","ECU","EST","EGY","ESH","ERI","ESP","ETH","FIN","FJI",
	"FLK","FSM","FRO","FRA","SXM","GAB","GBR","GRD","GEO","GUF",
	"GHA","GIB","GRL","GMB","GIN","GLP","GNQ","GRC","SGS","GTM",
	"GUM","GNB","GUY","HKG","HMD","HND","HRV","HTI","HUN","IDN",
	"IRL","ISR","IND","IOT","IRQ","IRN","ISL","ITA","JAM","JOR",
	"JPN","KEN","KGZ","KHM","KIR","COM","KNA","PRK","KOR","KWT",
	"CYM","KAZ","LAO","LBN","LCA","LIE","LKA","LBR","LSO","LTU",
	"LUX","LVA","LBY","MAR","MCO","MDA","MDG","MHL","MKD","MLI",
	"MMR","MNG","MAC","MNP","MTQ","MRT","MSR","MLT","MUS","MDV",
	"MWI","MEX","MYS","MOZ","NAM","NCL","NER","NFK","NGA","NIC",
	"NLD","NOR","NPL","NRU","NIU","NZL","OMN","PAN","PER","PYF",
	"PNG","PHL","PAK","POL","SPM","PCN","PRI","PSE","PRT","PLW",
	"PRY","QAT","REU","ROU","RUS","RWA","SAU","SLB","SYC","SDN",
	"SWE","SGP","SHN","SVN","SJM","SVK","SLE","SMR","SEN","SOM",
	"SUR","STP","SLV","SYR","SWZ","TCA","TCD","ATF","TGO","THA",
	"TJK","TKL","TKM","TUN","TON","TLS","TUR","TTO","TUV","TWN",
	"TZA","UKR","UGA","UMI","USA","URY","UZB","VAT","VCT","VEN",
	"VGB","VIR","VNM","VUT","WLF","WSM","YEM","MYT","SRB","ZAF",
	"ZMB","MNE","ZWE","A1","A2","O1","ALA","GGY","IMN","JEY",
  "BLM","MAF", "BES",
	);


		self::$DBInfo = array(
			self::GEOIP_COUNTRY_EDITION => array(
				'description' => "GeoIP Country Edition",
				'filename' => 'GeoIP.dat',
				'flags' => self::DB_ID + self::DB_COUNTRY,
				'record_length' => 3,
				'segments' => 16776960, // COUNTRY_BEGIN
				),
			self::GEOIP_CITY_EDITION_REV1 => array(
				'description' => "GeoIP City Edition, Rev 1",
				'filename' => 'GeoIPCity.dat',
				'flags' => self::DB_CITY,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_REGION_EDITION_REV1 => array(
				'description' => "GeoIP Region Edition, Rev 1",
				'filename' => 'GeoIPRegion.dat',
				'flags' => self::DB_REGION,
				'record_length' => 3,
				'segments' => 16000000, // STATE_BEGIN_REV1
				),
			self::GEOIP_ISP_EDITION => array(
				'description' => "GeoIP ISP Edition",
				'filename' => 'GeoIPISP.dat',
				'flags' => self::DB_NAME,
				'record_length' => 4,
				'segments' => null,
				),
			self::GEOIP_ORG_EDITION => array(
				'description' => "GeoIP Organization Edition",
				'filename' => 'GeoIPOrg.dat',
				'flags' => self::DB_NAME,
				'record_length' => 4,
				'segments' => null,
				),
			self::GEOIP_CITY_EDITION_REV0 => array(
				'description' => "GeoIP City Edition, Rev 0",
				'filename' => 'GeoIPCity.dat',
				'flags' => self::DB_CITY,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_REGION_EDITION_REV0 => array(
				'description' => "GeoIP Region Edition, Rev 0",
				'filename' => 'GeoIPRegion.dat',
				'flags' => self::DB_REGION,
				'record_length' => 3,
				'segments' => 16700000, // STATE_BEGIN_REV0,
				),
			self::GEOIP_PROXY_EDITION => array(
				'description' => "GeoIP Proxy Edition",
				'filename' => 'GeoIPProxy.dat',
				'flags' => self::DB_ID,
				'record_length' => 3,
				'segments' => 16776960, // COUNTRY_BEGIN
				),
			self::GEOIP_ASNUM_EDITION	=> array(
				'description' => "GeoIP ASNum Edition",
				'filename' => 'GeoIPASNum.dat',
				'flags' => self::DB_NAME,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_NETSPEED_EDITION => array(
				'description' => "GeoIP Netspeed Edition",
				'filename' => 'GeoIPNetSpeed.dat',
				'flags' => self::DB_ID,
				'record_length' => 3,
				'segments' => 16776960, // COUNTRY_BEGIN
				),
			self::GEOIP_DOMAIN_EDITION => array(
				'description' => "GeoIP Domain Name Edition",
				'filename' => 'GeoIPDomain.dat',
				'flags' => self::DB_NAME,
				'record_length' => 4,
				'segments' => null,
				),
			self::GEOIP_COUNTRY_EDITION_V6 => array(
				'description' => "GeoIP Country V6 Edition",
				'filename' => 'GeoIPv6.dat',
				'flags' => self::DB_ID + self::DB_COUNTRY + self::DB_V6,
				'record_length' => 3,
				'segments' => 16776960, // COUNTRY_BEGIN
				),
			self::GEOIP_LOCATIONA_EDITION => array(
				'description' => "GeoIP LocationID ASCII Edition",
				'filename' => 'GeoIPLocA.dat',
				'flags' => self::DB_NAME,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_ACCURACYRADIUS_EDITION => array(
				'description' => "GeoIP Accuracy Radius Edition",
				'filename' => 'GeoIPDistance.dat',
				'flags' => self::DB_ID,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_CITYCONFIDENCE_EDITION => array(
				'description' => "GeoIP City with Confidence Edition",
				'filename' => 'GeoIPCityConfidence.dat',
				'flags' => self::DB_CITY,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_CITYCONFIDENCEDIST_EDITION => array(
				'description' => "GeoIP City with Confidence and Accuracy Edition",
				'filename' => 'GeoIPCityConfidenceDist.dat',
				'flags' => self::DB_CITY,
				'record_length' => 4,
				'segments' => null,
				),
			self::GEOIP_LARGE_COUNTRY_EDITION => array(
				'description' => "GeoIP Large Country Edition",
				'filename' => 'GeoIP.dat',
				'flags' => self::DB_ID + self::DB_COUNTRY,
				'record_length' => 3,
				'segments' => 16515072, // LARGE_COUNTRY_BEGIN
				),
			self::GEOIP_LARGE_COUNTRY_EDITION_V6 => array(
				'description' => "GeoIP Large Country V6 Edition",
				'filename' => 'GeoIPv6.dat',
				'flags' => self::DB_ID + self::DB_COUNTRY + self::DB_V6,
				'record_length' => 3,
				'segments' => 16515072, // LARGE_COUNTRY_BEGIN
				),
			self::GEOIP_ASNUM_EDITION_V6 => array(
				'description' => "GeoIP ASNum V6 Edition",
				'filename' => 'GeoIPASNumv6.dat',
				'flags' => self::DB_NAME + self::DB_V6,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_ISP_EDITION_V6 => array(
				'description' => "GeoIP ISP V6 Edition",
				'filename' => 'GeoIPISPv6.dat',
				'flags' => self::DB_NAME + self::DB_V6,
				'record_length' => 4,
				'segments' => null,
				),
			self::GEOIP_ORG_EDITION_V6 => array(
				'description' => "GeoIP Organization V6 Edition",
				'filename' => 'GeoIPOrgv6.dat',
				'flags' => self::DB_NAME + self::DB_V6,
				'record_length' => 4,
				'segments' => null,
				),
			self::GEOIP_DOMAIN_EDITION_V6	=> array(
				'description' => "GeoIP Domain Name V6 Edition",
				'filename' => 'GeoIPDomainv6.dat',
				'flags' => self::DB_NAME + self::DB_V6,
				'record_length' => 4,
				'segments' => null,
				),
			self::GEOIP_LOCATIONA_EDITION_V6 => array(
				'description' => "GeoIP LocationID ASCII V6 Edition",
				'filename' => 'GeoIPLocAv6.dat',
				'flags' => self::DB_NAME + self::DB_V6,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_REGISTRAR_EDITION => array(
				'description' => "GeoIP Registrar Edition",
				'filename' => 'GeoIPRegistrar.dat',
				'flags' => self::DB_NAME,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_REGISTRAR_EDITION_V6 => array(
				'description' => "GeoIP Registrar V6 Edition",
				'filename' => 'GeoIPRegistrarv6.dat',
				'flags' => self::DB_NAME + self::DB_V6,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_USERTYPE_EDITION => array(
				'description' => "GeoIP UserType Edition",
				'filename' => 'GeoIPUserType.dat',
				'flags' => self::DB_NAME,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_USERTYPE_EDITION_V6 => array(
				'description' => "GeoIP UserType V6 Edition",
				'filename' => 'GeoIPUserTypev6.dat',
				'flags' => self::DB_NAME + self::DB_V6,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_CITY_EDITION_REV1_V6 => array(
				'description' => "GeoIP City Edition V6, Rev 1",
				'filename' => 'GeoIPCityv6.dat',
				'flags' => self::DB_CITY + self::DB_V6,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_CITY_EDITION_REV0_V6 => array(
				'description' => "GeoIP City Edition V6, Rev 0",
				'filename' => 'GeoIPCityv6.dat',
				'flags' => self::DB_CITY + self::DB_V6,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_NETSPEED_EDITION_REV1 => array(
				'description' => "GeoIP Netspeed Edition, Rev 1",
				'filename' => 'GeoIPNetspeedCell.dat',
				'flags' => self::DB_NAME,
				'record_length' => 3,
				'segments' => null,
				),
			self::GEOIP_NETSPEED_EDITION_REV1_V6 => array(
				'description' => "GeoIP Netspeed Edition V6, Rev1",
				'filename' => 'GeoIPNetseedCellv6.dat',
				'flags' => self::DB_NAME + self::DB_V6,
				'record_length' => 3,
				'segments' => null,
				),
		);
	}

	/**
	 * @param String $filename
	 * @param int $flags
	 */
	function __construct($filename, $flags = 0)
	{
		$clean_filename = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filename);

		$this->filehandle = @fopen($filename,"rb");
		if (false === $this->filehandle) {
			throw new Exception(sprintf('Can not open %s', $clean_filename));
		}

		$data = self::_getDatabaseInfo($this->filehandle);
		if (empty($data)) {
			throw new Exception(sprintf('Unrecognized database type in %s', $clean_filename));
		}

		foreach ($data as $name => $value) {
			$this->$name = $value;
		}

		$this->filename = $filename;
		$this->flags = $flags;

		$flags |= self::GEOIP_INDEX_CACHE;
		$f = $flags & (self::GEOIP_INDEX_CACHE | self::GEOIP_MEMORY_CACHE);
		if (null !== self::$DBInfo[$this->databaseType]['segments']) {
			$f |= 0x100;
		}

		switch ($f) {
			case 0:
			case 0x100:
				break;
			case self::GEOIP_INDEX_CACHE:
				$this->index_cache = self::_read($this->filehandle, 0, $this->databaseSegments * $this->record_length * 2);
				break;
			default:
				$this->index_cache = $this->cache = self::_read($this->filehandle, 0, $this->filesize);
				fclose($this->filehandle);
				$this->filehandle = false;
				break;
		}

		$this->record_cache = array_fill(0, 256, null);

		$this->seek_record_v4_function = (3 === $this->record_length)
			? '_seek_record3_v4_memory'
			: '_seek_record4_v4_memory';

		if ($this->DBFlags & self::DB_V6) {
			$this->seek_record_v6_function = (3 === $this->record_length)
				? '_seek_record3_V6_memory'
				: '_seek_record4_V6_memory';
			$this->_init_seek_record_offset();
		}

		$this->databaseEdition = $this->databaseType;
	}

	static function createFromFile($filename, $flags = 0)
	{
		return new self($filename, $flags);
	}


	static function GeoIP_id_by_code($country_code)
	{
		return isset(self::$GeoIP_country_code_to_number[$country_code]) ?
			self::$GeoIP_country_code_to_number[$country_code] : NULL;
	}

	/**
	 * return full name of country
	 */
	static function GeoIP_country_name_by_id($id)
	{
       /* return NULL also even for index 0 for backward compatibility */
		if ($id <= 0 || !isset(self::$GeoIP_country_name[$id])) {
			return NULL;
		}
		return mb_string_convert(self::$GeoIP_country_name[$id], self::charset, 'UTF-8');
	}

	static function GeoIP_country_name_by_code($country_code)
	{
		return self::GeoIP_country_name_by_id(self::GeoIP_id_by_code($country_code));
	}


	/**
	 * @param String $addr
	 */
	function GeoIP_id_by_addr($addr)
	{
		if ($this->DBFlags & self::DB_ID) {
			$id = $this->_GeoIP_seek_record($addr);
			if ($id > 0) return $id;
		}
		return NULL;
	}


	/**
	 * @param String $name El nombre de host.
	 * @return Int|Bool False si no se encuentra
	 */
	function GeoIP_id_by_name($name) {return $this->GeoIP_id_by_addr(gethostbyname($name));}
	function GeoIP_id_by_addr_V6($addr) {return $this->GeoIP_id_by_addr($addr);}
	function GeoIP_id_by_name_V6($name) {return $this->GeoIP_id_by_name($name);}



	function GeoIP_country_code_by_addr($addr)
	{
		if ($this->DBFlags & self::DB_COUNTRY) {
			$country_id = $this->_GeoIP_seek_record($addr);
			if ($country_id > 0) return self::$GeoIP_country_code[$country_id];
		}
		return NULL;
	}

	function GeoIP_country_code_by_name($name) {return $this->GeoIP_country_code_by_name(gethostbyname($name));}
	function GeoIP_country_code_by_addr_v6($addr) {return $this->GeoIP_country_code_by_addr($addr);}
	function GeoIP_country_code_by_name_v6($name) {return $this->GeoIP_country_code_by_name($name);}


	function GeoIP_region_by_addr($addr)
	{
		if ($this->DBFlags & self::DB_REGION) {
			$seek_region = $this->_GeoIP_seek_record($addr);
			if ($seek_region > 0) return $this->_get_region($seek_region);
		}
		return NULL;
	}

	function GeoIP_region_by_name($name) {return $this->GeoIP_region_by_addr(gethostbyname($name));}
	function GeoIP_region_by_addr_v6($addr) {return $this->GeoIP_region_by_addr($addr);}
	function GeoIP_region_by_name_v6($name) {return $this->GeoIP_region_by_name($name);}


	function GeoIP_name_by_addr($addr)
	{
		if ($this->DBFlags & self::DB_NAME) {
			$seek_org = $this->_GeoIP_seek_record($addr);
			if ($seek_org > 0) return $this->_get_name($seek_org);
		}
		return NULL;
	}

	function GeoIP_name_by_name($name) {return $this->GeoIP_name_by_addr(gethostbyname($name));}
	function GeoIP_name_by_addr_V6($addr) {return $this->GeoIP_name_by_addr($addr);}
	function GeoIP_name_by_name_V6($addr) {return $this->GeoIP_name_by_name($addr);}

	/**
	 * @param String $addr
	 * @return Array
	 */
	function GeoIP_record_by_addr($addr)
	{
		if ($this->DBFlags & self::DB_CITY) {
			$seek_record = $this->_GeoIP_seek_record($addr);
			if ($seek_record > 0) return $this->_extract_record($seek_record, NULL);
		}
		return NULL;
	}

	function GeoIP_record_by_name($name) {return $this->GeoIP_record_by_addr(gethostbyname($name));}
	function GeoIP_record_by_addr_V6($addr) {return $this->GeoIP_record_by_addr($addr);}
	function GeoIP_record_by_name_V6($addr) {return $this->GeoIP_record_by_name($addr);}


/*
    6to4 addresses -- 2002::/16 (take the next 32 bits, translate it into an IPv4 address, and look it up)
    Teredo addresses -- 2001:0000::/32 (take the last 32 bits, do a bitwise not, translate it into an IPv4 address, and look it up)
*/

	/**
	 * @param Int|String $addr
	 * @return Int 0 if not found
	 */
	protected function _GeoIP_seek_record($addr)
	{
		$fn = $this->seek_record_v4_function;
		if (is_int($addr)) {
			$ipnum = $addr;
		} else if (false === $ipnum = ip2long($addr)) {
			$ipnum = @inet_pton($addr);
			if (false === $ipnum) return 0;

			if (4 === strlen($ipnum)) {
				$ipnum = ord($ipnum[3])
					+ (ord($ipnum[2]) << 8)
					+ (ord($ipnum[1]) << 16)
					+ (ord($ipnum[0]) << 24);

			} else if (16 === strlen($ipnum)) {
				if ($ipnum[0] === "\x20" && $ipnum[1] === "\x02") { // 6TO4
					$ipnum = ord($ipnum[5])
						+ (ord($ipnum[4]) << 8)
						+ (ord($ipnum[3]) << 16)
						+ (ord($ipnum[2]) << 24);

				} else if (0 === substr_compare($ipnum, "\x20\x01\0\0", 0 ,4)) { // TEREDO
					$ipnum = ord($ipnum[15])
						+ (ord($ipnum[14]) << 8)
						+ (ord($ipnum[13]) << 16)
						+ (ord($ipnum[12]) << 24);
					$ipnum ^= 0xffffffff;

				} else if ( (0 === substr_compare($ipnum, "\0\0\0\0\0\0\0\0\0\0\0\0", 0, 12))
					|| (0 === substr_compare($ipnum, "\0\0\0\0\0\0\0\0\0\0\xff\xff", 0, 12)) ) {
					$ipnum = ord($ipnum[15])
						+ (ord($ipnum[14]) << 8)
						+ (ord($ipnum[13]) << 16)
						+ (ord($ipnum[12]) << 24);

				} else if ($this->DBFlags & self::DB_V6) {
					$fn = $this->seek_record_v6_function;
				} else {
					return 0;
				}
			} else {
				return 0;
			}
		}
		return $this->$fn($ipnum);
	}

	/**
	 * Seek record with memory_buffer and record_length = 3
	 *
	 * @param Int $ipnum (unsigned long)
	 * @return Int
	 */
	protected function _seek_record3_v4_memory($ipnum)
	{
		// acceder a variables locales es más rápido
		$b = $this->index_cache;
		$s = $this->databaseSegments;

		$x = $this->seek_record_offset;
		if ($x >= $s) return $x - $s;

		$m = ($ipnum >> 24) & 0XFF;
		if (isset($this->record_cache[$m])) {
			$x = $this->record_cache[$m];
		} else {
			for ($bit = 0x80; $bit > 0; $bit >>= 1) {
				$x *= 6;
				if ($m & $bit) $x += 3;
				$x = ord($b[$x]) + (ord($b[$x+1]) << 8) + (ord($b[$x+2]) << 16);
				if ($x >= $s) break;
			}
			$this->record_cache[$m] = $x;
		}
		if ($x >= $s) return $x - $s;
		for ($bit = 0x800000; $bit > 0; $bit >>= 1) {
			$x *= 6;
			if ($ipnum & $bit) $x += 3;
			$x = ord($b[$x]) + (ord($b[$x+1]) << 8) + (ord($b[$x+2]) << 16);
			if ($x >= $s) return $x - $s;
		}
		/* shouldn't reach here */
		return 0;
	}

	/**
	 * Seek record with memory_buffer and record_length = 4
	 *
	 * @param Int $ipnum (unsigned long)
	 * @return Int
	 */
	protected function _seek_record4_v4_memory($ipnum)
	{
		// acceder a variables locales es más rápido
		$b = $this->index_cache;
		$s = $this->databaseSegments;

		$x = $this->seek_record_offset;
		if ($x >= $s) return $x - $s;

		$m = ($ipnum >> 24) & 0XFF;
		if (isset($this->record_cache[$m])) {
			$x = $this->record_cache[$m];
		} else {
			for ($bit = 0x80; $bit > 0; $bit >>= 1) {
				$x *= 8;
				if ($m & $bit) $x += 4;
				$x = ord($b[$x]) + (ord($b[$x+1]) << 8) + (ord($b[$x+2]) << 16) + (ord($b[$x+3]) << 24);
				if ($x >= $s) break;
			}
			$this->record_cache[$m] = $x;
		}
		if ($x >= $s) return $x - $s;
		for ($bit = 0x800000; $bit > 0; $bit >>= 1) {
			$x *= 8;
			if ($ipnum & $bit) $x += 4;
			$x = ord($b[$x]) + (ord($b[$x+1]) << 8) + (ord($b[$x+2]) << 16) + (ord($b[$x+3]) << 24);
			if ($x >= $s) return $x - $s;
		}
		/* shouldn't reach here */
		return 0;
	}

	/**
	 * @param string $ipnum
	 *
	 * @return int
	 */
	protected function _seek_record3_v6_memory($ipnum)
	{
		$b = $this->index_cache;
		$s = $this->databaseSegments;
		$x = 0;
		for ($i = 0; $i < 16; $i++) {
			$p = ord($ipnum[$i]);
			for ($depth = 7; $depth >= 0; $depth--) {
				$x *= 6;
				if ($p & (1 << $depth)) $x += 3;
				$x = ord($b[$x]) + (ord($b[$x+1]) << 8) + (ord($b[$x+2]) << 16);
				if ($x >= $s) return $x - $s;
			}
		}
		/* shouldn't reach here */
		return 0;
	}


	/**
	 * @param string $ipnum
	 *
	 * @return int
	 */
	protected function _seek_record4_v6_memory($ipnum)
	{
		$b = $this->index_cache;
		$s = $this->databaseSegments;
		$x = 0;
		for ($i = 0; $i < 16; $i++) {
			$p = ord($ipnum[$i]);
			for ($depth = 7; $depth >= 0; $depth--) {
				$x *= 8;
				if ($p & (1 << $depth)) $x += 4;
				$x = ord($b[$x]) + (ord($b[$x+1]) << 8) + (ord($b[$x+2]) << 16) + (ord($b[$x+3]) << 24);
				if ($x >= $s) return $x - $s;
			}
		}
		/* shouldn't reach here */
		return 0;
	}


	/**
	 *
	 * Encuentra la posición para las direcciones IPV4 en un índice IPV6
	 *
	 */
	protected function _init_seek_record_offset()
	{
		$buf = $this->index_cache;
		$s = $this->databaseSegments;
		$record_length = $this->record_length;

		$x = 0;
		for ($i = 0; $i < 12; $i++) {
			$p = 0;
			for ($depth = 7; $depth >= 0; $depth--) {
				$x *= $record_length * 2;
				$x = ord($buf[$x+0]) + (ord($buf[$x+1]) << 8) + (ord($buf[$x+2]) << 16);
				if (4 === $record_length) {
					$x += (ord($buf[$x+3]) << 24);
				}
				if ($x >= $s) break 2;
			}
		}
		$this->seek_record_offset = $x;
	}


	// ======================================================================
	//
	// HELPER FUNCTIONS
	//
	// ======================================================================

	protected static function _saveEncoding($restore=false)
	{
		static $encoding;
		if ($restore) {
			mb_internal_encoding($encoding);
		} else {
			$encoding = mb_internal_encoding();
			mb_internal_encoding('ISO-8859-1');
		}
	}

	protected static function _unpack($buf, $start, $len)
	{
		if (3 === $len) {
			return ord($buf[$start])
				+ (ord($buf[$start+1]) << 8)
				+ (ord($buf[$start+2]) << 16);
		} else {
			$ret = 0;
			for ($i = 0; $i < $len; ++$i) {
				$ret += ord($buf[$start++]) << ($i * 8);
			}
			return $ret;
		}
	}


	protected static function _read($filehandle, $offset, $len)
	{
		if (0 !== @fseek($filehandle, $offset)) {
			throw new Exception("Error seeking file");
		}
		$ret = @fread($filehandle, $len);
		if (false === $ret) {
			throw new Exception("Error reading file");
		}
		return $ret;
	}

	public static function getDatabaseInfo($filename)
	{
		$filehandle = @fopen($filename, 'rb');
		if (false === $filehandle) return NULL;
		$ret = self::_getDatabaseInfo($filehandle);
		fclose($filehandle);
		return $ret;
	}


	/**
	 * @param Object $filehandle
	 * @return Array
	 */
	protected static function _getDatabaseInfo($filehandle)
	{
		/* default to GeoIP Country Edition */

		$databaseType = self::GEOIP_COUNTRY_EDITION;

		$DBInfo = self::$DBInfo[$databaseType];
		$databaseSegments = $DBInfo['segments'];
		$record_length = $DBInfo['record_length'];

		$database_info_offset =
		$structure_info_offset =
		$databaseInfo = null;

		$dyn_seg_size = 0;

		self::_saveEncoding();

		$delim = "\xff\xff\xff";
		$len = 20; // STRUCTURE_INFO_MAX_SIZE;

		$s_array = fstat($filehandle);
		$filesize = $s_array['size'];

		$offset = $filesize - $len;
		$buf = self::_read($filehandle, $offset, $len);
		$ipos = strrpos($buf, $delim);

		if (false !== $ipos) {
			$structure_info_offset = $offset + $ipos;
			$ipos += strlen($delim);

			$databaseType = ord($buf[$ipos++]);
			/* backwards compatibility with databases from April 2003 and earlier */
			if ($databaseType >= 106) {
				$databaseType -= 105;
			}

			if (!isset(self::$DBInfo[$databaseType])) {
				self::_saveEncoding(1);
				return NULL;
			}

			$DBInfo = self::$DBInfo[$databaseType];
			$record_length = $DBInfo['record_length'];
			$databaseSegments = $DBInfo['segments'];

			if (null === $databaseSegments) {

				$segment_record_length = $databaseType === self::GEOIP_CITYCONFIDENCEDIST_EDITION
					? 4  // LARGE_SEGMENT_RECORD_LENGTH
					: 3; // SEGMENT_RECORD_LENGTH;

				$databaseSegments = self::_unpack($buf, $ipos, $segment_record_length);
				$ipos += $segment_record_length;

				if ($databaseType === self::GEOIP_CITYCONFIDENCE_EDITION
				|| $databaseType === self::GEOIP_CITYCONFIDENCEDIST_EDITION) {
					$offs = $databaseSegments * 2 * $record_length;
					$buf = self::_read($filehandle, $offs, $record_length);
					$dyn_seg_size = self::_unpack($buf, 0, $record_length);
				}
			}
		}

		if (null !== $structure_info_offset) {
			$offset = $structure_info_offset;
		} else {
			/* no structure info, must be pre Sep 2002 database, go back to end */
			$offset = $filesize;
		}

		$len = 100; // DATABASE_INFO_MAX_SIZE;
		$offset -= $len;
		$buf = self::_read($filehandle, $offset, $len);
		$delim = "\0\0\0";
		$ipos = strrpos($buf, $delim);
		if (false !== $ipos) {
			$database_info_offset = $offset + $ipos;
			$databaseInfo = substr($buf, $ipos+3);
		}

		self::_saveEncoding(1);

		return array(
			'databaseType' => $databaseType,
			'record_length' => $record_length,
			'databaseSegments' => $databaseSegments,
			'databaseInfo' => $databaseInfo,
			'dyn_seg_size' => $dyn_seg_size,
			'filesize' => $filesize,
			'databaseDescription' => $DBInfo['description'],
			'DBFlags' => $DBInfo['flags'],
			'structure_info_offset' => $structure_info_offset,
			'database_info_offset' => $database_info_offset,
			);
	}

	/**
	 * @param Int $seek_record (unsigned int)
	 * @param Int $next_record_ptr
	 * @return GeoIPRecord
	 */
	private function _extract_record($seek_record, $next_record_ptr)
	{
		$region =
		$city =
		$postal_code =
		$latitude =
		$longitude =
		$metro_code =
		$area_code = null;

		$country_conf =
		$region_conf =
		$city_conf =
		$postal_conf = self::GEOIP_UNKNOWN_CONF;

		$accuracy_radius = self::GEOIP_UNKNOWN_ACCURACY_RADIUS;
		$charset = $this->charset;

		switch ($this->databaseType) {
			case self::GEOIP_CITYCONFIDENCE_EDITION:
			case self::GEOIP_CITYCONFIDENCEDIST_EDITION:

				$fixed_rec_size = $this->record_length +
					(($this->databaseType === self::GEOIP_CITYCONFIDENCE_EDITION)
					? 4 // CITYCONFIDENCE_FIXED_RECORD
					: 6); // CITYCONFIDENCEDIST_FIXED_RECORD);

				$dseg = $this->databaseSegments * $this->record_length * 2 + $this->record_length;
				$offset = $seek_record - 1;	// -1 b/c zero is not found. but the array start with 0
				$record_pointer = $offset * $fixed_rec_size + $dseg + $this->dyn_seg_size;

				if (null === $this->cache) {
					$buf = self::_read($this->filehandle, $record_pointer, $fixed_rec_size);
					$ipos = 0;
				} else {
					$buf = $this->cache;
					$ipos = $record_pointer;
				}

				$t = $fixed_rec_size - $this->record_length;
				$record_pointer = $dseg + self::_unpack($ipos + $t, $this->record_length);

				foreach (array('country_conf', 'region_conf', 'city_conf', 'postal_conf') as $name) {
					$$name = ord($buf[$ipos++]);
				}
				if ($this->databaseType === self::GEOIP_CITYCONFIDENCEDIST_EDITION) {
					$accuracy_radius = self::_unpack($buf, $ipos, 2) & 0x3ff;
				}
				break;

			default:
				/* other city records */
				$record_pointer = $seek_record + $this->databaseSegments + (2 * $this->record_length - 1) * $this->databaseSegments;
				break;
		}

		if (null === $this->cache) {
			$buf = self::_read($this->filehandle, $record_pointer, 50); // FULL_RECORD_LENGTH
			$ipos = 0;
		} else {
			$buf = $this->cache;
			$ipos = $record_pointer;
		}

		/* get country */
		$country_id = ord($buf[$ipos++]);


		self::_saveEncoding();
		foreach (array('region', 'city', 'postal_code') as $name) {
			$i = strpos($buf, "\0", $ipos);
			if ($len = $i - $ipos) {
				$$name = substr($buf, $ipos, $len);
			}
			$ipos = $i + 1;
		}
		if (null !== $city) {
			$city = mb_convert_encoding($city, $charset, 'ISO-8859-1');
		}
		self::_saveEncoding(1);


		/* get latitude */
		foreach (array('latitude', 'longitude') as $name) {
			$$name = self::_unpack($buf, $ipos, 3) / 10000 - 180;
			$ipos += 3;
		}

		/*
		 * get area code and metro code for post April 2002 databases and for US
		 * locations
		 */
		switch ($this->databaseType) {
			case self::GEOIP_CITY_EDITION_REV1:
			case self::GEOIP_CITYCONFIDENCE_EDITION:
				if (225 === $country_id) {
					$metroarea_combo = self::_unpack($buf, $ipos, 3);
					$ipos += 3;
					$metro_code = floor($metroarea_combo / 1000);
					$area_code = $metroarea_combo % 1000;
				}
			default:
				break;
		}

		return array(
			'country_code' => self::$GeoIP_country_code[$country_id],
			'country_code3' => self::$GeoIP_country_code3[$country_id],
			'country_name' => self::$GeoIP_country_name[$country_id],
			'region' => $region,
			'city' => $city,
			'postal_code' => $postal_code,
			'latitude' => $latitude,
			'longitude' => $longitude,
			'metro_code' => $metro_code,
			'area_code' => $area_code,
			'charset' => $charset,
			'continent_code' => self::$GeoIP_country_continent[$country_id],
			'country_conf' => $country_conf,
			'region_conf' => $region_conf,
			'city_conf' => $city_conf,
			'postal_conf' => $postal_conf,
			'accuracy_radius' => $accuracy_radius,
			);
	}


	/**
	 * GeoIP Organization, ISP and AS Number Edition private method
	 *
	 * @param int $ipnum
	 * @return string
	 */
	protected function _get_name($seek_org)
	{
		$x = $seek_org + (2 * $this->record_length) * $this->databaseSegments;
		if (null === $buf = $this->cache) {
			$buf = $this->_read($this->filehandle, $x, 300); // MAX_ORG_RECORD_LENGTH);
			$x = 0;
		}
		self::_saveEncoding();
		$i = strpos($buf, "\0", $x);
		$ret = substr($buf, $x, $i-$x);
		$ret = mb_convert_encoding($ret, $this->charset, 'ISO-8859-1');
		self::_saveEncoding(1);
		return $ret;
	}


	const US_OFFSET = 1;
	const CANADA_OFFSET = 677;
	const WORLD_OFFSET = 1353;
	const FIPS_RANGE = 360;

	protected function _get_region($seek_region) {
		$country_code = $region = null;
		switch ($this->databaseType) {
			case self::GEOIP_REGION_EDITION_REV0:
				/* Region Edition, pre June 2003 */
				if ($seek_region >= 1000) {
					$country_code = 'US';
					$region = $seek_region - 1000;
				} else {
					$country_code = self::$GeoIP_country_code[$seek_region];
				}
				break;

			case self::GEOIP_REGION_EDITION_REV1:
				/* Region Edition, post June 2003 */
				if ($seek_region < self::US_OFFSET) {
					/* Unknown */
				} else if ($seek_region < self::CANADA_OFFSET) {
					/* USA State */
					$country_code = 'US';
					$region = $seek_region - self::US_OFFSET;

				} else if ($seek_region < self::WORLD_OFFSET) {
					/* Canada Province */
					$country_code = 'CA';
					$region = $seek_region - self::CANADA_OFFSET;
				} else {
					/* Not US or Canada */
					$country_code = self::$GEOIP_country_code[($seek_region - self::WORLD_OFFSET) / self::FIPS_RANGE];
				}
			default:
				break;
		}
		if (null !== $region) {
			$region = chr($region / 26 + 65) . chr($region % 26 + 65);
		}
		return array('country_code' => $country_code, 'region' => $region);
	}
}