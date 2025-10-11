<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["connect-js"])){connect_js();exit;}
if(isset($_GET["disconnect-js"])){disconnect_js();exit;}
if(isset($_GET["reconnect-js"])){reconnect_js();exit;}

if(isset($_GET["connect-popup"])){connect_popup();exit;}
if(isset($_POST["MACADDR"])){connect_save();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$nic=$_GET["nic"];
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/iwlist.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/iwlist.log";
	$ARRAY["CMD"]="iwconfig.php?iwlist=$nic";
	$ARRAY["TITLE"]="{scanning} $nic";
	$ARRAY["AFTER"]="LoadAjaxTiny('$nic-main-table','$page?table=yes&nic=$nic');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsCompile="Loadjs('fw.progress.php?content=$prgress&mainid=$nic-iwscan')";
	
	
	$html[]="<div id='$nic-iwscan' style='margin-top:10px'></div>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	if($users->AsSystemAdministrator){
		$html[]="<label class=\"btn btn-info\" OnClick=\"javascript:LoadAjaxTiny('$nic-main-table','$page?table=yes&nic=$nic');\"><i class='fal fa-sync-alt'></i> {refresh} </label>";
		$html[]="<label class=\"btn btn-primary\" OnClick=\"javascript:$jsCompile\"><i class='fa fa-wifi'></i> {analyze} </label>";
	}
	$html[]="</div>";
	$html[]="<div id='$nic-main-table'></div>";
	$html[]="<script>LoadAjaxTiny('$nic-main-table','$page?table=yes&nic=$nic');</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function connect_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$nic=$_GET["nic"];
	$MACADDR=$_GET["connect-js"];
	$MACADDR_enc=urlencode($MACADDR);
	$array=unserialize(@file_get_contents("ressources/logs/iwlist.scan"));
	$ESSID=$array[$MACADDR]["ESSID"];
	$tpl->js_dialog2("$nic:{$MACADDR}/$ESSID", "$page?connect-popup=$MACADDR_enc&nic=$nic");
}
function disconnect_js(){
	header("content-type: application/x-javascript");
	$nic=$_GET["nic"];
	$page=CurrentPageName();
	$sock=new sockets();
	$sock->getFrameWork("iwconfig.php?disconnect=$nic");
	echo "LoadAjaxTiny('$nic-main-table','$page?table=yes&nic=$nic');";
}
function reconnect_js(){
	header("content-type: application/x-javascript");
	$nic=$_GET["nic"];
	$page=CurrentPageName();
	$sock=new sockets();
	$sock->getFrameWork("iwconfig.php?reconnect=$nic");
	echo "LoadAjaxTiny('$nic-main-table','$page?table=yes&nic=$nic');";
}

function connect_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MACADDR=$_GET["connect-popup"];
	$array=unserialize(@file_get_contents("ressources/logs/iwlist.scan"));
	$ESSID=$array[$MACADDR]["ESSID"];
	$KEY=$array[$MACADDR]["KEY"];
	$md=md5($MACADDR);
	$MACADDR_enc=urlencode($MACADDR);
	$WifiAccessPoint=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WifiAccessPoint"));
	$CONFIG=$WifiAccessPoint[$MACADDR];
	$nic=$_GET["nic"];
	$html[]="<div id='access-to-$md'></div>";
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/iwconf-ap.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/iwconf-ap.log";
	$ARRAY["CMD"]="iwconfig.php?connect=$MACADDR_enc&nic=$nic";
	$ARRAY["TITLE"]="{connect} $ESSID ($nic)";
	$ARRAY["AFTER"]="LoadAjaxTiny('$nic-main-table','$page?table=yes&nic=$nic');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsCompile="Loadjs('fw.progress.php?content=$prgress&mainid=access-to-$md')";
    $COUNTRIES=Countries_wpa();
	if($KEY){
		$form[]=$tpl->field_hidden("MACADDR", $MACADDR);
		$form[]=$tpl->field_hidden("nic", $nic);
		$form[]=$tpl->field_hidden("ESSID", $ESSID);
		$form[]=$tpl->field_array_hash($COUNTRIES,"COUNTRY","{country}",$CONFIG["COUNTRY"]);
		$form[]=$tpl->field_checkbox("WPA2","WPA2-PSK,WPA2-Personal",intval($CONFIG["WPA2"]));
		$form[]=$tpl->field_password("ESSID_PASSWORD", "{wifi_key}", $CONFIG["ESSID_PASSWORD"]);
		$html[]=$tpl->form_outside($ESSID, @implode("\n", $form),null,"{apply}",$jsCompile,"AsSystemAdministrator");
		
	}else{
		
		$html[]="<script>$jsCompile;</script>";
		
		
	}
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function connect_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$WifiAccessPoint=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WifiAccessPoint"));
	$WifiAccessPoint[$_POST["MACADDR"]]=$_POST;
	$sock=new sockets();
	$datas=base64_encode(serialize($WifiAccessPoint));
	$sock->SaveConfigFile($datas, "WifiAccessPoint");
}



function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$nic=$_GET["nic"];
	$sock=new sockets();

    $html=array();
	
	$array=unserialize(@file_get_contents("ressources/logs/iwlist.scan"));
	if(!is_array($array)){echo $tpl->_ENGINE_parse_body(@implode("\n", $html));echo $tpl->FATAL_ERROR_SHOW_128("<H2>{NO_ESSID}</H2>");return;}
	if(count($array)==0){echo $tpl->_ENGINE_parse_body(@implode("\n", $html));echo $tpl->FATAL_ERROR_SHOW_128("<H2>{NO_ESSID}</H2>");return;}
	

	
	$html[]="<table id='table-wifi-$nic' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{ESSID}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{quality}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{crypted}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{connect}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	
	$sock->getFrameWork("iwconfig.php?status=$nic");
	$tt=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/wpa_supplicant.$nic.status"));
	foreach ($tt as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#(.+?)=(.+)#", trim($line),$re)){
			$infos[]="<strong>{$re[1]}</strong>: {$re[2]}";
			$CURRENT[$re[1]]=$re[2];
			continue;
		}
		if(preg_match("#^[0-9]+\s+(.+?)\s+([a-z0-9\:]+)#", $line,$re)){
			$CURRENT["bssid"]=$re[2];
			$CURRENT["ssid"]=$re[1];
		}
		
	}
	
	
	
/*	bssid=f4:ca:e5:e1:c6:90
	freq=2432
	ssid=freebox_touzeau
	id=0
	mode=station
	pairwise_cipher=CCMP
	group_cipher=TKIP
	key_mgmt=WPA-PSK
	wpa_state=COMPLETED
	address=00:16:ea:54:b0:b8
	uuid=e7718b46-d78c-5cf7-b184-06c7ade4d468
*/
	foreach ($array as $MACADDR=>$ligne){

		
		$md=md5(serialize($ligne));
        $ESSID_TEXT=null;
		$APNUM=$num;
		$QUALITY=$ligne["QUALITY"];
		$ESSID=$ligne["ESSID"];
		$KEY=$ligne["KEY"];
		if($KEY){$key="<i class='fa fa-lock'></i>";}else{$key="<i class='fa fa-unlock'></i>";}
		$infos_text=null;
		$tooltip="{connect}";

		if(strlen($ESSID)>60){
            $ESSID_TEXT=substr($ESSID,0,57)."...";
        }else{
            $ESSID_TEXT=$ESSID;
        }
		
		$myRR=0;
		while (list ($b, $a) = each ($ligne["RATES"]) ){
			$b=trim($b);
			if(preg_match("#^([0-9\.]+)#", $b,$ri)){$INTR=intval($ri[1]);}
			if($INTR>$myRR){$myRR=$INTR;}
			$b=str_replace(" ","&nbsp;",$b);
			if($b<>null){$RR[]=$b;}
		}
		$status="<span class='label'>{unknown}</span>";
		$wifiok=null;
		$MACADDR_enc=urlencode($MACADDR);
		$connect_js="Loadjs('$page?connect-js=$MACADDR_enc&nic=$nic');";
		
		$button="<button OnClick=\"$connect_js\" class='btn btn-primary btn-xs' type='button'>{connect}</button>";

		if(strtolower($CURRENT["bssid"])==strtolower($MACADDR)){
			$wifiok="&nbsp;&nbsp;<i class='fa fa-wifi'></i>";
			if($CURRENT["wpa_state"]=="COMPLETED"){
				$status="<span class='label label-primary'>{connected}</span>";
				$button="<button OnClick=\"Loadjs('$page?disconnect-js=yes&nic=$nic');\" class='btn btn-danger btn-xs' type='button'>{disconnect}</button>";
			}
			if($CURRENT["wpa_state"]=="DISCONNECTED"){
				$status="<span class='label label-warning'>{disconnected}</span>";
				$button="<button OnClick=\"Loadjs('$page?reconnect-js=yes&nic=$nic');\" class='btn btn-warning btn-xs' type='button'>{connect}</button>";
			}
			if($CURRENT["wpa_state"]=="SCANNING"){
				$status="<span class='label label-info'>{scanning}</span>";
                $button="<button OnClick=\"Loadjs('$page?disconnect-js=yes&nic=$nic');\" class='btn btn-danger btn-xs' type='button'>{disconnect}</button>";
			}
			$infos_text=@implode("<br>", $infos)."<br>";
		}
		
		
		$ppbar="";
		if($QUALITY<80){$ppbar="progress-bar-warning";}
		if($QUALITY<60){$ppbar="progress-bar-danger";}
		if($QUALITY==0){continue;}
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		
		$explain="$infos_text<strong>{bits_rate}</strong>:<br>".implode("<br>",$RR)."";
		
		$tx=$tpl->td_href($ESSID_TEXT,$explain,$connect_js);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap><strong>$tx</strong>$wifiok<br><small>$MACADDR</small></td>";
		$html[]="<td style='width:1%' nowrap>
				<div><span class=small>{$QUALITY}%</span></div>
				<div class='progress progress-small'>
					<div class='progress-bar $ppbar' style='width: {$QUALITY}%;'></div>
                </div>
				
				</td>";
		$html[]="<td style='width:1%' class='center' nowrap>$key</center></td>";
		$html[]="<td style='width:1%' nowrap>$status</td>";
		$html[]="<td style='width:1%' nowrap>$button</td>";
		$html[]="</tr>";
		$RR=array();
	}	
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-wifi-$nic').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function Countries_wpa(){
    $COUNTRIES["AD"]="Andorra";
    $COUNTRIES["AE"]="United Arab Emirates";
    $COUNTRIES["AF"]="Afghanistan";
    $COUNTRIES["AG"]="Antigua and Barbuda";
    $COUNTRIES["AI"]="Anguilla";
    $COUNTRIES["AL"]="Albania";
    $COUNTRIES["AM"]="Armenia";
    $COUNTRIES["AO"]="Angola";
    $COUNTRIES["AQ"]="Antarctica";
    $COUNTRIES["AR"]="Argentina";
    $COUNTRIES["AS"]="American Samoa";
    $COUNTRIES["AT"]="Austria";
    $COUNTRIES["AU"]="Australia";
    $COUNTRIES["AW"]="Aruba";
    $COUNTRIES["AX"]="Åland Islands";
    $COUNTRIES["AZ"]="Azerbaijan";
    $COUNTRIES["BA"]="Bosnia and Herzegovina";
    $COUNTRIES["BB"]="Barbados";
    $COUNTRIES["BD"]="Bangladesh";
    $COUNTRIES["BE"]="Belgium";
    $COUNTRIES["BF"]="Burkina Faso";
    $COUNTRIES["BG"]="Bulgaria";
    $COUNTRIES["BH"]="Bahrain";
    $COUNTRIES["BI"]="Burundi";
    $COUNTRIES["BJ"]="Benin";
    $COUNTRIES["BL"]="Saint Barthélemy";
    $COUNTRIES["BM"]="Bermuda";
    $COUNTRIES["BN"]="Brunei Darussalam";
    $COUNTRIES["BO"]="Bolivia (Plurinational State of)";
    $COUNTRIES["BQ"]="Bonaire, Sint Eustatius and Saba";
    $COUNTRIES["BR"]="Brazil";
    $COUNTRIES["BS"]="Bahamas";
    $COUNTRIES["BT"]="Bhutan";
    $COUNTRIES["BV"]="Bouvet Island";
    $COUNTRIES["BW"]="Botswana";
    $COUNTRIES["BY"]="Belarus";
    $COUNTRIES["BZ"]="Belize";
    $COUNTRIES["CA"]="Canada";
    $COUNTRIES["CC"]="Cocos (Keeling) Islands";
    $COUNTRIES["CD"]="Congo, Democratic Republic of the";
    $COUNTRIES["CF"]="Central African Republic";
    $COUNTRIES["CG"]="Congo";
    $COUNTRIES["CH"]="Switzerland";
    $COUNTRIES["CI"]="Côte d'Ivoire";
    $COUNTRIES["CK"]="Cook Islands";
    $COUNTRIES["CL"]="Chile";
    $COUNTRIES["CM"]="Cameroon";
    $COUNTRIES["CN"]="China";
    $COUNTRIES["CO"]="Colombia";
    $COUNTRIES["CR"]="Costa Rica";
    $COUNTRIES["CU"]="Cuba";
    $COUNTRIES["CV"]="Cabo Verde";
    $COUNTRIES["CW"]="Curaçao";
    $COUNTRIES["CX"]="Christmas Island";
    $COUNTRIES["CY"]="Cyprus";
    $COUNTRIES["CZ"]="Czechia";
    $COUNTRIES["DE"]="Germany";
    $COUNTRIES["DJ"]="Djibouti";
    $COUNTRIES["DK"]="Denmark";
    $COUNTRIES["DM"]="Dominica";
    $COUNTRIES["DO"]="Dominican Republic";
    $COUNTRIES["DZ"]="Algeria";
    $COUNTRIES["EC"]="Ecuador";
    $COUNTRIES["EE"]="Estonia";
    $COUNTRIES["EG"]="Egypt";
    $COUNTRIES["EH"]="Western Sahara";
    $COUNTRIES["ER"]="Eritrea";
    $COUNTRIES["ES"]="Spain";
    $COUNTRIES["ET"]="Ethiopia";
    $COUNTRIES["FI"]="Finland";
    $COUNTRIES["FJ"]="Fiji";
    $COUNTRIES["FK"]="Falkland Islands (Malvinas)";
    $COUNTRIES["FM"]="Micronesia (Federated States of)";
    $COUNTRIES["FO"]="Faroe Islands";
    $COUNTRIES["FR"]="France";
    $COUNTRIES["GA"]="Gabon";
    $COUNTRIES["GB"]="United Kingdom of Great Britain and Northern Ireland";
    $COUNTRIES["GD"]="Grenada";
    $COUNTRIES["GE"]="Georgia";
    $COUNTRIES["GF"]="French Guiana";
    $COUNTRIES["GG"]="Guernsey";
    $COUNTRIES["GH"]="Ghana";
    $COUNTRIES["GI"]="Gibraltar";
    $COUNTRIES["GL"]="Greenland";
    $COUNTRIES["GM"]="Gambia";
    $COUNTRIES["GN"]="Guinea";
    $COUNTRIES["GP"]="Guadeloupe";
    $COUNTRIES["GQ"]="Equatorial Guinea";
    $COUNTRIES["GR"]="Greece";
    $COUNTRIES["GS"]="South Georgia and the South Sandwich Islands";
    $COUNTRIES["GT"]="Guatemala";
    $COUNTRIES["GU"]="Guam";
    $COUNTRIES["GW"]="Guinea-Bissau";
    $COUNTRIES["GY"]="Guyana";
    $COUNTRIES["HK"]="Hong Kong";
    $COUNTRIES["HM"]="Heard Island and McDonald Islands";
    $COUNTRIES["HN"]="Honduras";
    $COUNTRIES["HR"]="Croatia";
    $COUNTRIES["HT"]="Haiti";
    $COUNTRIES["HU"]="Hungary";
    $COUNTRIES["ID"]="Indonesia";
    $COUNTRIES["IE"]="Ireland";
    $COUNTRIES["IL"]="Israel";
    $COUNTRIES["IM"]="Isle of Man";
    $COUNTRIES["IN"]="India";
    $COUNTRIES["IO"]="British Indian Ocean Territory";
    $COUNTRIES["IQ"]="Iraq";
    $COUNTRIES["IR"]="Iran (Islamic Republic of)";
    $COUNTRIES["IS"]="Iceland";
    $COUNTRIES["IT"]="Italy";
    $COUNTRIES["JE"]="Jersey";
    $COUNTRIES["JM"]="Jamaica";
    $COUNTRIES["JO"]="Jordan";
    $COUNTRIES["JP"]="Japan";
    $COUNTRIES["KE"]="Kenya";
    $COUNTRIES["KG"]="Kyrgyzstan";
    $COUNTRIES["KH"]="Cambodia";
    $COUNTRIES["KI"]="Kiribati";
    $COUNTRIES["KM"]="Comoros";
    $COUNTRIES["KN"]="Saint Kitts and Nevis";
    $COUNTRIES["KP"]="Korea (Democratic People's Republic of)";
    $COUNTRIES["KR"]="Korea, Republic of";
    $COUNTRIES["KW"]="Kuwait";
    $COUNTRIES["KY"]="Cayman Islands";
    $COUNTRIES["KZ"]="Kazakhstan";
    $COUNTRIES["LA"]="Lao People's Democratic Republic";
    $COUNTRIES["LB"]="Lebanon";
    $COUNTRIES["LC"]="Saint Lucia";
    $COUNTRIES["LI"]="Liechtenstein";
    $COUNTRIES["LK"]="Sri Lanka";
    $COUNTRIES["LR"]="Liberia";
    $COUNTRIES["LS"]="Lesotho";
    $COUNTRIES["LT"]="Lithuania";
    $COUNTRIES["LU"]="Luxembourg";
    $COUNTRIES["LV"]="Latvia";
    $COUNTRIES["LY"]="Libya";
    $COUNTRIES["MA"]="Morocco";
    $COUNTRIES["MC"]="Monaco";
    $COUNTRIES["MD"]="Moldova, Republic of";
    $COUNTRIES["ME"]="Montenegro";
    $COUNTRIES["MF"]="Saint Martin (French part)";
    $COUNTRIES["MG"]="Madagascar";
    $COUNTRIES["MH"]="Marshall Islands";
    $COUNTRIES["MK"]="North Macedonia";
    $COUNTRIES["ML"]="Mali";
    $COUNTRIES["MM"]="Myanmar";
    $COUNTRIES["MN"]="Mongolia";
    $COUNTRIES["MO"]="Macao";
    $COUNTRIES["MP"]="Northern Mariana Islands";
    $COUNTRIES["MQ"]="Martinique";
    $COUNTRIES["MR"]="Mauritania";
    $COUNTRIES["MS"]="Montserrat";
    $COUNTRIES["MT"]="Malta";
    $COUNTRIES["MU"]="Mauritius";
    $COUNTRIES["MV"]="Maldives";
    $COUNTRIES["MW"]="Malawi";
    $COUNTRIES["MX"]="Mexico";
    $COUNTRIES["MY"]="Malaysia";
    $COUNTRIES["MZ"]="Mozambique";
    $COUNTRIES["NA"]="Namibia";
    $COUNTRIES["NC"]="New Caledonia";
    $COUNTRIES["NE"]="Niger";
    $COUNTRIES["NF"]="Norfolk Island";
    $COUNTRIES["NG"]="Nigeria";
    $COUNTRIES["NI"]="Nicaragua";
    $COUNTRIES["NL"]="Netherlands[note 1]";
    $COUNTRIES["NO"]="Norway";
    $COUNTRIES["NP"]="Nepal";
    $COUNTRIES["NR"]="Nauru";
    $COUNTRIES["NU"]="Niue";
    $COUNTRIES["NZ"]="New Zealand";
    $COUNTRIES["OM"]="Oman";
    $COUNTRIES["PA"]="Panama";
    $COUNTRIES["PE"]="Peru";
    $COUNTRIES["PF"]="French Polynesia";
    $COUNTRIES["PG"]="Papua New Guinea";
    $COUNTRIES["PH"]="Philippines";
    $COUNTRIES["PK"]="Pakistan";
    $COUNTRIES["PL"]="Poland";
    $COUNTRIES["PM"]="Saint Pierre and Miquelon";
    $COUNTRIES["PN"]="Pitcairn";
    $COUNTRIES["PR"]="Puerto Rico";
    $COUNTRIES["PS"]="Palestine, State of";
    $COUNTRIES["PT"]="Portugal";
    $COUNTRIES["PW"]="Palau";
    $COUNTRIES["PY"]="Paraguay";
    $COUNTRIES["QA"]="Qatar";
    $COUNTRIES["RE"]="Réunion";
    $COUNTRIES["RO"]="Romania";
    $COUNTRIES["RS"]="Serbia";
    $COUNTRIES["RU"]="Russian Federation";
    $COUNTRIES["RW"]="Rwanda";
    $COUNTRIES["SA"]="Saudi Arabia";
    $COUNTRIES["SB"]="Solomon Islands";
    $COUNTRIES["SC"]="Seychelles";
    $COUNTRIES["SD"]="Sudan";
    $COUNTRIES["SE"]="Sweden";
    $COUNTRIES["SG"]="Singapore";
    $COUNTRIES["SH"]="Saint Helena, Ascension and Tristan da Cunha";
    $COUNTRIES["SI"]="Slovenia";
    $COUNTRIES["SJ"]="Svalbard and Jan Mayen";
    $COUNTRIES["SK"]="Slovakia";
    $COUNTRIES["SL"]="Sierra Leone";
    $COUNTRIES["SM"]="San Marino";
    $COUNTRIES["SN"]="Senegal";
    $COUNTRIES["SO"]="Somalia";
    $COUNTRIES["SR"]="Suriname";
    $COUNTRIES["SS"]="South Sudan";
    $COUNTRIES["ST"]="Sao Tome and Principe";
    $COUNTRIES["SV"]="El Salvador";
    $COUNTRIES["SX"]="Sint Maarten (Dutch part)";
    $COUNTRIES["SY"]="Syrian Arab Republic";
    $COUNTRIES["SZ"]="Eswatini";
    $COUNTRIES["TC"]="Turks and Caicos Islands";
    $COUNTRIES["TD"]="Chad";
    $COUNTRIES["TF"]="French Southern Territories";
    $COUNTRIES["TG"]="Togo";
    $COUNTRIES["TH"]="Thailand";
    $COUNTRIES["TJ"]="Tajikistan";
    $COUNTRIES["TK"]="Tokelau";
    $COUNTRIES["TL"]="Timor-Leste";
    $COUNTRIES["TM"]="Turkmenistan";
    $COUNTRIES["TN"]="Tunisia";
    $COUNTRIES["TO"]="Tonga";
    $COUNTRIES["TR"]="Turkey";
    $COUNTRIES["TT"]="Trinidad and Tobago";
    $COUNTRIES["TV"]="Tuvalu";
    $COUNTRIES["TW"]="Taiwan, Province of China [note 2]";
    $COUNTRIES["TZ"]="Tanzania, United Republic of";
    $COUNTRIES["UA"]="Ukraine";
    $COUNTRIES["UG"]="Uganda";
    $COUNTRIES["UM"]="United States Minor Outlying Islands";
    $COUNTRIES["US"]="United States of America";
    $COUNTRIES["UY"]="Uruguay";
    $COUNTRIES["UZ"]="Uzbekistan";
    $COUNTRIES["VA"]="Holy See";
    $COUNTRIES["VC"]="Saint Vincent and the Grenadines";
    $COUNTRIES["VE"]="Venezuela (Bolivarian Republic of)";
    $COUNTRIES["VG"]="Virgin Islands (British)";
    $COUNTRIES["VI"]="Virgin Islands (U.S.)";
    $COUNTRIES["VN"]="Viet Nam";
    $COUNTRIES["VU"]="Vanuatu";
    $COUNTRIES["WF"]="Wallis and Futuna";
    $COUNTRIES["WS"]="Samoa";
    $COUNTRIES["YE"]="Yemen";
    $COUNTRIES["YT"]="Mayotte";
    $COUNTRIES["ZA"]="South Africa";
    $COUNTRIES["ZM"]="Zambia";
    $COUNTRIES["ZW"]="Zimbabwe";
    return $COUNTRIES;

}