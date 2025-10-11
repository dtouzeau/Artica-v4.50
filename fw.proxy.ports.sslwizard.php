<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["portid"])){Save();exit;}
if(isset($_GET["exit"])){page_final();exit;}


js();

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $addon=null;
    if(isset($_GET["main-wizard"])){
        $addon="&main-wizard=yes";
    }
    $portid=intval($_GET["portid"]);
    $tpl->js_dialog8("{create_certificate}: {wizard}","$page?start=yes&portid=$portid$addon");
    return true;

}
function start():bool{
    $portid=intval($_GET["portid"]);
    $page=CurrentPageName();

    $addon=null;
    if(isset($_GET["main-wizard"])){
        $addon="&main-wizard=yes";
    }

    echo "<div id='port-ssl-wizard'></div><script>LoadAjax('port-ssl-wizard','$page?popup=$portid$addon');</script>";
    return true;
}
function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ProxyCertificateWizard",serialize($_POST));
}

function page_final():bool{
    $tpl=new template_admin();
    $ProxyCertificateWizard=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyCertificateWizard"));
    $CertificateName=$ProxyCertificateWizard["CommonName"];
    $sql="SELECT ID FROM sslcertificates WHERE CommonName='$CertificateName'";
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $ligne=$q->mysqli_fetch_array($sql);
    $ID=intval($ligne["ID"]);

    if(isset($_GET["main-wizard"])){

        $html[]="<script>";
        $html[]="if(document.getElementById('activate_ssl_decryption') ){";
        $html[]="LoadAjaxTiny('activate_ssl_decryption','fw.proxy.ports.ssl.php?step2=yes&ssl-generated=$CertificateName');";
        $html[]="dialogInstance8.close();";
        $html[]="}";
        $html[]="</script>";
        $html[]="";
        echo @implode("\n",$html);
        return true;


    }



    $html[]="<center style='margin:20px'>";
    $html[]=$tpl->button_autnonome("{download2} {certificate}", "document.location.href='fw.certificates-center.php?download-pfx=$ID'",
            "fas fa-download",null,220);
    $html[]="</center>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function popup():bool{
    $portid=intval($_GET["popup"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $ProxyCertificateWizard=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyCertificateWizard"));

    $CountriesCodes["US"]="United States of America";
    $CountriesCodes["CA"]="Canada";
    $CountriesCodes["AX"]="Aland Islands";
    $CountriesCodes["AD"]="Andorra";
    $CountriesCodes["AE"]="United Arab Emirates";
    $CountriesCodes["AF"]="Afghanistan";
    $CountriesCodes["AG"]="Antigua and Barbuda";
    $CountriesCodes["AI"]="Anguilla";
    $CountriesCodes["AL"]="Albania";
    $CountriesCodes["AM"]="Armenia";
    $CountriesCodes["AN"]="Netherlands Antilles";
    $CountriesCodes["AO"]="Angola";
    $CountriesCodes["AQ"]="Antarctica";
    $CountriesCodes["AR"]="Argentina";
    $CountriesCodes["AS"]="American Samoa";
    $CountriesCodes["AT"]="Austria";
    $CountriesCodes["AU"]="Australia";
    $CountriesCodes["AW"]="Aruba";
    $CountriesCodes["AZ"]="Azerbaijan";
    $CountriesCodes["BA"]="Bosnia and Herzegovina";
    $CountriesCodes["BB"]="Barbados";
    $CountriesCodes["BD"]="Bangladesh";
    $CountriesCodes["BE"]="Belgium";
    $CountriesCodes["BF"]="Burkina Faso";
    $CountriesCodes["BG"]="Bulgaria";
    $CountriesCodes["BH"]="Bahrain";
    $CountriesCodes["BI"]="Burundi";
    $CountriesCodes["BJ"]="Benin";
    $CountriesCodes["BM"]="Bermuda";
    $CountriesCodes["BN"]="Brunei Darussalam";
    $CountriesCodes["BO"]="Bolivia";
    $CountriesCodes["BR"]="Brazil";
    $CountriesCodes["BS"]="Bahamas";
    $CountriesCodes["BT"]="Bhutan";
    $CountriesCodes["BV"]="Bouvet Island";
    $CountriesCodes["BW"]="Botswana";
    $CountriesCodes["BZ"]="Belize";
    $CountriesCodes["CA"]="Canada";
    $CountriesCodes["CC"]="Cocos (Keeling) Islands";
    $CountriesCodes["CF"]="Central African Republic";
    $CountriesCodes["CH"]="Switzerland";
    $CountriesCodes["CI"]="Cote D'Ivoire (Ivory Coast)";
    $CountriesCodes["CK"]="Cook Islands";
    $CountriesCodes["CL"]="Chile";
    $CountriesCodes["CM"]="Cameroon";
    $CountriesCodes["CN"]="China";
    $CountriesCodes["CO"]="Colombia";
    $CountriesCodes["CR"]="Costa Rica";
    $CountriesCodes["CS"]="Czechoslovakia (former)";
    $CountriesCodes["CV"]="Cape Verde";
    $CountriesCodes["CX"]="Christmas Island";
    $CountriesCodes["CY"]="Cyprus";
    $CountriesCodes["CZ"]="Czech Republic";
    $CountriesCodes["DE"]="Germany";
    $CountriesCodes["DJ"]="Djibouti";
    $CountriesCodes["DK"]="Denmark";
    $CountriesCodes["DM"]="Dominica";
    $CountriesCodes["DO"]="Dominican Republic";
    $CountriesCodes["DZ"]="Algeria";
    $CountriesCodes["EC"]="Ecuador";
    $CountriesCodes["EE"]="Estonia";
    $CountriesCodes["EG"]="Egypt";
    $CountriesCodes["EH"]="Western Sahara";
    $CountriesCodes["ER"]="Eritrea";
    $CountriesCodes["ES"]="Spain";
    $CountriesCodes["ET"]="Ethiopia";
    $CountriesCodes["FI"]="Finland";
    $CountriesCodes["FJ"]="Fiji";
    $CountriesCodes["FK"]="Falkland Islands (Malvinas)";
    $CountriesCodes["FM"]="Micronesia";
    $CountriesCodes["FO"]="Faroe Islands";
    $CountriesCodes["FR"]="France";
    $CountriesCodes["FX"]="France, Metropolitan";
    $CountriesCodes["GA"]="Gabon";
    $CountriesCodes["GB"]="Great Britain (UK)";
    $CountriesCodes["GD"]="Grenada";
    $CountriesCodes["GE"]="Georgia";
    $CountriesCodes["GF"]="French Guiana";
    $CountriesCodes["GG"]="Guernsey";
    $CountriesCodes["GH"]="Ghana";
    $CountriesCodes["GI"]="Gibraltar";
    $CountriesCodes["GL"]="Greenland";
    $CountriesCodes["GM"]="Gambia";
    $CountriesCodes["GN"]="Guinea";
    $CountriesCodes["GP"]="Guadeloupe";
    $CountriesCodes["GQ"]="Equatorial Guinea";
    $CountriesCodes["GR"]="Greece";
    $CountriesCodes["GS"]="S. Georgia and S. Sandwich Isls.";
    $CountriesCodes["GT"]="Guatemala";
    $CountriesCodes["GU"]="Guam";
    $CountriesCodes["GW"]="Guinea-Bissau";
    $CountriesCodes["GY"]="Guyana";
    $CountriesCodes["HK"]="Hong Kong";
    $CountriesCodes["HM"]="Heard and McDonald Islands";
    $CountriesCodes["HN"]="Honduras";
    $CountriesCodes["HR"]="Croatia (Hrvatska)";
    $CountriesCodes["HT"]="Haiti";
    $CountriesCodes["HU"]="Hungary";
    $CountriesCodes["ID"]="Indonesia";
    $CountriesCodes["IE"]="Ireland";
    $CountriesCodes["IL"]="Israel";
    $CountriesCodes["IM"]="Isle of Man";
    $CountriesCodes["IN"]="India";
    $CountriesCodes["IO"]="British Indian Ocean Territory";
    $CountriesCodes["IS"]="Iceland";
    $CountriesCodes["IT"]="Italy";
    $CountriesCodes["JE"]="Jersey";
    $CountriesCodes["JM"]="Jamaica";
    $CountriesCodes["JO"]="Jordan";
    $CountriesCodes["JP"]="Japan";
    $CountriesCodes["KE"]="Kenya";
    $CountriesCodes["KG"]="Kyrgyzstan";
    $CountriesCodes["KH"]="Cambodia";
    $CountriesCodes["KI"]="Kiribati";
    $CountriesCodes["KM"]="Comoros";
    $CountriesCodes["KN"]="Saint Kitts and Nevis";
    $CountriesCodes["KR"]="Korea (South)";
    $CountriesCodes["KW"]="Kuwait";
    $CountriesCodes["KY"]="Cayman Islands";
    $CountriesCodes["KZ"]="Kazakhstan";
    $CountriesCodes["LA"]="Laos";
    $CountriesCodes["LC"]="Saint Lucia";
    $CountriesCodes["LI"]="Liechtenstein";
    $CountriesCodes["LK"]="Sri Lanka";
    $CountriesCodes["LS"]="Lesotho";
    $CountriesCodes["LT"]="Lithuania";
    $CountriesCodes["LU"]="Luxembourg";
    $CountriesCodes["LV"]="Latvia";
    $CountriesCodes["LY"]="Libya";
    $CountriesCodes["MA"]="Morocco";
    $CountriesCodes["MC"]="Monaco";
    $CountriesCodes["MD"]="Moldova";
    $CountriesCodes["ME"]="Montenegro";
    $CountriesCodes["MG"]="Madagascar";
    $CountriesCodes["MH"]="Marshall Islands";
    $CountriesCodes["MK"]="Macedonia";
    $CountriesCodes["ML"]="Mali";
    $CountriesCodes["MM"]="Myanmar";
    $CountriesCodes["MN"]="Mongolia";
    $CountriesCodes["MO"]="Macau";
    $CountriesCodes["MP"]="Northern Mariana Islands";
    $CountriesCodes["MQ"]="Martinique";
    $CountriesCodes["MR"]="Mauritania";
    $CountriesCodes["MS"]="Montserrat";
    $CountriesCodes["MT"]="Malta";
    $CountriesCodes["MU"]="Mauritius";
    $CountriesCodes["MV"]="Maldives";
    $CountriesCodes["MW"]="Malawi";
    $CountriesCodes["MX"]="Mexico";
    $CountriesCodes["MY"]="Malaysia";
    $CountriesCodes["MZ"]="Mozambique";
    $CountriesCodes["NA"]="Namibia";
    $CountriesCodes["NC"]="New Caledonia";
    $CountriesCodes["NE"]="Niger";
    $CountriesCodes["NF"]="Norfolk Island";
    $CountriesCodes["NG"]="Nigeria";
    $CountriesCodes["NI"]="Nicaragua";
    $CountriesCodes["NL"]="Netherlands";
    $CountriesCodes["NO"]="Norway";
    $CountriesCodes["NP"]="Nepal";
    $CountriesCodes["NR"]="Nauru";
    $CountriesCodes["NT"]="Neutral Zone";
    $CountriesCodes["NU"]="Niue";
    $CountriesCodes["NZ"]="New Zealand (Aotearoa)";
    $CountriesCodes["OM"]="Oman";
    $CountriesCodes["PA"]="Panama";
    $CountriesCodes["PE"]="Peru";
    $CountriesCodes["PF"]="French Polynesia";
    $CountriesCodes["PG"]="Papua New Guinea";
    $CountriesCodes["PH"]="Philippines";
    $CountriesCodes["PK"]="Pakistan";
    $CountriesCodes["PL"]="Poland";
    $CountriesCodes["PM"]="St. Pierre and Miquelon";
    $CountriesCodes["PN"]="Pitcairn";
    $CountriesCodes["PR"]="Puerto Rico";
    $CountriesCodes["PS"]="Palestinian Territory";
    $CountriesCodes["PT"]="Portugal";
    $CountriesCodes["PW"]="Palau";
    $CountriesCodes["PY"]="Paraguay";
    $CountriesCodes["QA"]="Qatar";
    $CountriesCodes["RE"]="Reunion";
    $CountriesCodes["RO"]="Romania";
    $CountriesCodes["RS"]="Serbia";
    $CountriesCodes["RU"]="Russian Federation";
    $CountriesCodes["RW"]="Rwanda";
    $CountriesCodes["SA"]="Saudi Arabia";
    $CountriesCodes["SB"]="Solomon Islands";
    $CountriesCodes["SC"]="Seychelles";
    $CountriesCodes["SE"]="Sweden";
    $CountriesCodes["SG"]="Singapore";
    $CountriesCodes["SH"]="St. Helena";
    $CountriesCodes["SI"]="Slovenia";
    $CountriesCodes["SJ"]="Svalbard and Jan Mayen Islands";
    $CountriesCodes["SK"]="Slovak Republic";
    $CountriesCodes["SL"]="Sierra Leone";
    $CountriesCodes["SM"]="San Marino";
    $CountriesCodes["SN"]="Senegal";
    $CountriesCodes["SR"]="Suriname";
    $CountriesCodes["ST"]="Sao Tome and Principe";
    $CountriesCodes["SU"]="USSR (former)";
    $CountriesCodes["SV"]="El Salvador";
    $CountriesCodes["SZ"]="Swaziland";
    $CountriesCodes["TC"]="Turks and Caicos Islands";
    $CountriesCodes["TD"]="Chad";
    $CountriesCodes["TF"]="French Southern Territories";
    $CountriesCodes["TG"]="Togo";
    $CountriesCodes["TH"]="Thailand";
    $CountriesCodes["TJ"]="Tajikistan";
    $CountriesCodes["TK"]="Tokelau";
    $CountriesCodes["TM"]="Turkmenistan";
    $CountriesCodes["TN"]="Tunisia";
    $CountriesCodes["TO"]="Tonga";
    $CountriesCodes["TP"]="East Timor";
    $CountriesCodes["TR"]="Turkey";
    $CountriesCodes["TT"]="Trinidad and Tobago";
    $CountriesCodes["TV"]="Tuvalu";
    $CountriesCodes["TW"]="Taiwan";
    $CountriesCodes["TZ"]="Tanzania";
    $CountriesCodes["UA"]="Ukraine";
    $CountriesCodes["UG"]="Uganda";
    $CountriesCodes["UM"]="US Minor Outlying Islands";
    $CountriesCodes["US"]="United States";
    $CountriesCodes["UY"]="Uruguay";
    $CountriesCodes["UZ"]="Uzbekistan";
    $CountriesCodes["VA"]="Vatican City State (Holy See)";
    $CountriesCodes["VC"]="Saint Vincent and the Grenadines";
    $CountriesCodes["VE"]="Venezuela";
    $CountriesCodes["VG"]="Virgin Islands (British)";
    $CountriesCodes["VI"]="Virgin Islands (U.S.)";
    $CountriesCodes["VN"]="Viet Nam";
    $CountriesCodes["VU"]="Vanuatu";
    $CountriesCodes["WF"]="Wallis and Futuna Islands";
    $CountriesCodes["WS"]="Samoa";
    $CountriesCodes["YE"]="Yemen";
    $CountriesCodes["YT"]="Mayotte";
    $CountriesCodes["ZA"]="South Africa";
    $CountriesCodes["ZM"]="Zambia";
    $CountriesCodes["COM"]="US Commercial";
    $CountriesCodes["EDU"]="US Educational";
    $CountriesCodes["GOV"]="US Government";
    $CountriesCodes["INT"]="International";
    $CountriesCodes["MIL"]="US Military";
    $CountriesCodes["NET"]="Network";
    $CountriesCodes["ORG"]="Non-Profit Organization";
    $CountriesCodes["ARPA"]="Old style Arpanet";

    if($ProxyCertificateWizard["CountryName"]==null){$ProxyCertificateWizard["CountryName"]="US";}
    if($ProxyCertificateWizard["stateOrProvinceName"]==null){$ProxyCertificateWizard["stateOrProvinceName"]="California";}
    if($ProxyCertificateWizard["localityName"]==null){$ProxyCertificateWizard["localityName"]="Los Angeles";}
    if($ProxyCertificateWizard["organizationName"]==null){$ProxyCertificateWizard["organizationName"]="ACME ltd";}
    if($ProxyCertificateWizard["organizationalUnitName"]==null){$ProxyCertificateWizard["organizationalUnitName"]="Proxy Internet Access";}


    $form[]=$tpl->field_hidden("portid",$portid);
    $form[]=$tpl->field_array_hash($CountriesCodes,"CountryName","nonull:{CountryName}",$ProxyCertificateWizard["CountryName"]);
    $form[]=$tpl->field_text("stateOrProvinceName","{stateOrProvinceName}",$ProxyCertificateWizard["stateOrProvinceName"]);
    $form[]=$tpl->field_text("localityName","{localityName}",$ProxyCertificateWizard["localityName"]);
    $form[]=$tpl->field_text("organizationName","{organizationName}",$ProxyCertificateWizard["organizationName"]);
    $form[]=$tpl->field_text("organizationalUnitName","{organizationalUnitName}",$ProxyCertificateWizard["organizationalUnitName"]);


    $addon=null;
    if(isset($_GET["main-wizard"])){
        $addon="&main-wizard=yes";
    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/selfsign.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/selfsign.log";
    $ARRAY["CMD"]="openssl.php?wizard-proxy=yes";
    $ARRAY["TITLE"]="{squid_wizard_ssl1_title}";
    $ARRAY["AFTER"]="LoadAjax('port-ssl-wizard','$page?exit=$portid&$addon');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-$t')";




    $squid_wizard_ssl1=$tpl->_ENGINE_parse_body("{squid_wizard_ssl1}");
    $squid_wizard_ssl1=str_replace("%ppport","",$squid_wizard_ssl1);
    $html[]="<div id='progress-$t' style='margin-top:20px;margin-bottom:20px;'></div>";
    $html[]=$tpl->div_wizard("{squid_wizard_ssl1_title}||$squid_wizard_ssl1");
    $html[]=$tpl->form_outside(null, $form,null,"{next}",$jsrestart,"AsSquidAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}