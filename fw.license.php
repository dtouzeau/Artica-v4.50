<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
// Patch license
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsWizardExecuted",1);
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__).'/ressources/class.identity.inc');
include_once(dirname(__FILE__)."/ressources/class.activedirectory.license.inc");
// A folha de estilo das páginas de licença, partilhada com a fw.licensev2.php:
// as duas são a mesma consola, e duas cópias divergiam no primeiro acerto que
// alguém fizesse só numa. Ver ressources/class.license-ui.inc.
include_once(dirname(__FILE__)."/ressources/class.license-ui.inc");

if($GLOBALS["CLASS_SOCKETS"]->USE_LICENSE_V2()){
    // As accoes do uuid nao se fazem aqui: mudar o SYSTEMID a mao invalida o
    // nome dentro do certificado, e a maquina fica sem licenca sem que nada o
    // explique. Recusar e mais util do que redirecionar -- o operador fica a
    // saber que a accao nao existe neste modelo.
    if(isset($_GET["ch-uuid"]) OR isset($_GET["ch-uuid-popup"]) OR isset($_GET["reset-uuid"])
        OR isset($_GET["reset-js"]) OR isset($_POST["reset"])){
        $tpl=new template_admin();
        echo $tpl->js_error("{lv2_action_not_available}");
        exit;
    }
    // O resto vai para a pagina nova com a mesma pergunta: um 302 serve tanto a
    // navegacao normal como o LoadAjax, que o segue sozinho.
    $qs=isset($_SERVER["QUERY_STRING"])?(string)$_SERVER["QUERY_STRING"]:"";
    header("Location: fw.licensev2.php".($qs!=""?"?$qs":""));
    exit;
}
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
if(isset($_GET["licmig-js"])){licmig_js();exit;}
if(isset($_GET["licmig-popup"])){licmig_popup();exit;}
if(isset($_GET["licmig-uploaded"])){licmig_uploaded();exit;}
if(isset($_GET["tiny-js"])){tiny_js();exit;}
if(isset($_GET["licx-host"])){licx_host();exit;}

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
    if(isset($_GET["without-confirm"])){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/license/reset");
        header("content-type: application/x-javascript");
        echo "LoadAjax('table-loader-license-service','$page?table=yes');";
        return admin_tracks("Artica License was reseted");
    }

    $tpl->js_dialog_confirm_action("{reset_the_license}",
        "reset","Reset the Current License",
        "LoadAjax('table-loader-license-service','$page?table=yes');");

    return true;
}
function reset_license_perform():bool{
    if(!licx_require_admin_post()){return false;}
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $LicenseInfos["X-API-KEY"]="";
    $NewLicenseInfos=base64_encode(serialize($LicenseInfos));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicenseInfos",$NewLicenseInfos);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TokenRequest","");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/license/reset");
    return admin_tracks("Artica License was reseted");
}
function reset_uuid(){
    if(!licx_is_admin()){header("content-type: application/x-javascript");$tpl=new template_admin();$tpl->js_no_privileges();return;}
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
function prefixLic(){
    $pref="https://licensing.artica.center";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("USE_LIC_DEV_CENTER"))==0){
        return $pref;
    }
    return "https://dev.license.artica.systems";
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
/**
 * Invalide le cache de verdict de cle d'API du daemon.
 *
 * A appeler APRES toute ecriture de LicenseInfos["X-API-KEY"]. Sans cela le
 * daemon continue de servir son verdict precedent pendant 26 heures
 * (CacheTime 120 min + SoftStaleMax 24 h), et surtout ValidateAPIKey() sort
 * sur « if Lic.CacheFound » AVANT de decider de revalider : un « NO API KEY »
 * ecrit une fois se reaffirme tout seul, sans qu'aucun paquet ne parte vers
 * licensing.artica.center. Cas vecu : cache ecrit a 10:30 alors que
 * LicenseInfos n'avait pas encore de cle, cle arrivee a 11:59, page de licence
 * bloquee sur « non validee » sans autre symptome.
 *
 * /register/license?reset=1 et NON /license/reset : ce dernier enchaine
 * ResetLicense(true) et DETRUIT la licence. La route de registre se contente
 * de DeleteCaches() puis relance un enregistrement en tache de fond.
 *
 * A n'appeler QUE sur une action de l'exploitant. Le daemon documente le cout
 * de l'inverse (license/http_handlers_register.go:137) : appelee a chaque tick
 * d'ordonnanceur, elle « turned this endpoint into a cache-buster », N
 * appelants concurrents tapant N fois sur le reseau.
 */
function lic_invalidate_apikey_cache(){
    // 1) Invalider. DeleteCaches() supprime /usr/local/share/artica/.apikey.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/license?reset=1");

    // 2) REVALIDER. Cette seconde etape n'est PAS optionnelle, et c'est le
    //    piege de toute cette correction : la reinscription lancee en tache de
    //    fond par la route ci-dessus ne reconstruit PAS le cache de cle d'API.
    //    Mesure sur appliance : fichier absent encore 90 s apres l'appel.
    //    Or GetApiKeyStatusFromCache() rend ApiKeyStatus{CacheFound:false},
    //    dont le champ Status vaut la VALEUR ZERO -- false. La route
    //    /license/apikey/status ne distingue donc pas « refusee » de « jamais
    //    verifiee », et la page afficherait « non validee » indefiniment.
    //    Invalider sans revalider est donc PIRE que ne rien faire.
    //
    //    Synchrone a dessein : ~2,5 s de demarrage, et l'appel reseau est
    //    borne a 5 s (httpclient, Conf.Timeout=5), soit ~7,5 s au pire pour un
    //    geste rare et delibere. Le prix d'un etat juste.
    //    Route du daemon : go_exec() passait par go-exec (127.0.0.1:3334),
    //    absent des appliances -- la revalidation ne partait jamais.
    $GLOBALS["CLASS_SOCKETS"]->REST_API_TIMEOUT=15;
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/license/apikey/refresh");
}

function save_api_key(){
    if(!licx_require_admin_post()){return;}

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
    lic_invalidate_apikey_cache();
}
function save_api_key_get():bool{
    if(!licx_is_admin()){return false;}

    $_SESSION["LICENSE-API-KEY"]=$_GET["token"];
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $LicenseInfos["X-API-KEY"]=$_GET["token"];
    $NewLicenseInfos=base64_encode(serialize($LicenseInfos));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LicenseInfos",$NewLicenseInfos);
    lic_invalidate_apikey_cache();
    return true;
}
function page_request_apikey():bool{
    $change_private_key_explain=null;
    $page=CurrentPageName();
    $tpl=new template_admin();
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $accountInfos = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AccountInfo")));
    $uuid=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID");

    $left=ad_license_banner().left_status($LicenseInfos);
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

    // Le formulaire reste un form_outside: refaire ses champs a la main ferait
    // perdre la validation, l'auto-save et le $jsafter que la console garantit.
    // Seul le CADRE change -- il entre dans le panneau de la nouvelle charte.
    $body[]="<div id='REQUEST-API-KEY'></div>";
    if (!isset($LicenseInfos["X-API-KEY"])  || empty($LicenseInfos["X-API-KEY"])){
        $body[]=$tpl->form_outside("", @implode("\n", $form), "<H2>{your_registered_account}</H2>{your_registered_account_explain}", "{login}", "Loadjs('$page?get-token=yes');", "AsSystemAdministrator");
    }
    else {
        $body[]=$tpl->form_outside("", @implode("\n", $form), "$change_private_key_explain{link_this_server_account_explain}", "{ask_license}", "Loadjs('$page?register-token=yes');", "AsSystemAdministrator");
    }
    $right =lic_panel_open("{link_this_server_account}","{lic_machine_details}",null,"fa-solid fa-id-card");
    $right.="<div class='lic-body'>".@implode("\n",$body)."</div>";
    $right.=lic_panel_close();

    echo lic_style()
        .$tpl->_ENGINE_parse_body(lic_shell($left,$right))
        ."<script>NoSpinner();Loadjs('$page?tiny-js=yes');</script>";
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

    $body[]="<div id='REQUEST-API-KEY'></div>";
    $body[]=$tpl->form_outside("", @implode("\n", $form), "$change_private_key_explain{create_new_account_explain}", "{create}", "Loadjs('$page?create-new-account=yes');", "AsSystemAdministrator");
    $right =lic_panel_open("{create_new_account_title}","{lic_machine_details}",null,"fa-solid fa-user-plus");
    $right.="<div class='lic-body'>".@implode("\n",$body)."</div>";
    $right.=lic_panel_close();
    echo lic_style()
        .$tpl->_ENGINE_parse_body(lic_shell("",$right))
        ."<script>NoSpinner();</script>";

}
function page_create_new_account(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $no_internet_connection=$tpl->javascript_parse_text("{no_internet_connection}");
    $pref       = prefixLic();
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
	url: '$pref/api/create/account',
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
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=REQUEST-API-KEY')";
    $pref       = prefixLic();
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
	url: '$pref/api/resent/activation/code',
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
    $pref       = prefixLic();
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
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=REQUEST-API-KEY')";



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
	url: '$pref/api/get/key/by/user',
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
    $pref       = prefixLic();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.license.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica_license.txt";
    //$ARRAY["CMD"]="services.php?license-register=yes";
    $ARRAY["CMD"]="/register/license";
    $ARRAY["TITLE"]="{artica_license}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["AFTER_FAILED"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["REFRESH-MENU"]="yes";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=REQUEST-API-KEY')";

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
	url: '$pref/api/get/key',
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
// ============================================================================
//  Refonte visuelle (design stitch_redesign_page_licence). Composants scopes
//  .lic-* : le mock est en Tailwind, la console n'en a pas -- la charte est
//  donc transposee ici, une regle par element, sans dependre du kit awc_*
//  (dont la version deployee sur une appliance peut etre plus ancienne).
//  UN COMPOSANT = UNE FONCTION: chaque carte, tuile ou section a la sienne.
// ============================================================================

// La feuille, une seule fois par requete. JAMAIS passee a _ENGINE_parse_body():
// son motif #{(.+?)}# lit chaque corps de regle CSS comme un {token} et declenche
// un acces memcached par regle. Toujours concatener APRES l'analyse.
function lic_style():string{
    if(isset($GLOBALS["LIC_CSS_DONE"])){return "";}
    $GLOBALS["LIC_CSS_DONE"]=true;
    return "<style>
.lic-wrap{color:#0f172a}
.lic-wrap *{box-sizing:border-box}
.lic-grid{display:flex;flex-wrap:wrap;gap:22px;align-items:flex-start;margin-top:10px}
.lic-col-l{flex:0 0 330px;max-width:330px;display:flex;flex-direction:column;gap:18px}
.lic-col-r{flex:1 1 560px;min-width:0}
@media(max-width:1100px){.lic-col-l{flex:1 1 100%;max-width:none}}
/* Le vert est EXACTEMENT celui des vignettes d'etat de service
   (.svctile-green, class.template-admin.inc): deux arrets, #0a8f66 -> #067451.
   Les deux se lisent cote a cote dans la console -- la licence active a gauche,
   les services juste au-dessus -- et l'ancien degrade teal a trois arrets
   (#0d9488 -> #0f766e -> #065f46) donnait deux verts differents pour un meme
   « tout va bien ».
   L'ombre suit: teintee #065f46, elle restait l'ombre d'une couleur que le
   degrade ne contient plus. Le rayon de 18px et le remplissage ne bougent pas,
   c'est la couleur qu'on aligne, pas la forme. */
.lic-hero{position:relative;overflow:hidden;border-radius:18px;padding:26px 22px;color:#fff;
 background:linear-gradient(135deg,#0a8f66 0%,#067451 100%);
 box-shadow:0 10px 26px rgba(6,116,81,.18)}
/* Rouge et ambre alignes eux aussi sur les vignettes de service
   (.svctile-red et .svctile-yellow), ombres refaites sur le nouveau dernier
   arret comme pour le vert.
   Ne pas « raviver » ces teintes vers les oranges/rouges vifs d'INSPINIA
   (#e74c3c, #f39c12): en blanc dessus le contraste tombe a 3.82 et 2.19, sous
   le seuil AA de 4.5. Mesure sur la palette posee ici, au CENTRE du degrade --
   c'est la que le texte est pose, le contenu de la tuile etant centre, et non
   sur l'arret de depart qui n'a que l'icone: vert 4.82, rouge 6.73, ambre
   4.78, donc AA pour les trois.
   L'arret de DEPART de l'ambre, lui, ne vaut que 2.91: n'y mettez pas de
   texte, cette zone est reservee au medaillon d'icone. */
.lic-hero-red{background:linear-gradient(135deg,#d92b32 0%,#93000a 100%);box-shadow:0 10px 26px rgba(147,0,10,.18)}
.lic-hero-amber{background:linear-gradient(135deg,#d98324 0%,#7a3c05 100%);box-shadow:0 10px 26px rgba(122,60,5,.18)}
.lic-hero-grey{background:linear-gradient(135deg,#64748b 0%,#475569 55%,#1e293b 100%);box-shadow:0 10px 26px rgba(30,41,59,.18)}
.lic-hero-navy{background:linear-gradient(135deg,#0ea5e9 0%,#0369a1 55%,#0c4a6e 100%);box-shadow:0 10px 26px rgba(12,74,110,.18)}
.lic-hero-in{position:relative;z-index:2;display:flex;flex-direction:column;align-items:center;text-align:center}
.lic-hero-ico{width:62px;height:62px;border-radius:16px;background:rgba(255,255,255,.12);
 border:1px solid rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;
 font-size:25px;color:#fff;margin-bottom:14px}
.lic-hero-badge{display:inline-flex;align-items:center;gap:7px;padding:4px 13px;border-radius:999px;
 font-size:11px;font-weight:600;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.22);
 color:#ecfdf5;margin-bottom:9px}
.lic-hero-title{font-size:23px;font-weight:800;letter-spacing:-.3px;margin:0;color:#fff;line-height:1.2}
.lic-hero-sub{font-size:11.5px;font-weight:500;color:rgba(236,253,245,.85);margin:5px 0 0}
.lic-hero-exp{width:100%;margin-top:22px;padding-top:17px;border-top:1px solid rgba(255,255,255,.16);text-align:left}
.lic-hero-lab{display:flex;align-items:center;justify-content:space-between;font-size:11px;
 font-weight:500;color:rgba(236,253,245,.82);margin-bottom:3px}
.lic-hero-lab b{color:#fff;font-weight:600}
.lic-hero-date{font-size:13.5px;font-weight:700;color:#fff;letter-spacing:.2px;margin:0}
.lic-hero-track{margin-top:11px;width:100%;height:6px;border-radius:999px;background:rgba(0,0,0,.22);overflow:hidden}
.lic-hero-fill{height:100%;border-radius:999px;background:#6ee7b7}
.lic-hero-note{display:block;margin-top:5px;font-size:10.5px;color:rgba(236,253,245,.72)}
.lic-hero-wm{position:absolute;right:-14px;bottom:-18px;font-size:120px;color:rgba(255,255,255,.06);
 z-index:1;pointer-events:none;line-height:1}
/* ---- cartes d'information (colonne gauche) ---- */
.lic-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:19px;
 box-shadow:0 1px 2px rgba(15,23,42,.05);display:flex;align-items:flex-start;gap:15px}
.lic-card-ico{width:40px;height:40px;border-radius:12px;background:#f1f5f9;border:1px solid #e2e8f0;
 color:#64748b;display:flex;align-items:center;justify-content:center;font-size:15px;flex:0 0 auto}
.lic-card-ico-teal{background:#f0fdf9;border-color:#ccfbf1;color:#0d9488}
.lic-card-bd{flex:1 1 auto;min-width:0}
.lic-card-title{font-size:13px;font-weight:700;color:#1e293b;margin:0 0 7px}
.lic-card-txt{font-size:11.5px;line-height:1.65;color:#475569;margin:0 0 7px}
.lic-card-txt:last-child{margin-bottom:0}
.lic-card-txt b,.lic-card-txt strong{color:#1e293b;font-weight:600}
.lic-card-act{margin-top:13px}
.lic-card-act .btn,.lic-card-act button{width:100%}
/* ---- panneau principal (colonne droite) ---- */
.lic-panel{background:#fff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;
 box-shadow:0 1px 2px rgba(15,23,42,.05)}
.lic-panel-head{padding:18px 22px;border-bottom:1px solid #f1f5f9;background:#fbfdfe;
 display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px}
.lic-panel-id{display:flex;align-items:center;gap:13px;min-width:0}
.lic-panel-ico{width:40px;height:40px;border-radius:12px;background:#f0fdf9;border:1px solid #ccfbf1;
 color:#0d9488;display:flex;align-items:center;justify-content:center;font-size:16px;flex:0 0 auto}
.lic-panel-title{font-size:15px;font-weight:700;color:#0f172a;margin:0;letter-spacing:-.2px}
.lic-panel-sub{font-size:11.5px;color:#64748b;margin:3px 0 0}
.lic-dot{width:7px;height:7px;border-radius:999px;background:#10b981;flex:0 0 auto}
/* ---- sections ---- */
.lic-body{padding:22px}
.lic-body+.lic-body{border-top:1px solid #f1f5f9}
@media(max-width:820px){.lic-tiles{grid-template-columns:1fr}}
</style>";
}

// Le squelette deux colonnes. Il remplace les trois <table style='width:100%'>
// recopiees a l'identique dans table(), page_request_apikey() et page_wizard().
function lic_shell($left,$right):string{
    $l=is_array($left)?@implode("\n",$left):(string)$left;
    $r=is_array($right)?@implode("\n",$right):(string)$right;
    $h ="<div class='lic-wrap'><div class='lic-grid'>";
    if(trim($l)<>""){$h.="<div class='lic-col-l'>$l</div>";}
    $h.="<div class='lic-col-r'>$r</div>";
    $h.="</div></div>";
    return $h;
}

// La carte d'etat. Les cinq etats (active / expire bientot / expiree /
// community / gold) ne changent QUE la couleur et le texte: une mise en page
// par etat serait cinq fois plus de code a maintenir, et l'operateur perdrait
// le repere visuel qui lui dit ou lire la date.
function lic_card_hero($tone,$badge,$title,$subtitle,$expBlock,$ico="fa-solid fa-key"):string{
    $tones=array("green"=>"","red"=>" lic-hero-red","amber"=>" lic-hero-amber",
                 "grey"=>" lic-hero-grey","navy"=>" lic-hero-navy");
    $cls=isset($tones[$tone])?$tones[$tone]:"";
    $h ="<div class='lic-hero$cls'>";
    $h.="<span class='lic-hero-wm'><i class='$ico'></i></span>";
    $h.="<div class='lic-hero-in'>";
    $h.="<span class='lic-hero-ico'><i class='$ico'></i></span>";
    if($badge<>null){
        $h.="<span class='lic-hero-badge'><span class='lic-dot'></span>$badge</span>";
    }
    $h.="<h2 class='lic-hero-title'>$title</h2>";
    if($subtitle<>null){$h.="<p class='lic-hero-sub'>$subtitle</p>";}
    if($expBlock<>null){$h.=$expBlock;}
    $h.="</div></div>";
    return $h;
}

// Le bloc d'expiration de la carte d'etat: cible, distance, date, jauge.
// $percent vaut -1 quand la fenetre de maintenance n'est pas calculable
// (installation inconnue) -- on masque alors la jauge au lieu d'en inventer une.
function lic_hero_expire($labelLeft,$labelRight,$dateText,$percent,$note):string{
    $h ="<div class='lic-hero-exp'>";
    $h.="<div class='lic-hero-lab'><span>$labelLeft</span><b>$labelRight</b></div>";
    $h.="<p class='lic-hero-date'>$dateText</p>";
    if($percent>=0){
        $p=intval($percent); if($p>100){$p=100;} if($p<2){$p=2;}
        $h.="<div class='lic-hero-track'><div class='lic-hero-fill' style='width:{$p}%'></div></div>";
    }
    if($note<>null){$h.="<span class='lic-hero-note'>$note</span>";}
    $h.="</div>";
    return $h;
}

// Une carte blanche d'explication. $action = HTML d'un bouton, optionnel.
function lic_card_info($ico,$title,$body,$action=null,$tone="grey"):string{
    $icls=($tone=="teal")?" lic-card-ico-teal":"";
    $h ="<div class='lic-card'>";
    $h.="<span class='lic-card-ico$icls'><i class='$ico'></i></span>";
    $h.="<div class='lic-card-bd'>";
    $h.="<h3 class='lic-card-title'>$title</h3>";
    $h.=$body;
    if($action<>null){$h.="<div class='lic-card-act'>$action</div>";}
    $h.="</div></div>";
    return $h;
}

function lic_panel_open($title,$subtitle,$badge=null,$ico="fa-solid fa-shield-halved"):string{
    $h ="<div class='lic-panel'>";
    $h.="<div class='lic-panel-head'>";
    $h.="<div class='lic-panel-id'>";
    $h.="<span class='lic-panel-ico'><i class='$ico'></i></span>";
    $h.="<div><h3 class='lic-panel-title'>$title</h3>";
    if($subtitle<>null){$h.="<p class='lic-panel-sub'>$subtitle</p>";}
    $h.="</div></div>";
    if($badge<>null){$h.="<div>$badge</div>";}
    $h.="</div>";
    return $h;
}

function lic_panel_close():string{ return "</div>"; }


function lic_node_id():string{
    $h=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    if (trim((string)$h)==""){$h=gethostname();}
    if (trim((string)$h)==""){$h="unknown";}
    return (string)$h;
}

// La colonne de gauche: une carte d'etat (lic_card_hero) suivie de zero a deux
// cartes d'information. Les sept etats d'origine sont conserves tels quels --
// seule leur PRESENTATION change: la couleur du degrade porte l'etat, la mise
// en page ne bouge pas d'un etat a l'autre.
function left_status($LicenseInfos):string{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $FINAL_TIME = 0;
    if(isset($LicenseInfos["FINAL_TIME"])){$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}
    if(!isset($LicenseInfos["X-API-KEY"])){$LicenseInfos["X-API-KEY"]=null;}
    if(!isset($LicenseInfos["license_status"])){$LicenseInfos["license_status"]=null;}
    if(!isset($LicenseInfos["assigned_to_company"])){$LicenseInfos["assigned_to_company"]=0;}
    $DateCreated=isset($LicenseInfos["date_created"])?intval($LicenseInfos["date_created"]):0;

    if($GLOBALS["VERBOSE"]){
        foreach ($LicenseInfos as $key=>$value){
            if(is_array($value)){continue;}
            VERBOSE("License Info: $key = <strong>$value</strong>",__LINE__);
        }
    }

    // -- 1. Licence Gold: aucune echeance -----------------------------------
    if ($GLOBALS["CLASS_SOCKETS"]->CORP_GOLD()) {
        $exp=lic_hero_expire("{expiredate}","{unlimited}","{never}",-1,"{gold_license}");
        return lic_card_hero("green","{license_active}","Enterprise Edition","{gold_license}",$exp);
    }

    // -- 2. Aucune licence, aucun compte: edition communautaire --------------
    if (!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        if ($LicenseInfos["X-API-KEY"]==null && $LicenseInfos["license_status"]==null && $FINAL_TIME==0) {
            return lic_card_hero("grey",null,"Community Edition","{no_license}",null);
        }
    }

    // -- 3. Demande en attente d'approbation ---------------------------------
    if ($LicenseInfos["license_status"]=="{waiting_approval}") {
        $h =lic_card_hero("amber","{waiting_approval}","{waiting_approval}","{waiting_approval_text}",null,
            "fa-solid fa-hourglass-half");
        $h.=lic_card_info("fa-solid fa-circle-info","{waiting_approval}",
            "<p class='lic-card-txt'>{waiting_approval_text}</p>");
        return $h;
    }

    $AS_EXPIRE      = ($FINAL_TIME>0 && $FINAL_TIME<time());
    $AS_EXPIRE_SOON = false;
    $ExpiresSoon    = 0;
    if ($FINAL_TIME>0) {
        $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
        if ($ExpiresSoon<7 && !$AS_EXPIRE){$AS_EXPIRE_SOON=true;}
    }
    if ($FINAL_TIME==0 && $LicenseInfos["license_status"]<>null && !$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $AS_EXPIRE=true;
    }

    // -- 4 et 5. Licence Enterprise en cours de validite ---------------------
    if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $cards="";

        // Trial: la licence d'evaluation est rattachee a la societe 1807.
        if (intval($LicenseInfos["assigned_to_company"])==1807) {
            $cards.=lic_card_info("fa-solid fa-circle-info","{trial_mode}",
                "<p class='lic-card-txt'>{WELCOME_ARTICA_EVAL}</p>");
        }

        // Cle en attente cote serveur de licences (check-ack) : le binaire peut
        // etre absent ou muet -- sans la garde isset(), json_decode(null) puis
        // $response[0] est une erreur fatale en PHP 8, pas un simple warning.
        @chmod("/usr/share/artica-postfix/bin/check-ack",0755);
        $output=array();
        exec("/usr/share/artica-postfix/bin/check-ack -check 2>&1", $output);
        if (isset($output[0])) {
            $response=json_decode($output[0],TRUE);
            if (is_array($response) && isset($response[0]["token"])) {
                if (intval($response[0]["token"])==1) {
                    $gotime=intval($response[0]["time"]);
                    $timediff=round(($gotime-time())/(60*60*24));
                    $explain=$tpl->_ENGINE_parse_body("{explain_license_key_wait}");
                    $explain=str_replace("%s",$timediff,$explain);
                    $cards.=lic_card_info("fa-solid fa-hourglass-half","{license_server}",
                        "<p class='lic-card-txt'>$explain</p>","","teal");
                }
            }
        }

        // Limite de serveurs atteinte cote licence.
        if (isset($LicenseInfos["slr"])) {
            if ($LicenseInfos["slr"]=="1") {
                $cards.=lic_card_info("fa-solid fa-triangle-exclamation","{warning}",
                    "<p class='lic-card-txt'>{explain_license_reach_limit}</p>");
            }
        }

        $subtitle=($ExpiresSoon>0 && $ExpiresSoon<31)?"Trial Edition":"{license_active}";
        $tone=$AS_EXPIRE_SOON?"amber":"green";
        $badge=$AS_EXPIRE_SOON?"{ExpiresSoon}":"{license_active}";
        $exp=null;
        if ($FINAL_TIME>0) {
            $exp=lic_hero_expire("{expiredate}",lic_remaining_text($FINAL_TIME),
                $tpl->time_to_date($FINAL_TIME),lic_window_percent($DateCreated,$FINAL_TIME),
                "{lic_valid_maintenance_window}");
        }
        return lic_card_hero($tone,$badge,"Enterprise Edition",$subtitle,$exp).$cards;
    }

    // -- 6. Retombee en edition communautaire (aucune echeance connue) -------
    if ($AS_EXPIRE && $FINAL_TIME==0) {
        return lic_card_hero("grey","{expired}","Community Edition","{expired}",null)
              .lic_card_info("fa-solid fa-triangle-exclamation","{warning}",
                   "<p class='lic-card-txt'>{explain_license_reach_limit}</p>");
    }

    // -- 7. Licence expiree: deposer une cle, ou repartir a zero -------------
    if ($AS_EXPIRE) {
        $exp=lic_hero_expire("{expiredate}","{expired}",$tpl->time_to_date($FINAL_TIME),0,
            "{license_expired_explain}");
        $upload=$tpl->button_upload("{upload_your_license} (*.key)", $page,"btn-danger");
        $reset =$tpl->button_autnonome("{reset_the_license}","Loadjs('$page?reset-js=yes')",
            "fa-solid fa-link-simple-slash","AsSystemAdministrator",0,"btn-warning");
        $h =lic_card_hero("red","{expired}","Enterprise Edition","{license_expired_explain}",$exp);
        $h.=lic_card_info("fa-solid fa-file-arrow-up","{upload_your_license}",
            "<p class='lic-card-txt'>{upload_artica_license_explain}</p>",$upload,"teal");
        $h.=lic_card_info("fa-solid fa-link-simple-slash","{reset_the_license}",
            "<p class='lic-card-txt'>{trial_mode_reset_explain}</p>",$reset);
        return $h;
    }

    // Etat residuel: une licence demandee, pas encore active.
    $exp=null;
    if ($FINAL_TIME>0) {
        $exp=lic_hero_expire("{expiredate}",lic_remaining_text($FINAL_TIME),
            $tpl->time_to_date($FINAL_TIME),lic_window_percent($DateCreated,$FINAL_TIME),
            "{lic_valid_maintenance_window}");
    }
    return lic_card_hero("grey",$LicenseInfos["license_status"],"Community Edition",
        $LicenseInfos["license_status"],$exp);
}

// Le temps restant, en mots ("Over 3 years" dans la maquette).
function lic_remaining_text($FINAL_TIME):string{
    if (intval($FINAL_TIME)<=0){return "{never}";}
    if ($FINAL_TIME<time()){return "{expired}";}
    return distanceOfTimeInWords(time(),$FINAL_TIME);
}

// La jauge represente la part RESTANTE de la fenetre de maintenance: elle se
// vide en approchant de l'echeance. Sans date d'installation il n'y a pas de
// denominateur -- on renvoie -1 et lic_hero_expire() masque la jauge plutot
// que d'en afficher une arbitraire.
function lic_window_percent($DateCreated,$FINAL_TIME):int{
    $DateCreated=intval($DateCreated); $FINAL_TIME=intval($FINAL_TIME);
    if ($DateCreated<=0 || $FINAL_TIME<=0){return -1;}
    $total=$FINAL_TIME-$DateCreated;
    if ($total<=0){return -1;}
    $left=$FINAL_TIME-time();
    if ($left<=0){return 0;}
    return intval(round(($left*100)/$total));
}
function LICENCE_REQUEST(){
    if(!licx_require_admin_post()){return;}
    $sidentity=new sidentity();
    $sock=new sockets();
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
    $WizardSavedSettings=$sidentity->RepairSidentity($WizardSavedSettings);

    if (isset($_POST['tokenRequest'])) {
        $sock->SaveConfigFile(json_decode(json_encode($_POST['tokenRequest']), true), "TokenRequest");
        $tokenRequest = unserialize(base64_decode($_POST["tokenRequest"]),array("allowed_classes"=>false));
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
    // La cle vient de changer : le verdict en cache porte sur l'ANCIENNE.
    lic_invalidate_apikey_cache();
}
function file_uploaded():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    if(!licx_is_admin()){$tpl=new template_admin();return $tpl->js_no_privileges();}
    // basename : le nom vient de la requete, jamais un chemin.
    $file=basename((string)$_GET["file-uploaded"]);
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
// ===========================================================================
// Refonte stitch_artica_license_admin_console (2026-11-03).
// Un modele (licx_model) calcule une fois, deux vues :
//   - licx_view_licensed() : licence active / expiree / en attente / Gold
//   - licx_view_unlinked() : aucune cle privee (ancien page_wizard)
// Une fonction par widget. CSS et JS concatenes HORS de _ENGINE_parse_body().
// ===========================================================================
function licx_is_admin():bool{
    $users=new usersMenus();
    return $users->AsSystemAdministrator==true;
}
// Refus cote serveur pour un POST : erreur seule, rien en cas de succes.
function licx_require_admin_post():bool{
    if(licx_is_admin()){return true;}
    $tpl=new template_admin();
    echo $tpl->post_error("{ERROR_NO_PRIVS2}");
    return false;
}
// "Titre||texte" : seuls quelques helpers savent le decouper, pas nos cartes.
function licx_split_token(string $token):array{
    $tpl=new template_admin();
    $txt=$tpl->_ENGINE_parse_body($token);
    if(strpos($txt,"||")===false){return array("",$txt);}
    $p=explode("||",$txt,2);
    return array(trim($p[0]),trim($p[1]));
}
function licx_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $L=unserializeb64($sock->GET_INFO("LicenseInfos"));
    if(!is_array($L)){$L=array();}
    $W=@unserialize(base64_decode((string)$sock->GET_INFO("WizardSavedSettings")));
    if(!is_array($W)){$W=array();}
    $m=array();
    $m["pref"]=prefixLic();
    $m["uuid"]=(string)$sock->GET_INFO("SYSTEMID");
    $m["hostname"]=lic_node_id();
    $m["admin"]=licx_is_admin();
    $m["gold"]=$sock->CORP_GOLD() || (intval($sock->GET_INFO("isCorpGold"))==1 && !empty($L["license_number"]));
    $m["corp"]=$sock->CORP_LICENSE();
    $m["apikey"]=trim((string)($L["X-API-KEY"] ?? ""));
    $m["status"]=(string)($L["license_status"] ?? "");
    $m["final"]=intval($L["FINAL_TIME"] ?? 0);
    $m["created"]=intval($L["date_created"] ?? 0);
    $m["sync"]=is_numeric($L["TIME"] ?? null)?intval($L["TIME"]):0;
    $m["trial"]=intval($L["assigned_to_company"] ?? 0)==1807;
    $m["slr"]=(string)($L["slr"] ?? "")=="1";
    $m["max_server"]=(string)($L["max_server"] ?? "");
    $num=(string)($L["license_number"] ?? "");
    if(!empty($L["GoldKey"])){$num=(string)$L["GoldKey"];}
    $m["number"]=trim($num);
    $company=trim((string)($L["COMPANY"] ?? ""));
    if($company==""){$company=trim((string)($W["company_name"] ?? ($W["organization"] ?? "")));}
    $email=trim((string)($L["EMAIL"] ?? ""));
    if($email==""){$email=trim((string)($W["mail"] ?? ""));}
    $m["company"]=$company;
    $m["email"]=$email;
    $m["register_email"]=trim((string)$sock->GET_INFO("REGISTER_EMAIL"));
    $m["requested_by"]=trim((string)($L["REQUEST_BY"] ?? ""));
    $m["about"]=trim((string)($L["ABOUT_PP"] ?? ""));
    $m["employees"]=(string)($L["EMPLOYEES"] ?? "");
    $m["confirm_uuid"]=isset($L["CONFIRMUUID"]);
    $m["company_id"]=(string)($L["company_id"] ?? "");
    $m["seller_cid"]=(string)($L["seller_cid"] ?? "");
    $m["seller_uid"]=(string)($L["seller_uid"] ?? "");

    // Une seule question au daemon pour la signature ET le mode "communautaire apres essai".
    $m["sig"]="unknown";
    if($m["gold"]){
        $m["sig"]="ok";
    }else{
        $j=json_decode((string)$sock->REST_API("/license/apikey/status"),true);
        if(is_array($j) && !empty($j["Status"])){
            $m["sig"]=!empty($j["ApiKeyStatus"])?"ok":"bad";
            // CacheFound=false : aucun verdict pour la cle EN SERVICE (jamais
            // verifiee, ou verdict d'une ancienne cle). Ce n'est pas un refus :
            // le daemon revalide en tache de fond (Revalidating).
            if(isset($j["CacheFound"]) && empty($j["CacheFound"])){
                $m["sig"]=!empty($j["Revalidating"])?"checking":"unknown";
            }
        }
    }
    // Essai rattache a la societe 1807 dont la cle est REFUSEE : retour communautaire.
    $m["community_reset"]=$m["trial"] && $m["sig"]=="bad";

    $m["days"]=$m["final"]>0?intval(time_between_day_Web($m["final"])):0;
    $m["expired"]=$m["final"]>0 && $m["final"]<time();
    $m["state"]=licx_state($m);
    $m["wait_days"]=-1;
    if($m["corp"] && !$m["gold"]){$m["wait_days"]=licx_check_ack_days();}
    $m["rp"]=licx_rp_quota();
    return $m;
}
// Etat unique de la licence, dans l'ordre des cas de left_status().
function licx_state(array $m):string{
    if($m["gold"]){return "gold";}
    if($m["community_reset"]){return "community";}
    if($m["status"]=="{waiting_approval}"){return "waiting_approval";}
    if($m["corp"]){
        if($m["final"]>0 && !$m["expired"] && $m["days"]<7){return "expires_soon";}
        return "active";
    }
    if($m["expired"]){return "expired";}
    // Une licence qui ETAIT active et n'a plus d'echeance : retombee en Community.
    // Une demande en cours (commande, enregistrement) n'est pas une expiration.
    if($m["final"]==0 && $m["status"]=="{license_active}"){return "expired_community";}
    if($m["status"]==""){return "nolicense";}
    return "pending";
}
// check-ack : la cle est active mais l'appliance doit encore s'enregistrer (jours restants).
function licx_check_ack_days():int{
    $bin="/usr/share/artica-postfix/bin/check-ack";
    if(!is_file($bin)){return -1;}
    @chmod($bin,0755);
    $output=array();
    exec("$bin -check 2>&1",$output);
    if(!isset($output[0])){return -1;}
    $r=json_decode($output[0],true);
    if(!is_array($r) || !isset($r[0]["token"]) || intval($r[0]["token"])!=1){return -1;}
    return intval(round((intval($r[0]["time"])-time())/86400));
}
// Quota du reverse-proxy : sites actifs contre le maximum de la licence nginx.
function licx_rp_quota():array{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"))==0){return array();}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/license"));
    if(!is_object($json) || !property_exists($json,"status") || !$json->status){
        $err=(is_object($json) && property_exists($json,"Error"))?(string)$json->Error:"{error}";
        return array("error"=>$err);
    }
    $max=intval(property_exists($json,"max_servers")?$json->max_servers:0);
    if($max==0 && property_exists($json,"max_websites")){$max=intval($json->max_websites);}
    if($max==0){return array("max"=>0,"used"=>0);}
    $resp=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/enabled/1"),true);
    $used=0;
    if(is_array($resp) && !empty($resp["Status"]) && isset($resp["rows"]) && is_array($resp["rows"])){$used=count($resp["rows"]);}
    return array("max"=>$max,"used"=>$used);
}
function licx_edition(array $m):string{
    if($m["gold"]){return "{gold_license}";}
    if(in_array($m["state"],array("active","expires_soon","expired"))){return "{licx_enterprise}";}
    return "{licx_community}";
}
// Pastille d'etat : icone + mot, couleur assortie au mot.
function licx_pill(array $m):string{
    $map=array(
        "gold"=>array("ok","fa-solid fa-circle-check","{licx_active}"),
        "active"=>array("ok","fa-solid fa-circle-check","{licx_active}"),
        "expires_soon"=>array("warn","fa-solid fa-triangle-exclamation","{ExpiresSoon}"),
        "expired"=>array("err","fa-solid fa-circle-xmark","{expired}"),
        "expired_community"=>array("err","fa-solid fa-circle-xmark","{expired}"),
        "community"=>array("neutral","fa-solid fa-circle-minus","{licx_community_mode}"),
        "waiting_approval"=>array("warn","fa-solid fa-hourglass-half","{waiting_approval}"),
        "nolicense"=>array("neutral","fa-solid fa-circle-minus","{no_license}"),
    );
    $p=$map[$m["state"]] ?? array("neutral","fa-solid fa-hourglass-half",$m["status"]);
    $tone=($p[0]=="neutral")?"off":$p[0];
    return "<span class='ak-pill ak-$tone'><i class='{$p[1]}'></i> {$p[2]}</span>";
}
function licx_accent(array $m):string{
    $map=array("gold"=>"ok","active"=>"ok","expires_soon"=>"warn","waiting_approval"=>"warn",
        "expired"=>"err","expired_community"=>"err","community"=>"neutral","nolicense"=>"neutral","pending"=>"neutral");
    return $map[$m["state"]] ?? "neutral";
}
// ---------------------------------------------------------------- vue licenciee
function licx_view_licensed(array $m):string{
    $h=array();
    $h[]=licx_hero($m);
    $n=licx_notices($m);
    // Un avis seul prend toute la largeur ; a partir de deux, une grille de deux colonnes.
    $one=substr_count($n,"class='licx-notice ")==1?" licx-notices-1":"";
    if($n!=""){$h[]="<div class='licx-notices$one'>$n</div>";}
    $h[]="<div class='licx-cards'>".licx_card_identifiers($m).licx_card_organization($m)."</div>";
    return @implode("\n",$h);
}
function licx_hero(array $m):string{
    $ico=$m["gold"]?"fa-solid fa-crown":"fa-solid fa-key";
    $sub="";
    if($m["company"]!=""){$sub="<p class='licx-hero-sub'>{licx_registered_to} <b>".htmlspecialchars($m["company"])."</b></p>";}
    $trial=($m["trial"] && !$m["community_reset"])?"<span class='ak-tag'>{trial_mode}</span>":"";
    $h=array();
    $h[]="<section class='licx-hero licx-acc-".licx_accent($m)."'>";
    $h[]="<div class='licx-hero-id'><span class='licx-hero-ico'><i class='$ico'></i></span><div>";
    $h[]="<div class='licx-hero-line'><h2>".licx_edition($m)."</h2>".licx_pill($m).$trial."</div>$sub</div></div>";
    $h[]=licx_hero_expiry($m);
    $h[]=licx_hero_sync($m);
    $h[]="</section>";
    return @implode("",$h);
}
function licx_hero_expiry(array $m):string{
    $tpl=new template_admin();
    if($m["gold"]){
        return "<div class='licx-hero-exp'><div class='licx-lab'>{expiredate}</div>"
            ."<div class='licx-exp-line'><span class='licx-exp-date'>{never}</span><span class='ak-tag ak-ok'>{licx_unlimited}</span></div></div>";
    }
    if($m["final"]<=0){return "";}
    $date=$tpl->time_to_date($m["final"]);
    if($m["expired"]){
        $chip="<span class='ak-tag ak-err'>{expired}</span>";
    }else{
        $cls=$m["days"]<31?"ak-warn":"ak-ok";
        $chip="<span class='ak-tag $cls'>".htmlspecialchars(distanceOfTimeInWords(time(),$m["final"]))."</span>";
    }
    $bar="";
    $left=lic_window_percent($m["created"],$m["final"]);
    if($left>=0){
        $elapsed=100-$left;
        $fill=$m["expired"]?"licx-fill-err":($m["days"]<31?"licx-fill-warn":"licx-fill-ok");
        $bar="<div class='licx-track' role='progressbar' aria-valuemin='0' aria-valuemax='100' aria-valuenow='$elapsed'>"
            ."<div class='licx-fill $fill' style='width:{$elapsed}%'></div></div>"
            ."<div class='licx-exp-foot'><span>{install_date}: ".$tpl->time_to_date($m["created"])."</span><span>$elapsed% {licx_elapsed}</span></div>";
    }
    return "<div class='licx-hero-exp'><div class='licx-lab'>{expiredate}</div>"
        ."<div class='licx-exp-line'><span class='licx-exp-date'>$date</span>$chip</div>$bar</div>";
}
function licx_hero_sync(array $m):string{
    $sig=array("ok"=>array("ok","fa-solid fa-shield-halved","{lic_signature_verified}"),
        "bad"=>array("warn","fa-solid fa-triangle-exclamation","{lic_signature_unverified}"),
        "checking"=>array("neutral","fa-solid fa-rotate ak-spin","{licx_sig_checking}"),
        "unknown"=>array("neutral","fa-solid fa-circle-question","{lic_signature_unknown}"));
    $s=$sig[$m["sig"]];
    $h=array();
    $h[]="<div class='licx-hero-sync'>";
    $pending=$m["sig"]=="checking"?" licx-sig-pending":"";
    $h[]="<div class='licx-sig licx-sig-{$s[0]}$pending' role='status'><i class='{$s[1]}'></i> {$s[2]}</div>";
    if($m["sync"]>0){
        $h[]="<div class='licx-muted'>{last_sync}: ".htmlspecialchars(distanceOfTimeInWords($m["sync"],time()))."</div>";
    }
    if(!$m["gold"] && !$m["community_reset"] && $m["admin"]){
        $h[]="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"RegisterSave();\"><i class='fa-solid fa-rotate'></i> {refresh_status}</button>";
    }
    $h[]="</div>";
    return @implode("",$h);
}
// Une carte d'avis : bordure gauche de la couleur de la gravite, au plus deux boutons.
function licx_notice(string $tone,string $ico,string $title,string $body,string $actions=""):string{
    $a=$actions!=""?"<div class='licx-notice-act'>$actions</div>":"";
    return "<div class='licx-notice licx-notice-$tone'><span class='licx-notice-ico'><i class='$ico'></i></span>"
        ."<div class='licx-notice-bd'><h4>$title</h4><div class='licx-notice-txt'>$body</div></div>$a</div>";
}
function licx_notices(array $m):string{
    $out=array();
    $out[]=licx_notice_expired($m);
    $out[]=licx_notice_community($m);
    $out[]=licx_notice_trial($m);
    $out[]=licx_notice_unregistered($m);
    $out[]=licx_notice_limit($m);
    $out[]=licx_notice_waiting($m);
    $out[]=licx_notice_request($m);
    $out[]=licx_notice_articabox($m);
    return @implode("",$out);
}
function licx_notice_expired(array $m):string{
    if($m["state"]!="expired" && $m["state"]!="expired_community"){return "";}
    $page=CurrentPageName();
    $tpl=new template_admin();
    $acts="";
    if($m["admin"]){
        $acts=$tpl->button_upload("{upload_your_license} (*.key)",$page,"licx-upload-btn")
            ."<button type='button' class='ak-btn ak-btn-danger' OnClick=\"Loadjs('$page?reset-js=yes');\"><i class='fa-solid fa-link-slash'></i> {reset_the_license}</button>";
    }
    return licx_notice("err","fa-solid fa-circle-xmark","{expired}","{license_expired_explain}",$acts);
}
function licx_notice_community(array $m):string{
    if(!$m["community_reset"]){return "";}
    $page=CurrentPageName();
    $acts=$m["admin"]?"<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?reset-js=yes&without-confirm=yes');\"><i class='fa-solid fa-certificate'></i> {by_a_license}</button>":"";
    return licx_notice("neutral","fa-solid fa-cart-shopping","{licx_community_mode}","{license_explain_com_reset}",$acts);
}
function licx_notice_trial(array $m):string{
    if(!$m["trial"] || $m["community_reset"] || !$m["corp"]){return "";}
    $page=CurrentPageName();
    $acts=$m["admin"]?"<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?reset-js=yes');\"><i class='fa-solid fa-right-left'></i> {reset_the_license}</button>":"";
    return licx_notice("info","fa-solid fa-circle-info","{trial_mode}","{WELCOME_ARTICA_EVAL}<br>{trial_mode_reset_explain}",$acts);
}
function licx_notice_unregistered(array $m):string{
    if($m["wait_days"]<0){return "";}
    list($title,$body)=licx_split_token("{explain_license_key_wait}");
    $body=str_replace("%s","<b>".intval($m["wait_days"])."</b>",$body);
    $acts=$m["admin"]?"<button type='button' class='ak-btn' OnClick=\"RegisterSave();\"><i class='fa-solid fa-rotate'></i> {refresh_status}</button>":"";
    return licx_notice("info","fa-solid fa-circle-info",$title!=""?$title:"{license_server}",$body,$acts);
}
function licx_notice_limit(array $m):string{
    if(!$m["slr"]){return "";}
    list($title,$body)=licx_split_token("{explain_license_reach_limit}");
    $acts="<button type='button' class='ak-btn' OnClick=\"s_PopUpFull('{$m["pref"]}/','1024','900');\"><i class='fa-solid fa-earth-americas'></i> {license_server}</button>";
    return licx_notice("warn","fa-solid fa-triangle-exclamation",$title!=""?$title:"{licx_warning}",$body,$acts);
}
function licx_notice_waiting(array $m):string{
    if($m["state"]!="waiting_approval"){return "";}
    return licx_notice("warn","fa-solid fa-hourglass-half","{waiting_approval}","{waiting_approval_text}");
}
// Demande en cours (evaluation, commande) : le texte d'origine expliquant la demarche.
function licx_notice_request(array $m):string{
    if($m["state"]!="pending"){return "";}
    return licx_notice("info","fa-solid fa-circle-info","{artica_license}","{artica_license_explain}");
}
function licx_notice_articabox(array $m):string{
    $tpl=new template_admin();
    if($tpl->language!="fr"){return "";}
    $acts="<button type='button' class='ak-btn' OnClick=\"s_PopUpFull('http://www.articabox.com/?pk_campaign=FromArticaInstall','1024','1024');\"><i class='fa-solid fa-link'></i> {licx_open}</button>";
    return licx_notice("neutral","fa-solid fa-phone","ArticaBox.com","{articaboxxcom}",$acts);
}
// --------------------------------------------------------------- identifiants
function licx_card_identifiers(array $m):string{
    $page=CurrentPageName();
    $h=array();
    $h[]="<section class='ak-card licx-card'><div class='licx-card-head'><h3>{licx_identifiers}</h3></div>";
    $up=$m["admin"]?"<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?upload-lic=yes');\"><i class='fa-solid fa-file-arrow-up'></i> {licx_upload_key}</button>":"";
    if($m["number"]!=""){
        $h[]=licx_id_row("{license_number}",htmlspecialchars($m["number"]),licx_copy_btn($m["number"],"{license_number}").$up);
    }else{
        $h[]=licx_id_row("{license_number}","<span class='licx-txt-err'>{no_license}</span>",$up);
    }
    $edit=$m["admin"]?"<button type='button' class='licx-ibtn' OnClick=\"Loadjs('$page?ch-uuid=".urlencode($m["uuid"])."');\" title='{licx_edit}' aria-label='{licx_edit} {uuid}'><i class='fa-solid fa-pen'></i></button>":"";
    $h[]=licx_id_row("{uuid}",htmlspecialchars($m["uuid"]),licx_copy_btn($m["uuid"],"{uuid}").$edit);
    if(!$m["gold"] && $m["apikey"]!=""){$h[]=licx_key_row($m);}
    $h[]=licx_id_row("{lic_node_id}",htmlspecialchars($m["hostname"]),licx_copy_btn($m["hostname"],"{lic_node_id}"),
        " <span class='ak-tag'>{licx_hostname}</span>");
    $h[]="</section>";
    return @implode("",$h);
}
// La cle privee authentifie l'appliance : masquee par defaut, reservee a l'administrateur.
function licx_key_row(array $m):string{
    $page=CurrentPageName();
    $key=$m["apikey"];
    $mask=str_repeat("&bull;",12).htmlspecialchars(substr($key,-4));
    if(!$m["admin"]){
        return licx_id_row("{insert_token}",$mask,"");
    }
    $secret=htmlspecialchars($key,ENT_QUOTES);
    $acts="<button type='button' class='ak-btn' aria-pressed='false' data-licx-secret=\"$secret\" OnClick=\"LicxReveal(this);\">"
        ."<i class='fa-solid fa-eye'></i> <span class='licx-show'>{licx_show}</span><span class='licx-hide'>{licx_hide}</span></button>"
        .licx_copy_btn($key,"{insert_token}");
    if(!$m["community_reset"]){
        $acts.="<button type='button' class='licx-ibtn' OnClick=\"LoadAjax('table-loader-license-service','$page?change-token=yes');\" title='{change_private_key}' aria-label='{change_private_key}'><i class='fa-solid fa-pen'></i></button>";
    }
    return licx_id_row("{insert_token}","<span class='licx-key' data-licx-mask=\"$mask\">$mask</span>",$acts);
}
// ---------------------------------------------------------------- organisation
function licx_dl_row(string $label,string $value):string{
    return "<div class='licx-dl'><dt>$label</dt><dd>$value</dd></div>";
}
function licx_card_organization(array $m):string{
    $tpl=new template_admin();
    $h=array();
    $h[]="<section class='ak-card licx-card'><div class='licx-card-head'><h3>{licx_organization}</h3></div><dl class='licx-dls'>";
    $h[]=licx_dl_row("{company}",$m["company"]!=""?htmlspecialchars($m["company"]):"<span class='licx-muted'>&mdash;</span>");
    if(!$m["gold"]){
        $h[]=licx_dl_row("{licx_email}",$m["email"]!=""?htmlspecialchars($m["email"]):"<span class='licx-muted'>&mdash;</span>");
        if($m["requested_by"]!="" && !$m["community_reset"]){
            $h[]=licx_dl_row("{requested_by}",htmlspecialchars($m["requested_by"]));
        }
        $h[]=licx_dl_row("{licx_license_type}",licx_license_type($m));
    }
    if($m["created"]>0){$h[]=licx_dl_row("{install_date}",$tpl->time_to_date($m["created"]));}
    $h[]="</dl>";
    $h[]=licx_rp_block($m);
    $h[]="</section>";
    return @implode("",$h);
}
// ABOUT_PP vient du serveur de licences (texte ou HTML simple) : on n'en garde que le texte.
function licx_license_type(array $m):string{
    if($m["about"]==""){
        return "<span class='licx-txt-err'>{no_assigned_license}</span> "
            ."<a class='licx-link' href=\"javascript:blur();\" OnClick=\"s_PopUpFull('{$m["pref"]}/','1024','900');\">{license_server}</a>";
    }
    return "<span class='ak-tag ak-ok'>".htmlspecialchars(trim(strip_tags($m["about"])))."</span>";
}
function licx_rp_block(array $m):string{
    $rp=$m["rp"];
    if(count($rp)==0){return "";}
    $h="<div class='licx-rp'><div class='licx-rp-head'><span class='licx-lab2'>{licx_rp_websites}</span>";
    if(isset($rp["error"])){
        return $h."<span class='licx-txt-err'>".htmlspecialchars($rp["error"])."</span></div></div>";
    }
    if($rp["max"]==0){
        return $h."<span class='ak-tag ak-warn'>{no_license}</span></div></div>";
    }
    $pct=min(100,intval(round($rp["used"]*100/$rp["max"])));
    $free=max(0,$rp["max"]-$rp["used"]);
    $fill=$pct>=100?"licx-fill-err":($pct>90?"licx-fill-warn":"licx-fill-ok");
    $h.="<span><b>{$rp["used"]} / {$rp["max"]}</b> {web_rules}</span></div>";
    $h.="<div class='licx-track' role='progressbar' aria-valuemin='0' aria-valuemax='{$rp["max"]}' aria-valuenow='{$rp["used"]}'><div class='licx-fill $fill' style='width:".max(2,$pct)."%'></div></div>";
    $h.="<div class='licx-exp-foot'><span>$pct% {licx_allocated}</span><span>$free {licx_available}</span></div></div>";
    return $h;
}
// -------------------------------------------------------------- vue non rattachee
function licx_view_unlinked(array $m):string{
    $h=array();
    $h[]=licx_banner_unlinked($m);
    $n=licx_notice_limit($m).licx_notice_waiting($m).licx_notice_articabox($m);
    if($n!=""){$h[]="<div class='licx-notices licx-notices-1'>$n</div>";}
    $h[]="<div class='licx-choice' role='list'>".licx_card_account($m)
        ."<span class='licx-or' aria-hidden='true'>{licx_or}</span>".licx_card_upload($m)."</div>";
    return @implode("\n",$h);
}
function licx_banner_unlinked(array $m):string{
    $sub="{licx_unlinked_sub}";
    if($m["state"]=="expired" || $m["state"]=="expired_community"){$sub="{licx_expired_sub}";}
    if($m["state"]=="waiting_approval"){$sub="{waiting_approval_text}";}
    return "<section class='licx-hero licx-hero-unl licx-acc-".licx_accent($m)."'>"
        ."<div class='licx-hero-id'><span class='licx-hero-ico licx-ico-neutral'><i class='fa-solid fa-key'></i></span><div>"
        ."<div class='licx-hero-line'><h2>".licx_edition($m)."</h2>".licx_pill($m)."</div><p class='licx-hero-sub'>$sub</p></div></div>"
        ."<div class='licx-uuid-box'><div class='licx-lab2'>{uuid}</div><div class='licx-uuid-line'><span class='licx-code'>"
        .htmlspecialchars($m["uuid"])."</span>".licx_copy_btn($m["uuid"],"{uuid}")."</div><div class='licx-muted'>{licx_uuid_hint}</div></div>"
        ."</section>";
}
function licx_card_account(array $m):string{
    $tpl=new template_admin();
    $reg=$tpl->_ENGINE_parse_body("{registration_form}");
    $link="<a class='licx-link' href=\"javascript:blur();\" OnClick=\"s_PopUpFull('{$m["pref"]}/register','1024','900');\">$reg</a>";
    $explain=str_replace("%s",$link,$tpl->_ENGINE_parse_body("{license_account_explain}"));
    $mail=htmlspecialchars(strpos($m["register_email"],"@")>0?$m["register_email"]:"",ENT_QUOTES);
    $h=array();
    $h[]="<section class='ak-card licx-card licx-choice-card' role='listitem'>";
    $h[]="<div class='licx-choice-head'><span class='licx-tile-ico'><i class='fa-solid fa-id-card'></i></span><h3>{license_account}</h3></div>";
    $h[]="<p class='licx-p'>$explain</p>";
    if(!$m["admin"]){
        $h[]="<p class='licx-muted'>{ERROR_NO_PRIVS2}</p></section>";
        return @implode("",$h);
    }
    $h[]="<div id='REQUEST-API-KEY'></div>";
    $h[]="<input type='hidden' name='LICENSE-API-KEY' value=''>";
    $h[]="<label class='licx-field'><span>{licx_email}</span><input type='email' name='username' id='licx-user' class='ak-in' value='$mail' autocomplete='username'></label>";
    $h[]="<label class='licx-field'><span>{password}</span><span class='licx-pass'><input type='password' name='password' id='licx-pass' class='ak-in' autocomplete='current-password' OnKeyPress=\"if(event.keyCode==13){LicxLogin();}\">"
        ."<button type='button' class='licx-ibtn licx-eye' aria-pressed='false' aria-label='{licx_show} {password}' OnClick=\"LicxEye(this);\"><i class='fa-solid fa-eye'></i></button></span></label>";
    $h[]="<div class='licx-form-err' id='licx-login-err' role='alert'></div>";
    $h[]="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"LicxLogin();\"><i class='fa-solid fa-right-to-bracket'></i> {login}</button>";
    $h[]="</section>";
    return @implode("",$h);
}
function licx_card_upload(array $m):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btn=$m["admin"]?$tpl->button_upload("{key_file} (*.key)",$page,"licx-upload-btn"):"";
    return "<section class='ak-card licx-card licx-choice-card' role='listitem'>"
        ."<div class='licx-choice-head'><span class='licx-tile-ico'><i class='fa-solid fa-file-arrow-up'></i></span><h3>{upload_your_license}</h3></div>"
        ."<p class='licx-p'>{upload_artica_license_explain}</p>"
        ."<div class='licx-drop'>$btn<span class='licx-muted'>{licx_key_only}</span></div>"
        ."</section>";
}
// ------------------------------------------------------------------ assemblage
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(isset($_GET["register-save"]) && licx_is_admin()){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("REGISTER_EMAIL",(string)$_GET["email"]);
    }
    $m=licx_model();
    $unlinked=(!$m["gold"] && $m["apikey"]=="");
    $body=$unlinked?licx_view_unlinked($m):licx_view_licensed($m);
    // licensing-section : cible historique du formulaire de cle (change-token).
    echo licx_style()
        ."<div id='licensing-section'><div id='licx-host'>".$tpl->_ENGINE_parse_body($body)."</div></div>"
        .licx_js($m,!$unlinked)
        ."<script>NoSpinner();Loadjs('$page?tiny-js=yes');</script>";
}
// Rafraichissement de la vue licenciee seule (jamais le TINY, jamais les scripts).
function licx_host(){
    $tpl=new template_admin();
    $m=licx_model();
    if(!$m["gold"] && $m["apikey"]==""){echo "<script>LoadAjax('table-loader-license-service','".CurrentPageName()."?table=yes');</script>";return;}
    echo $tpl->_ENGINE_parse_body(licx_view_licensed($m));
}
function page_wizard(){ table(); }
function page_gold_license():bool{ table(); return true; }

function licx_js(array $m,bool $timer):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $pref=$m["pref"];
    $uuid=addslashes($m["uuid"]);
    $copied=addslashes($tpl->javascript_parse_text("{copied}"));
    $empty=addslashes($tpl->javascript_parse_text("{licx_empty_token}"));
    $missing=addslashes($tpl->javascript_parse_text("{licx_login_missing}"));
    $nonet=addslashes($tpl->javascript_parse_text("{no_internet_connection}"));
    $token=addslashes($m["apikey"]);
    $emp=addslashes($m["employees"]);

    $ARRAY=array();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/artica.license.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/artica_license.txt";
    $ARRAY["CMD"]="/register/license";
    $ARRAY["TITLE"]="{artica_license}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["AFTER_FAILED"]="LoadAjax('table-loader-license-service','$page?table=yes');";
    $ARRAY["REFRESH-MENU"]="yes";
    $prgress=base64_encode(serialize($ARRAY));

    // Actualiser : meme dialogue avec le serveur de licences qu'avant la refonte.
    $url="$pref/api/get/key";
    $data="{'X-API-KEY': token,'uuid':'$uuid'}";
    if($m["confirm_uuid"]){
        $url="$pref/api/set/key";
        $data="{'X-API-KEY': token,'UUID':'$uuid','CID':'".addslashes($m["company_id"])."','seller_cid':'".addslashes($m["seller_cid"])."','seller_uid':'".addslashes($m["seller_uid"])."'}";
    }
    $js="<script>
function LicxErr(t){ swal({title:'Oops...',text:t,html:true,type:'error'}); }
function LicxCopy(el){
    var v=el.getAttribute('data-licx-copy')||'';
    var done=function(){ el.classList.add('licx-copied'); el.setAttribute('title','$copied'); setTimeout(function(){ el.classList.remove('licx-copied'); },1400); };
    if(navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(v).then(done,function(){}); return; }
    var t=document.createElement('textarea'); t.value=v; t.style.position='fixed'; t.style.opacity='0';
    document.body.appendChild(t); t.select();
    try{ document.execCommand('copy'); done(); }catch(e){}
    document.body.removeChild(t);
}
function LicxReveal(btn){
    var row=btn.closest('.licx-idrow'); if(!row){return;}
    var k=row.querySelector('.licx-key'); if(!k){return;}
    var on=btn.getAttribute('aria-pressed')!=='true';
    btn.setAttribute('aria-pressed',on?'true':'false');
    if(on){ k.textContent=btn.getAttribute('data-licx-secret')||''; }else{ k.innerHTML=k.getAttribute('data-licx-mask')||''; }
}
function LicxEye(btn){
    var i=document.getElementById('licx-pass'); if(!i){return;}
    var on=i.type==='password'; i.type=on?'text':'password';
    btn.setAttribute('aria-pressed',on?'true':'false');
}
function LicxLogin(){
    var u=document.getElementById('licx-user'), p=document.getElementById('licx-pass'), e=document.getElementById('licx-login-err');
    if(!u||!p){return;}
    if(u.value.trim()==='' || p.value===''){ if(e){e.textContent='$missing';} return; }
    if(e){e.textContent='';}
    var XHR=new XHRConnection();
    XHR.appendData('LICENSE-API-KEY','');
    XHR.appendData('username',u.value.trim());
    XHR.appendData('password',p.value);
    XHR.sendAndLoad('$page','POST',function(obj){
        var t=obj.responseText||'';
        if(t.length>3){ LicxErr(t.indexOf('jserror:')===0?t.substring(8):t); return; }
        Loadjs('$page?get-token=yes');
    });
}
var x_RegisterSave=function(obj){
    var t=obj.responseText||'';
    if(t.length>3){ LicxErr(t.indexOf('jserror:')===0?t.substring(8):t); return; }
    Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-license-restart');
};
function RegisterSave(){
    var token='$token';
    if(token.length==0){ LicxErr('$empty'); return; }
    $.ajax({type:'POST',url:'$url',data:$data,cache:false,
        success:function(bucket){
            if(bucket.status==false){ LicxErr(bucket.message); return; }
            if(bucket.status==true){
                var XHR=new XHRConnection();
                XHR.appendData('tokenRequest',bucket.message);
                XHR.appendData('EMPLOYEES','$emp');
                XHR.appendData('LICENCE_REQUEST','1');
                XHR.sendAndLoad('$page','POST',x_RegisterSave);
            }
        },
        error:function(bucket){
            if(bucket.readyState==4 && bucket.responseJSON){ LicxErr(bucket.responseJSON.error||bucket.responseJSON.message||''); return; }
            LicxErr('$nonet');
        }});
}
function GetToken(){ window.open('$pref/dashboard/endpoint/create/token/$uuid','MsgWindow','width=800,height=500,top=80'); }
";
    if($timer){
        $js.="if(window.LicxTimer){clearInterval(window.LicxTimer);}
window.LicxTick=0;
// Toutes les 10 s : on recharge tout de suite si une verification de cle est en
// cours (le verdict arrive en quelques secondes), sinon une fois par minute.
window.LicxTimer=setInterval(function(){
    if(!document.getElementById('licx-host')){clearInterval(window.LicxTimer);window.LicxTimer=null;return;}
    if(document.querySelector('.modal.in, .modal.show, .sweet-alert.visible')){return;}
    window.LicxTick++;
    if(!document.querySelector('.licx-sig-pending') && window.LicxTick<6){return;}
    window.LicxTick=0;
    LoadAjaxSilent('licx-host','$page?licx-host=yes');
},10000);
";
    }
    return $js."</script>";
}
// En-tete : boutons sobres selon l'etat ; la reinitialisation est dans le menu "plus".
function tiny_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=array("pref"=>prefixLic());
    $L=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    if(!is_array($L)){$L=array();}
    $gold=$GLOBALS["CLASS_SOCKETS"]->CORP_GOLD() || intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("isCorpGold"))==1;
    $haskey=trim((string)($L["X-API-KEY"] ?? ""))!="";
    $b=array();
    if(licx_is_admin()){
        if(!$gold){
            $b[]="<button type='button' class='licx-tb' OnClick=\"s_PopUpFull('{$m["pref"]}/','1024','900');\"><i class='fa-solid fa-earth-americas'></i> {license_server}</button>";
        }
        if(!$gold && !$haskey){
            $b[]="<button type='button' class='licx-tb' OnClick=\"LoadAjax('table-loader-license-service','$page?create-account=yes');\"><i class='fa-solid fa-user-plus'></i> {create_account}</button>";
            $b[]="<button type='button' class='licx-tb' OnClick=\"Loadjs('$page?resent-activation-code=yes');\"><i class='fa-solid fa-envelope'></i> {resent_activation_code}</button>";
        }
        if(!$gold && trim((string)($L["max_server"] ?? ""))==""){
            $b[]="<button type='button' class='licx-tb' OnClick=\"Loadjs('fw.license.gold.php');\"><i class='fa-solid fa-crown'></i> {gold_license}</button>";
        }
        $b[]="<button type='button' class='licx-tb' OnClick=\"Loadjs('fw.license.events.php');\"><i class='fa-solid fa-eye'></i> {events}</button>";
        // Passar ao licenciamento por certificado.
        //
        // Só aparece a quem ainda não passou: numa máquina migrada é a
        // fw.licensev2.php que manda, e esta página não devia nem abrir.
        if(!$GLOBALS["CLASS_SOCKETS"]->USE_LICENSE_V2()){
            $b[]="<button type='button' class='licx-tb licx-tb-info' OnClick=\"Loadjs('$page?licmig-js=yes&_t='+Date.now());\">"
                ."<i class='fa-solid fa-arrow-right-arrow-left'></i> {licmig_button}</button>";
        }
        $b[]="<div class='btn-group'><button type='button' class='licx-tb licx-tb-more dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' aria-label='{licx_more_actions}' title='{licx_more_actions}'><i class='fa-solid fa-ellipsis-vertical'></i></button>"
            ."<ul class='dropdown-menu dropdown-menu-right'><li><a href=\"javascript:blur();\" class='licx-tb-danger' OnClick=\"Loadjs('$page?reset-js=yes');\"><i class='fa-solid fa-link-slash'></i> {reset_the_license}</a></li></ul></div>";
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{artica_license}";
    $TINY_ARRAY["ICO"]="fa-duotone fa-file-certificate";
    $TINY_ARRAY["EXPL"]="{CORP_LICENSE_EXPLAIN}";
    $TINY_ARRAY["URL"]="license";
    $TINY_ARRAY["BUTTONS"]="<div class='licx-tbar'>".@implode("",$b)."</div>";
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

// ===========================================================================
// Passar esta máquina para o licenciamento por certificado (mTLS).
//
// Um assistente de um passo só, porque a operação é uma: carregar o ficheiro que
// a Artica emitiu. O resto -- ligar o interruptor, mandar o daemon reler, trocar
// de página -- é consequência, e consequência não se pede a quem está ao balcão.
//
// A ORDEM É A SEGURANÇA TODA: instala-se o certificado PRIMEIRO e só se liga o
// interruptor se a instalação disser que sim. Pela ordem contrária, um ficheiro
// errado deixava a máquina no mundo novo sem credencial nenhuma -- Community no
// instante, e com a página antiga já fora de alcance para desfazer.
// ===========================================================================
function licmig_js():bool{
    $tpl=new template_admin();
    if(!licx_is_admin()){return $tpl->js_no_privileges();}
    return $tpl->js_dialog4("{licmig_button}",
        CurrentPageName()."?licmig-popup=yes&_t=".time(),620);
}
function licmig_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!licx_is_admin()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    // O mesmo botão de upload da página nova, de propósito: é o mesmo gesto, e
    // quem o fez uma vez já sabe fazê-lo.
    $upload_button=$tpl->button_upload("{certificate} (*.pem)",$page,null,null,"licmig-uploaded");
    $html[]=$tpl->div_explain("{licmig_button}||{licmig_explain}");
    $html[]="<div style='margin-top:20px;text-align:right;border-top:1px solid #CCCCCC;padding-top:10px'>$upload_button</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function licmig_uploaded():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!licx_is_admin()){return $tpl->js_no_privileges();}
    $dir="/usr/share/artica-postfix/ressources/conf/upload";
    $file=basename((string)$_GET["licmig-uploaded"]);
    $path="$dir/$file";
    // Tolerância ao nome: o recebedor acrescenta " (1)" quando já existe um
    // ficheiro com o mesmo nome, e o navegador faz o mesmo ao descarregar duas
    // vezes. Se o nome exacto não estiver lá, procura-se o mesmo nome sem
    // diferenças de caixa ou de espaços antes de desistir.
    if($file!="" && !is_file($path)){
        foreach((array)@scandir($dir) as $cand){
            if($cand=="." || $cand=="..") continue;
            if(strcasecmp(trim($cand),trim($file))==0){
                $file=$cand; $path="$dir/$cand"; break;
            }
        }
    }
    if($file=="" || !is_file($path)){
        // O erro DIZ O QUE SE PASSOU: o que se procurou e o que a pasta tinha.
        // "cannot be uploaded" sozinho manda quem o lê adivinhar entre um nome
        // alterado no caminho, um disco cheio e uma gravação falhada em silêncio.
        $havia=array();
        foreach((array)@scandir($dir) as $cand){
            if($cand=="." || $cand=="..") continue;
            $havia[]=$cand;
        }
        $lista=count($havia)>0 ? @implode(", ",array_slice($havia,0,6)) : "{licmig_empty}";
        admin_tracks("Licence migration: upload not found, looked for [$file], dir holds [$lista]");
        echo $tpl->js_error("{licmig_not_found}: [$file]<br>{licmig_dir_holds}: $lista");
        return false;
    }
    $size=filesize($path);
    admin_tracks("Licence migration: uploaded certificate $file ($size bytes)");

    // As mesmas duas recusas da página nova: um .key não é um certificado, e um
    // ficheiro de outra coisa qualquer não se manda ao daemon para ele descobrir.
    if(preg_match("#\.key$#i",$file)){
        @unlink($path);
        echo $tpl->js_error("{lv2_key_not_supported}");
        return false;
    }
    if(!preg_match("#\.(pem|crt|cer|bundle|txt)$#i",$file)){
        @unlink($path);
        echo $tpl->js_error("{lv2_not_a_certificate}");
        return false;
    }

    // 1) O certificado. Se isto falhar, nada mais acontece.
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/licensev2/certificate/upload/".base64_encode($file));
    $j=@json_decode($raw,true);
    if(!is_array($j) || !isset($j["Status"]) || !$j["Status"]){
        $err=is_array($j)?(string)($j["Error"] ?? "{failed}"):"{failed}";
        echo $tpl->js_error($err);
        return false;
    }

    // 2) E só agora o interruptor. O daemon relê-o em cada pergunta (ver
    // licensev2.Enabled), por isso não há nada a reiniciar.
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UseLicmTLS",1);
    admin_tracks("Licence migration: UseLicmTLS set to 1");

    // 3) E a página nova, que é onde esta máquina vive a partir de agora.
    $js=array();
    $js[]="if(typeof dialogInstance4 == 'object'){ dialogInstance4.close();}";
    // A maquina mudou de modelo: recarrega-se tudo, incluindo o menu.
    $js[]="document.location.reload();";
    echo @implode("\n",$js);
    return true;
}
