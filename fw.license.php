<?php
// Patch license
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsWizardExecuted",1);
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/ressources/class.identity.inc');
if(isset($_GET["js-expire-soon-explain"])){expire_soon_explain_js();exit;}
if(isset($_GET["popup-expire-soon-explain"])){expire_soon_explain_popup();exit;}
if(isset($_POST["LICENSE-API-KEY"]) || isset($_POST["password"])){save_api_key();exit;}
if(isset($_GET["upload-lic"])){upload_lic_js();exit;}
if(isset($_GET["upload-lic-popup"])){upload_lic_popup();exit;}
if(isset($_GET["token"])){save_api_key_get();}
if(isset($_GET["register-token"])){js_request_token();exit;}
if(isset($_GET["get-token"])){js_get_api_token();exit;}
if(isset($_GET["change-token"])){page_request_apikey();exit;}
if(isset($_GET["create-account"])){page_create_account();exit;}
if(isset($_GET['resent-activation-code'])){page_resent_code();exit;}
if(isset($_GET["create-new-account"])){page_create_new_account();exit;}
if(isset($_GET["register-save-failed"])){save_register_save_failed();}
if(isset($_GET["reset-js"])){reset_license_js();exit;}
if(isset($_POST["reset"])){reset_license_perform();exit;}
if(isset($_GET['reset-uuid'])){reset_uuid();exit;}
if(isset($_GET["ch-uuid"])){change_uuid_js();exit;}
if(isset($_GET["ch-uuid-popup"])){change_uuid_popup();exit;}
if(isset($_GET["tiny-js"])){tiny_js();exit;}
if (isset($_GET["table"])) {
    table();
    exit;
}
if (isset($_POST["LICENCE_REQUEST"])) {
    LICENCE_REQUEST();
    exit;
}
if (isset($_GET["file-uploaded"])) {
    file_uploaded();
    exit;
}

page();


function save_register_save_failed():bool{
    $email=$_GET["register-save-failed"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("REGISTER_EMAIL",$email);
    return true;
}
function expire_soon_explain_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog4("{artica_license}:{ExpiresSoon}","$page?popup-expire-soon-explain=yes",550);
}
function expire_soon_explain_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]=$tpl->div_explain("{ExpiresSoon_explains}");
    echo url_decode_special_tool($tpl->_ENGINE_parse_body($html));
    return true;
}
function reset_license_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $tpl->js_dialog_confirm_action("{reset_the_license}","reset","Reset the Current License","LoadAjax('table-loader-license-service','$page?table=yes');");

    return true;
}
function reset_license_perform():bool{
    $sock=new sockets();
    $sock->REST_API("/license/reset");
    return admin_tracks("Artica License was reseted");
}
function reset_uuid(){
    $sock=new sockets();
    $sock->REST_API("/reset/uuid");
}
function change_uuid_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $uuid       = $_GET["ch-uuid"];
    return $tpl->js_dialog4("{uuid}: $uuid","$page?ch-uuid-popup=$uuid");
}
function upload_lic_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }
    return $tpl->js_dialog4("{upload_your_license}","$page?upload-lic-popup=yes",550);
}
function upload_lic_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $upload_button=$tpl->button_upload("{key_file} (*.key)", $page);
    $html[]=$tpl->div_explain("{upload_your_license}||{upload_artica_license_explain}");
    $html[]="<div style='margin-top:20px;text-align:right;border-top:1px solid #CCCCCC;padding-top:10px'>$upload_button</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function expire_text($FINAL_TIME=0):array{
    $tpl=new template_admin();
    $ExpiresSoon_label      = "<div style='float: right'>&nbsp;&nbsp;<span class='label label-info'>{use_community_edition}</span>&nbsp;&nbsp;</div>";
    $ExpiresSoon_eval        = "<div style='float: right'>&nbsp;&nbsp;<span class='label label-warning'>{evaluation_mode}</span>&nbsp;&nbsp;</div>";
    $ExpiresSoon_expired    = "<div style='float: right'>&nbsp;&nbsp;<span class='label label-danger'>{expired}</span>&nbsp;&nbsp;</div>";
    $ExpiresSoon_text="";
    $ExpiresSoon_ok= "<div style='float: right'>&nbsp;&nbsp;<span class='label label-primary'>Enterprise Edition</span>&nbsp;&nbsp;</div>";


    if($GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()){
        return array($ExpiresSoon_ok,"{unlimited}");
    }


    if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $ExpiresSoon_label=$ExpiresSoon_ok;
        $LicenseInfos["license_status"]="{license_active}";
    }


    if ($FINAL_TIME>0) {
        $ExpiresSoon_label=$ExpiresSoon_ok;
        $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));

        $distanceOfTimeInWords="(".distanceOfTimeInWords(time(), $FINAL_TIME).")";
        if ($ExpiresSoon<7) {
            $ExpiresSoon_text="<span class=text-danger>&nbsp;{ExpiresSoon}</span>";
            $ExpiresSoon_label=$ExpiresSoon_eval;
        }

        if ($ExpiresSoon<31) {
            VERBOSE("Evaluation Mode",__LINE__);
            $ExpiresSoon_label=$ExpiresSoon_eval;
        }

        if ($FINAL_TIME<time()) {
            $head_error="{license_expired_explain}";
            $ExpiresSoon_text="<span class=text-danger><strong>&laquo;{expired}&raquo;</strong></span>";
            $distanceOfTimeInWords=null;
            $ExpiresSoon_label=$ExpiresSoon_expired;
        }

        return array($ExpiresSoon_label,$tpl->time_to_date($FINAL_TIME)." $distanceOfTimeInWords$ExpiresSoon_text");



    }

    return array($ExpiresSoon_label,"{never}");


}
function tiny_js(){
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $isCorpGold=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("isCorpGold"));
if(!isset($LicenseInfos["max_server"])){
    $LicenseInfos["max_server"]="";
}
    $cea2=$tpl->button_autnonome("{resent_activation_code}",
        "Loadjs('$page?resent-activation-code=yes')",
        "fas fa-envelope","AsSystemAdministrator",255);

    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";

    if($isCorpGold==0) {
        $bts[] = "<label class=\"btn btn-primary\" 
        OnClick=\"s_PopUpFull('https://licensing.artica.center/','1024','900');\">
        <i class='fa-duotone fa-earth-americas'></i> {license_server} </label>";


        $bts[] = "<label class=\"btn btn-info\" 
        OnClick=\"LoadAjax('table-loader-license-service','$page?create-account=yes');\">
        <i class='fa-solid fa-user-plus'></i> {create_account} </label>";

        $bts[] = "<label class=\"btn btn-primary\" 
        OnClick=\"Loadjs('$page?resent-activation-code=yes');\">
        <i class='fa-duotone fa-envelope'></i> {resent_activation_code} </label>";
    }
    if($LicenseInfos["max_server"]==null ){
        $bts[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('fw.license.gold.php')\"><i class='fa-solid fa-treasure-chest'></i> {gold_license} </label>";
    }



    $bts[]="<label class=\"btn btn-warning\" 
            OnClick=\"Loadjs('$page?reset-js=yes')\">
                <i class='fa-solid fa-trash-can'></i> {reset_the_license} </label>";

    $bts[]="<label class=\"btn btn-primary\" 
            OnClick=\"Loadjs('fw.license.events.php')\">
                <i class='fa-solid fa-eye'></i> {events} </label>";

    $bts[]="</div>";

    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$bts=array();}
    $TINY_ARRAY["TITLE"]="{artica_license}";
    $TINY_ARRAY["ICO"]="fa-duotone fa-file-certificate";
    $TINY_ARRAY["EXPL"]="{CORP_LICENSE_EXPLAIN}";
    $TINY_ARRAY["URL"]="license";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo $jstiny;


}
function change_uuid_popup(){
    $uuid=$_GET["ch-uuid-popup"];
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $t          = time();
    $tt[]="dialogInstance4.close();";
    $tt[]="if( document.getElementById('table-loader-license-service') ){ LoadAjax('table-loader-license-service','$page?table=yes');}";
    $tt[]="if( document.getElementById('table-loader-system') ){ LoadAjax('table-loader-system','fw.system.information.php?table=yes');}";

    $jsafter=@implode("",$tt);
    $js=$tpl->framework_buildjs("/reset/uuid",
        "reset-uuid.progress",
        "reset-uuid.log",
        "reset-uuid-$t",$jsafter,null,"{change_uuid}","AsSystemAdministrator"
    );

    $chuuid_why=$tpl->_ENGINE_parse_body("{chuuid_why}</strong>");
    $chuuid_why=str_replace("%s",$uuid,$chuuid_why);
    $html[]=$tpl->div_warning($chuuid_why);
    $html[]="<div id='reset-uuid-$t'><center style='margin:30px'>";
    $html[]=$tpl->button_autnonome("{change_uuid}", $js, "fas fa-file-code","AsSystemAdministrator",335);
    $html[]="</center></div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function page($return=false):bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $addon      = null;
    if (isset($_GET["request"])) {
        $addon="&request=yes";
    }

    $html=$tpl->page_header("{artica_license}",
        "fa-duotone fa-file-certificate","{CORP_LICENSE_EXPLAIN}","$page?table=yes$addon","license","progress-license-restart",false,"table-loader-license-service");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{artica_license}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    if (isset($_GET["request-page"])) {
        $tpl=new template_admin("{artica_license}", $html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function save_api_key(){

    $_SESSION["LICENSE-API-KEY"]=$_POST["LICENSE-API-KEY"];
    $_SESSION["EMPLOYEES"]=$_POST["EMPLOYEES"];
    $_SESSION["username"] = $_POST["username"];
    $_SESSION["password"] = $_POST["password"];
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $LicenseInfos["X-API-KEY"]=$_POST["LICENSE-API-KEY"];
    $LicenseInfos["EMPLOYEES"]=$_POST["EMPLOYEES"];
    $LicenseInfos["username"]=$_POST["username"];
    $LicenseInfos["password"]=$_POST["password"];
    $NewLicenseInfos=base64_encode(serialize($LicenseInfos));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicenseInfos",$NewLicenseInfos);
}
function save_api_key_get():bool{

    $_SESSION["LICENSE-API-KEY"]=$_GET["token"];
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $LicenseInfos["X-API-KEY"]=$_GET["token"];
    $NewLicenseInfos=base64_encode(serialize($LicenseInfos));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicenseInfos",$NewLicenseInfos);
    return true;
}
function page_request_apikey():bool{
    $change_private_key_explain=null;
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $accountInfos = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AccountInfo")));
    $uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px' nowrap=''><div style='width:350px'>";
    $html[]=left_status($LicenseInfos);
    $html[]="</div>";
    $html[]="<td style='vertical-align:top;width:100%'>";
    if(!isset($LicenseInfos["EMPLOYEES"])){
        $LicenseInfos["EMPLOYEES"]=5;
    }

    $REGISTER_EMAIL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("REGISTER_EMAIL");
    if(strpos($REGISTER_EMAIL,"@")>0) {
        $LicenseInfos["mail"] = $REGISTER_EMAIL;
    }

    if(!isset($LicenseInfos["password"])){$LicenseInfos["password"]=null;}
    if($LicenseInfos["X-API-KEY"]<>null) {

        $form[] = $tpl->field_text("LICENSE-API-KEY", "{private_key}", $LicenseInfos["X-API-KEY"], false, null, false, true);
    }else{
        $form[] = $tpl->field_hidden("LICENSE-API-KEY",$LicenseInfos["X-API-KEY"]);
        //$form[] = $tpl->field_section("{your_registered_account}","{your_registered_account_explain}");
    }
    $form[]=$tpl->field_email("username","{email}",$REGISTER_EMAIL,true);
    $form[]=$tpl->field_password("password","{password}",$LicenseInfos["password"],true);

    if(isset($_GET["change-token"])){
        $tpl->form_add_button("{cancel}","LoadAjax('table-loader-license-service','$page?table=yes');");
        $change_private_key_explain=$tpl->_ENGINE_parse_body("{change_private_key_explain}")."<br>";
        $change_private_key_explain=str_replace("%UUID%",$uuid,$change_private_key_explain);
        $change_private_key_explain=str_replace("%PPKEY%",$LicenseInfos["X-API-KEY"],$change_private_key_explain);
    }

    $html[]="<div class='animated fadeInDown' style='max-width:800px'>";
    $html[]="<div id='REQUEST-API-KEY' style='margin-left:15px'></div>";

    if (!isset($LicenseInfos["X-API-KEY"])  || empty($LicenseInfos["X-API-KEY"])){
        $html[]=$tpl->form_outside("{artica_license}&nbsp;&raquo;&nbsp;{link_this_server_account}", @implode("\n", $form), "<H2>{your_registered_account}</H2>{your_registered_account_explain}", "{login}", "Loadjs('$page?get-token=yes');", "AsSystemAdministrator");
    }
    else {
        $html[]=$tpl->form_outside("{artica_license}&nbsp;&raquo;&nbsp;{link_this_server_account}", @implode("\n", $form), "$change_private_key_explain{link_this_server_account_explain}", "{ask_license}", "Loadjs('$page?register-token=yes');", "AsSystemAdministrator");
    }
    $html[]="</div>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>Loadjs('$page?tiny-js=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function page_create_account(){
    $change_private_key_explain=null;
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");

    if(!isset($LicenseInfos["EMPLOYEES"])){
        $LicenseInfos["EMPLOYEES"]=5;
    }
    $country = array(
        'AF' => 'Afghanistan',
        'AX' => 'Åland Islands',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua & Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AC' => 'Ascension Island',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia & Herzegovina',
        'BW' => 'Botswana',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'VG' => 'British Virgin Islands',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'IC' => 'Canary Islands',
        'CV' => 'Cape Verde',
        'BQ' => 'Caribbean Netherlands',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'EA' => 'Ceuta & Melilla',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo - Brazzaville',
        'CD' => 'Congo - Kinshasa',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => 'Côte d’Ivoire',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CW' => 'Curaçao',
        'CY' => 'Cyprus',
        'CZ' => 'Czechia',
        'DK' => 'Denmark',
        'DG' => 'Diego Garcia',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'SZ' => 'Eswatini',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong SAR China',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'XK' => 'Kosovo',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao SAR China',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar (Burma)',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'KP' => 'North Korea',
        'MK' => 'North Macedonia',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestinian Territories',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn Islands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'XA' => 'Pseudo-Accents',
        'XB' => 'Pseudo-Bidi',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Réunion',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'São Tomé & Príncipe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SX' => 'Sint Maarten',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia & South Sandwich Islands',
        'KR' => 'South Korea',
        'SS' => 'South Sudan',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'BL' => 'St. Barthélemy',
        'SH' => 'St. Helena',
        'KN' => 'St. Kitts & Nevis',
        'LC' => 'St. Lucia',
        'MF' => 'St. Martin',
        'PM' => 'St. Pierre & Miquelon',
        'VC' => 'St. Vincent & Grenadines',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard & Jan Mayen',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad & Tobago',
        'TA' => 'Tristan da Cunha',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks & Caicos Islands',
        'TV' => 'Tuvalu',
        'UM' => 'U.S. Outlying Islands',
        'VI' => 'U.S. Virgin Islands',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican City',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'WF' => 'Wallis & Futuna',
        'EH' => 'Western Sahara',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    );
    $blang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

    $form[]=$tpl->field_text("company","{company}",$LicenseInfos["company"],true);
    $form[]=$tpl->field_text("website","{website}",$LicenseInfos["website"]);
    $form[]=$tpl->field_array_hash_simple($country,"country","{country}",$blang);
    $form[]=$tpl->field_text("name","{name}",urldecode($LicenseInfos["name"]),true);
    $form[]=$tpl->field_email("email","{email}",urldecode($LicenseInfos["email"]),true);
    $form[]=$tpl->field_password("password","{password}",$LicenseInfos["password"],true);
    $form[]=$tpl->field_checkbox("terms","{terms_and_services}",1);
    $tpl->form_add_button("{cancel}","LoadAjax('table-loader-license-service','$page?table=yes');");


    if(isset($_GET["create-account"])){
        $tpl->form_add_button("{cancel}","LoadAjax('table-loader-license-service','$page?table=yes');");
        $change_private_key_explain=str_replace("%UUID%",$uuid,$change_private_key_explain);
        $change_private_key_explain=str_replace("%PPKEY%",$LicenseInfos["X-API-KEY"],$change_private_key_explain);
    }

    $html[]="<div class='animated fadeInDown' style='max-width:800px'>";
    $html[]="<div id='REQUEST-API-KEY'></div>";
    $html[]=$tpl->form_outside("{artica_license}&nbsp;&raquo;&nbsp;{create_new_account_title}", @implode("\n", $form), "$change_private_key_explain{create_new_account_explain}", "{create}", "Loadjs('$page?create-new-account=yes');", "AsSystemAdministrator");
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);

}
function page_create_new_account(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $no_internet_connection=$tpl->javascript_parse_text("{no_internet_connection}");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.license.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica_license.txt";
    //$ARRAY["CMD"]="services.php?license-register=yes";
    $ARRAY["CMD"]="/register/license";
    $ARRAY["TITLE"]="{artica_license}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-license-service','$page?table=yes');";





    $html="	var x_RegisterSave= function (obj) {
		var tempvalue=obj.responseText;
		
		
    }
    
	function RegisterSave(){
        var company = document.querySelectorAll('input[name=company]')[0].value;
        var www = document.querySelectorAll('input[name=website]')[0].value;
        var country = $('[name=country]').val()
        var name = document.querySelectorAll('input[name=name]')[0].value;
        var email = document.querySelectorAll('input[name=email]')[0].value;
        var password = document.querySelectorAll('input[name=password]')[0].value;
        var terms = document.querySelectorAll('input[name=terms]')[0].checked;
        if (terms == false){
            swal( {title:'Oops...', text:'<H1>Error!</H1>{terms_services_alert}', html: true,type:'error'})
            return false;
        }
        if (country == ''){
            swal( {title:'Oops...', text:'<H1>Error!</H1>{select_country_alert}', html: true,type:'error'})
            return false;
        }
$.ajax({
	type: 'POST',
	url: 'https://licensing.artica.center/api/create/account',
	data: {'company': company, 'www':www,'country':country, 'name':name,'email':email,'password':password,'terms':terms},
	cache: false, 
	success: function (bucket) {
	  if (bucket.status == false) {
	      Loadjs('$page?register-save-failed='+email);
          swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.message, html: true,type:'error'})

	  }
	  if (bucket.status == true) {
        swal( {title:'Success!', text:bucket.message, html: true,type:'success'})
        LoadAjax('table-loader-license-service','$page?table=yes&register-save=yes&email='+email)
		
	  }
	},
	error:function(bucket) {
		console.log(bucket);
		if (bucket.readyState == 4) {
            //alert(bucket.responseJSON.message);
            if ($.isPlainObject(bucket.responseJSON.message)){
                console.log('is array')
            var message = ''
            Object.keys(bucket.responseJSON.message).map(function(objectKey, index) {
                var value = bucket.responseJSON.message[objectKey];
                message += value+'<br>';
            });
            }
            else{
                console.log('is not array')
            var message = bucket.responseJSON.message
            }
            swal( {title:'Oops...', text:'<H1>Error!</H1>'+message, html: true,type:'error'})
		}
		else {
            //alert('$no_internet_connection');
            swal( {title:'Oops...', text:'<H1>Error!</H1>$no_internet_connection', html: true,type:'error'})
		}	
	}
  }); 

	}
	
	RegisterSave();";

    header("content-type: application/x-javascript");
    echo $html;

}
function page_gold_license():bool{
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $WizardSaved            = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings");
    $License                = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos");
    $LicenseInfos           = unserialize(base64_decode($License));
    $WizardSavedSettings    = unserialize(base64_decode($WizardSaved));
    if(!isset($LicenseInfos["COMPANY"])){$LicenseInfos["COMPANY"]=$WizardSavedSettings["organization"];}
    $uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");


    $tpl->table_form_field_text("{license_status}","{license_active}",ico_certificate);
    $tpl->table_form_field_text("{expire}","{never}",ico_timeout);
    $tpl->table_form_field_text("{uuid}",$uuid,ico_computer);
    $tpl->table_form_field_text("{company}",$LicenseInfos["COMPANY"],ico_computer);
    $tpl->table_form_field_text("{license_number}",$LicenseInfos["license_number"],ico_certificate);





    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:1%' valign='top'>";
    $html[]=$tpl->widget_vert("{gold_license}","{license_active}<hr>{expire}: {never}");
    $html[]="</td>";
    $html[]="<td style='width:350px'>";
    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>Loadjs('$page?tiny-js=yes');</script>";



    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function page_resent_code(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $no_internet_connection=$tpl->javascript_parse_text("{no_internet_connection}");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.license.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica_license.txt";
    //$ARRAY["CMD"]="services.php?license-register=yes";
    $ARRAY["CMD"]="/register/license";
    $ARRAY["TITLE"]="{artica_license}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["AFTER_FAILED"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["REFRESH-MENU"]="yes";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=REQUEST-API-KEY')";

    $link_this_server_account=$tpl->_ENGINE_parse_body("{link_this_server_account}");


    $html="	var x_RegisterSave= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$jsrestart;	
		
	}

	function RegisterSave(){
	
	    if(!document.querySelectorAll('input[name=username]')[0]){
            swal( {title:'Wrong section', text:'<H1>Error!</H1>Please use the <br><strong>$link_this_server_account</strong> section', html: true,type:'error'})
            return false;	    
	    }
	
        var u = document.querySelectorAll('input[name=username]')[0].value;
        
        if(u.length==0){
            swal( {title:'eMail Address missing', text:'<H1>Error!</H1>Please type your eMail address', html: true,type:'error'})
            return false;
        }
        
$.ajax({
	type: 'POST',
	url: 'https://licensing.artica.center/api/resent/activation/code',
	data: {'username': u},
	cache: false, 
	success: function (bucket) {
		console.log(bucket)
	  if (bucket.status == false) {
          swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.message, html: true,type:'error'})

	  }
	  if (bucket.status == true) {
        swal( {title:'Activation Code', text:'<H1>Success!</H1>'+bucket.message, html: true,type:'success'})      
        
		
	  }
	},
	error:function(bucket) {
		console.log(bucket);
		if (bucket.readyState == 4) {
            //alert(bucket.responseJSON.message); 
            swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.responseJSON.message, html: true,type:'error'})
		}
		else {
            //alert('$no_internet_connection');
            swal( {title:'Oops...', text:'<H1>Error!</H1>$no_internet_connection', html: true,type:'error'})
		}	
	}
  }); 

	}
	
	RegisterSave();";

    header("content-type: application/x-javascript");
    echo $html;
}
function js_get_api_token(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");
    $no_internet_connection=$tpl->javascript_parse_text("{no_internet_connection}");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.license.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica_license.txt";
    //$ARRAY["CMD"]="services.php?license-register=yes";
    $ARRAY["CMD"]="/register/license";
    $ARRAY["TITLE"]="{artica_license}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["AFTER_FAILED"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["REFRESH-MENU"]="yes";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=REQUEST-API-KEY')";



    $html="	var x_RegisterSave= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$jsrestart;	
		
	}

	function RegisterSave(){
        var u = document.querySelectorAll('input[name=username]')[0].value;
        var p = document.querySelectorAll('input[name=password]')[0].value;
$.ajax({
	type: 'POST',
	url: 'https://licensing.artica.center/api/get/key/by/user',
	data: {'username': u, 'password':p,'uuid':'$uuid'},
	cache: false, 
	success: function (bucket) {
	  if (bucket.status == false) {
          if(bucket.blacklisted == true){
            swal( {title:'Oops...', text:'<H1>Error!</H1>Reseting the UUID, please try again.', html: true,type:'error'})
            setTimeout(function(){ Loadjs('$page?reset-uuid=yes') }, 1000);
          }
          else {
            swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.message, html: true,type:'error'})
          }
	  }
	  if (bucket.status == true) {
        swal( {title:'Success!', text:bucket.message, html: true,type:'success'})
        document.querySelectorAll('input[name=LICENSE-API-KEY]')[0].value = bucket.token
        setTimeout(function(){ Loadjs('$page?token='+bucket.token+'&register-token=yes') }, 1000);
        
        
		
	  }
	},
	error:function(bucket) {
		console.log(bucket);
		if (bucket.readyState == 4) {
            if(bucket.responseJSON.blacklisted == true){
                console.log('vl0');
                swal( {title:'Oops...', text:'<H1>Error!</H1>Reseting the UUID, please try again.', html: true,type:'error'})
                setTimeout(function(){ Loadjs('$page?reset-uuid=yes') }, 1000);
                LoadAjax('table-loader-license-service','$page?table=yes');

              }
              else {
              console.log('vl11');
            //alert(bucket.responseJSON.message); 
			swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.responseJSON.message, html: true,type:'error'})
			//location.reload(); 
            LoadAjax('table-loader-license-service','$page?table=yes');
              }


		}
		else {
            //alert('$no_internet_connection');
			swal( {title:'Oops...', text:'<H1>Error!</H1>$no_internet_connection', html: true,type:'error'})
		}	
	}
  }); 

	}
	
	RegisterSave();";

    header("content-type: application/x-javascript");
    echo $html;

}
function js_request_token(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $token2=null;
    $token=null;
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    if(isset($LicenseInfos["key"])) {
        $token2 = $LicenseInfos["key"];
    }

    if(isset($_SESSION["LICENSE-API-KEY"])){
        $token=$_SESSION["LICENSE-API-KEY"];
    }
    if ($token==null){
        $token=$token2;
    }
    $employees=$_SESSION["EMPLOYEES"];
    $uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");
    $no_internet_connection=$tpl->javascript_parse_text("{no_internet_connection}");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.license.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica_license.txt";
    //$ARRAY["CMD"]="services.php?license-register=yes";
    $ARRAY["CMD"]="/register/license";
    $ARRAY["TITLE"]="{artica_license}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["AFTER_FAILED"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["REFRESH-MENU"]="yes";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=REQUEST-API-KEY')";

    if($token==null){
        $tpl->js_error("{error_copy_paste_the_private_key}");
        return null;
    }

    $html="	var x_RegisterSave= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$jsrestart;	
		
	}

	function RegisterSave(){

		var token = '$token';
$.ajax({
	type: 'POST',
	url: 'https://licensing.artica.center/api/get/key',
	data: {'X-API-KEY': token,'uuid':'$uuid'},
	cache: false, 
	success: function (bucket) {
	  if (bucket.status == false) {
        swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.error, html: true,type:'error'})
	  }
	  if (bucket.status == true) {
		var XHR = new XHRConnection();
		XHR.appendData('tokenRequest',bucket.message);
		XHR.appendData('EMPLOYEES','$employees');
		XHR.appendData('LICENCE_REQUEST','1');
		XHR.sendAndLoad('$page', 'POST',x_RegisterSave);
		
	  }
	},
	error:function(bucket) {
		console.log(bucket);
		if (bucket.readyState == 4) {
            swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.responseJSON.error, html: true,type:'error'})
		}
		else {
            swal( {title:'Oops...', text:'<H1>Error!</H1>$no_internet_connection', html: true,type:'error'})
		}	
	}
  }); 

	}
	
	RegisterSave();";

    header("content-type: application/x-javascript");
    echo $html;

}
function table(){
    $page                   = CurrentPageName();
    $tpl                    = new template_admin();
    $users                  = new usersMenus();
    $license_number         = null;
    $uuid                   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");
    $ExpiresSoon_ok= "<div style='float: right'>&nbsp;&nbsp;<span class='label label-primary'>Enterprise Edition</span>&nbsp;&nbsp;</div>";
    VERBOSE("Starting TABLE...",__LINE__);

    if(isset($_GET["register-save"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("REGISTER_EMAIL",$_GET["email"]);
    }
    $uuid_text=$uuid;
    $sidentity              = new sidentity();
    $RegisterCloudBadEmail  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RegisterCloudBadEmail"));
    $LicenseInfos           = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    VERBOSE("License_number: {$LicenseInfos["license_number"]}");
    if(isset($LicenseInfos["license_number"])){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("isCorpGold"))==1){
            return page_gold_license();

        }
    }



    if(!isset($LicenseInfos["ABOUT_PP"])){$LicenseInfos["ABOUT_PP"]=null;}
    if(!isset($LicenseInfos["GoldKey"])){$LicenseInfos["GoldKey"]=null;}
    if(!isset($LicenseInfos["EMAIL"])){$LicenseInfos["EMAIL"]=null;}
    if(!isset($LicenseInfos["FINAL_TIME"])){$LicenseInfos["FINAL_TIME"]=0;}
    if(!isset($LicenseInfos["COMPANY"])){$LicenseInfos["COMPANY"]=null;}
    if(!isset($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=0;}
    $WizardSavedSettings    = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
    $WizardSavedSettings    = $sidentity->RepairSidentity($WizardSavedSettings);
    $FINAL_TIME             = intval($LicenseInfos["FINAL_TIME"]);
    $TIME                   = intval($LicenseInfos["TIME"]);
    $GOLDKEY                = $GLOBALS["CLASS_SOCKETS"]->CORP_GOLD();
    $head_error             = null;
    if(!isset($WizardSavedSettings["employees"])){
        $WizardSavedSettings["employees"]="";
    }

    if(!isset($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]="";}

    if($LicenseInfos["ABOUT_PP"]==null){

        $Link="s_PopUpFull(' https://licensing.artica.center/','1024','900');";
        $no_assigned_license_explain=$tpl->_ENGINE_parse_body("{no_assigned_license_explain}");
        $url=$tpl->td_href("https://licensing.artica.center/","",$Link);
        $no_assigned_license_explain=str_replace("%s",$url,$no_assigned_license_explain);
        $LicenseInfos["ABOUT_PP"]="<span class='text-danger'>{no_assigned_license}</span>
        <br><small>$no_assigned_license_explain</small>";

    }

    if ($LicenseInfos["COMPANY"]==null) {
        $LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];
    }
    if ($LicenseInfos["EMAIL"]==null) {
        $LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];
    }
    if (!is_numeric($LicenseInfos["EMPLOYEES"])) {
        $LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];
    }


    if(!isset($LicenseInfos["X-API-KEY"])){$LicenseInfos["X-API-KEY"]=null;}
    $Migration = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Migration"));

    if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        VERBOSE("CORP_LICENSE = TRUE",__LINE__);
        $ppclas="alert-success";
        VERBOSE("Migration === $Migration",__LINE__);
        if($Migration==0){
            if ($FINAL_TIME>0) {
                $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
                $distanceOfTimeInWords="(".distanceOfTimeInWords(time(), $FINAL_TIME).")";
                if ($ExpiresSoon<7) {
                    $ExpiresSoon_text="{ExpiresSoon}";
                    $ppclas="alert-warning";
                }

                if ($FINAL_TIME<time()) {
                    $head_error="{license_expired_explain}";
                    $ExpiresSoon_text="<strong>&laquo;{expired}&raquo;</strong></span>";
                    $distanceOfTimeInWords=null;
                    $ppclas="alert-danger";
                }

                $expire="{expiredate}: ".$tpl->time_to_date($FINAL_TIME)."&nbsp;$distanceOfTimeInWords$ExpiresSoon_text <br><small>({last_update} ".$tpl->time_to_date($TIME,true).") <br><strong>{uuid}: $uuid_text</strong></small>";
            }else{
                $expire="{expiredate}: {unlimited} <small>({last_update} ".$tpl->time_to_date($TIME,true).") <br><strong>{uuid}: $uuid_text</strong></small>";

            }

            $license_migration_explain2=$tpl->_ENGINE_parse_body("{license_migration_explain2}");
            $license_migration_explain2=str_replace("%LICENSEINFOS%","<br>$expire",$license_migration_explain2);
            $html[]="<div class='passwordBox animated fadeInDown' style='max-width:800px'>";
            $html[]="<H2>{license_migration_title}</H2>";
            $html[]="<div class='alert $ppclas'>$license_migration_explain2</div>";
            $html[]="<div class='alert alert-danger'>{license_migration_body}";
            $button=$tpl->button_autnonome("{run_migration_tool}","Loadjs('fw.license.migration.php')","fas fa-file-certificate","AsSystemAdministrator");
            $html[]="<div style='text-align: right'>$button</div></div>";
            $html[]="</div>";
            echo $tpl->_ENGINE_parse_body($html);
            VERBOSE("END SECTION",__LINE__);
            return null;


        }
    }

    if(!isset($LicenseInfos["REQUEST_BY"])){$LicenseInfos["REQUEST_BY"]=null;}
    if(!isset($LicenseInfos["ABOUT_PP"])){$LicenseInfos["ABOUT_PP"]=null;}
    if(!isset($LicenseInfos["X-API-KEY"])){$LicenseInfos["X-API-KEY"]=null;}
    VERBOSE("X-API-KEY: {$LicenseInfos["X-API-KEY"]}",__LINE__);
    if($LicenseInfos["X-API-KEY"]==null){
        VERBOSE(" * * * * page_wizard old page_request_apikey();* * * *",__LINE__);
        page_wizard();
        return null;
    }

    $step_text="";
    $License_explain=$tpl->_ENGINE_parse_body("{artica_license_explain}");
    $reset_trial_explain="";
    if(isset($LicenseInfos["assigned_to_company"])){
        if(intval($LicenseInfos["assigned_to_company"])==1807){
            $reset_license=$tpl->button_autnonome("{reset_the_license}","Loadjs('$page?reset-js=yes')",
                "fa-solid fa-link-simple-slash","AsSystemAdministrator",0,"btn-info");
            $reset_trial_explain=$tpl->div_explain("{trial_mode}||{trial_mode_reset_explain}<hr>
            <div style='text-aling:right;margin:30px;text-align:right'>$reset_license</div>");
        }
    }


    if (!isset($LicenseInfos["LICENCE_REQUEST"])) {
        $LicenseInfos["LICENCE_REQUEST"]=null;
    }

    if ($LicenseInfos["license_status"]==null) {
        $LicenseInfos["license_status"]="{waiting_registration}";
        $button_text="{request_an_evaluation_license}";
    } else {
        $step_text="{waiting_order}";
        $button_text="{refresh_status}";
        if ($LicenseInfos["LICENCE_REQUEST"]=="evaluation") {
            $step_text="{request_an_evaluation_license}";
        }
    }

    if ($LicenseInfos["license_status"]=="{license_active}") {
        $users->CORP_LICENSE=true;
        $License_explain=null;
    }
    if ($LicenseInfos["GoldKey"]<>null) {$LicenseInfos["license_number"]=$LicenseInfos["GoldKey"]; }
    list($ExpiresSoon_label,$expiredate)=expire_text($FINAL_TIME);
    $tpl->table_form_section("{artica_license}$ExpiresSoon_label",$License_explain);

    if (!$GOLDKEY) {
        if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
            $step_text = "{license_active}";
        } else {
            $tpl->table_form_field_info("{license_request}","{request_an_evaluation_license}",ico_certificate);
        }
        $tpl->table_form_field_info("{step}",$step_text,ico_certificate);
    }

    if (is_numeric($LicenseInfos["TIME"])) {
        $tt=distanceOfTimeInWords($LicenseInfos["TIME"], time());
        $tpl->table_form_field_info("{last_update}","{since} $tt",ico_clock);
    }

    if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $LicenseInfos["license_status"]="{license_active}";
    }

    $tpl->table_form_field_info("{expiredate}",$expiredate,ico_timeout);

    $tpl=ReverseProxyLicenseRow($tpl);

    if ($LicenseInfos["license_number"]<>null) {
        $topbuttons[] = array("Loadjs('$page?upload-lic=yes');", ico_upload, "{upload_your_license}");
        $tpl->table_form_field_info("{license_number}", $LicenseInfos["license_number"],ico_certificate,$topbuttons);

    }

    if (file_exists('/etc/artica-postfix/settings/Daemons/NewLicServer')) {
        VERBOSE("NewLicServer START",__LINE__);
        if(!isset($LicenseInfos["max_server"])){
            $LicenseInfos["max_server"]=null;
        }

        if($LicenseInfos["X-API-KEY"]<>null && $LicenseInfos["max_server"]==null){
            $tpl->table_form_field_js("LoadAjax('table-loader-license-service','$page?change-token=yes')","AsSystemAdministrator");
            $tpl->table_form_field_button("{insert_token}","{change_private_key}",ico_key);
            $tpl->table_form_field_js(null);
        }

        $LicenseINGP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseINGP"));
        if($LicenseINGP>0) {
            if (!$GOLDKEY) {
                if ($LicenseINGP > time() AND intval($LicenseInfos["TIME"]) < time()) {
                    $LicenseINGPDistance = distanceOfTimeInWords(time(), $LicenseINGP);
                    $LicenseInfos["license_status"] = "<span class=text-danger>{grace_period} <small>({ExpiresSoon}: $LicenseINGPDistance)</small></span>";
                }
            }
        }

        if(!$GOLDKEY) {
            $tpl->table_form_button($button_text,"RegisterSave()","AsSystemAdministrator",ico_refresh);
            $tpl->table_form_field_info("{license_status}", $LicenseInfos["license_status"],ico_infoi);
        }

        $tpl->table_form_field_js("Loadjs('$page?ch-uuid=$uuid')","AsSystemAdministrator");
        $tpl->table_form_field_info("{uuid}",$uuid_text,ico_server);
        $tpl->table_form_field_js(null);
        $tpl->table_form_field_info("{company}", $LicenseInfos["COMPANY"],ico_city);


        if(!$GOLDKEY) {
            $tpl->table_form_field_info("{requested_by}", $LicenseInfos["REQUEST_BY"],ico_admin);
            $tpl->table_form_field_info("{type}", $LicenseInfos["ABOUT_PP"],ico_diplome);
            $tpl->table_form_field_info("{insert_token}", $LicenseInfos["X-API-KEY"],ico_key);
        }
    } else {
        if(!$GOLDKEY) {
            $tpl->table_form_field_info("{license_status}", $LicenseInfos["license_status"],ico_infoi);

        }
        $tpl->table_form_field_js("Loadjs('$page?ch-uuid=$uuid')","AsSystemAdministrator");
        $tpl->table_form_field_info("{uuid}",$uuid_text,ico_server);
        $tpl->table_form_field_js(null);
        $tpl->table_form_field_info("{company}", $LicenseInfos["COMPANY"],ico_city);
        $tpl->table_form_field_info("{your_email_address}", $LicenseInfos["EMAIL"],ico_admin);
          if(!$GOLDKEY) {
              $tpl->table_form_field_info("{type}", $LicenseInfos["ABOUT_PP"],ico_diplome);
        }
    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.license.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica_license.txt";
    //$ARRAY["CMD"]="services.php?license-register=yes";
    $ARRAY["CMD"]="/register/license";
    $ARRAY["TITLE"]="{artica_license}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["AFTER_FAILED"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["REFRESH-MENU"]="yes";

    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-license-restart')";




    $no_internet_connection=$tpl->javascript_parse_text("{no_internet_connection}");
    $registerFunc="
    function RegisterSave(){
		var token = '{$LicenseInfos["X-API-KEY"]}';
		if(token.length==0){alert('{empty_token}');return;}
$.ajax({
	type: 'POST',
	url: 'https://licensing.artica.center/api/get/key',
	data: {'X-API-KEY': token,'uuid':'$uuid'},
	
	cache: false, 
	success: function (bucket) {
	  if (bucket.status == false) {
        swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.message, html: true,type:'error'})
	  }
	  if (bucket.status == true) {
		var XHR = new XHRConnection();
		XHR.appendData('tokenRequest',bucket.message);
		XHR.appendData('EMPLOYEES','{$LicenseInfos["EMPLOYEES"]}');
		XHR.appendData('LICENCE_REQUEST','1');
		XHR.sendAndLoad('$page', 'POST',x_RegisterSave);
		
	  }
	},
	error:function(bucket) {
		console.log(bucket);
		if (bucket.readyState == 4) {
            swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.responseJSON.error, html: true,type:'error'})

             
		}
		else {
			swal( {title:'Oops...', text:'<H1>Error!</H1>$no_internet_connection', html: true,type:'error'})
		}	
	}
  }); 

	}
    
    ";

    if(isset($LicenseInfos["CONFIRMUUID"])){
        $registerFunc="
    function RegisterSave(){
       
		var token = '{$LicenseInfos["X-API-KEY"]}';
		if(token.length==0){alert('{empty_token}');return;}
$.ajax({
	type: 'POST',
	url: 'https://licensing.artica.center/api/set/key',
	data: {'X-API-KEY': token,'UUID':'$uuid','CID':'{$LicenseInfos["company_id"]}','seller_cid':'{$LicenseInfos["seller_cid"]}','seller_uid':'{$LicenseInfos["seller_uid"]}'},
	
	cache: false, 
	success: function (bucket) {
	  if (bucket.status == false) {
        swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.message, html: true,type:'error'})
	  }
	  if (bucket.status == true) {
		var XHR = new XHRConnection();
		XHR.appendData('tokenRequest',bucket.message);
		XHR.appendData('EMPLOYEES','{$LicenseInfos["EMPLOYEES"]}');
		XHR.appendData('LICENCE_REQUEST','1');
		XHR.sendAndLoad('$page', 'POST',x_RegisterSave);
		
	  }
	},
	error:function(bucket) {
		console.log(bucket);
		if (bucket.readyState == 4) {
            swal( {title:'Oops...', text:'<H1>Error!</H1>'+bucket.responseJSON.error, html: true,type:'error'})

             
		}
		else {
			swal( {title:'Oops...', text:'<H1>Error!</H1>$no_internet_connection', html: true,type:'error'})
		}	
	}
  }); 

	}
    
    ";
    }




    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px'>";
    $html[]=left_status($LicenseInfos);
    $html[]=$reset_trial_explain;



    if ($tpl->language=="fr") {
        $html[]="<div class='widget lazur-bg p-lg text-center'>
		<div class='m-b-md'>
		<i class='fa fa-phone fa-4x'></i>
		<h1 class='m-xs'></h1>
		<h3 class='font-bold no-margins'>
		{articaboxxcom}
		</h3>
		<p style='margin-top:20px'>".$tpl->button_autnonome("ArticaBox.com", "s_PopUpFull('http://www.articabox.com/?pk_campaign=FromArticaInstall','1024','1024')", "fa-link") ."</p>
		</div>
		</div>";
    }

    $html[]="</td>";
    $html[]="<td style='vertical-align:top;padding-left:20px'>";
    if($head_error<>null){
        $html[]=$tpl->div_error($head_error);
    }

    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr></table>";
    $html[]="";
    $html[]="<script> 
    function GetToken(){
		window.open(
			'https://licensing.artica.center/dashboard/endpoint/create/token/$uuid',
			'MsgWindow', 'width=800,height=500,top=80'
		  );
	}
	
	var x_RegisterSave= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		Loadjs('fw.progress.php?content=$prgress&mainid=progress-license-restart');			
		
	}
   $registerFunc;
	
    Loadjs('$page?tiny-js=yes');
	</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function left_status($LicenseInfos):string{
    $AS_EXPIRE=false;
    $AS_EXPIRE_SOON=false;
    $FINAL_TIME=0;
    $titleadd="";
    $tpl=new template_admin();
    $html=array();
    if (isset($LicenseInfos["FINAL_TIME"])) {$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}


    if ($GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()) {
        $html[]="<div class='widget navy-bg p-lg text-center'>
			<div class='m-b-md'>
			<i class='fa fa-key fa-4x'></i>
			<h1 class='m-xs'>Enterprise Edition</h1>";
        $html[] = "<h3 class='font-bold no-margins'>{gold_license}</h3>";
        $html[] = "<small>{license_active}</small>
			</div>
			</div>";


        return @implode("\n",$html);

    }
    if(!isset($LicenseInfos["X-API-KEY"])){$LicenseInfos["X-API-KEY"]=null;}
    if(!isset($LicenseInfos["license_status"])){$LicenseInfos["license_status"]=null;}


    if($GLOBALS["VERBOSE"]){
        foreach ($LicenseInfos as $key=>$value){
            VERBOSE("License Info: $key = <strong>$value</strong>",__LINE__);
        }
    }

    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        if ($LicenseInfos["X-API-KEY"] == null) {
            if ($LicenseInfos["license_status"] == null) {
                if ($FINAL_TIME == 0) {
                    return "<div class='widget gray-bg p-lg text-center'>
		<div class='m-b-md'>
		<i class='fa fa-key fa-4x'></i>
		<h1 class='m-xs'>Community Edition</h1>
		<h3 class='font-bold no-margins'>
		{no_license}
		</h3>
		<small></small>
		</div>
		</div>";

                }
            }

        }
    }


    if ($FINAL_TIME<time()) {
        $ExpiresSoon_text="<span class=text-danger><strong>&laquo;{expired}&raquo;</strong><br>{license_expired_explain}</span>";
        $distanceOfTimeInWords=null;
        $AS_EXPIRE_SOON=false;
        $AS_EXPIRE=true;
    }


    if ($FINAL_TIME>0) {
        $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));

        if ($ExpiresSoon<7) {
            $AS_EXPIRE_SOON=true;
            $ExpiresSoon_text="<span class=text-danger>&nbsp;{ExpiresSoon}</span>";
        }

        if ($FINAL_TIME<time()) {
            $ExpiresSoon_text="<span class=text-danger><strong>&laquo;{expired}&raquo;</strong><br>{license_expired_explain}</span>";
            $distanceOfTimeInWords=null;
            $AS_EXPIRE_SOON=false;
            $AS_EXPIRE=true;
        }

    }

    if ($LicenseInfos["license_status"]=="{waiting_approval}") {
        return "<div class='widget yellow-bg p-lg text-center'>
		<div class='m-b-md'>
		<i class='fa fa-key fa-4x'></i>
		<h1 class='m-xs'>{waiting_approval}</h1>
		<h3 class='font-bold no-margins'>
		{waiting_approval_text}
		</h3>
		<small>{waiting_approval}</small>
		</div>
		</div>";
    }
    if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $WELCOME_ARTICA_EVAL=null;
        if($LicenseInfos["assigned_to_company"]==1807){
            $WELCOME_ARTICA_EVAL=$tpl->div_explain("Trial Edition||{WELCOME_ARTICA_EVAL}",true);
        }

        if ($AS_EXPIRE_SOON) {
            return "<div class='widget yellow-bg p-lg text-center'>
			<div class='m-b-md'>
			<i class='fa fa-key fa-4x'></i>
			<h1 class='m-xs'>Enterprise Edition</h1>
			<h3 class='font-bold no-margins'>
			$ExpiresSoon_text
			</h3>
			<small>{license_active}</small>
			</div>
			</div>$WELCOME_ARTICA_EVAL";
        } else {
            if ($ExpiresSoon<31) {
                $titleadd="&nbsp;-&nbsp;Trial Edition";
            }

            @chmod("/usr/share/artica-postfix/bin/check-ack",0755);
            exec("/usr/share/artica-postfix/bin/check-ack -check 2>&1", $output);


            $response = json_decode($output[0],TRUE);
            $gotoken=intval($response[0]["token"]);
            $gotime=intval($response[0]["time"]);
            $timediff= $gotime - time();
            $timediff=round($timediff / (60 * 60 * 24));
            if($gotoken==1){
                $explain=$tpl->_ENGINE_parse_body("{explain_license_key_wait}");
                $explain=str_replace("%s",$timediff,$explain);
                               $WELCOME_ARTICA_EVAL=$tpl->div_warning("$explain");
            }
            if(isset($LicenseInfos["slr"])){
                if($LicenseInfos["slr"]=="1"){
                    $explain=$tpl->_ENGINE_parse_body("{explain_license_reach_limit}");
                    $WELCOME_ARTICA_EVAL=$tpl->div_warning("$explain");
                }
            }

            $html[]="<div class='widget navy-bg p-lg text-center'>
			<div class='m-b-md'>
			<i class='fa fa-key fa-4x'></i>
			<h1 class='m-xs'>Enterprise Edition</h1>";
            if(intval($FINAL_TIME)>0) {
                $html[] = "<h3 class='font-bold no-margins'>
			{expire} " . $tpl->time_to_date($FINAL_TIME) . "
			</h3>";
            }
            $html[] = "<small>{license_active}$titleadd</small>
			</div>
			</div>$WELCOME_ARTICA_EVAL";
            return @implode("\n",$html);
        }
    }
    if ($AS_EXPIRE) {

        if($FINAL_TIME==0){
            $explain=$tpl->_ENGINE_parse_body("{explain_license_reach_limit}");
            $WELCOME_ARTICA_EVAL=$tpl->div_warning("$explain");
            $html[]="<div class='widget navy-bg p-lg text-center'>
			<div class='m-b-md'>
			<i class='fa fa-key fa-4x'></i>
			<h1 class='m-xs'>Community Edition</h1>";
            $html[] = "<small>{expired}</small>
			</div>
			</div>";
            return @implode("\n",$html);
        }

        $page=CurrentPageName();
        $upload_button=$tpl->button_upload("{upload_your_license} (*.key)", $page,"btn-danger");
        $reset_license=$tpl->button_autnonome("{reset_the_license}","Loadjs('$page?reset-js=yes')",
            "fa-solid fa-link-simple-slash","AsSystemAdministrator",0,"btn-warning");



        return "<div class='widget red-bg p-lg text-center'>
				<div class='m-b-md'>
				<i class='fa fa-key fa-4x'></i>
				<h1 class='m-xs'>Enterprise Edition</h1>
				<h3 class='font-bold no-margins'>
				{expired}
				</h3>
				<div class='center' style='margin-top:30px;border:white 1px solid;width:95%;padding:10px;margin-left:7px;'>$upload_button</div>
				<small></small>
				</div>
				</div>
				<p>&nbsp;</p>
                <div class='widget yellow-bg p-lg text-center'>
				<div class='m-b-md'>
				<i class='fa-solid fa-link-simple-slash fa-4x'></i>
				<h1 class='m-xs'>Reset</h1>
				<h3 class='font-bold no-margins'>
				{reset_the_license}
				</h3>
				<div class='center' style='margin-top:30px;border:white 1px solid;width:95%;padding:10px;margin-left:7px;'>$reset_license</div>
				<small></small>
				</div>
				</div>
				<p>&nbsp;</p>				
		";




    }

    return @implode("\n",$html);

}
function LICENCE_REQUEST(){
    $sidentity=new sidentity();
    $sock=new sockets();
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
    $WizardSavedSettings=$sidentity->RepairSidentity($WizardSavedSettings);

    if (isset($_POST['tokenRequest'])) {
        $sock->SaveConfigFile(json_decode(json_encode($_POST['tokenRequest']), true), "TokenRequest");
        $tokenRequest = unserialize(base64_decode($_POST["tokenRequest"]));
        foreach ($tokenRequest as $key => $value) {
            $_POST[$key] = $value;
        }
    }



    foreach ($_POST as $num=>$ligne) {
        $ligne=url_decode_special_tool($ligne);
        $LicenseInfos[$num]=$ligne;
        $WizardSavedSettings[$num]=$ligne;
        $sidentity->SET($num, $ligne);
    }

    if (isset($_POST['tokenRequest'])) {
        unset($LicenseInfos['tokenRequest']);
        unset($WizardSavedSettings['tokenRequest']);
    }

    $sock->SET_INFO("LicenseWasRequested", 1);

    $sock->SaveConfigFile(base64_encode(serialize($WizardSavedSettings)), "WizardSavedSettings");
    $sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
}
function file_uploaded():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];
    $keypath = "/usr/share/artica-postfix/ressources/conf/upload/$file";
    $tpl=new template_admin();
    if(!is_file($keypath)){
        return $tpl->js_error("$file cannot be uploaded");
    }
    $size=filesize($keypath);
    admin_tracks("Uploaded key file $file ($size bytes)");
    writelogs("Uploaded key file $file ($size bytes)",__FUNCTION__,__FILE__,__LINE__);

    if(!preg_match("#^(ack|server)_.*#",$file)){
        $tpl=new template_admin();
        if(is_file($keypath)){
            @unlink($keypath);
        }
        return $tpl->js_error("{license_ack_not_start}");

    }
    $filenameEncoded=base64_encode($file);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/license/key/upload/$filenameEncoded"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }

    header("content-type: application/x-javascript");
    $js[]="if( document.getElementById('table-loader-license-service') ){";
    $js[]="\nLoadAjax('table-loader-license-service','$page?table=yes');";
    $js[]="}";
    $js[]="if (typeof RegisterSave === 'function') {";
    $js[]="\tRegisterSave();";
    $js[]="}";
    $js[]="if(typeof dialogInstance4 == 'object'){ dialogInstance4.close();}";
    echo @implode("\n",$js);

    return true;
}
function page_wizard(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $upload_button=$tpl->button_upload("{key_file} (*.key)", $page);

    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $html[]="<div id='licensing-section'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px' nowrap=''><div style='width:350px'>";
    $html[]=left_status($LicenseInfos);
    $html[]="</div>";
    $html[]="<td style='vertical-align:top;width:100%;padding-left: 140px'>";

    $html[]="<table style='width:450px'>";


    $license_account_explain=$tpl->_ENGINE_parse_body("{license_account_explain}");
    $registrationF=$tpl->_ENGINE_parse_body("{registration_form}");
    $url=$tpl->td_href($registrationF,$registrationF,"s_PopUpFull('https://licensing.artica.center/register','1024','900');");
    $license_account_explain=str_replace("%s","<strong>$url</strong>",$license_account_explain);

    $registrationB=$tpl->button_autnonome("{login_form}","LoadAjax('licensing-section','$page?change-token=yes')","fa-id-card");

    $html[]="<tr>";
    $html[]="<td style='width:125px'><i class='fa-duotone fa-id-card fa-8x' style='color:rgb(26, 179, 148)'></i></td>";
    $html[]="<td style='vertical-align: top'><H1>{license_account}</H1>";
    $html[]="<p>$license_account_explain</p>";
    $html[]="<div style='margin-top:20px;text-align:right;border-top:1px solid #CCCCCC;padding-top:10px'>$registrationB</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr style='height:80px'><td coslpan='2'>&nbsp;</td></tr>";
    $html[]="<tr>";
    $html[]="<td style='width:125px'><i class='fa-duotone fa-file-certificate fa-8x' style='color:rgb(26, 179, 148)'></i></td>";
    $html[]="<td style='vertical-align: top'><H1>{upload_your_license}</H1>";
    $html[]="<p>{upload_artica_license_explain}</p>";
    $html[]="<div style='margin-top:20px;text-align:right;border-top:1px solid #CCCCCC;padding-top:10px'>$upload_button</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script>Loadjs('$page?tiny-js=yes');</script></div>";
    echo $tpl->_ENGINE_parse_body($html);

}
function ReverseProxyLicenseRow($tpl){
    $EnableNginx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==0){
        return $tpl;
    }

    $sock=new sockets();
    $data=$sock->REST_API_NGINX("/reverse-proxy/license");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $tpl->table_form_field_text("{APP_NGINX}",json_last_error_msg(),ico_error,true);
        return $tpl;

    }
    if(!$json->status){
        $tpl->table_form_field_text("{APP_NGINX}",$json->Error,ico_error,true);
        return $tpl;
    }

    if(!property_exists($json,"max_websites")){
        $tpl->table_form_field_text("{APP_NGINX}", "{no_license}",ico_certificate);
        return $tpl;
    }

    if($json->max_websites==0) {
        $tpl->table_form_field_text("{APP_NGINX}", "{no_license}",ico_certificate);
        return $tpl;
    }
    $q=new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM nginx_services WHERE enabled=1");
    $Current=$ligne["tcount"];
    $tpl->table_form_field_text("{APP_NGINX}", "$Current/$json->max_servers {web_rules}",ico_certificate);
    return $tpl;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}