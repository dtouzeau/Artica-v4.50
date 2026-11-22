<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){
    if(!is_file(dirname(__FILE__)."/ressources/VERBOSE")){
        $GLOBALS["VERBOSE"]=false;
        unset($_GET["verbose"]);
    }else {
        echo "VERBOSED!!!!<br>\n\n";
    }
}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.langages.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.privileges.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-logon.inc');
include_once(dirname(__FILE__).'/ressources/class.login.csrf.inc');
include_once(dirname(__FILE__).'/ressources/class.login.2fa.inc');
include_once(dirname(__FILE__)."/ressources/externals/GeoIP2/vendor/autoload.php");
use GeoIp2\Database\Reader;
//$GLOBALS["VERBOSE"]=true;
if(isset($_GET["artica-restcheck"])){RestCheck();exit;}
if(isset($_GET["api-probe"])){ApiProbe();exit;}
if(isset($_GET["apikey"])){apikey();exit;}
if(isset($_GET["disconnect"])){disconnect();exit;}
// Une session avec un uid mais encore 2FA_PENDING n'a prouvé qu'un mot de
// passe: elle ne doit PAS être renvoyée vers /index, sous peine de ne jamais
// laisser le POST du code (dispatché plus bas dans ce fichier) s'exécuter --
// ce bloc s'exécuterait avant lui à chaque requête, y compris le POST du
// code, et bouclerait indéfiniment via le garde de class.sessions.inc.
if(isset($_SESSION["uid"]) && empty($_SESSION["2FA_PENDING"])){
    $HTTP_X_ARTICA_SUBFOLDER=null;
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){$HTTP_X_ARTICA_SUBFOLDER=$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/"; }
   // echo "Location: /{$HTTP_X_ARTICA_SUBFOLDER}index";die();
    header("Location: /{$HTTP_X_ARTICA_SUBFOLDER}index");exit();
}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}
CheckTrustedNets();

// Mode SAML: la page de connexion ne rend plus de formulaire, elle delegue au
// fournisseur d'identite. Le drapeau est relu A CHAQUE requete -- jamais depuis
// la session -- pour qu'une bascule de secours depuis logon-artica reprenne la
// main immediatement, y compris sur un navigateur deja poste ici.
// Le POST du formulaire historique est refuse tant que le mode est actif: sans
// cela, une requete forgee court-circuiterait l'authentification multifacteur.
include_once(dirname(__FILE__)."/ressources/class.saml.sp.inc");
if(saml_sp::enabled()){
    if(isset($_POST["username"])){
        saml_sp::audit("password-login-refused",strval($_POST["username"]));
        header("Location: /fw.login.saml.php");
        exit;
    }
    header("Location: /fw.login.saml.php");
    exit;
}

// Un seul argument : header("Nom","valeur") prend la valeur pour le booleen $replace
// et emet un en-tete VIDE -- la politique n'a jamais ete appliquee (pentest SysDream V6).
// img-src data: couvre le logo client ; pas de form-action, que Chrome applique aussi
// a la redirection SAML vers le fournisseur d'identite. Valide dans Chromium sur les
// parcours echec et succes de connexion, sans aucune violation. Emise AVANT le
// traitement des POST : la page reaffichee apres un echec et l'ecran 2FA en ont besoin.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' data: fonts.gstatic.com; img-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");

if(isset($_POST["twofactor-code"])){twofactor_submit();exit;}
if(isset($_POST["username"])){CheckLogin();exit;}
if(isset($_GET["debugcredentials"])){debugcredentials();exit;}



    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/wizard-status"),true);
    if(!isset($json["Status"])){
        LoginNoAPI($json);
        exit;
    }
    if(!isset($json["wizard_executed"])){
        LoginNoAPI($json);
        exit;
    }
    $wizard_executed=intval($json["wizard_executed"]);
    if ($wizard_executed == 0) {
        header('location:fw.wizard.intro.php');
        exit;
    }




$ArticaWebOldLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebOldLogin"));
if($ArticaWebOldLogin==1){
    login();
    exit;
}
function SMTPDomain():string{
    $WizardSavedSettingsData=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings");
    if(strlen($WizardSavedSettingsData)<10){
        return "";
    }
    $WizardSavedSettings=unserialize(base64_decode($WizardSavedSettingsData));
    if(!$WizardSavedSettings){
        return "";
    }
    if (!isset($WizardSavedSettings["smtp_domainname"])) {
        return "";
    }
    if (!is_null($WizardSavedSettings["smtp_domainname"])) {
        $WizardSavedSettings["smtp_domainname"] = trim($WizardSavedSettings["smtp_domainname"]);
    }

    $smtp_domainname = trim($WizardSavedSettings["smtp_domainname"]);
    if(is_null($smtp_domainname)){
        return "";
    }
    if(strlen($smtp_domainname) < 2) {
       return "";
    }
    return trim($smtp_domainname);

}

new_login();

function disconnect():bool{
    logon_events("LOGOUT");
    admin_tracks("Logout");

    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }

    // Le drapeau est relu ici, jamais depuis la session.
    include_once(dirname(__FILE__)."/ressources/class.saml.sp.inc");
    $artica_logon=new artica_logon();

    // Trois sorties possibles, de la plus complete a la plus degradee.
    //
    // 1. SAML + deconnexion unique disponible: on passe la main a saml_slo(), qui doit lire le
    //    NameID DANS LA SESSION -- donc on ne la ferme PAS ici. C'est le seul chemin ou logoff()
    //    n'est pas appele a cet endroit; saml_slo() s'en charge apres avoir construit la demande.
    // 2. SAML sans SLO: fermeture locale, puis page de fin de session. NE PAS revenir sur
    //    fw.login.php, qui redirige inconditionnellement vers le flux SAML et reconnecterait
    //    aussitot l'utilisateur (le fournisseur ayant garde sa propre session).
    // 3. Mode mot de passe: comportement historique.
    if(saml_sp::enabled()){
        if(saml_sp::slo_enabled() && isset($_SESSION["saml_nameid"]) && trim(strval($_SESSION["saml_nameid"]))!==""){
            $back="{$HTTP_X_ARTICA_SUBFOLDER}fw.login.saml.php?slo=yes";
        }else{
            $artica_logon->logoff();
            $back="{$HTTP_X_ARTICA_SUBFOLDER}fw.login.saml.php?loggedout=yes";
        }
    }else{
        $artica_logon->logoff();
        $back="{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php";
    }

    echo "
    <html>
	<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$back\"> </head>
	<body>
	<script>document.location.href='$back';</script>
	</body>
	</html>";
    return true;

}
function RestCheck():bool{

    if(!function_exists("curl_init")){
        header("X-CURL: 0");
        return false;
    }
    header("X-CURL: 1");
    return true;
}

function new_css():string{

    if(!isset($_COOKIE["userfont"])){$_COOKIE["userfont"]=null;}
    $HideArticaLogo             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaLogo"));

    // Les quatre couleurs passent par brand_palette(), qui porte le controle de
    // licence. Jusqu'ici seules DEUX des quatre revenaient a l'usine sans
    // licence : ArticaFontColorTitle et ArticaFontColorFields survivaient.
    // class.brand.inc est un fichier NEUF : garde is_file() + function_exists(),
    // sinon une appliance ou il n'est pas deploye rend un fatal.
    $BRANDINC=dirname(__FILE__)."/ressources/class.brand.inc";
    if(is_file($BRANDINC)){include_once($BRANDINC);}
    $BRANDPAL=array();
    if(function_exists("brand_palette")){$BRANDPAL=brand_palette();}
    if(count($BRANDPAL)>0){
        $ArticaBackGroundColor=$BRANDPAL["login_bg"];
        $ArticaFontColor=$BRANDPAL["login_fg"];
        $ArticaFontColorTitle=$BRANDPAL["login_title"];
        $ArticaFontColorFields=$BRANDPAL["login_fields"];
        $SKIN=brand_login_skin($BRANDPAL);
        if(!brand_corp_license()){$HideArticaLogo=0;}
    }else{
        $ArticaBackGroundColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLoginBackGroundColor"));
        $ArticaFontColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLoginFontColor"));
        $ArticaFontColorTitle=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFontColorTitle"));
        $ArticaFontColorFields=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFontColorFields"));
        if($ArticaFontColor==null){$ArticaFontColor="#a7b1c2";}
        if($ArticaBackGroundColor==null){$ArticaBackGroundColor="#283437";}
        if($ArticaFontColorTitle==null){$ArticaFontColorTitle="#ffffff";}
        if($ArticaFontColorFields==null){$ArticaFontColorFields="#ffffff";}
        if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
            $HideArticaLogo=0;
            $ArticaBackGroundColor="#283437";
            $ArticaFontColor="#a7b1c2";
        }
        $SKIN=array("light"=>false,"button_bg"=>"#18a689",
            "input_bg"=>"rgba(255,255,255,0.2)","input_bg_ie"=>"#576D73",
            "input_border"=>"0","input_fg"=>$ArticaFontColorFields,
            "nologin_bg"=>"rgba(255,255,255,0.2)","nologin_fg"=>"#FFF",
            "error_fg"=>"rgb(255, 204, 0)");
    }
    // Les @font-face de lato / bariol / ailerons vivent dans angular/fonts.css,
    // que cette page ne charge pas autrement : sans ce lien la font-family
    // composee plus bas n-a AUCUNE source et le navigateur retombe en silence
    // sur Arial. Le lien est emis ICI, dans new_css(), et non dans les <head>
    // appelants : new_css() est la fonction qui DECIDE la police, c'est donc
    // elle qui doit en declarer les sources -- new_login(), LoginNoAPI() et
    // l-ecran 2FA en heritent d-un coup. Meme convention d-URL relative que le
    // fond et que fingerprint/query juste au-dessus, pour survivre a une
    // publication en sous-dossier.
    $SUBF=null;
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $SUBF="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }
    $f[]="<link href=\"{$SUBF}angular/fonts.css\" rel=\"stylesheet\">";
    $f[]="<style>";
    // La police de marque doit etre declaree ICI : cette page porte sa propre
    // feuille et ne charge pas angular.css.php. Seule la declaration, pas la
    // regle d'application -- la font-family du corps est composee plus bas.
    if(count($BRANDPAL)>0 && function_exists("brand_css_fontface")){
        $BRANDFACE=brand_css_fontface($BRANDPAL);
        if($BRANDFACE!=""){$f[]=$BRANDFACE;}
    }
    $f[]="body{";
    if($HideArticaLogo==0) {

        $HTTP_X_ARTICA_SUBFOLDER=null;
        if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
            $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
        }

        $f[] = "background-image:url('{$HTTP_X_ARTICA_SUBFOLDER}img/Articafond3.png');";
    }


    // La police de marque prime sur la preference personnelle : un client
    // white-label impose sa charte, le cookie userfont ne la surcharge pas.
    // Cascade, dans cet ordre : police de marque, sinon cookie, sinon lato.
    // 'lato' suit TOUJOURS le nom demande, jamais Arial : c'est la police par
    // defaut de la console (angular.css.php) et c'est elle qui doit prendre le
    // relais quand la police demandee n'a pas de @font-face servi. Le cas est
    // reel pour la marque : $BRANDFONTNAME ne vaut que le NOM, alors que le
    // @font-face n'est emis par brand_css_fontface() que si le fichier woff2
    // est present ET la licence Corporate active -- un nom sans source.
    $BRANDFONTNAME="";
    if(count($BRANDPAL)>0 && $BRANDPAL["font_on"] && function_exists("brand_css_ident")){
        $BRANDFONTNAME=brand_css_ident($BRANDPAL["font_name"]);
    }
    if($BRANDFONTNAME!=""){
        $fontFamily="font-family: '$BRANDFONTNAME', 'lato', Arial, \"MS UI Gothic\", \"MS P Gothic\", sans-serif;";

    }else if($_COOKIE["userfont"]==null){
        $fontFamily="font-family: 'lato', Arial, \"MS UI Gothic\", \"MS P Gothic\", sans-serif;";

    }else{
        if($_COOKIE["userfont"]=="standard"){
            // "standard" veut dire explicitement la pile systeme neutre :
            // surtout pas de lato ici, ce serait annuler le choix.
            $fontFamily="font-family: \"open sans\",\"Helvetica Neue\",Helvetica,Arial,sans-serif;";

        }else{
            // Le cookie est une donnee du client, et cette page est la SEULE
            // des trois a etre servie sans authentification : sans filtre, la
            // valeur sort du 'quote' et de la regle CSS. Meme garde que
            // angular.css.php et fw.captcha.php.
            $safefont=preg_replace('/[^a-zA-Z0-9 -]/','',$_COOKIE["userfont"]);
            // 'lato' est l'une des cinq valeurs du menu : sans ce test la pile
            // sortirait en "'lato', 'lato'" -- valide, mais illisible.
            $stack="'$safefont', ";
            if(strtolower($safefont)!="lato"){$stack=$stack."'lato', ";}
            $fontFamily="font-family: {$stack}Arial, \"MS UI Gothic\", \"MS P Gothic\", sans-serif;";

        }
    }

    $f[]=$fontFamily;
    //$f[]="font-family: Arial, MS UI Gothic, MS P Gothic, sans-serif;";
    $f[]="}";
    $f[]=".wrapper{";
    $f[]="background-color:$ArticaBackGroundColor;";
    $f[]="background-size: 1280px 1076px;";
    $f[]="background-position: center 0%;";
    $f[]="background-repeat:no-repeat;";
    $f[]="margin: 0px;";
    $f[]="background:$ArticaBackGroundColor\9;";
    $f[]="}";
    $f[]=".title_name {";
    $f[]="font-size: 40pt;";
    $f[]="color:$ArticaFontColorTitle;";
    $f[]="}";
    $f[]=".LoginTitle2{";
    $f[]="font-size: 26pt;";
    $f[]="color:$ArticaFontColor;";
    $f[]="margin-left:78px;";
    $f[]="margin-top: 10px;";
    $f[]="}";
    $f[]=".login_img{";
    $f[]="width:43px;";
    $f[]="height:43px;";
    $f[]="}";
    $f[]=".p1{";
    $f[]="font-size: 11pt;";
    $f[]="color:$ArticaFontColorTitle;";
    $f[]="width:480px;";
    $f[]="}";
    $f[]=".ArticaVer{";
    $f[]="margin-top:20px;";
    $f[]="font-size:x-small;";
    $f[]="color:$ArticaFontColorTitle;";
    $f[]="text-align: right";
    $f[]="}";
    $f[]=".button{";
    $f[]="background-color:{$SKIN["button_bg"]};";
    $f[]="border-radius: 4px ;";
    $f[]="transition: visibility 0s linear 0.218s,opacity 0.218s,background-color 0.218s;";
    $f[]="height: 68px;";
    $f[]="width: 300px;";
    $f[]="font-size: 28pt;";
    $f[]="color:#fff;";
    $f[]="text-align: center;";
    $f[]="float:right;";
    $f[]="margin:50px 0px 0px 78px;";
    $f[]="line-height:68px;";
    $f[]="cursor:pointer;";
    $f[]="}";
    $f[]=".form_input{";
    $f[]="background-color:{$SKIN["input_bg"]};";
    $f[]="background-color:{$SKIN["input_bg_ie"]}\9;";
    $f[]="border-radius: 4px;";
    $f[]="padding:23px 22px;";
    $f[]="width: 480px;";
    $f[]="border: {$SKIN["input_border"]};";
    $f[]="height:30px;";
    $f[]="color:{$SKIN["input_fg"]};";
    $f[]="font-size:28px;";
    $f[]="font-weight:bold;";
    $f[]="}";
    $f[]=".nologin{";
    $f[]="margin:10px 0px 0px 78px;";
    $f[]="background-color:{$SKIN["nologin_bg"]};";
    $f[]="padding:20px;";
    $f[]="line-height:36px;";
    $f[]="border-radius: 5px;";
    $f[]="width: 480px;";
    $f[]="border: 0;";
    $f[]="color:{$SKIN["nologin_fg"]};";
    $f[]="color:#FFF\9; /* IE6 IE7 IE8 */";
    $f[]="font-size:28px;";
    $f[]="}";
    $f[]=".div_table{";
    $f[]="display:table;";
    $f[]="}";
    $f[]=".div_tr{";
    $f[]="display:table-row;";
    $f[]="}";
    $f[]=".div_td{";
    $f[]="display:table-cell;";
    $f[]="}";
    $f[]=".title_gap{";
    $f[]="margin:20px 0px 0px 78px;";
    $f[]="}";
    $f[]=".img_gap{";
    $f[]="padding-right:30px;";
    $f[]="vertical-align:middle;";
    $f[]="}";
    $f[]=".password_gap{";
    $f[]="margin:30px 0px 0px 78px;";
    $f[]="}";
    $f[]=".error_hint{";
    $f[]="color: {$SKIN["error_fg"]};";
    $f[]="margin:10px 0px -10px 78px;";
    $f[]="font-size: 18px;";
    $f[]="}";
    $f[]=".error_hint1{";
    $f[]="margin:40px 0px -10px 78px;";
    $f[]="font-size: 24px;";
    $f[]="line-height:32px;";
    $f[]="width: 580px;";
    $f[]="}";
    $f[]=".main_field_gap{";
    $f[]="margin:100px auto 0;";
    $f[]="}";
    $f[]=".warming_desc{";
    $f[]="font-size: 16px;";
    $f[]="color:#FC0;";
    $f[]="width: 600px;";
    $f[]="}";
    $f[]="#captcha_img_div{";
    $f[]="margin: 30px 0px 0px 30px;";
    $f[]="width: 160px;";
    $f[]="height: 60px;";
    $f[]="border-radius: 4px;";
    $f[]="background-color:#FFF;";
    $f[]="float: left;";
    $f[]="}";
    $f[]="#captcha_pic{";
    $f[]="width: 90%;";
    $f[]="height:90%;";
    $f[]="margin: 3px 0px 0px 0px;";
    $f[]="}";
    $f[]="#captcha_input_div{";
    $f[]="margin: 30px 0px 0px 78px;";
    $f[]="float: left;";
    $f[]="}";
    $f[]="#captcha_text{";
    $f[]="width: 245px;";
    // Cinquieme valeur dessinee pour un fond sombre, au meme titre que .button,
    // .form_input, .nologin et .error_hint : sur fond clair le champ du captcha
    // etait invisible. Trouve au deploiement, pas a la relecture.
    $f[]="background-color: {$SKIN["input_bg"]};";
    $f[]="background-color: {$SKIN["input_bg_ie"]}\9;";
    $f[]="border-radius: 4px;";
    $f[]="padding: 15px 22px;";
    $f[]="border: {$SKIN["input_border"]};";
    $f[]="height: 30px;";
    // Sur fond sombre, l'original ecrivait le litteral "#fff" et non "#ffffff" :
    // meme couleur, octet different. L'invariant de non-regression se mesure par
    // empreinte md5 de la feuille, donc on preserve le litteral historique.
    $f[]="color: ".($SKIN["light"]?$SKIN["input_fg"]:"#fff").";";
    $f[]="font-size: 28px;";
    $f[]="}";
    $f[]="/*for mobile device*/";
    $f[]="@media screen and (max-width: 1000px){";
    $f[]=".title_name {";
    $f[]="font-size: 20pt;";
    $f[]="color:$ArticaFontColor;";
    $f[]="margin-left:15px;";
    $f[]="}";
    $f[]=".LoginTitle2{";
    $f[]="font-size: 13pt;";
    $f[]="margin-left: 15px;";
    $f[]="}";
    $f[]=".p1{";
    $f[]="font-size: 12pt;";
    $f[]="width:100%;";
    $f[]="}";
    $f[]=".login_img{";
    $f[]="background-size: 75%;";
    $f[]="}";
    $f[]=".form_input{";
    $f[]="padding:10px 11px;";
    $f[]="width: 100%;";
    $f[]="height:30px;";
    $f[]="font-size:16px";
    $f[]="}";
    $f[]=".button{";
    $f[]="height: 50px;";
    $f[]="width: 100%;";
    $f[]="font-size: 14pt;";
    $f[]="text-align: center;";
    $f[]="float:right;";
    $f[]="margin: 25px -22px 40px 15px;";
    $f[]="line-height:50px;";
    $f[]="padding-left: 7px;";
    $f[]="}";
    $f[]=".nologin{";
    $f[]="margin-left:10px;";
    $f[]="padding:10px;";
    $f[]="line-height:18px;";
    $f[]="width: 100%;";
    $f[]="font-size:14px;";
    $f[]="}";
    $f[]=".error_hint{";
    $f[]="margin-left:10px;";
    $f[]="}";
    $f[]=".error_hint1{";
    $f[]="width: 100%;";
    $f[]="font-size:14px;";
    $f[]="}";
    $f[]=".main_field_gap{";
    $f[]="width:80%;";
    $f[]="margin:30px 0 0 15px;";
    $f[]="/*margin:30px auto 0;*/";
    $f[]="}";
    $f[]=".title_gap{";
    $f[]="margin-left:15px;";
    $f[]="}";
    $f[]=".password_gap{";
    $f[]="margin-left:15px;";
    $f[]="}";
    $f[]=".img_gap{";
    $f[]="padding-right:0;";
    $f[]="vertical-align:middle;";
    $f[]="}";
    $f[]=".warming_desc{";
    $f[]="margin: 10px 15px;";
    $f[]="width: 100%;";
    $f[]="}";
    $f[]="</style>";
    $f[]="";
    return @implode("",$f);
}

function client_certificate(){
    if(!isset($_SERVER["SSL_CLIENT_RAW_CERT"])){return false;}
    $LighttpdAllowAuthenticateScreen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdAllowAuthenticateScreen"));
    if($LighttpdAllowAuthenticateScreen==1){return false;}
    $array=openssl_x509_parse($_SERVER["SSL_CLIENT_RAW_CERT"]);
    if(!isset($array["subject"])){
        VERBOSE("client_certificate: subject isset:False",__LINE__);
        return false;
    }
    $login=new artica_logon();
    $login->x509=$array;
    if(!$login->CheckCreds()){
        $ip=GetRemoteIP();
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/btmp/{$array["subject"]}/$ip/ArticaWebx509:notty");
        return false;
    }

    // Le certificat client EST un facteur de possession, plus fort qu'un mot
    // de passe: exiger les deux protégerait peu et casserait un usage établi.
    // L'exemption est écrite ici, pas obtenue par omission.
    $login->twofactor_release("x509");

    $HTTP_X_ARTICA_SUBFOLDER=null;
    //if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){$HTTP_X_ARTICA_SUBFOLDER=$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/"; }

    $redirect="/{$HTTP_X_ARTICA_SUBFOLDER}index";
    setcookie("shellinaboxCooKie", "1", time()+172800,"","",true,true);
    echo "<html>
	<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$redirect\"> </head>
	<body>
	<script>document.location.href='$redirect';</script>
	</body>
	</html>";
    exit();
}

function GetMyHostname():string{
    $hostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    if(!is_null($hostname)){
        if(strlen($hostname)>3){
            return $hostname;
        }
    }
    return php_uname("n");

}

function new_login($return=false,$error=null,$customBody=null){
    client_certificate();
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $clogo="";
    $TitleOfArticaPage          = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LoginTitle2"));
    $TitleLogon                 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TitleLogon"));
    $TextOfArticaPage           = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TextOfArticaPage"));
    $useCustomLogo             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCustomLogo"));
    $customLogo=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("customLogo"));
    $HideArticaVersion          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaVersion"));
    $HideVirtualizationVersion  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideVirtualizationVersion"));

    if($TitleOfArticaPage==null){$TitleOfArticaPage="%SERVERNAME%";}
    if($TextOfArticaPage==null){$TextOfArticaPage="{default_login_explain}";}
    if($TitleLogon==null){$TitleLogon="{Connection}";}
    $title_button="{login}";
    $ArticaVer=null;

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $TextOfArticaPage="{default_login_explain}";
        $TitleOfArticaPage="%SERVERNAME%";
        $HideArticaVersion=0;
        $HideVirtualizationVersion=0;
        $TitleLogon="{Connection}";
        $useCustomLogo=0;
     }
    if(strlen($customLogo)<2){
        $useCustomLogo=0;
    }
    if(strpos(" $TitleOfArticaPage","%SERVERNAME%")>0){
        $hostname=GetMyHostname();
        $myhostname=str_replace("%SERVERNAME%",$hostname,$TitleOfArticaPage);
    }else{
        $myhostname=$TitleOfArticaPage;
    }
    $HTTP_X_ARTICA_SUBFOLDER="/";
    fingerprint();

    if($HideArticaVersion==0) {
        $SP=null;
        $CURVER=trim(@file_get_contents("VERSION"));
        $CURPATCH= $GLOBALS["CLASS_SOCKETS"]->SPVersion();
        if($CURPATCH>0){
            $SP="&nbsp;Service Pack $CURPATCH";
        }
        $ArticaVer = "Artica $CURVER{$SP} &copy; " . date("Y");

    }
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }


    $f[]="<!DOCTYPE html>";
    $f[]="<html xmlns=\"http://www.w3.org/1999/xhtml\">";
    $f[]="<html xmlns:v>";
    $f[]="<head>";
    $f[]="<meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge\"/>";
    $f[]="<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
    $f[]="<meta HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">";
    $f[]="<meta HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">";
    $f[]="<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, user-scalable=no\">";
    if(function_exists("brand_favicon_tag")){
        $f[]=brand_favicon_tag($HTTP_X_ARTICA_SUBFOLDER);
    }else{
        $f[]="<link rel='icon' href='{$HTTP_X_ARTICA_SUBFOLDER}ressources/templates/default/favicon.ico' type='image/x-icon' />";
    }
    $f[]="<title>$myhostname</title>";
    $f[]=new_css();
    $f[]="<script src=\"{$HTTP_X_ARTICA_SUBFOLDER}fingerprint/query\"></script>";
    $f[]="<script>";
    $f[]="function login(){";
    $f[]="document.form.submit();";
    $f[]="}";
    $f[]="function initial(){";

    $f[]="if(document.getElementById('username')){";
    $f[]="    document.form.username.focus();";
    $f[]="    document.form.username.onkeyup = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){";
    $f[]="            document.form.password.focus();";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="    document.form.username.onkeypress = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){ return false; }";
    $f[]="    };";
    $f[]="    document.form.password.onkeyup = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){";
    $f[]="            login();";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="    document.form.password.onkeypress = function(e){";
    $f[]="        e = e || event;";
    $f[]="        if (e.keyCode == 13) {";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="  }";
    $f[]="}";
    $f[]="</script>";
    $f[]="</head>";

    $VirtualText=null;
    if($HideVirtualizationVersion==0) {
        if ($sock->GET_INFO("VMWARE_HOST") == 1) {
            $VirtualText = "VMWare Edition";
        }
        if (intval($sock->GET_INFO("NUTANIX_HOST")) == 1) {
            $VirtualText="Nutanix Edition";
        }
        if ($sock->GET_INFO("QEMU_HOST") == 1) {
           $VirtualText="Qemu Edition";
        }
    }


$f[]="<body class=\"wrapper\" onload=\"initial();\">";
    $f[]="<iframe name=\"hidden_frame\" id=\"hidden_frame\" width=\"0\" height=\"0\" style='border:0'></iframe>";
    $f[]="<iframe id=\"dmRedirection\" width=\"0\" height=\"0\" frameborder=\"0\" scrolling=\"no\" src=\"\"></iframe>";
    $f[]="<form method=\"post\" name=\"form\" action=\"$page\" target=\"\">";
    $f[]=login_csrf_field_session();
    $rquire[]="userfont";
    $rquire[]="artica-language";
    $rquire[]="StandardDropDown";
    $rquire[]="HTMLTITLE";

    $FORM_ACCEPTABLE=true;

    if(GeoIPCheck()){
        $FORM_ACCEPTABLE=false;
    }

    if($FORM_ACCEPTABLE) {
        foreach ($rquire as $field) {
            if (!isset($_COOKIE[$field])) {
                $_COOKIE[$field] = null;
            }
            $_COOKIE[$field] = $tpl->CLEAN_BAD_XSS($_COOKIE[$field]);
            $f[] = "<input type='hidden' name='$field' value='$_COOKIE[$field]'>";
        }
    }



    $f[]="<div class=\"div_table main_field_gap\">";
    $f[]="<div class=\"div_tr\">";
    $f[]="<div class=\"title_name\">";
    $f[]="<div class=\"div_td img_gap\">";
    $f[]="<div class=\"login_img\"></div>";
    $f[]="</div>";
    if($useCustomLogo==1){
        $clogo = "<div style='width:100%;max-width: 524px;text-align:right;margin-top:-5px;padding-top:5px'><img alt='' style='width:100%' src='data:image/png;base64,$customLogo'></div>";

    }
    if($FORM_ACCEPTABLE) {
        $f[] = "<div class=\"div_td\">$clogo$TitleLogon</div>";
    }
    $f[]="</div>";

    if($VirtualText<>null){
        $VirtualText="&nbsp;<span style='font-size:small'>($VirtualText)</span>";
    }


    clean_xss_deep();



    if($error<>null){
        $TextOfArticaPage="<span style='color:#ee8d8d;font-weight: bolder'>$error</span>";
    }
    if(!is_file(dirname(__FILE__) . '/ressources/class.manager.inc')){
        $TextOfArticaPage=$TextOfArticaPage."<br>
        <p style='color:#ee8d8d;font-weight: bolder'>{no_service_pack4}</p>";
    }




    if($FORM_ACCEPTABLE) {
        $f[]="<div class=\"LoginTitle2\">$myhostname$VirtualText</div>";
        $f[] = "<div id=\"login_filed\">";
        if($customBody!==null){
            // Écran de second facteur (task 9, finding 8): même document que le
            // formulaire mot de passe -- doctype, <head>, feuille de new_css(),
            // et le <form name="form" action="$page"> déjà ouvert plus haut,
            // qui porte déjà les champs cachés de préférence (userfont,
            // artica-language, ...). On y insère le contenu du défi À LA PLACE
            // des champs username/password, jamais en plus: les deux écrans ne
            // coexistent jamais dans la même réponse.
            $f[] = $customBody;
        }else{
            $f[] = "<div class=\"p1 title_gap\">$TextOfArticaPage</div>";
            $f[] = "<div id=\"name_title_ie\" style=\"display:none;margin:20px 0 -10px 78px;\" class=\"p1 title_gap\">{username}</div>";
            $f[] = "<div class=\"title_gap\">";
            $f[] = "<input type=\"text\" id=\"username\" name=\"username\" tabindex=\"1\" class=\"form_input\"autocapitalize=\"off\" autocomplete=\"off\" placeholder=\"{username}\" >";
            $f[] = "</div>";
            $f[] = "<div id=\"password_title_ie\" style=\"display:none;margin:20px 0 -20px 78px;\" class=\"p1 title_gap\">{password}</div>";
            $f[] = "<div class=\"password_gap\">";
            $f[] = "<input type=\"password\" name=\"password\" tabindex=\"2\" class=\"form_input\" placeholder=\"{password}\" autocapitalize=\"off\" autocomplete=\"off\" >";
            $f[] = "</div>";

            $f[] = "<div class=\"button\" onclick=\"login();\">$title_button&nbsp;&raquo;&raquo;</div>";
        }
        $f[] = "</div>";
    }
    $f[]="</div>";
    if($ArticaVer<>null) {
        if($FORM_ACCEPTABLE) {
            $f[] = "<p class='ArticaVer'>$ArticaVer</p>";
        }
    }
    $f[]="</form>";
    $f[]="</body>";
    $f[]="</html>";

    if($return){return $tpl->_ENGINE_parse_body($f);}
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}

function fingerprint_status($visitorId):int{
    // visitorId vient d'un cookie : requete preparee, et une valeur qui n'a pas
    // la forme d'une empreinte (VARCHAR(50) alphanumerique) n'interroge rien.
    $visitorId=trim((string)$visitorId);
    if(!preg_match('#^[A-Za-z0-9_-]{1,50}$#',$visitorId)){return 0;}
    $q = new postgres_sql();
    $res = $q->QUERY_SQL_PARAMS("SELECT status FROM fingerprints WHERE fingerprint=$1",array($visitorId));
    if(!$q->ok || !$res){
        return 0;
    }
    $ligne=@pg_fetch_array($res);
    if(!is_array($ligne)){return 0;}
    return intval($ligne["status"]);
}
function fingerprint():bool{
    $visitorId="";
    if(isset($_COOKIE["visitorId"])){$visitorId=$_COOKIE["visitorId"];}
    $ArticaWebFingerPrint=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"));
    $ArticaWebFingerPrintCaptcha=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrintCaptcha"));
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));

    if($DisablePostGres==1) { return true;}
    if ($ArticaWebFingerPrint == 0) {return true;}


    if ($visitorId == "") {
        if ($ArticaWebFingerPrintCaptcha == 1) {
            header("Location: /captcha");
            die();
        }

        echo "<html>
                <head>
                <META HTTP-EQUIV=\"Refresh\" CONTENT=\"3; URL=/\"> 
                <script src=\"/fingerprint/query\"></script>
                </head>
                <body></body>
                </html>";
        die();
    }

    $Status=fingerprint_status($visitorId);

    if($Status==1){return true;}

    if($Status==2){
        page_no_privs();
        die;
    }
    if($Status==0){
        if($ArticaWebFingerPrintCaptcha==1) {
            header("Location: /captcha");
            exit();
        }
        page_no_privs();
        die;
    }

    return true;
}

function LoginNoAPI($json=array()){
    client_certificate();
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $clogo="";
    $TitleOfArticaPage          = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LoginTitle2"));
    $TitleLogon                 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TitleLogon"));
    $TextOfArticaPage           = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TextOfArticaPage"));
    $useCustomLogo             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCustomLogo"));
    $customLogo=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("customLogo"));
    $HideArticaVersion          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaVersion"));
    $HideVirtualizationVersion  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideVirtualizationVersion"));

    if($TitleOfArticaPage==null){$TitleOfArticaPage="%SERVERNAME%";}
    if($TextOfArticaPage==null){$TextOfArticaPage="{default_login_explain}";}
    if($TitleLogon==null){$TitleLogon="{Connection}";}
    $title_button="{login}";
    $ArticaVer=null;

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $TextOfArticaPage="{default_login_explain}";
        $TitleOfArticaPage="%SERVERNAME%";
        $HideArticaVersion=0;
        $HideVirtualizationVersion=0;
        $TitleLogon="{Connection}";
        $useCustomLogo=0;
    }
    if(strlen($customLogo)<2){
        $useCustomLogo=0;
    }
    if(strpos(" $TitleOfArticaPage","%SERVERNAME%")>0){
        $hostname=GetMyHostname();
        $myhostname=str_replace("%SERVERNAME%",$hostname,$TitleOfArticaPage);
    }else{
        $myhostname=$TitleOfArticaPage;
    }
    $HTTP_X_ARTICA_SUBFOLDER="/";
    fingerprint();

    if($HideArticaVersion==0) {
        $SP=null;
        $CURVER=trim(@file_get_contents("VERSION"));
        $CURPATCH= $GLOBALS["CLASS_SOCKETS"]->SPVersion();
        if($CURPATCH>0){
            $SP="&nbsp;Service Pack $CURPATCH";
        }
        $ArticaVer = "Artica $CURVER{$SP} &copy; " . date("Y");

    }
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }


    $f[]="<!DOCTYPE html>";
    $f[]="<html xmlns=\"http://www.w3.org/1999/xhtml\">";
    $f[]="<html xmlns:v>";
    $f[]="<head>";
    $f[]="<meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge\"/>";
    $f[]="<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
    $f[]="<meta HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">";
    $f[]="<meta HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">";
    $f[]="<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, user-scalable=no\">";
    if(function_exists("brand_favicon_tag")){
        $f[]=brand_favicon_tag($HTTP_X_ARTICA_SUBFOLDER);
    }else{
        $f[]="<link rel='icon' href='{$HTTP_X_ARTICA_SUBFOLDER}ressources/templates/default/favicon.ico' type='image/x-icon' />";
    }
    $f[]="<title>$myhostname</title>";
    $f[]=new_css();
    $f[]="<script src=\"{$HTTP_X_ARTICA_SUBFOLDER}fingerprint/query\"></script>";
    $f[]="<script>";
    $f[]="function login(){";
    $f[]="document.form.submit();";
    $f[]="}";
    $f[]="function initial(){";

    $f[]="if(document.getElementById('username')){";
    $f[]="    document.form.username.focus();";
    $f[]="    document.form.username.onkeyup = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){";
    $f[]="            document.form.password.focus();";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="    document.form.username.onkeypress = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){ return false; }";
    $f[]="    };";
    $f[]="    document.form.password.onkeyup = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){";
    $f[]="            login();";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="    document.form.password.onkeypress = function(e){";
    $f[]="        e = e || event;";
    $f[]="        if (e.keyCode == 13) {";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="  }";
    $f[]="}";
    $f[]="</script>";
    $f[]="</head>";

    $VirtualText=null;
    if($HideVirtualizationVersion==0) {
        if ($sock->GET_INFO("VMWARE_HOST") == 1) {
            $VirtualText = "VMWare Edition";
        }
        if (intval($sock->GET_INFO("NUTANIX_HOST")) == 1) {
            $VirtualText="Nutanix Edition";
        }
        if ($sock->GET_INFO("QEMU_HOST") == 1) {
            $VirtualText="Qemu Edition";
        }
    }


    $f[]="<body class=\"wrapper\" onload=\"initial();\">";
    $f[]="<iframe name=\"hidden_frame\" id=\"hidden_frame\" width=\"0\" height=\"0\" style='border:0'></iframe>";
    $f[]="<iframe id=\"dmRedirection\" width=\"0\" height=\"0\" frameborder=\"0\" scrolling=\"no\" src=\"\"></iframe>";
    $f[]="<form method=\"post\" name=\"form\" action=\"$page\" target=\"\">";
    $f[]=login_csrf_field_session();
    $rquire[]="userfont";
    $rquire[]="artica-language";
    $rquire[]="StandardDropDown";
    $rquire[]="HTMLTITLE";

    $FORM_ACCEPTABLE=true;

    $f[]="<div class=\"div_table main_field_gap\">";
    $f[]="<div class=\"div_tr\">";
    $f[]="<div class=\"title_name\">";
    $f[]="<div class=\"div_td img_gap\">";
    $f[]="<div class=\"login_img\"></div>";
    $f[]="</div>";
    if($useCustomLogo==1){
        $clogo = "<div style='width:100%;max-width: 524px;text-align:right;margin-top:-5px;padding-top:5px'><img alt='' style='width:100%' src='data:image/png;base64,$customLogo'></div>";

    }
    if($FORM_ACCEPTABLE) {
        $f[] = "<div class=\"div_td\">$clogo$TitleLogon</div>";
    }
    $f[]="</div>";

    if($VirtualText<>null){
        $VirtualText="&nbsp;<span style='font-size:small'>($VirtualText)</span>";
    }


    clean_xss_deep();
    $error="";



        if(isset($json["Error"])){
            $error=$json["Error"];
        }
        // Pentest 2026-11-04 : message d'erreur de l'API (donnee externe) affiche sans encodage (XSS stockee).
        $TextOfArticaPage="<p style='color:#ee8d8d;font-weight: bolder;background-color: white !important;padding: 20px;border-radius: 5px;font-size: 14px;border: 2px solid;border-color: black;'>{API_NOT_AVAILABLE}<hr>".req_html($error)."</p>";

    // Sans l'extension curl dans PHP-FPM (juste apres l'installation, le temps
    // que FPM soit relance avec php-curl), la console ne peut pas joindre l'API :
    // ce n'est pas une panne, la page revient seule en une trentaine de
    // secondes. Message d'attente neutre au lieu de « redemarrez l'API ».
    if(!function_exists("curl_init") || strpos((string)$error,"curl_init")!==false){
        $TextOfArticaPage="<p style='font-weight:bolder;background-color:rgba(255,255,255,0.12);padding:20px;border-radius:5px;font-size:14px;border:1px solid rgba(255,255,255,0.35)'>{php_curl_loading}</p>";
    }

    if(!is_file(dirname(__FILE__) . '/ressources/class.manager.inc')){
        $TextOfArticaPage=$TextOfArticaPage."<br>
        <p style='color:#ee8d8d;font-weight: bolder'>{no_service_pack4}</p>";
    }








    if($FORM_ACCEPTABLE) {
        $f[]="<div class=\"LoginTitle2\">$myhostname$VirtualText</div>";
        $f[] = "<div id=\"login_filed\">";
        $f[] = "<div class=\"p1 title_gap\">$TextOfArticaPage</div>";
        $f[] = "</div>";
    }
    $f[]="</div>";
    if($ArticaVer<>null) {
        if($FORM_ACCEPTABLE) {
            $f[] = "<p class='ArticaVer'>$ArticaVer</p>";
        }
    }
    $f[]=LoginNoAPIWaitBar();
    $f[]="</form>";
    $f[]="</body>";
    $f[]="</html>";

    // Le script ne passe pas par _ENGINE_parse_body() (accolades du JS).
    $html=$tpl->_ENGINE_parse_body($f);
    echo str_replace("</body>",LoginNoAPIWaitJS()."</body>",$html);
    return true;
}

// ApiProbe repond {"ok":true} quand l'API articarest repond a nouveau : meme
// test que l'entree de la page (curl charge + /webconsole/wizard-status).
// Interrogee par la barre d'attente de LoginNoAPI().
function ApiProbe():bool{
    header("Content-Type: application/json");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    $ok=false;
    if(function_exists("curl_init")){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/wizard-status"),true);
        if(is_array($json) && isset($json["Status"]) && isset($json["wizard_executed"])){
            $ok=true;
        }
    }
    echo json_encode(array("ok"=>$ok));
    return true;
}

// Barre de progression affichee sous le message « API indisponible ».
function LoginNoAPIWaitBar():string{
    $accent="#18a689";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $acc=brand_ui_accents(brand_palette());
        if(isset($acc["accent"]) && $acc["accent"]<>""){$accent=$acc["accent"];}
    }
    $f[]="<div id='api-wait' style='margin-top:18px;color:#ffffff'>";
    $f[]="<div style='font-size:13px;margin-bottom:6px;color:#ffffff'>{api_wait_progress}</div>";
    $f[]="<div style='height:10px;border-radius:5px;background:rgba(255,255,255,0.2);overflow:hidden'>";
    $f[]="<div id='api-wait-bar' style='height:10px;width:0%;background:$accent;transition:width 0.9s linear'></div>";
    $f[]="</div>";
    $f[]="<div id='api-wait-pct' style='font-size:12px;margin-top:4px;text-align:right;color:#ffffff'>0%</div>";
    $f[]="</div>";
    return @implode("",$f);
}

// Avance de 10 % toutes les 3 s ; a chaque palier (10 %, 20 %... 100 %)
// interroge ?api-probe=yes et recharge la page des que l'API repond. A 100 %
// la barre repart de zero : on attend tant que l'API ne revient pas.
function LoginNoAPIWaitJS():string{
    $page=CurrentPageName();
    $f[]="<script>";
    $f[]="(function(){";
    $f[]="  var pct=0, busy=false;";
    $f[]="  var bar=document.getElementById('api-wait-bar');";
    $f[]="  var txt=document.getElementById('api-wait-pct');";
    $f[]="  if(!bar){ return; }";
    $f[]="  function show(v){ bar.style.width=v+'%'; txt.innerHTML=v+'%'; }";
    $f[]="  function probe(){";
    $f[]="    busy=true;";
    $f[]="    var x=new XMLHttpRequest();";
    $f[]="    x.open('GET','$page?api-probe=yes&_='+Date.now(),true);";
    $f[]="    x.timeout=8000;";
    $f[]="    x.onload=function(){";
    $f[]="      busy=false;";
    $f[]="      var r=null;";
    $f[]="      try { r=JSON.parse(x.responseText); } catch(e) { r=null; }";
    $f[]="      if(r && r.ok===true){ show(100); document.location.href='$page'; }";
    $f[]="    };";
    $f[]="    x.onerror=function(){ busy=false; };";
    $f[]="    x.ontimeout=function(){ busy=false; };";
    $f[]="    x.send();";
    $f[]="  }";
    $f[]="  setInterval(function(){";
    $f[]="    if(busy){ return; }";
    $f[]="    if(pct>=100){ pct=0; bar.style.transition='none'; show(0); bar.offsetWidth; bar.style.transition='width 0.9s linear'; }";
    $f[]="    pct+=10; show(pct); probe();";
    $f[]="  },3000);";
    $f[]="})();";
    $f[]="</script>";
    return @implode("\n",$f);
}


function login($return=false,$error=null){
    VERBOSE(__FUNCTION__,__LINE__);
    client_certificate();
    clean_xss_deep();
    if($GLOBALS["VERBOSE"]){echo "<H1>".__FILE__."</H1><br>\n";$uriverbadd="?verbose=yes";}
    $page=CurrentPageName();
    $error_text=null;
    $sock=new sockets();
    $tpl=new template_admin();
    $myhostname=null;
    $ArticaVer=null;

    $HTTP_X_ARTICA_SUBFOLDER=null;
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }

    $bgstyle="background-image:url({$HTTP_X_ARTICA_SUBFOLDER}img/Articafond1.png);background-repeat:no-repeat;";
    $columns="col-md-6";
    $TitleOfArticaPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TitleOfArticaPage"));
    $TextOfArticaPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TextOfArticaPage"));
    $HideArticaLogo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaLogo"));
    $useCustomLogo             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCustomLogo"));
    $customLogo=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("customLogo"));
    if($TitleOfArticaPage==null){$TitleOfArticaPage="%SERVERNAME%";}
    if($TextOfArticaPage==null){$TextOfArticaPage="{default_login_explain}";}
    $HideArticaVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaVersion"));
    $HideVirtualizationVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideVirtualizationVersion"));
    if(strlen($customLogo)<2){
        $useCustomLogo=0;
    }

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        if($HideArticaLogo==1){$bgstyle=null;}
        $TextOfArticaPage="{default_login_explain}";
        $TitleOfArticaPage="%SERVERNAME%";
        $HideArticaVersion=0;
        $HideVirtualizationVersion=0;
        $HideArticaLogo=0;
        $useCustomLogo=0;

    }



    if(strpos(" $TitleOfArticaPage","%SERVERNAME%")>0){
        $hostname=GetMyHostname();
        $myhostname=str_replace("%SERVERNAME%",$hostname,$TitleOfArticaPage);
    }else{
        $myhostname=$TitleOfArticaPage;
    }

    if($HideArticaLogo==1) {
        $bgstyle=null;
        $columns="col-md-11";
    }
    if($useCustomLogo==1){
        $columns="col-md-6";
    }

    if($HideArticaVersion==0) {
        $SP=null;
        $CURVER=trim(@file_get_contents("VERSION"));
        $CURPATCH= $GLOBALS["CLASS_SOCKETS"]->SPVersion();
        if($CURPATCH>0){
            $SP="&nbsp;Service Pack $CURPATCH";
        }
        $ArticaVer = "Artica $CURVER{$SP} &copy; " . date("Y");

    }

    if($GLOBALS["VERBOSE"]){echo "ERROR --- $error<br>\n";}


    $picture=null;
    if($HideVirtualizationVersion==0) {
        if ($sock->GET_INFO("VMWARE_HOST") == 1) {
            $picture = "<div style='width:100%;text-align:right;border-top:1px solid #717075;margin-top:-5px;padding-top:5px'><img src='img/vmware-edition.png'></div>";
        }
        if (intval($sock->GET_INFO("NUTANIX_HOST")) == 1) {
            $picture = "<div style='width:100%;text-align:right;border-top:1px solid #717075;margin-top:-5px;padding-top:5px'><img src='img/nutanix-edition.png'></div>";
        }
        if ($sock->GET_INFO("QEMU_HOST") == 1) {
            $picture = "<div style='width:100%;text-align:right;border-top:1px solid #717075;margin-top:-5px;padding-top:5px'><img src='img/qemu-edition.png'></div>";
        }
    }

    if($useCustomLogo==1){
        $clogo = "<div style='width:100%;max-width: 524px;text-align:right;margin-top:-5px;padding-top:5px'><img style='width:100%' src='data:image/png;base64,$customLogo'></div>";

    }

    if($picture<>null){$picture="<p>$picture</p>";}

    if($error<>null) {
        $error_text = "<div class='alert alert-danger'>$error</div>";
    }

    $html="<!DOCTYPE html>
<html>

<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">

    <title>$myhostname</title>

    <link href=\"/angular/bootstrap.min.css\" rel=\"stylesheet\">
    <link href=\"/angular/font-awesome/css/all.min.css\" rel=\"stylesheet\">

    <link href=\"/angular/animate.css\" rel=\"stylesheet\">
    <!-- fonts.css porte les @font-face de lato, bariol et ailerons : les
         seules polices du menu de preference que style.css ne declare pas.
         Sans elle, un userfont=lato n-a aucune source ici et le navigateur
         retombe en silence sur Arial. Chargee AVANT style.css a dessein :
         a famille, poids et style egaux la DERNIERE declaration @font-face
         gagne, et les Roboto / Open Sans de style.css (woff2 sous-decoupes
         par unicode-range) valent mieux que les woff pleins de fonts.css. -->
    <link href=\"/angular/fonts.css\" rel=\"stylesheet\">
    <link href=\"/angular/style.css\" rel=\"stylesheet\">
    <link href=\"/angular.css.php\" rel=\"stylesheet\">
    ".(function_exists("console_zoom_script")?console_zoom_script():"")."
</head>

<body class=\"gray-bg\" style='$bgstyle'>
<!-- ".basename(__FILE__) ." " .__LINE__. " -->
    <div class=\"loginColumns animated fadeInDown\">
        <div class=\"row\">

            <div class=\"$columns\">
            $clogo
                <h2 class=\"font-bold\">$myhostname</h2>
                $picture
				<p>$TextOfArticaPage</p>


            </div>
            <div class=\"$columns\">
                <div class=\"ibox-content\">";

    $rquire[]="userfont";
    $rquire[]="artica-language";
    $rquire[]="StandardDropDown";
    $rquire[]="HTMLTITLE";

    foreach ($rquire as $field){
        if(!isset($_COOKIE[$field])){$_COOKIE[$field]=null;}
        // Pentest 2026-11-04 : valeur de cookie (donnee externe) dans un attribut sans encodage (XSS stockee).
        $fields[]="<input type='hidden' name='$field' value='".req_html($_COOKIE[$field])."'>";
    }

    $added_fields=@implode("\n",$fields);
    $valueuser=null;
    $valuepass=null;
    // Le mot de passe n'est JAMAIS renvoye dans la page : il se retrouvait en clair
    // dans le HTML (cache, historique, outils de developpement) et, sans
    // echappement, un POST forge y injectait du HTML. L'identifiant est echappe.
    if(isset($_POST["username"]) && is_string($_POST["username"])){
        $valueuser="value=\"".htmlspecialchars($_POST["username"],ENT_QUOTES)."\"";
    }


    $form_login="<form class=\"m-t\" role=\"form\" action=\"$page\" method='post'>".login_csrf_field_session()."
                        <div class=\"form-group\">
                            $added_fields
                            <input type=\"username\" class=\"form-control\" placeholder=\"{username}\" $valueuser name='username' required=\"\">
                        </div>
                        <div class=\"form-group\">
                            <input type=\"password\" class=\"form-control\" placeholder=\"{password}\" $valuepass name='password' required=\"\">
                        </div>
                        <button type=\"submit\" class=\"btn btn-primary block full-width m-b\">{login}</button>
                </form>";


    if(GeoIPCheck()){
        $form_login=null;
    }

    $html=$html.$form_login;
    $html=$html." $error_text
                    <p class=\"m-t\" style='text-align:right'>
                        <small>$ArticaVer</small>
                    </p>
                </div>
            </div>
        </div>
</body>

</html>";

    if($return){return $tpl->_ENGINE_parse_body($html);}
    echo $tpl->_ENGINE_parse_body($html);



}
function ifRights($users):bool{
    if(!isset($_SESSION["MANAGE_CATEGORIES"])){$_SESSION["MANAGE_CATEGORIES"]=array();}
    if(count($_SESSION["MANAGE_CATEGORIES"])>0){return true;}
    if($users->AsWebMaster){return true;}
    if($users->AsWebSecurity){return true;}
    if($users->AsFirewallManager){return true;}
    if($users->AsDatabaseAdministrator){return true;}
    if($users->AsVPNManager){return true;}
    if($users->AsProxyMonitor){return true;}
    if($users->AsMessagingOrg ){return true;}
    if($users->AsPostfixAdministrator ){return true;}
    if($users->AsDnsAdministrator ){return true;}
    if($users->AsDnsMonitor ){return true;}
    if($users->AsWebStatisticsAdministrator ){return true;}
    if($users->AsCertifsManager){return true;}
    if($users->AsDockerAny){return true;}
    if($users->AsDockerReverse){return true;}
    if($users->AsSquidPersonalCategories){return true;}
    if($users->AsHamrpAdmin){return true;}
    if($users->AsWebSecurity){return true;}
    if($users->AsWebMonitor){return true;}
    if($users->AsDansGuardianAdministrator){return true;}
    // Administrateur Artica Meta d'un ou plusieurs tenants (clients) : un compte qui n'a que ce
    // droit ne voit que les pages Meta, mais il doit pouvoir ouvrir une session.
    if(!empty($users->AsMetaAdmin)){return true;}

    if(count($users->NGINX_SERVICES)>0){
        $EnableNginx=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx");
        if($EnableNginx==1){
            return true;
        }
    }
    if(count($users->SIMPLE_ACLS)>0){
        return true;
    }
    // Un perimetre de tenants SIEM ouvre une session, au meme titre qu'un perimetre de services
    // nginx ou d'ACL: c'est le seul droit d'un operateur de tenant, et sans cette ligne il est
    // authentifie puis immediatement deconnecte pour « privileges insuffisants » -- un refus qui
    // ne nomme jamais la cause, sur un compte a qui l'on vient pourtant d'accorder un tenant.
    // Conditionne au module actif: une attribution restee en base ne doit pas ouvrir de session
    // sur une appliance ou le SIEM a ete desactive depuis.
    if(count($users->SIEM_TENANTS)>0){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaSiem"))==1){
            return true;
        }
    }

    return false;
}
function debugcredentials(){

    $DebugInterfaceCredentials=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugInterfaceCredentials"));
    if($DebugInterfaceCredentials==0){exit;}
    $GLOBALS["VERBOSE"]=true;
    $_POST["username"]=$_GET["username"];
    $_POST["password"]=$_GET["password"];
    CheckLogin();
}
function LoginPage($error=""):bool{
    $ArticaWebOldLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebOldLogin"));
    if($ArticaWebOldLogin==1){
        echo login(true,$error);
        die();
    }
    echo new_login(true,$error);
    die();

}

/**
 * Décide de la suite après un mot de passe validé.
 * Renvoie true si la page a déjà répondu (écran affiché), false pour laisser
 * CheckLogin() poursuivre sa redirection normale.
 */
function twofactor_gate($login,$users):bool{
    $POLICY=twofactor_policy_status();
    if($POLICY=="unknown"){
        // Échec FERMÉ: on ne sait pas si le second facteur doit s'appliquer
        // (daemon injoignable ou réglage illisible) alors qu'il a déjà été vu
        // actif -- on n'accorde donc PAS de session mot-de-passe seul. C'est
        // délibérément une nouvelle classe de refus, distincte de
        // "out-of-scope": ici la politique EST active, seule sa lecture a
        // échoué.
        logon_events("2FA_POLICY_UNKNOWN");
        $login->logoff();
        echo new_login(true,"{twofactor_policy_unreadable}");
        return true;
    }
    if($POLICY=="disabled" || !twofactor_in_scope($users)){
        $login->twofactor_release("out-of-scope");
        return false;
    }
    $key=isset($_SESSION["2FA_IDENTITY"])?$_SESSION["2FA_IDENTITY"]:null;
    if($key==null || trim($key)==""){
        // Identité non enrôlable (§4.3 de la conception): on refuse. Une porte
        // qui s'ouvre quand elle ne sait pas est une porte ouverte.
        // Événement dédié (pas "2FA_FAILED", qui désigne un code refusé) --
        // sans quoi cette cause de refus n'est visible que sur l'écran, pas
        // dans le journal que consulte un auditeur.
        logon_events("2FA_NO_IDENTITY");
        $login->logoff();
        echo new_login(true,"{twofactor_no_stable_identity}");
        return true;
    }
    // Fail-closed EXPLICITE (§10: « daemon injoignable -> refus »), et non par
    // effet de bord.
    //
    // L'ancienne rédaction laissait $enrolled à false quand la liste ne
    // répondait pas, comptant sur l'échec de la vérification qui suit pour
    // barrer l'entrée. Ce raisonnement ne tient plus: l'écran d'enrôlement
    // appelle /localadmins/2fa/enroll/start, qui fait un INSERT OR REPLACE.
    // Pour le super-administrateur -- le seul à qui le daemon autorise
    // désormais le redémarrage d'un enrôlement déjà confirmé -- un simple
    // hoquet de la socket aurait donc JETÉ le secret du Manager et l'aurait
    // remplacé en silence, sans que personne ne demande rien. Le compte de
    // recours est justement celui qu'il ne faut pas faire tourner par accident.
    //
    // Ceci n'ajoute aucune classe de verrouillage nouvelle: on n'arrive ici que
    // si la politique est ACTIVE et l'identité DANS le périmètre (les autres
    // sont déjà relâchées plus haut), et GET_INFO() transite de toute façon par
    // la même socket -- une console privée de son daemon est déjà hors service.
    $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/localadmins/2fa")),true);
    $listOk=(is_array($resp) && !empty($resp["success"]) && is_array(isset($resp["data"])?$resp["data"]:null));
    if(!$listOk){
        logon_events("2FA_DAEMON_UNREACHABLE");
        $login->logoff();
        echo new_login(true,"{console_2fa_daemon_unreachable}");
        return true;
    }
    $enrolled=false;
    foreach($resp["data"] as $row){
        if(!is_array($row)){continue;}
        if((isset($row["IdentityKey"])?$row["IdentityKey"]:"")===$key && !empty($row["Enrolled"])){$enrolled=true;break;}
    }
    // Mémorisé pour que twofactor_submit() (une requête ultérieure, sans
    // accès à $enrolled ni au $_POST du mot de passe) sache si le code qui va
    // arriver clôt un premier enrôlement ou vérifie un compte déjà enrôlé --
    // les deux méritent un événement d'audit distinct.
    $_SESSION["2FA_SCREEN_MODE"]=$enrolled?"code":"enrol";

    // Les cookies de préférence (police, langue, ...) et le cookie
    // shellinabox sont normalement posés à la toute fin de CheckLogin(), à
    // partir du $_POST du mot de passe. Ce POST n'existera plus quand le
    // code TOTP sera soumis (requête séparée) -- il faut donc les poser ICI,
    // pendant qu'ils sont encore disponibles, plutôt que de les perdre pour
    // tout compte dans le périmètre du second facteur.
    $tpl=new template_admin();
    if($users->AsWebStatisticsAdministrator){
        setcookie("AsWebStatisticsCooKie", "1", time()+172800,"","",true,true);
    }
    $rquire=array("userfont","artica-language","StandardDropDown","HTMLTITLE");
    foreach ($rquire as $field){
        if(isset($_POST[$field])){
            $datasrc=$_POST[$field];
            $data=$tpl->CLEAN_BAD_XSS($datasrc);
            if($data<>$datasrc){
                admin_tracks("XSS Detected on value COOKIE $field [$datasrc]");
            }
            setcookie($field, $data, time()+31536000,"","",true,true);
        }
    }
    setcookie("shellinaboxCooKie", "1", time()+172800,"","",true,true);

    echo twofactor_screen($enrolled?"code":"enrol","");
    return true;
}

/**
 * Fabrique le QR d'enrôlement EN MEMOIRE et le renvoie en data-URI PNG.
 *
 * Un QR d'enrôlement EST le secret TOTP en forme visuelle: il ne doit jamais
 * toucher le disque du serveur (le répertoire img/ est servi par nginx), et
 * l'otpauth:// qu'il encode ne doit jamais apparaître en paramètre d'URL
 * (journaux nginx, en-tête Referer, historique navigateur). Aucune bibliothèque
 * JS de rendu de QR n'est présente dans ce dépôt (angular/js/plugins ne
 * contient rien en "qr") -- le rendu est donc fait ici, côté serveur, avec
 * l'encodeur PHP déjà présent sous ressources/jpgraph/QR/, capturé depuis le
 * flux de sortie par ob_start()/ob_get_clean(), jamais depuis un fichier.
 */
function twofactor_render_qr_data_uri($uri):string{
    if($uri==""){return "";}
    $qrdir=dirname(__FILE__)."/ressources/jpgraph/QR";
    // Les require_once() de la bibliothèque sont relatifs (pas de dirname(__FILE__)
    // dans qrencoder.inc.php) -- on se place temporairement dans son répertoire.
    $oldcwd=getcwd();
    // Niveau de tampon AVANT ob_start(): QRCodeBackend_IMAGE::Stroke() peut
    // lever après avoir ouvert le tampon (format non supporté, échec
    // d'allocation de couleur, imagepng() en échec) -- sans ce repère, le
    // catch ci-dessous rendrait "" mais laisserait le tampon ouvert, et PHP
    // le viderait tout seul en fin de requête: des octets PNG partiels
    // atterriraient dans la réponse HTML, à l'exact endroit où ce repli
    // gracieux était censé les empêcher.
    $level=ob_get_level();
    try{
        chdir($qrdir);
        require_once($qrdir."/qrencoder.inc.php");
        chdir($oldcwd);
        $encoder=new QREncoder(-1, QRCapacity::ErrM);
        $backend=QRCodeBackendFactory::Create($encoder, BACKEND_IMAGE);
        $backend->SetImgFormat('png');
        ob_start();
        $backend->Stroke($uri,'');
        $png=ob_get_clean();
        // QRCodeBackend_IMAGE::Stroke() emits its own "Content-type: image/$fmt"
        // header on a non-CLI SAPI before writing to the output buffer (it was
        // written to answer a request directly, not to be embedded). This page's
        // real response is HTML: put the header back before anything else runs.
        if(!headers_sent()){header("Content-Type: text/html; charset=utf-8");}
        if($png===false || $png==""){return "";}
        return "data:image/png;base64,".base64_encode($png);
    }catch(\Throwable $ex){
        if(getcwd()<>$oldcwd){chdir($oldcwd);}
        // Boucle, pas un seul niveau: Stroke() peut lever alors que PLUSIEURS
        // tampons sont ouverts (un appelant futur qui ferait déjà du
        // ob_start() avant cette fonction, par exemple) -- ne fermer qu'un
        // niveau laisserait les autres fuiter au shutdown tout pareil.
        while(ob_get_level()>$level){ob_end_clean();}
        if(function_exists("writelogs")){writelogs("twofactor_render_qr_data_uri: ".$ex->getMessage(),__FUNCTION__,__FILE__,__LINE__);}
        return "";
    }
}

/**
 * Écran de défi/enrôlement 2FA -- rendu à travers le MÊME document que le
 * formulaire mot de passe (finding 8): new_login() fournit le doctype, le
 * <head>, la feuille de new_css() et le <form name="form" action=$page> déjà
 * ouvert (avec ses champs cachés de préférence). Sans cela, cet écran était
 * un fragment nu (un <div>+<script> sans doctype/head/feuille), affiché en
 * quirks mode à CHAQUE connexion d'un compte dans le périmètre 2FA -- et son
 * bouton "valider" ne répondait jamais: _ENGINE_parse_body() réécrit
 * "javascript:blur()" en "javascript:Blurz()" (class.templates.inc:641),
 * une fonction de default.js, qu'aucun écran de connexion (ni celui-ci, ni
 * new_login() lui-même) ne charge. Le formulaire déjà présent dans le
 * document et un simple document.form.submit() évitent le problème plutôt
 * que de le contourner.
 */
function twofactor_screen($mode,$error):string{
    $tpl=new template_admin();
    $qr="";
    if($mode=="enrol"){
        // Réponse jamais mise en cache: elle porte un secret d'enrôlement.
        if(!headers_sent()){header("Cache-Control: no-store");}
        $payload=array(
            "identity_key"=>$_SESSION["2FA_IDENTITY"],
            "backend"=>twofactor_backend_of($_SESSION["2FA_IDENTITY"]),
            "display_name"=>twofactor_display_name(),
            "issuer"=>"Artica"
        );
        $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/localadmins/2fa/enroll/start",$payload)),true);
        $uri="";
        if(is_array($resp) && !empty($resp["success"]) && is_array(isset($resp["data"])?$resp["data"]:null)){
            $uri=strval(isset($resp["data"]["uri"])?$resp["data"]["uri"]:"");
        }
        $dataUri=twofactor_render_qr_data_uri($uri);
        if($dataUri<>""){
            $qr="<div id='twofactor-qr' style='text-align:center;margin:14px 0'>"
               ."<img src='".$dataUri."' width='200' height='200' alt='QR'>"
               ."</div>";
        }else{
            $qr=$tpl->div_error("{twofactor_qr_render_failed}");
        }
        // Clé en clair sous le QR: sans elle, l'enrôlement est IMPOSSIBLE pour
        // qui n'a pas d'appareil photo braqué sur cet écran -- gestionnaire de
        // mots de passe sur la même machine, client TOTP de bureau, console
        // distante. Le QR n'est rien d'autre que cette chaîne rendue en image:
        // l'afficher n'ouvre aucune exposition nouvelle (même réponse HTTP,
        // même Cache-Control: no-store), là où son absence force soit un
        // second appareil, soit l'abandon.
        $secret="";
        $qs=parse_url($uri,PHP_URL_QUERY);
        if(is_string($qs) && $qs<>""){
            $parts=array();
            parse_str($qs,$parts);
            if(isset($parts["secret"])){$secret=strval($parts["secret"]);}
        }
        if($secret<>""){
            // Pas de balise <code>: elle entre en conflit avec le thème
            // (CLAUDE.md, « Never use <code> tags »).
            // Couleur EXPLICITE: l'ecran de connexion est sombre et cette
            // ligne heritait d'un gris trop peu contraste pour une chaine
            // qu'on doit recopier caractere par caractere.
            $qr.="<div style='text-align:center;margin:-6px 0 12px 0;font-size:12px;color:#dfe6e9'>"
                ."{twofactor_manual_key}<br>"
                ."<span style=\"font-family:monospace;letter-spacing:2px;font-size:14px;color:#ffffff\">"
                .htmlspecialchars($secret)."</span></div>";
        }
    }
    $err="";
    if($error<>""){$err=$tpl->div_error($error);}
    // L'écran d'enrôlement et l'écran de vérification ne disent PAS la même
    // chose. « Saisissez le code de votre application » devant un QR code que
    // rien n'invite à scanner laisse le premier enrôlement sans mode d'emploi:
    // l'administrateur voit une image et un champ, et doit deviner le lien.
    $explain=($mode=="enrol")?"{twofactor_enrol_explain}":"{twofactor_explain}";
    $customBody="<div class=\"p1 title_gap\">$explain</div>
        $err
        $qr
        <div class=\"title_gap\">
            <input type=\"text\" name=\"twofactor-code\" id=\"twofactor-code\" tabindex=\"1\"
                   inputmode=\"numeric\" autocomplete=\"one-time-code\" maxlength=\"6\"
                   class=\"form_input\" autocapitalize=\"off\"
                   style=\"font-size:22px;letter-spacing:6px;text-align:center\"
                   placeholder=\"{twofactor_code_placeholder}\">
        </div>
        <div class=\"button\" onclick=\"document.form.submit();\">{validate}&nbsp;&raquo;&raquo;</div>
        <script>
        (function(){
            var el=document.getElementById('twofactor-code');
            if(el){
                el.focus();
                el.addEventListener('keydown',function(e){
                    if(e.key==='Enter'){ document.form.submit(); }
                });
            }
        })();
        </script>";
    return new_login(true,null,$customBody);
}

function twofactor_display_name():string{
    // Le test porte sur la CLÉ D'IDENTITÉ, pas sur $_SESSION["uid"]:
    // SetManager() range '-100' dans uid pour DEUX comptes distincts -- le
    // Manager et l'administrateur de l'assistant (clé "wizadmin:<nom>") --
    // si bien qu'un test sur uid affichait « Manager » en face du second dans
    // la table des identités enrôlées, exactement la confusion que la
    // séparation des clés vient de lever.
    $key=strval(isset($_SESSION["2FA_IDENTITY"])?$_SESSION["2FA_IDENTITY"]:"");
    if($key=="manager:-100"){return "Manager";}
    if(isset($_POST["username"]) && is_string($_POST["username"])){return $_POST["username"];}
    return strval(isset($_SESSION["uid"])?$_SESSION["uid"]:"");
}

function twofactor_submit():bool{
    $tpl=new template_admin();
    if(empty($_SESSION["2FA_PENDING"])){LoginPage();return false;}
    if(intval(isset($_SESSION["2FA_DEADLINE"])?$_SESSION["2FA_DEADLINE"]:0) < time()){
        $login=new artica_logon();
        $login->logoff();
        LoginPage("{twofactor_expired}");
        return false;
    }
    $code=preg_replace("#[^0-9]#","",strval(isset($_POST["twofactor-code"])?$_POST["twofactor-code"]:""));
    $payload=array("identity_key"=>$_SESSION["2FA_IDENTITY"],"code"=>$code);
    // Le premier code d'un enrôlement va sur /enroll/confirm, tous les autres
    // sur /verify. Le CONTRAT DE RÉPONSE est identique (les deux passent par
    // twoFactorCheckCode/TwoFactorVerify et rendent {valid, reason}) -- ce qui
    // change est l'ÉVÉNEMENT D'AUDIT du daemon: 2fa.enrol.confirm au lieu de
    // 2fa.verify.
    //
    // Sans cet aiguillage, /enroll/confirm n'était appelé par personne et le
    // kind 2fa.enrol.confirm ne se produisait jamais: dans la piste du daemon,
    // un PREMIER enrôlement -- l'événement qu'un auditeur cherche -- était
    // indiscernable d'une connexion de routine. Le mode a été mémorisé par
    // twofactor_gate(), seule requête à avoir su lequel des deux écrans a été
    // montré.
    $endpoint="/localadmins/2fa/verify";
    if(isset($_SESSION["2FA_SCREEN_MODE"]) && $_SESSION["2FA_SCREEN_MODE"]=="enrol"){
        $endpoint="/localadmins/2fa/enroll/confirm";
    }
    $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($endpoint,$payload)),true);
    $valid=false;$reason="";
    if(is_array($resp) && !empty($resp["success"]) && is_array(isset($resp["data"])?$resp["data"]:null)){
        $valid=!empty($resp["data"]["valid"]);
        $reason=strval(isset($resp["data"]["reason"])?$resp["data"]["reason"]:"");
    }
    if(!$valid){
        logon_events($reason=="locked"?"2FA_LOCKED":"2FA_FAILED");
        $msg=($reason=="locked")?"{twofactor_locked}":"{twofactor_bad_code}";
        $rescreen=($reason=="not-enrolled")?"enrol":"code";
        // Le daemon peut renvoyer "not-enrolled" alors que twofactor_gate()
        // avait mémorisé "code" (compte vu enrôlé à l'affichage précédent,
        // puis désenrôlé entre-temps -- ou l'inverse): le mode mémorisé DOIT
        // suivre le mode réellement réaffiché, sinon un premier enrôlement
        // qui se termine après un tel aller-retour perd son événement
        // 2FA_ENROLLED (finding 5) sans que rien ne le signale.
        $_SESSION["2FA_SCREEN_MODE"]=$rescreen;
        echo twofactor_screen($rescreen,$msg);
        return false;
    }
    // Un premier enrôlement mérite un événement distinct d'une vérification
    // ordinaire -- twofactor_gate() a mémorisé lequel des deux écrans a été
    // montré, seule façon pour cette requête (séparée, sans le $_POST du mot
    // de passe) de le savoir.
    $wasEnrolment=(isset($_SESSION["2FA_SCREEN_MODE"]) && $_SESSION["2FA_SCREEN_MODE"]=="enrol");
    unset($_SESSION["2FA_SCREEN_MODE"]);

    // Même raison, et même piège: twofactor_display_name() lit 2FA_IDENTITY,
    // que twofactor_release() efface quelques lignes plus bas. Le calculer
    // APRÈS la levée fait retomber le nom sur $_SESSION["uid"], soit "-100"
    // pour le Manager et pour le compte assistant -- la piste lisible cesse
    // alors de nommer la même personne que le champ acteur (§11 de la
    // conception), alors que les deux doivent se recouper.
    $uname=twofactor_display_name();

    $login=new artica_logon();
    // Élévation de privilège: l'identifiant ayant circulé pendant l'écran de
    // défi ne doit pas être celui qui ouvre la console.
    safe_session_regenerate_id();
    $login->twofactor_release("verified");

    // Un login réussi via 2FA doit produire EXACTEMENT la même piste d'audit
    // qu'un login réussi sans 2FA (logon_events("SUCCESS") +
    // admin_tracks() + squid_admin_mysql(2,...), voir la fin de
    // CheckLogin()) -- sinon /var/log/artica-webauth.log, "la seule trace
    // des connexions console qui ne dépende d'aucune licence", ne contient
    // plus jamais SUCCESS pour un compte 2FA, et toute règle SIEM qui compte
    // SUCCESS manque ces comptes en silence. L'événement d'enrôlement est en
    // plus, jamais à la place.
    logon_events("SUCCESS");
    if($wasEnrolment){logon_events("2FA_ENROLLED");}
    $ip=GetRemoteIP();
    admin_tracks("Success to logon ($uname) on the Artica Web console from $ip");
    $tpl->squid_admin_mysql(2,"Success to logon ($uname) on the Artica Web console from $ip","",__FILE__,__LINE__);

    echo "<html><head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=/index\"></head>
    <body><script>document.location.href='/index';</script></body></html>";
    return true;
}

function CheckLogin():bool{
$visitorId="";
    if(GeoIPCheck()){
        echo new_login(true,"");
        return true;
    }

    // Robustesse/sécurité : username et password DOIVENT être des chaînes. Un POST
    // forgé (`username[]=x`) passait sinon un tableau à preg_match()/CLEAN_BAD_XSS()
    // → TypeError, HTTP 500. On refuse proprement, exactement comme un mauvais
    // identifiant — jamais de crash, jamais de session.
    if(!isset($_POST["username"]) || !is_string($_POST["username"])
       || !isset($_POST["password"]) || !is_string($_POST["password"])){
        logon_events("FAILED");
        echo new_login(true,"{wrong_password_or_username}");
        return false;
    }

    $POST_OUT=false;
    clean_xss_deep();
    // Jeton du formulaire (class.login.csrf.inc) : un POST qui ne vient pas d'un
    // formulaire rendu dans CETTE session est refuse avant toute verification
    // d'identifiants -- ni tentative comptee, ni btmp, ni session ouverte.
    if(!login_csrf_check_session()){
        $tpl=new template_admin();
        $ip=GetRemoteIP();
        admin_tracks("Refused logon (".$tpl->CLEAN_BAD_XSS($_POST["username"]).") on the Artica Web console from $ip: missing or invalid login form token");
        echo new_login(true,"{login_form_expired}");
        return false;
    }
    if(is_file("/usr/share/artica-postfix/ressources/VERBOSE")) {
        if (preg_match("#(.+?):VERBOSE#i", $_POST["username"], $re)) {
            $_POST["username"] = $re[1];
            $POST_OUT = true;
            $GLOBALS["VERBOSE"] = true;
        }
    }

    if(isset($_COOKIE["visitorId"])){
        $visitorId=$_COOKIE["visitorId"];
        $_SESSION["visitorId"]=$_COOKIE["visitorId"];
    }


    $ArticaWebFingerPrint=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"));

    if($ArticaWebFingerPrint==1){
        if($visitorId==""){
            $ip=GetRemoteIP();
            admin_tracks("Failed to logon ({$_POST["username"]}/$ip) no fingerprint associated");
            LoginPage();
        }
        $Status=fingerprint_status($visitorId);
        if($Status<>1){
            $ip=GetRemoteIP();
            admin_tracks("Failed to logon ({$_POST["username"]}/$ip) fingerprint $visitorId is not allowed by the Artica Web console");
            LoginPage();
        }
    }


    foreach ($_SERVER as $index=>$value){
        if(is_array($value)){
            foreach ($value as $a=>$b){
                $notice[]="$index:$a:$b";
            }
            continue;
        }
        $notice[]="$index:$value";
    }

    $DebugWebConsoleAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugWebConsoleAuth");
    $tpl=new template_admin();
    $_POST["username"]=$tpl->CLEAN_BAD_XSS($_POST["username"]);
    $login=new artica_logon();

    if(method_exists($login,"VERBOSE")) {
        $login->VERBOSE("Check Login {$_POST["username"]}...", __FILE__);
    }
    webconsole_syslog("Check Login {$_POST["username"]}...",__FILE__);

    if(method_exists($login,"VERBOSE")) {
        $login->VERBOSE("CheckCreds({$_POST["username"]},...)", __FILE__);
    }
    if(!$login->CheckCreds($_POST["username"],$_POST["password"])){

        if($DebugWebConsoleAuth==1){
            writelogs("[{$_POST["username"]}] Failed to logon",__FUNCTION__,__FILE__,__LINE__);;
        }

        if(method_exists($login,"VERBOSE")) {
            $login->VERBOSE("Checking Login...FAILED", __LINE__);
        }
        logon_events("FAILED");
        $ip=GetRemoteIP();
        if($DebugWebConsoleAuth==0) {
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/btmp/{$_POST["username"]}/$ip/ArticaWeb:notty");
        }
        admin_tracks("Failed to logon ({$_POST["username"]}) on the Artica Web console from $ip L.".__LINE__);
        echo new_login(true,"{wrong_password_or_username}");


        $tpl->squid_admin_mysql(1,"Failed to logon ({$_POST["username"]}) on the Artica Web console from $ip  L.".__LINE__,@implode("\n",$notice),__FILE__,__LINE__);
        return false;
    }


    $users=new usersMenus();
    if(!ifRights($users)){
        logon_events("NO_PRIVS");
        $ERROR="{ERROR_NO_PRIVS}";
        if($POST_OUT){
            $tt[]="<H1 style='font-size:11px'>{ERROR_NO_PRIVS}</H1>";
            if(is_array($login->VERBOSE)) {
                foreach ($login->VERBOSE as $line) {
                    // Pentest 2026-11-04 : trace de logon (nom d'utilisateur saisi, attributs AD : donnee externe) affichee sans encodage (XSS stockee).
                    $tt[] = "<div style='font-size:11px'>".req_html($line)."</div>";

                }
            }
            $ERROR=@implode("\n",$tt);
        }
        $ip=GetRemoteIP();
        admin_tracks("Failed to logon ({$_POST["username"]}) no privileges associated on the Artica Web console from $ip");
        $login->logoff();
        $tpl->squid_admin_mysql(1,"Failed to logon ({$_POST["username"]}) on the Artica Web console from $ip",@implode("\n",$notice),__FILE__,__LINE__);
        if(isset($_GET["debugcredentials"])){VERBOSE("login->logoff()");exit;}
        LoginPage($ERROR);
        return false;
    }
    if(twofactor_gate($login,$users)){return true;}
    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }
    $redirect="{$HTTP_X_ARTICA_SUBFOLDER}index";


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/rrd/allimages");
    $ip=GetRemoteIP();
    logon_events("SUCCESS");
    admin_tracks("Success to logon ({$_POST["username"]}) on the Artica Web console from $ip");
    $tpl->squid_admin_mysql(2,"Success to logon ({$_POST["username"]}) on the Artica Web console from $ip",@implode("\n",$notice),__FILE__,__LINE__);


    if($users->AsWebStatisticsAdministrator){
        setcookie("AsWebStatisticsCooKie", "1", time()+172800,"","",true,true);
    }

    $rquire[]="userfont";
    $rquire[]="artica-language";
    $rquire[]="StandardDropDown";
    $rquire[]="HTMLTITLE";


    foreach ($rquire as $field){
        if(isset($_POST[$field])){
            $datasrc=$_POST[$field];
            $data=$tpl->CLEAN_BAD_XSS($datasrc);
            if($data<>$datasrc){
                admin_tracks("XSS Detected on value COOKIE $field [$datasrc]");
            }
            setcookie($field, $data, time()+31536000,"","",true,true);
        }
    }
    setcookie("shellinaboxCooKie", "1", time()+172800,"","",true,true);

    echo "<html>
	<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$redirect\"> </head>
	<body>
	<script>document.location.href='$redirect';</script>
	</body>
	</html>";
    return true;
}
function LoadTrustedNets():array{
    $memcached=new lib_memcached();
    $data=$memcached->getKey("WebConsoleTrustedNet");
    if(strlen($data)>7){
        return explode(",",$data);
    }
    $f=array();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results= $q->QUERY_SQL("SELECT * FROM networks_infos WHERE trusted=1 AND enabled=1");
    foreach ($results as $index => $ligne) {
        $maks=$ligne["ipaddr"];
        $f[]=$maks;
    }
    $memcached->saveKey("WebConsoleTrustedNet",@implode(",",$f));
    return $f;


}
function CheckTrustedNets():bool{
    $WebConsoleCheckTrustedNets = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleCheckTrustedNets"));
    if ($WebConsoleCheckTrustedNets == 0) {
        return false;
    }
    $GetRemoteIP=GetRemoteIP();
    $Nets=LoadTrustedNets();
    $IP=new IP();
    $c=0;
    foreach ($Nets as $masks){
        if(!$IP::IsACDIROrIsValid($masks)){
            logon_events("INVALID CONFIGURATION MASK $masks");
            continue;
        }
        $c++;
        if(webconsole_ip_in_range($GetRemoteIP,$masks)){
            logon_events("TRUSTED/$masks");
            return true;
        }
    }
    if($c>0) {
        return logon_events("UNTRUSTED");
    }
    return false;
}
function GetRemoteIP():string{
    $IPADDR="";
    if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
    return $IPADDR;
}
function apikey_die(){
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
    echo @file_get_contents("404.html");
    die();
}
function apikey(){
    if(GeoIPCheck()){apikey_die();}
    $tpl=new template_admin();
    $md5=$tpl->CLEAN_BAD_CHARSNET($_GET["apikey"]);
    // SÉCURITÉ (audit du 2026-11-03, M-02) : la clé vaut une session complète,
    // elle n'est jamais journalisée — ni entière, ni tronquée.
    webconsole_syslog("API KEY login attempt from {$_SERVER["REMOTE_ADDR"]}");

    $AuthLinkRestrictions=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthLinkRestrictions")),array("allowed_classes"=>false));
    if(!is_array($AuthLinkRestrictions)){$AuthLinkRestrictions=array();}
    if(count($AuthLinkRestrictions)==0){
        $AuthLinkRestrictions[] = "192.168.0.0/16";
        $AuthLinkRestrictions[] = "10.0.0.0/8";
        $AuthLinkRestrictions[] = "172.16.0.0/12";
    }
    // SÉCURITÉ : la porte IP de la clé API s'appuie sur l'adresse du PAIR TCP
    // (REMOTE_ADDR), jamais sur GetRemoteIP() qui préfère X-Forwarded-For / X-Real-IP
    // — des en-têtes contrôlables par le client. Le port 9000 est servi en direct,
    // sans proxy de confiance qui réécrirait ces en-têtes, donc X-Forwarded-For est
    // ici purement attaquant : l'honorer laisserait usurper une IP interne pour
    // franchir l'allowlist. Un vrai déport proxy devra réécrire REMOTE_ADDR, pas XFF.
    $IPADDR=isset($_SERVER["REMOTE_ADDR"])?$_SERVER["REMOTE_ADDR"]:"";
    $PASS_IPADDR=false;

    foreach ($AuthLinkRestrictions as $cdir){
        if(webconsole_ip_in_range($IPADDR,$cdir)){
            $PASS_IPADDR=true;
            break;
        }
    }

    if(!$PASS_IPADDR){
        webconsole_syslog("API KEY FAILED, did not matches any of ".@implode(", ",$AuthLinkRestrictions));
        apikey_die();
    }


    if($md5==null){
        logon_events("FAILED");
        webconsole_syslog("API KEY FAILED, no token sent");
        apikey_die();
    }


    if(!preg_match("#^[0-9a-z]+$#",$md5)){
        webconsole_syslog("API KEY FAILED, corrupted token sent");
        apikey_die();
    }
    // Token resolution delegated to articarest GET /localadmins/apis/key/.
    // Fail-closed: any API/transport error denies the auth-link, like the
    // legacy behaviour on a SQLite error.
    $resp=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API("/localadmins/apis/key/$md5")),true);
    if(!is_array($resp) || empty($resp["success"]) || !is_array($resp["data"] ?? null)){
        webconsole_syslog("API KEY FAILED, localadmins API unreachable");
        logon_events("FAILED");
        apikey_die();
    }
    if(intval($resp["data"]["total"] ?? 0)==0){
        webconsole_syslog("API KEY FAILED, No API KEY in database");
        logon_events("FAILED");
        apikey_die();
    }

    $ID=intval($resp["data"]["id"] ?? 0);
    if($ID==0){
        logon_events("FAILED");
        webconsole_syslog("API KEY FAILED, the key matches no record");
        apikey_die();}
    $CONTENT=unserialize(base64_decode(strval($resp["data"]["content"] ?? "")),array("allowed_classes"=>false));
    $_SESSION["uid"]=$CONTENT["uid"];
    // ARTICA_SUPERADMIN est restauré depuis le contenu de la clé (une copie
    // sérialisée d'une session console réelle, cf. API_LINK_SAVE() dans
    // fw.account.php), jamais recalculé depuis $CONTENT["uid"]: c'est
    // exactement la distinction du drapeau -- il décrit le chemin qui a ouvert
    // la session d'origine, pas une chaîne que quelqu'un choisit. Sans cette
    // ligne, une clé API créée par le Manager perdrait silencieusement le droit
    // de réinitialiser un second facteur (cf. audit_headers()).
    if(isset($CONTENT["ARTICA_SUPERADMIN"]) && !empty($CONTENT["ARTICA_SUPERADMIN"])){
        $_SESSION["ARTICA_SUPERADMIN"]=true;
    }
    if($_SESSION["uid"]=="-100"){$Name="SuperAdmin";}else{$Name=$_SESSION["uid"];}

    if(isset($CONTENT["ACTIVE_DIRECTORY_INDEX"])){
        $_SESSION["ACTIVE_DIRECTORY_INDEX"]=$CONTENT["ACTIVE_DIRECTORY_INDEX"];
        $_SESSION["ACTIVE_DIRECTORY_DN"]=$CONTENT["ACTIVE_DIRECTORY_DN"];
        $_SESSION["ACTIVE_DIRECTORY_INFO"]=$CONTENT["ACTIVE_DIRECTORY_INFO"];
    }

    if(isset($CONTENT["RADIUS_ID"])){$_SESSION["RADIUS_ID"]=$CONTENT["RADIUS_ID"];}
    if(isset($CONTENT["SQLITE_ID"])){$_SESSION["SQLITE_ID"]=$CONTENT["SQLITE_ID"];}
    if(isset($CONTENT["detected_lang"])){$_SESSION["detected_lang"]=$CONTENT["detected_lang"];}
    if(isset($CONTENT["UID_KEY"])){$_SESSION["UID_KEY"]=$CONTENT["UID_KEY"];}
    if(isset($CONTENT["CORP"])){$_SESSION["CORP"]=$CONTENT["CORP"];}
    if(isset($CONTENT["privileges_array"])){$_SESSION["privileges_array"]=$CONTENT["privileges_array"];}
    if(isset($CONTENT["privs"])){$_SESSION["privs"]=$CONTENT["privs"];}
    if(isset($CONTENT["OU_LANG"])){$_SESSION["OU_LANG"]=$CONTENT["OU_LANG"];}
    if(isset($CONTENT["privileges"])){$_SESSION["privileges"]=$CONTENT["privileges"];}
    if(isset($CONTENT["groupid"])){$_SESSION["groupid"]=$CONTENT["groupid"];}
    if(isset($CONTENT["ou"])){$_SESSION["ou"]=$CONTENT["ou"];}
    // Perimetre Artica Meta (tenants) : derive des privileges restaures (META_TENANTS y figure
    // comme les autres droits de la cle), par la meme fonction que la connexion normale.
    unset($_SESSION["META_SCOPE"]);
    unset($_SESSION["META_TENANT"]);
    unset($_SESSION["META_GEN"]);
    if(function_exists("meta_scope_session_sync")){meta_scope_session_sync();}


    $login=new artica_logon();
    logon_events("OK");
    $ip=$login->GetRemoteIP();
    $tpl->squid_admin_mysql(2,"Success to logon ({$_POST["username"]}) on Artica Web console API from $ip as $Name",$login->GetNotice(),__FILE__,__LINE__);
    $HTTP_X_ARTICA_SUBFOLDER=null;
    //if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){$HTTP_X_ARTICA_SUBFOLDER=$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/"; }
    webconsole_syslog("API KEY SUCCESS, Redirecting to /{$HTTP_X_ARTICA_SUBFOLDER}index");
    echo "<html>
	<head>
	<title>$Name: Redirecting</title>
	<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=/{$HTTP_X_ARTICA_SUBFOLDER}index\"> </head>
	<body>
	<script>document.location.href='/{$HTTP_X_ARTICA_SUBFOLDER}index';</script>
	</body>
	</html>";
    exit();

}
function logon_events($succes):bool{
    $uid=null;
    if(isset($_SESSION["uid"])) {
        $uid = $_SESSION["uid"];
    }

    if (empty($uid)) {
        if(isset($_POST["username"])) {
            $uid = $_POST["username"];
        }
    }
    if($uid==-100){$uid="Manager";}
    $IPADDR=GetRemoteIP();
    $logFile="/var/log/artica-webauth.log";
    $date=date('M  j H:i:s');
    if(is_writable($logFile)) {
        $f = fopen($logFile, 'a');
        $hostname = GetMyHostname();
        fwrite($f, "$date $hostname: $uid $IPADDR $succes\n");
        fclose($f);
    }
    return true;
}
function webconsole_ip_in_range( $ip, $range ) {
    if ( strpos( $range, '/' ) == false ) { $range .= '/32';}
    list( $range, $netmask ) = explode( '/', $range, 2 );
    $range_decimal = ip2long( $range );
    $ip_decimal = ip2long( $ip );
    $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

function GeoIPCheck():bool{

    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    $PHP_GEOIP_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"));
    if($PHP_GEOIP_INSTALLED==0){
        VERBOSE("PHP_GEOIP_INSTALLED = 0",__LINE__);
        $EnableGeoipUpdate=0;
    }
    if($EnableGeoipUpdate==0){return false;}
    if (!extension_loaded("maxminddb")) {
        VERBOSE("extension_loaded -> maxminddb -> FALSE",__LINE__);
        return false;
    }



    $ArticaWebDenyCountries=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebDenyCountries"));
    $c=0;
    foreach ($ArticaWebDenyCountries as $CN=>$none){
        if (strlen($CN)<2){continue;}
        $c++;
    }
    if($c==0){ return false; }
    $GetRemoteIP=GetRemoteIP();
    try {
            $reader = new Reader('/usr/local/share/GeoIP/GeoLite2-Country.mmdb');
            $record = $reader->country($GetRemoteIP);
            $value["countryCode"] = $record->country->isoCode;
            $value["countryName"] = $record->country->name;
        } catch (Exception $e) {
            writelogs("GeoIP: fatal error:".  $e->getMessage(),__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
    if ($value["countryCode"] == null) {
        writelogs("GeoIP: countryCode($GetRemoteIP) is null",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    $countryCode=$value["countryCode"];
    VERBOSE("countryCode = $countryCode",__LINE__);
    if(isset($ArticaWebDenyCountries[$countryCode])){
        logon_events("FAILED");
        return true;
    }
    return false;
}
function page_no_privs():bool{
    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }
    VERBOSE("BUILDING ERROR 500",__LINE__);
    $data=@file_get_contents("generic.html");
    $data=str_replace("_CODE_",500,$data);
    $data=str_replace("_TITLE_","{ERROR_NO_PRIVS}",$data);

    $tpl=new template_admin();
    // Pentest 2026-11-04 : uid de session (login AD/LDAP, donnee externe) affiche sans encodage (XSS stockee).
    $content=$tpl->_ENGINE_parse_body("<strong>".req_html($_SESSION["uid"] ?? "")."</strong>, {not allowed}<p>{session_expired_text}</p>");
    $content=str_replace("href=logon.php","{$HTTP_X_ARTICA_SUBFOLDER}fw.login.php?disconnect=yes",$content);
    $data=str_replace("_DESC_",$content,$data);
    echo $tpl->_ENGINE_parse_body($data);
    return true;
}