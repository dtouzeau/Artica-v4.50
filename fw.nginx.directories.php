<?php
// awc_*() et consorts : helpers partages, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Onglet « Chemins » d'un site reverse-proxy (fw.nginx.sites.php / fw.nginx.services.php,
// ?service=ID), charge dans la modale de configuration du site. Maquette :
// /home/claude/images/stitch_web_route_manager_desk.zip -- 4 tuiles, barre d'actions +
// recherche, tableau Statut / Chemins & regles / Moteur WAF / Active / Actions.
//
// Pas de TINY : cet onglet vit dans une modale, et tiny-page ecrit dans le #tiny-button
// de la page parente. Les boutons vont dans la barre d'outils de l'onglet.

if(isset($_GET["directory-js"])){directory_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["directory-settings"])){directory_settings();exit;}
if(isset($_GET["directory-tab"])){directory_tab();exit;}
if(isset($_POST["ID"])){directory_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["ngx-path-delete"])){delete_path();exit;}
if(isset($_GET["items"])){items_fragment();exit;}
if(isset($_GET["enabled"])){directory_enabled();exit;}
if(isset($_GET["export-json"])){export_json();exit;}

table_start();


// ---------------------------------------------------------------------------
// Lecture des parametres et droits
// ---------------------------------------------------------------------------

// Nom de la fonction de rafraichissement generee par search_block (ss<time>).
// Il part tel quel dans des OnClick et des $jsafter : liste blanche a la lecture,
// jamais $_GET["function"] brut (XSS reflechi -- skill §7).
function ngxp_function():string{
    $f=isset($_GET["function"])?(string)$_GET["function"]:"";
    return preg_replace('/[^A-Za-z0-9_]/','',$f);
}
function ngxp_md5():string{
    $m=isset($_GET["md5"])?(string)$_GET["md5"]:"";
    return preg_replace('/[^a-fA-F0-9]/','',$m);
}
// Appel du rafraichissement du tableau, sans jeter si la fonction a quitte le DOM.
function ngxp_refresh_js($function):string{
    if($function==""){return "";}
    return "if(typeof $function==='function'){ $function(); }";
}

// Masquer un bouton n'est pas un controle de droits : chaque handler qui ecrit
// le verifie cote serveur. AsSystemWebMaster est le droit que la page affichait deja.
function ngxp_can_write():bool{
    if(isset($GLOBALS["NGXP_CAN_WRITE"])){return $GLOBALS["NGXP_CAN_WRITE"];}
    $users=new usersMenus();
    $GLOBALS["NGXP_CAN_WRITE"]=(bool)$users->AsSystemWebMaster;
    return $GLOBALS["NGXP_CAN_WRITE"];
}
function ngxp_can_read():bool{
    $users=new usersMenus();
    if($users->AsSystemWebMaster){return true;}
    return (bool)$users->AsWebMaster;
}

function ngxp_val($array,$key,$default=""){
    if(!is_array($array) || !isset($array[$key])){return $default;}
    return $array[$key];
}

// Valeur d'exploitation (chemin, IP, URL) rendue dans du HTML qui passe ensuite
// par _ENGINE_parse_body() : les accolades sont neutralisees, sinon un chemin
// « /api/{items} » serait traduit en « /api/Items ».
function ngxp_h($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}"),array("&#123;","&#125;"),$s);
}

// "" si la reponse de /nginx-db/exec prouve un succes, sinon le message.
function ngxp_rest_error($resp):string{
    $data=json_decode((string)$resp,true);
    if(is_array($data)){
        if(!empty($data["Status"])){return "";}
        if(!empty($data["error"])){return (string)$data["error"];}
        if(!empty($data["Error"])){return (string)$data["Error"];}
        return "{error}";
    }
    $raw=trim(str_replace(array("'","\\"),array(" "," "),(string)$resp));
    return $raw==""?"{error}":substr($raw,0,300);
}


// ---------------------------------------------------------------------------
// Modales d'edition (inchangees, hormis la liste blanche des parametres)
// ---------------------------------------------------------------------------

function directory_js():bool{
    $function=ngxp_function();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["directory-js"]);
    $serviceid=intval($_GET["serviceid"]);
    $md5=ngxp_md5();
    $title="{rule}: $ID";
    if($ID==0){$title="{new_path}";}

    if($ID>0){
        $ligne=fetch_ngx_directory($ID);
        $directory=isset($ligne["directory"])?$ligne["directory"]:"";
       return  $tpl->js_dialog3($directory, "$page?directory-tab=$ID&serviceid=$serviceid&md5=$md5&function=$function");
    }

    return $tpl->js_dialog3($title, "$page?directory-settings=$ID&serviceid=$serviceid&md5=$md5&function=$function");
}
function directory_tab():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["directory-tab"]);
    $serviceid  = intval($_GET["serviceid"]);
    $md5        = ngxp_md5();
    $function   = ngxp_function();
    $array["{settings}"]="$page?directory-settings=$ID&serviceid=$serviceid&md5=$md5&function=$function";
    $array["{ACLS}"]="fw.nginx.directories.items.php?directory_id=$ID&serviceid=$serviceid&md5=$md5&function=$function";
    $array["{backends}"]="fw.nginx.directories.backends.php?directory_id=$ID&serviceid=$serviceid&md5=$md5&function=$function";
    $array["{replace_rules}"]="fw.nginx.directories.replace.php?popup-main=yes&directory_id=$ID&serviceid=$serviceid&md5=$md5&function=$function";
    $array["{content}"]="fw.nginx.directories.content.php?popup-main=yes&directory_id=$ID&serviceid=$serviceid&md5=$md5&function=$function";
    echo $tpl->tabs_default($array);
    return true;
}

// Etape 1 de la suppression : garde « desarmement du redirector », puis
// confirmation. La suppression elle-meme est un POST (delete_path()).
function delete_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["delete-js"]);
    $function   = ngxp_function();
    header("content-type: application/x-javascript");
    if(!ngxp_can_write()){
        return $tpl->js_error("{ERROR_NO_PRIVS}");
    }
    $ligne=fetch_ngx_directory($ID);
    if(count($ligne)==0){
        return $tpl->js_error("{ngx_paths_not_found}");
    }
    // I-1 : une suppression desarme un chemin exactement comme la case
    // decochee dans le formulaire (la ligne disparait, le daemon ne l'emet
    // plus jamais) -- sans ce garde, ce chemin contournait completement
    // l'avertissement de directory_save().
    $confirm=intval(isset($_GET["confirm_disarm"])?$_GET["confirm_disarm"]:0)==1?1:0;
    if($confirm==0){
        $warn=admins_redirector_disarm_check($ID, false, null);
        if(strlen($warn)>0){
            return admins_redirector_confirm_dialog($warn, "$page?delete-js=$ID&function=$function");
        }
    }
    $serviceid=intval(ngxp_val($ligne,"serviceid",0));
    $after="Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');".ngxp_refresh_js($function);
    return $tpl->js_confirm_delete(ngxp_h(ngxp_val($ligne,"directory","#$ID")),"ngx-path-delete","$ID:$confirm",$after);
}

// Etape 2 : js_confirm_delete() traite toute reponse de plus de 3 caracteres
// comme une erreur et n'execute son $jsafter que sur une reponse vide.
function delete_path():bool{
    $tpl=new template_admin();
    if(!ngxp_can_write()){
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
        return false;
    }
    $parts=explode(":",(string)$_POST["ngx-path-delete"]);
    $ID=intval($parts[0]);
    $confirm=intval(isset($parts[1])?$parts[1]:0);
    if($ID==0){
        echo $tpl->_ENGINE_parse_body("{ngx_paths_not_found}");
        return false;
    }
    // Le garde est rejoue cote serveur : le POST est la mutation, pas le JS.
    if($confirm!=1){
        $warn=admins_redirector_disarm_check($ID, false, null);
        if(strlen($warn)>0){
            echo $tpl->_ENGINE_parse_body($warn);
            return false;
        }
    }
    $get_servicename=get_servicename($ID);
    $err=ngxp_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"delete","table"=>"ngx_subdir_items","where"=>array("directoryid"=>"$ID"))));
    if($err<>""){
        echo $tpl->_ENGINE_parse_body($err);
        return false;
    }
    $err=ngxp_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"delete","table"=>"ngx_directories","where"=>array("ID"=>"$ID"))));
    if($err<>""){
        echo $tpl->_ENGINE_parse_body($err);
        return false;
    }
    return admin_tracks("Remove path $ID from $get_servicename service");
}

function get_servicename($ngx_directories_id=0):string{
    $ID=intval($ngx_directories_id);
    if($ID==0){return "Unknown";}
    $ligne=fetch_ngx_directory($ID);
    $serviceid=intval(isset($ligne["serviceid"])?$ligne["serviceid"]:0);
    if($serviceid==0){return "Unknown";}
    $sock=new socksngix($serviceid);
    return $sock->GetServiceName();
}

function directory_enabled():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ID         = intval($_GET["enabled"]);
    $function   = ngxp_function();
    header("content-type: application/x-javascript");
    if(!ngxp_can_write()){
        return $tpl->js_error("{ERROR_NO_PRIVS}");
    }
    $ligne=fetch_ngx_directory($ID);
    if(count($ligne)==0){
        return $tpl->js_error("{ngx_paths_not_found}");
    }
    $serviceid=intval(ngxp_val($ligne,"serviceid",0));
    $get_servicename=get_servicename($ID);
    $newValue=1;
    if(intval(ngxp_val($ligne,"enabled",0))==1){
        $newValue=0;
        // I-1 : desactiver une ligne armee a exactement le meme effet chez le
        // daemon qu'un desarmement (nginxsites/directories.go ne lit que
        // enabled=1, /admins-redirector/paths.go ne propose que enabled=1) --
        // seule la direction "desactiver" est concernee, "activer" ne desarme
        // jamais rien.
        if(intval(isset($_GET["confirm_disarm"])?$_GET["confirm_disarm"]:0)!=1){
            $warn=admins_redirector_disarm_check($ID, false, null);
            if(strlen($warn)>0){
                return admins_redirector_confirm_dialog($warn, "$page?enabled=$ID&function=$function");
            }
        }
    }
    $err=ngxp_rest_error($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array("action"=>"update","table"=>"ngx_directories","set"=>array("enabled"=>"$newValue"),"where"=>array("ID"=>"$ID"))));
    if($err<>""){
        return $tpl->js_error($err);
    }
    // Le tableau est redessine depuis la base : l'interrupteur montre l'etat
    // enregistre, jamais celui que le clic a suppose.
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');\n";
    echo ngxp_refresh_js($function)."\n";
    if($newValue==0){
        return admin_tracks("Disable path $ID from $get_servicename service");
    }
    return admin_tracks("Enable path $ID from $get_servicename service");
}

function ifModSecurityDisabled($serviceid):bool{

    $NginxHTTPModSecurity=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    if($NginxHTTPModSecurity==0){
        return true;
    }
    $EnableModSecurityIngix = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    if($EnableModSecurityIngix==0){
        return true;}
    $socknginx= new socksngix($serviceid);
    $EnableModSecurity=intval($socknginx->GET_INFO("EnableModSecurity"));
    if($EnableModSecurity==0){
        return true;}
    VERBOSE("EnableModSecurity = [SUCCESS]",__LINE__);
    return false;
}

function directory_settings():bool{
        $page       = CurrentPageName();
        $tpl        = new template_admin();
        $fieldH     = array();
        $ID         = intval($_GET["directory-settings"]);
        $serviceid  = intval($_GET["serviceid"]);
        $zproxy_http_version["1.0"]="1.0: {default} ";
        $zproxy_http_version["1.1"]="1.1: KeepAlive {or} NTLM";
        $function   = ngxp_function();

        $options["REDIRECT_HTTP"]=null;
        $btname="{add}";
        for($i=1;$i<24;$i++){$fieldH[$i]="$i {hours}";}
        for($i=2;$i<30;$i++){$hours=$i*24;$fieldH[$hours]="$i {days}";}

        $directory="/";
        $ligne=array();
        if($ID>0){
            $ligne=fetch_ngx_directory($ID);
            $btname="{apply}";
            $serviceid=intval($ligne["serviceid"]);
            $directory=$ligne["directory"];
            $options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
        }
        if(!isset($options["REDIRECT_HTTP"])){$options["REDIRECT_HTTP"]=null;}
        if(!isset($options["proxy_http_version"])){$options["proxy_http_version"]="1.0";}
        if(!isset($options["HostHeader"])){$options["HostHeader"]="";}
        if(!isset($options["WebSocketsSupport"])){$options["WebSocketsSupport"]=0;}
        if(!isset($options["sslclient"])){$options["sslclient"]=0;}
        if(!isset($options["AdminsRedirector"])){$options["AdminsRedirector"]=0;}

        if(!isset($ligne["enabled"])){$ligne["enabled"]=1;}
        if(!isset($ligne["deny"])){$ligne["deny"]=0;}
        if(!isset($ligne["cachetime"])){$ligne["cachetime"]=0;}
        if(!isset($ligne["cacheargs"])){$ligne["cacheargs"]=0;}
        if(!isset($ligne["wafwhite"])){$ligne["wafwhite"]=0;}

        if(!isset($ligne["bcacheimages"])){$ligne["bcacheimages"]=0;}
        if(!isset($ligne["bcachehtml"])){$ligne["bcachehtml"]=0;}
        if(!isset($ligne["bcachebinaries"])){$ligne["bcachebinaries"]=0;}
        if(!isset($ligne["content_type"])){$ligne["content_type"]="";}

        $bcacheimages=$ligne["bcacheimages"];
        $bcachehtml=$ligne["bcachehtml"];
        $bcachebinaries=$ligne["bcachebinaries"];

        // Le tableau de l'onglet se rafraichit par la fonction de search_block ;
        // l'ancien LoadAjax('ngx_directories_access_module-…') visait un div
        // qui n'existe plus depuis longtemps.
        $tt[]="dialogInstance3.close();";
        $tt[]=ngxp_refresh_js($function);
        $tt[]="Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');";
        $js=@implode("",$tt);

        $proxy_http_version=$options["proxy_http_version"];
        $HostHeader=$options["HostHeader"];
        $WebSocketsSupport=intval($options["WebSocketsSupport"]);

        $form[]=$tpl->field_hidden("ID", $ID);
        $form[]=$tpl->field_hidden("serviceid", $serviceid);

        if($ID>0){
            $form[]=$tpl->field_checkbox("enabled", "{enabled}", intval($ligne["enabled"]));
        }else {
            $form[]=$tpl->field_hidden("enabled", 1);
        }
        $LockCertif=LockCertif($serviceid);
        VERBOSE("LockCertif = $LockCertif",__LINE__);
        if($LockCertif){
            $options["sslclient"]=0;
        }

        $form[] = $tpl->field_text("directory", "{path}", $directory,true);
        $form[] = $tpl->field_text("REDIRECT_HTTP","{label_redirect}",$options["REDIRECT_HTTP"]);
        $form[] = $tpl->field_array_hash($zproxy_http_version,"proxy_http_version",
                    "{proxy_http_version}",$proxy_http_version);
        $form[] = $tpl->field_text("HostHeader","{HostHeader}",$HostHeader);
        $form[] = $tpl->field_checkbox("WebSocketsSupport","{websockets_support}",$WebSocketsSupport);
        $ctypes=array(
            "text/javascript"          => "text/javascript",
            "application/javascript"   => "application/javascript",
            "text/css"                 => "text/css",
            "application/json"         => "application/json",
            "application/xml"          => "application/xml",
            "text/plain"               => "text/plain",
            "text/csv"                 => "text/csv",
            "image/svg+xml"            => "image/svg+xml",
            "application/pdf"          => "application/pdf",
            "application/octet-stream" => "application/octet-stream",
            "application/wasm"         => "application/wasm",
        );
        $form[] = $tpl->field_array_hash($ctypes,"content_type","Content-Type",$ligne["content_type"]);
        $form[] = $tpl->field_section("{caching}");
        $form[] = $tpl->field_numeric("cachetime","{cachetime} ({minutes})",$ligne["cachetime"]);
        $form[] = $tpl->field_checkbox("cacheargs", "{cache_arguments}", intval($ligne["cacheargs"]));
        $form[] = $tpl->field_section("{browser_caching}||{browser_caching_explain}");
        $form[] = $tpl->field_array_hash($fieldH,"bcacheimages", "{cache_images}",$bcacheimages);
        $form[] = $tpl->field_array_hash($fieldH,"bcachehtml", "{cache_htmlext}",$bcachehtml);
        $form[] = $tpl->field_array_hash($fieldH,"bcachebinaries", "{cache_binaries}",$bcachebinaries);


        $form[] = $tpl->field_section("{security}");
        $form[] = $tpl->field_checkbox("deny", "{deny}", intval($ligne["deny"]));
        $ifModSecurityEnabled=ifModSecurityDisabled($serviceid);
        $form[] = $tpl->field_checkbox("wafwhite", "{wafwhite}", intval($ligne["wafwhite"]),false,null,$ifModSecurityEnabled);

        $form[] = $tpl->field_checkbox("sslclient", "{verify_client_certificate}", intval($options["sslclient"]),false,null,$LockCertif);

        // AdminsRedirector: relais du service mTLS d'administration du daemon
        // sur ce chemin, pour qu'un client desktop l'atteigne en 443 sans port
        // supplementaire. Verrouillee avec sa RAISON (mTLS eteint, ou CA
        // cliente deja portee par le site -- les deux CA ne peuvent coexister
        // sur un meme vhost), jamais un simple grisage muet.
        $LockRedirector=LockRedirector($serviceid);
        VERBOSE("LockRedirector = $LockRedirector",__LINE__);
        // Ce que la BASE dit, avant tout forcage d'affichage. La case de
        // confirmation ci-dessous doit se decider LA-DESSUS : verrouiller le
        // formulaire (mTLS eteint, ou CA cliente apparue sur le site) remet
        // AdminsRedirector a 0 a l'ecran, mais la ligne reste armee en base.
        // En testant la valeur forcee, la case n'etait plus rendue, alors que
        // directory_save() continuait d'exiger la confirmation -- plus aucune
        // modification de cette ligne n'etait enregistrable.
        $armedInDB=intval($options["AdminsRedirector"] ?? 0)==1;
        if(strlen($LockRedirector)>0){$options["AdminsRedirector"]=0;}
        $form[] = $tpl->field_checkbox("AdminsRedirector","{admins_redirector}",
                    intval($options["AdminsRedirector"]),false,
                    strlen($LockRedirector)>0?$LockRedirector:null,strlen($LockRedirector)>0);

        // Desarmer ce chemin ou renommer sa route casse -- silencieusement,
        // chez leurs porteurs -- tous les certificats deja distribues qui le
        // portent. La sauvegarde refuse tant que ceci n'est pas coche; le
        // message de refus (directory_save()) donne le nombre reel.
        //
        // Mineur 17 : n'a de sens que sur un chemin ARME -- sur tous les
        // autres formulaires (chemin non arme, ou site verrouille ci-dessus)
        // aucun desarmement n'est possible et la case n'affichait qu'une
        // question sans objet. Rendu statique (PHP), donc sans le probleme
        // XHR invoque initialement.
        if($armedInDB){
            $form[] = $tpl->field_checkbox("confirm_disarm","{admins_redirector_confirm_disarm}",0);
        }

        $help="https://wiki.articatech.com/en/reverse-proxy/paths";
        echo $tpl->form_outside("", $form,$help,$btname,$js,"AsSystemWebMaster");
        return true;
}

function ActiveCertif($serviceid):bool{
    $sockngix=new socksngix($serviceid);
    $ssl_certificate=$sockngix->GET_INFO("ssl_certificate");
    if (strlen($ssl_certificate)<4){
        VERBOSE("[$serviceid]: ssl_certificate len is false",__LINE__);
        return false;
    }
    $EnableClientCertificate    = intval($sockngix->GET_INFO("EnableClientCertificate"));
    if($EnableClientCertificate==0){
        VERBOSE("EnableClientCertificate =0",__LINE__);
        return false;
    }
    return true;
}
function LockCertif($serviceid):bool{
    $sockngix=new socksngix($serviceid);
    if(!ActiveCertif($serviceid)){
        return true;
    }
    $OptionalClientCertificate=intval($sockngix->GET_INFO("OptionalClientCertificate"));
    if($OptionalClientCertificate==0){
        VERBOSE("[$serviceid]: OptionalClientCertificate = 0 -> LOCK",__LINE__);
        return true;
    }
    return false;
}

/**
 * Un chemin ne peut porter le redirector que si le service mTLS existe et que
 * le site ne porte pas deja SA propre CA cliente : les deux CA ne peuvent pas
 * coexister sur un meme vhost.
 *
 * Rend la RAISON et pas seulement un booleen : une case grisee sans explication
 * se contourne en tatonnant, ou decourage sans qu'on sache pourquoi.
 */
function LockRedirector($serviceid):string{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ReverseAppClients"))!=1){
        return "{admins_redirector_lock_disabled}";
    }
    $sockngix=new socksngix($serviceid);
    // I-4 : le Go refuse sur EnableClientCertificate==1 OU
    // Ssl_client_certificate!="" (Locations/AdminsRedirector.go) -- cette
    // fonction ne testait que la seconde porte. La combinaison
    // EnableClientCertificate=1 + certificat vide est atteignable (case
    // cochee, certificat pas encore choisi/enregistre) : sans ce test, la
    // console offrait la case, l'exploitant l'armait, et le daemon refusait
    // en silence dans un commentaire au fond d'un 0-{ID}.conf. Effet de bord
    // voulu : les deux cases deviennent mutuellement exclusives par
    // construction (spec §5.5).
    $enableClientCertificate=intval($sockngix->GET_INFO("EnableClientCertificate"));
    if($enableClientCertificate==1){
        return "{admins_redirector_lock_conflict}";
    }
    $clientca=trim($sockngix->GET_INFO("ssl_client_certificate"));
    if(strlen($clientca)>0){
        return "{admins_redirector_lock_conflict}";
    }
    return "";
}

// _directory_options_from(): unserialize le blob "options" (base64 + PHP
// serialize()) d'une ligne ngx_directories -- meme forme que rend
// /nginx-db/query/ngx_directories/ID/{id} (verifiee sur 192.168.90.88):
// {"Status":true,"rows":[{"options":"<base64>", "directory":"...", ...}]}.
// $row est la ligne elle-meme (deja extraite de "rows"), comme le rend
// fetch_ngx_directory(). Jamais un warning unserialize() sur un blob absent
// ou corrompu -- retourne [] dans ce cas, jamais null.
function _directory_options_from($row):array{
    if(!is_array($row) || !isset($row["options"])){return array();}
    $opts=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($row["options"]);
    return is_array($opts)?$opts:array();
}

// _directory_path_from(): la colonne "directory" de la meme ligne.
function _directory_path_from($row):string{
    if(!is_array($row) || !isset($row["directory"])){return "";}
    return (string)$row["directory"];
}

// admins_redirector_disarm_check(): partie FACTORISEE du garde-fou de
// directory_save() (I-1) -- appelee aussi par delete_js()/delete_path() et
// directory_enabled(), les autres chemins qui desarment un chemin
// exactement comme la case decochee : supprimer une ligne armee ou la
// desactiver (enabled=0) ont chez le daemon le MEME effet qu'un desarmement
// explicite -- la location disparait (nginxsites/directories.go ne lit que
// enabled=1) et /admins-redirector/paths ne la propose plus (Paths.go),
// silencieusement, sans le moindre avertissement avant cette correction.
//
// Rend "" si l'action peut proceder sans avertissement, sinon le message a
// afficher. $newArmed/$newDirectory decrivent l'etat PROPOSE : pour une
// suppression ou une desactivation, l'appelant passe toujours
// $newArmed=false (l'action desarme par construction, quel que soit l'etat
// de la case AdminsRedirector en base) et $newDirectory=null (pas de
// renommage possible par ces deux chemins).
//
// Lecture DIRECTE (jamais fetch_ngx_directory(), qui confond "la ligne
// n'existe pas" et "la lecture a echoue") -- Status:true est la seule preuve
// d'une lecture reussie ; une lecture ratee bloque toujours, meme traitement
// que le compte de certificats a -1 (Pattern #54).
function admins_redirector_disarm_check($ID,bool $newArmed,$newDirectory):string{
    $ID=intval($ID);
    if($ID==0){return "";}
    $prevresp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_directories/ID/$ID");
    $prevdata=json_decode($prevresp,true);
    $readOk=is_array($prevdata) && isset($prevdata["Status"]) && $prevdata["Status"]===true;
    if(!$readOk){
        return "{admins_redirector_disarm_warning_unknown}";
    }
    $prevrows=(isset($prevdata["rows"]) && is_array($prevdata["rows"])) ? $prevdata["rows"] : array();
    if(count($prevrows)==0){return "";}
    $previous=$prevrows[0];
    $prevopts=_directory_options_from($previous);
    if(intval(isset($prevopts["AdminsRedirector"])?$prevopts["AdminsRedirector"]:0)!=1){return "";}
    $prevpath=_directory_path_from($previous);
    $willDisarm=!$newArmed;
    $willRename=($newDirectory!==null && $prevpath!==trim((string)$newDirectory));
    if(!$willDisarm && !$willRename){return "";}
    $sid=intval(isset($previous["serviceid"])?$previous["serviceid"]:0);
    $usage=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX(
        "/admins-redirector/usage/$sid?path=".urlencode($prevpath)));
    $n=-1;
    if(isset($usage->Status) && $usage->Status && isset($usage->count)){
        $n=intval($usage->count);
    }
    if($n===0){return "";}
    return ($n>=0) ? "{admins_redirector_disarm_warning}: $n" : "{admins_redirector_disarm_warning_unknown}";
}

// admins_redirector_confirm_dialog(): emet un $.confirm() minimal (meme
// convention que fw.nginx.status.memory.php:nm_restart_js -- jamais un
// alert() natif, skill §5b) qui rejoue l'appel courant avec
// confirm_disarm=1 si l'exploitant accepte. $retryUrl porte deja tous les
// parametres necessaires, il ne manque que ce flag.
function admins_redirector_confirm_dialog($warn,$retryUrl):bool{
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{admins_redirector_confirm_disarm}");
    // Le bouton portait le titre entier : jquery-confirm met les libelles en
    // capitales de mot et la phrase debordait de la boite, masquant le bouton
    // d'annulation. Le titre reste au-dessus, le bouton dit juste « Confirm ».
    $confirm=$tpl->javascript_parse_text("{confirm}");
    $cancel=$tpl->javascript_parse_text("{cancel}");
    $content=base64_encode($tpl->_ENGINE_parse_body($warn));
    $sep=(strpos($retryUrl,"?")===false)?"?":"&";
    $retryUrl="$retryUrl{$sep}confirm_disarm=1";
    header("content-type: application/x-javascript");
    echo "
\$.confirm({
  theme: 'light',
  containerFluid: true,
  title: \"$title\",
  content: base64_decode('$content'),
  icon: 'fas fa-triangle-exclamation',
  buttons: {
    confirm: { text: \"$confirm\", action: function () { Loadjs('$retryUrl'); } },
    cancel: { text: \"$cancel\" }
  }
});
";
    return true;
}

function directory_save():bool{
    $tpl=new template_admin();
    if(!ngxp_can_write()){
        echo $tpl->post_error("{ERROR_NO_PRIVS}");
        return false;
    }
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);

    // Une case GRISEE n'emet pas de ligne XHR.appendData (field_checkbox sort
    // avant, class.template-admin.inc) : sa cle est absente du POST. Sans
    // repli, chaque enregistrement ecrasait par 0 une valeur que
    // l'administrateur ne pouvait meme pas voir -- « sslclient » perdu des que
    // le site n'a plus de certificat client optionnel, « wafwhite » perdu des
    // que ModSecurity est eteint globalement, armement du redirector perdu des
    // que le mTLS est desactive. On repart donc de ce qui est EN BASE.
    $previousRow=($ID>0)?fetch_ngx_directory($ID):array();
    $previousOpts=_directory_options_from($previousRow);
    $postedOr=function($key,$previous)use($previousOpts){
        if(isset($_POST[$key])){return intval($_POST[$key]);}
        return intval($previous);
    };

    $options["REDIRECT_HTTP"]=$_POST["REDIRECT_HTTP"];
    $options["deny"]=intval($_POST["deny"]);
    $options["sslclient"]=$postedOr("sslclient",$previousOpts["sslclient"] ?? 0);
    $options["proxy_http_version"]=$_POST["proxy_http_version"];
    $options["HostHeader"]=$_POST["HostHeader"];
    $options["WebSocketsSupport"]=$_POST["WebSocketsSupport"];
    $options["AdminsRedirector"]=$postedOr("AdminsRedirector",$previousOpts["AdminsRedirector"] ?? 0);

    // Desarmer un chemin, ou le renommer, casse tous les certificats deja
    // distribues qui le portent -- silencieusement, chez leurs porteurs. On
    // refuse tant que l'administrateur n'a pas confirme en connaissant le
    // nombre -- et on refuse aussi quand ce nombre n'a pas pu etre obtenu
    // (base illisible, chemin invalide cote daemon): on ne desarme jamais sur
    // une information qu'on n'a pas pu obtenir. Garde FACTORISEE (I-1),
    // partagee avec delete_js()/delete_path() et directory_enabled().
    if($ID>0 && intval($_POST["confirm_disarm"] ?? 0)!=1){
        $warn=admins_redirector_disarm_check($ID, $options["AdminsRedirector"]==1, $_POST["directory"]);
        if(strlen($warn)>0){
            echo $tpl->post_error($warn);
            return true;
        }
    }

    // Meme repli que ci-dessus : la case est grisee quand ModSecurity est
    // eteint globalement, et son absence du POST ne doit pas effacer un
    // « {wafwhite} » pose avant l'extinction. wafwhite est une COLONNE, pas
    // une cle du blob options.
    $wafwhite=isset($_POST["wafwhite"])?intval($_POST["wafwhite"]):intval(ngxp_val($previousRow,"wafwhite",0));
    $cachetime=intval($_POST["cachetime"]);
    $cacheargs=intval($_POST["cacheargs"]);
    $bcacheimages=intval($_POST["bcacheimages"]);
    $bcachehtml=intval($_POST["bcachehtml"]);
    $bcachebinaries=intval($_POST["bcachebinaries"]);


    $options=base64_encode(serialize($options));
    $directory=$_POST["directory"];
    if( $directory =="/"){
        echo $tpl->post_error(" / {bad_configuration}");
        return true;
    }

    $enabled = intval($_POST["enabled"]);
    $deny    = intval($_POST["deny"]);
    $get_servicename=get_servicename($ID);

    $values=array(
        "directory"     => $directory,
        "deny"          => $deny,
        "enabled"       => $enabled,
        "options"       => $options,
        "wafwhite"      => $wafwhite,
        "cachetime"     => $cachetime,
        "cacheargs"     => $cacheargs,
        "bcacheimages"  => $bcacheimages,
        "bcachehtml"    => $bcachehtml,
        "bcachebinaries"=> $bcachebinaries,
        "content_type"  => isset($_POST["content_type"])?$_POST["content_type"]:"",
    );

    if($ID==0){
        $serviceid=intval($_POST["serviceid"]);
        if($serviceid==0){
            echo $tpl->post_error("serviceid = {$_POST["serviceid"]}, corrupted post");
            return false;
        }
        $values["serviceid"]=$serviceid;
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array(
            "action"=>"insert","table"=>"ngx_directories","values"=>$values,
        ));
        $get_servicename=$serviceid;
    }else{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/nginx-db/exec", array(
            "action"=>"update","table"=>"ngx_directories","set"=>$values,"where"=>array("ID"=>$ID),
        ));
    }
    $data=json_decode($resp,true);
    if(!is_array($data) || empty($data["Status"])){
        $err=is_array($data) && isset($data["error"]) ? strval($data["error"]) : "save failed";
        echo $tpl->post_error($err);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($_POST["serviceid"]);
    return admin_tracks_post("Add or Modify Web service Path $get_servicename");

}


// ---------------------------------------------------------------------------
// Donnees : un modele par chemin, partage par le tableau, les tuiles et l'export
// ---------------------------------------------------------------------------

function items_proxies($directoryid):array{
    $tt=array();
    $results=fetch_directories_backends_for_dir($directoryid);
    foreach ($results as $ligne) {
        $options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(ngxp_val($ligne,"options",""));
        $proto="http";
        if(intval(ngxp_val($options,"UseSSL",0))==1){
            $proto="https";
        }
        $tt[]="$proto://".ngxp_val($ligne,"hostname","").":".ngxp_val($ligne,"port","").ngxp_val($ligne,"root","");
    }
    return $tt;
}

function items($directoryid):array{
    $results=fetch_ngx_subdir_items_for_dir($directoryid);
    $tt=array();
    foreach ($results as $ligne) {
        $tt[] = (string)ngxp_val($ligne,"item","");
    }
    return $tt;
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

// Chemins reellement emis par le daemon dans la configuration en service.
// Rend array(map ID=>true, message d'erreur) -- l'erreur est affichee au-dessus
// du tableau au lieu d'etre ecrite au milieu du flux comme avant.
function ngxp_live_paths($serviceid):array{
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/checkdirs/$serviceid");
    $json=json_decode($data);
    if(!is_object($json)){
        return array(array(),"checkdirs: ".ngxp_h(json_last_error_msg()));
    }
    if(!isset($json->Status) || !$json->Status){
        $err=str_replace("::","<br>",ngxp_h(isset($json->Error)?$json->Error:""));
        $traces=str_replace("::","<br>",ngxp_h(isset($json->Traces)?$json->Traces:""));
        return array(array(),"$err<br>$traces");
    }
    $MAIN=array();
    foreach (explode(",",(string)(isset($json->Info)?$json->Info:"")) as $line){
        $line=trim($line);
        if($line==""){continue;}
        $MAIN[$line]=true;
    }
    return array($MAIN,"");
}

function ngxp_context($serviceid):array{
    $ctx=array();
    $ctx["harmp"]=isHarmpID();
    $ctx["live"]=array();
    $ctx["live_error"]="";
    if(!$ctx["harmp"]){
        list($ctx["live"],$ctx["live_error"])=ngxp_live_paths($serviceid);
    }
    $ctx["waf_engine"]=!ifModSecurityDisabled($serviceid);
    $ctx["cert_lock"]=LockCertif($serviceid);
    $nginxsockGen=new socksngix(0);
    $ctx["cache_dir"]=intval($nginxsockGen->GET_INFO("nginxCachesDir"))>0;
    $ctx["can_write"]=ngxp_can_write();

    $sock=new socksngix($serviceid);
    $ctx["bulk_on"]=intval($sock->GET_INFO("BulkRedirects"))==1;
    $ctx["bulk_count"]=0;
    if($ctx["bulk_on"]){
        $data=@unserialize(base64_decode((string)$sock->GET_INFO("FBulkRedirectsData")),array("allowed_classes"=>false));
        if(is_array($data)){$ctx["bulk_count"]=count($data);}
    }
    return $ctx;
}

function ngxp_collect($serviceid,$ctx):array{
    $results=fetch_ngx_directories_for_service($serviceid);
    // Daemon's column-equals query has no ORDER BY; sort by directory PHP-side.
    usort($results,function($a,$b){return strcmp((string)ngxp_val($a,"directory",""),(string)ngxp_val($b,"directory",""));});
    $paths=array();
    foreach ($results as $ligne){
        $ID=intval(ngxp_val($ligne,"ID",0));
        if($ID==0){continue;}
        $options=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(ngxp_val($ligne,"options",""));
        $redirect=trim((string)ngxp_val($options,"REDIRECT_HTTP",""));
        $websockets=intval(ngxp_val($options,"WebSocketsSupport",0));
        $version=(string)ngxp_val($options,"proxy_http_version","1.0");
        if($version==""){$version="1.0";}
        if($websockets==1){$version="1.1";}
        $sslclient=intval(ngxp_val($options,"sslclient",0));
        if($ctx["cert_lock"]){$sslclient=0;}
        $paths[]=array(
            "ID"=>$ID,
            "directory"=>(string)ngxp_val($ligne,"directory",""),
            "enabled"=>intval(ngxp_val($ligne,"enabled",0)),
            "deny"=>intval(ngxp_val($ligne,"deny",0)),
            "live"=>isset($ctx["live"][$ID]),
            "items"=>items($ID),
            // Une redirection ne passe par aucun back-end : inutile de les lire.
            "backends"=>$redirect==""?items_proxies($ID):array(),
            "redirect"=>$redirect,
            "http_version"=>$version,
            "websockets"=>$websockets,
            "host_header"=>trim((string)ngxp_val($options,"HostHeader","")),
            "sslclient"=>$sslclient,
            "admins_redirector"=>intval(ngxp_val($options,"AdminsRedirector",0)),
            "wafwhite"=>intval(ngxp_val($ligne,"wafwhite",0)),
            "iscontent"=>intval(ngxp_val($ligne,"iscontent",0)),
            "cachetime"=>intval(ngxp_val($ligne,"cachetime",0)),
            "cacheargs"=>intval(ngxp_val($ligne,"cacheargs",0)),
            "bcacheimages"=>intval(ngxp_val($ligne,"bcacheimages",0)),
            "bcachehtml"=>intval(ngxp_val($ligne,"bcachehtml",0)),
            "bcachebinaries"=>intval(ngxp_val($ligne,"bcachebinaries",0)),
            "content_type"=>trim((string)ngxp_val($ligne,"content_type","")),
        );
    }
    return $paths;
}

// Filtre de search_block : chemin, IP des ACL, back-ends, cible de redirection.
// Sous-chaine insensible a la casse ; « * » sert de joker.
function ngxp_search_match($p,$search):bool{
    if($search===""){return true;}
    $hay=strtolower($p["directory"]." ".implode(" ",$p["items"])." ".implode(" ",$p["backends"])." ".$p["redirect"]." ".$p["host_header"]);
    $needle=strtolower($search);
    if(strpos($needle,"*")===false){
        return strpos($hay,$needle)!==false;
    }
    $re=str_replace("\\*",".*",preg_quote($needle,"#"));
    return preg_match("#$re#",$hay)==1;
}


// ---------------------------------------------------------------------------
// Rendu : squelette de l'onglet
// ---------------------------------------------------------------------------

function table_start():bool{
    $page=CurrentPageName();
    $serviceid=intval($_GET["service"]);
    $tpl=new template_admin();

    // Une seule grille : tuiles (ligne 1), barre d'outils (ligne 2), tableau
    // (ligne 3). search_block reste le vrai moteur de recherche ; ses deux
    // conteneurs passent en display:contents pour que son champ se range dans
    // la barre d'outils et son div de resultats sous elle (ngxp_css()).
    $html[]="<div class='ngxp-host'><div class='ngxp ak' id='ngxp-$serviceid'>";
    $html[]="<div class='ngxp-kpis' id='ngxp-kpis-$serviceid'></div>";
    $html[]="<div class='ngxp-toolbar'></div>";
    $html[]="<div class='ngxp-actions' id='ngxp-actions-$serviceid'></div>";
    $html[]="<span hidden id='ngxp-ph-$serviceid'>{ngx_paths_search}</span>";
    $body=$tpl->_ENGINE_parse_body(@implode("\n",$html));

    // search_block() traduit deja ses propres jetons et porte du JS : il ne
    // repasse pas par le moteur.
    $search=$tpl->search_block($page,null,null,null,"&table=$serviceid");

    // Le texte d'aide du champ est lu depuis un span : le navigateur y a deja
    // decode les entites HTML de la traduction.
    $script="<script>(function(){var r=document.getElementById('ngxp-$serviceid');if(!r){return;}"
        ."var i=r.querySelector('.sb-stitch input.form-control');var p=document.getElementById('ngxp-ph-$serviceid');"
        ."if(i&&p){i.setAttribute('placeholder',p.textContent);}})();</script>";

    echo ngxp_css().$body.$search."</div></div>".$script;
    return true;
}

// Repondeur de search_block : tableau + tuiles + barre d'actions, dans cet ordre.
function table():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["table"]);
    $function=ngxp_function();
    $search=isset($_GET["search"])?trim((string)$_GET["search"]):"";
    if($search=="*"){$search="";}

    $ctx=ngxp_context($serviceid);
    $paths=ngxp_collect($serviceid,$ctx);

    $rows=array();
    if($search==""){
        $rows[]=ngxp_row_bulk($serviceid,$function,$ctx);
    }
    $shown=0;
    foreach ($paths as $p){
        if(!ngxp_search_match($p,$search)){continue;}
        $shown++;
        $rows[]=ngxp_row_path($p,$serviceid,$function,$ctx);
    }
    if($shown==0){
        $rows[]=ngxp_row_empty(count($paths),$search);
    }

    $html=array();
    if($ctx["live_error"]<>""){
        $html[]=$tpl->div_error($ctx["live_error"]);
    }
    $html[]="<div class='ak-card'><div class='ngxp-scroll'><table class='ngxp-tbl'>";
    $html[]=ngxp_table_head();
    $html[]="<tbody>".@implode("\n",$rows)."</tbody>";
    $html[]="</table></div>";
    $html[]=ngxp_table_foot($shown,count($paths),$serviceid);
    $html[]="</div>";
    // Tuiles et boutons sont calcules ici, sur les memes donnees que le tableau,
    // puis deplaces dans leurs emplacements : un seul aller-retour vers le
    // daemon, et ils suivent chaque rafraichissement (sauvegarde, suppression,
    // bascule). Les tuiles portent sur TOUS les chemins, pas sur le filtre.
    $html[]="<div hidden id='ngxp-kpis-src-$serviceid'>".ngxp_kpis($paths,$ctx)."</div>";
    $html[]="<div hidden id='ngxp-actions-src-$serviceid'>".ngxp_actions_bar($serviceid,$function,$ctx)."</div>";

    $script="<script>NoSpinner();(function(){"
        ."function mv(a,b){var s=document.getElementById(a),d=document.getElementById(b);if(!s){return;}"
        ."if(d){d.innerHTML='';while(s.firstChild){d.appendChild(s.firstChild);}}s.parentNode.removeChild(s);}"
        ."mv('ngxp-kpis-src-$serviceid','ngxp-kpis-$serviceid');"
        ."mv('ngxp-actions-src-$serviceid','ngxp-actions-$serviceid');"
        ."})();</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
    return true;
}

// ?items=ID : rafraichit la seule zone des ACL d'une ligne, appele par
// fw.nginx.directories.items.php apres un ajout ou une suppression d'element.
// Avant : un print_r() brut du tableau atterrissait dans la ligne.
function items_fragment():bool{
    $ID=intval($_GET["items"]);
    $tpl=new template_admin();
    $ligne=fetch_ngx_directory($ID);
    if(count($ligne)==0){return true;}
    echo $tpl->_ENGINE_parse_body(ngxp_access_badges(intval(ngxp_val($ligne,"deny",0)),items($ID)));
    return true;
}


// ---------------------------------------------------------------------------
// Rendu : tuiles
// ---------------------------------------------------------------------------

function ngxp_kpi_tile($label,$value,$icon,$tone):string{
    return "<div class='ak-fig $tone'><div class='ak-fig-txt'>"
        ."<span class='ak-fig-l'>$label</span>"
        ."<span class='ak-fig-v'>$value</span>"
        ."</div><span class='ak-ico'><i class='$icon'></i></span></div>";
}

function ngxp_kpi_total($paths):string{
    return ngxp_kpi_tile("{ngx_paths_kpi_total}",count($paths),"fa-solid fa-folder-tree","ak-off");
}

function ngxp_kpi_live($paths,$ctx):string{
    if($ctx["harmp"] || $ctx["live_error"]<>""){
        return ngxp_kpi_tile("{ngx_paths_kpi_live}","<span class='ngxp-na'>&mdash;</span>","fa-regular fa-circle-check","ak-accent");
    }
    $live=0;
    foreach ($paths as $p){if($p["live"]){$live++;}}
    return ngxp_kpi_tile("{ngx_paths_kpi_live}","$live <small>/ ".count($paths)."</small>","fa-regular fa-circle-check","ak-accent");
}

// Chemins ACTIFS en « interdire a tous (sauf) » : ce qui filtre reellement.
function ngxp_kpi_strict($paths):string{
    $n=0;
    foreach ($paths as $p){
        if($p["enabled"]==1 && $p["deny"]==1){$n++;}
    }
    return ngxp_kpi_tile("{ngx_paths_kpi_strict}","$n <small>{ngx_paths_unit}</small>","fa-solid fa-lock","ak-err");
}

// Part des chemins actifs dont les requetes passent par le WAF.
function ngxp_kpi_waf($paths,$ctx):string{
    if(!$ctx["waf_engine"]){
        return ngxp_kpi_tile("{ngx_paths_kpi_waf}","<span class='ngxp-na'>&mdash;</span> <small>{disabled}</small>","fa-solid fa-shield-halved","ak-info");
    }
    $enabled=0;
    $covered=0;
    foreach ($paths as $p){
        if($p["enabled"]<>1){continue;}
        $enabled++;
        if($p["wafwhite"]==0){$covered++;}
    }
    if($enabled==0){
        return ngxp_kpi_tile("{ngx_paths_kpi_waf}","<span class='ngxp-na'>&mdash;</span> <small class='is-on'>{enabled}</small>","fa-solid fa-shield-halved","ak-info");
    }
    $pct=intval(round($covered*100/$enabled));
    return ngxp_kpi_tile("{ngx_paths_kpi_waf}","$pct% <small class='is-on'>{enabled}</small>","fa-solid fa-shield-halved","ak-info");
}

function ngxp_kpis($paths,$ctx):string{
    return ngxp_kpi_total($paths).ngxp_kpi_live($paths,$ctx).ngxp_kpi_strict($paths).ngxp_kpi_waf($paths,$ctx);
}


// ---------------------------------------------------------------------------
// Rendu : barre d'actions
// ---------------------------------------------------------------------------

function ngxp_btn_new_path($serviceid,$function,$ctx):string{
    if(!$ctx["can_write"]){return "";}
    $page=CurrentPageName();
    $js="Loadjs('$page?directory-js=0&serviceid=$serviceid&md5=&function=$function');";
    return "<a href=\"javascript:blur();\" class='ak-btn ak-btn-lg ak-btn-primary' OnClick=\"$js\"><i class='".ico_plus."'></i><span>{new_path}</span></a>";
}

function ngxp_btn_build($serviceid,$function,$ctx):string{
    if($ctx["harmp"]){return "";}
    $js="Loadjs('fw.nginx.apply.php?serviceid=$serviceid&function=$function&addjs=');";
    return "<a href=\"javascript:blur();\" class='ak-btn ak-btn-lg ngxp-btn-build' OnClick=\"$js\"><i class='fa-solid fa-download'></i><span>{build_configuration}</span></a>";
}

function ngxp_actions_bar($serviceid,$function,$ctx):string{
    return ngxp_btn_new_path($serviceid,$function,$ctx).ngxp_btn_build($serviceid,$function,$ctx);
}


// ---------------------------------------------------------------------------
// Rendu : tableau
// ---------------------------------------------------------------------------

function ngxp_table_head():string{
    return "<thead><tr>"
        ."<th class='ngxp-c-status'>{ngx_paths_col_status}</th>"
        ."<th>{ngx_paths_col_rules}</th>"
        ."<th class='ngxp-c-waf'>{ngx_paths_col_waf}</th>"
        ."<th class='ngxp-c-toggle'>{enabled}</th>"
        ."<th class='ngxp-c-acts'>{ngx_paths_col_actions}</th>"
        ."</tr></thead>";
}

function ngxp_table_foot($shown,$total,$serviceid):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $text=sprintf($tpl->_ENGINE_parse_body("{ngx_paths_showing}"),"<strong>$shown</strong>","<strong>$total</strong>");
    $export="";
    if(ngxp_can_read()){
        $export="<a href=\"javascript:blur();\" OnClick=\"document.location.href='$page?export-json=$serviceid';\"><i class='".ico_export."'></i>{ngx_paths_export_json}</a>";
    }
    return "<div class='ngxp-foot'><span>$text</span>$export</div>";
}

function ngxp_row_empty($total,$search):string{
    $text="{ngx_paths_empty}";
    if($total>0 && $search<>""){$text="{ngx_paths_no_match}";}
    return "<tr><td colspan='5' class='ngxp-empty'>$text</td></tr>";
}

// Redirections en masse du site : une pseudo-ligne en tete, sans suppression
// ni bascule (elles se reglent dans leur propre modale).
function ngxp_row_bulk($serviceid,$function,$ctx):string{
    $tpl=new template_admin();
    $js="Loadjs('fw.nginx.bulkredirects.php?function=$function&service-js=$serviceid')";
    $count=sprintf($tpl->_ENGINE_parse_body("{redirectsCounts}"),$ctx["bulk_count"]);
    $status=ngxp_badge_status($ctx["bulk_on"]);
    $switch=ngxp_switch($ctx["bulk_on"],"",false);

    $h="<tr".($ctx["bulk_on"]?"":" class='is-off'").">";
    $h.="<td class='ngxp-c-status'>$status</td>";
    $h.="<td><div class='ngxp-line'>"
        ."<a href=\"javascript:blur();\" class='ngxp-flag' OnClick=\"$js\"><i class='fa-solid fa-shuffle'></i>{redirects}</a>"
        ."<span class='ngxp-count'>$count</span></div></td>";
    $h.="<td class='ngxp-c-waf'><span class='ngxp-waf-na'>{ngx_paths_waf_na}</span></td>";
    $h.="<td class='ngxp-c-toggle'>$switch</td>";
    $h.="<td class='ngxp-c-acts'><div class='ngxp-acts'>".ngxp_act_edit($js)."</div></td>";
    $h.="</tr>";
    return $h;
}

function ngxp_row_path($p,$serviceid,$function,$ctx):string{
    $md5=md5("ngxp-{$p["ID"]}");
    $h="<tr id='$md5'".($p["enabled"]==1?"":" class='is-off'").">";
    $h.=ngxp_cell_status($p,$ctx);
    $h.=ngxp_cell_path($p,$serviceid,$function,$md5);
    $h.=ngxp_cell_waf($p,$ctx);
    $h.=ngxp_cell_enabled($p,$function,$ctx);
    $h.=ngxp_cell_actions($p,$serviceid,$function,$md5,$ctx);
    $h.="</tr>";
    return $h;
}

function ngxp_badge_status($on):string{
    if($on){return "<span class='ak-pill ak-ok'><span class='ak-dot'></span>{active2}</span>";}
    return "<span class='ak-pill ak-off'><span class='ak-dot'></span>{inactive2}</span>";
}

// Statut = presence du chemin dans la configuration en service (checkdirs).
// Inconnu en HARMP ou si le daemon n'a pas repondu : un tiret, jamais
// « inactif » par defaut.
function ngxp_cell_status($p,$ctx):string{
    if($ctx["harmp"] || $ctx["live_error"]<>""){
        return "<td class='ngxp-c-status'><span class='ngxp-na'>&mdash;</span></td>";
    }
    return "<td class='ngxp-c-status'>".ngxp_badge_status($p["live"])."</td>";
}

function ngxp_cell_path($p,$serviceid,$function,$md5):string{
    $ID=$p["ID"];
    $page=CurrentPageName();
    $js="Loadjs('$page?directory-js=$ID&serviceid=$serviceid&md5=$md5&function=$function')";

    $line="<a href=\"javascript:blur();\" class='ngxp-path' OnClick=\"$js\">".ngxp_h($p["directory"])."</a>";
    if($p["directory"]=="/"){
        $line.="<span class='ngxp-bad'>{bad_configuration} &laquo;/&raquo;</span>";
    }
    $line.="<span class='ngxp-access' id='itemsofdir-$ID'>".ngxp_access_badges($p["deny"],$p["items"])."</span>";
    $line.=ngxp_path_flags($p,$serviceid,$function);

    return "<td><div class='ngxp-line'>$line</div>".ngxp_path_target($p).ngxp_path_options($p)."</td>";
}

// Regle d'acces du chemin : un badge + une puce par element d'ACL.
function ngxp_access_badges($deny,$items):string{
    $count=count($items);
    if($count==0){
        if($deny==1){return "<span class='ak-tag ak-err'>{deny_all}</span>";}
        return "<span class='ak-tag ak-info'>{allow_all}</span>";
    }
    $h="<span class='ak-tag ak-warn'>{allow_access_except}</span>";
    if($deny==1){$h="<span class='ak-tag ak-err'>{deny_access_except}</span>";}
    // Au-dela de 4 elements, une puce « +N » porte le reste dans son infobulle :
    // une ACL de 200 adresses ne doit pas faire 30 lignes de haut.
    $max=4;
    for($i=0;$i<$count && $i<$max;$i++){
        $h.="<span class='ak-chip'>".ngxp_h($items[$i])."</span>";
    }
    if($count>$max){
        $rest=ngxp_h(implode(", ",array_slice($items,$max)));
        $h.="<span class='ak-count' title=\"$rest\">+".($count-$max)."</span>";
    }
    return $h;
}

function ngxp_path_flags($p,$serviceid,$function):string{
    $h="";
    if($p["admins_redirector"]==1){
        $h.="<span class='ngxp-flag'><i class='fa-brands fa-windows t-win'></i>{admins_redirector}</span>";
    }
    if($p["sslclient"]==1){
        $h.="<span class='ngxp-flag' title=\"{verify_client_certificate}\"><i class='".ico_user_lock."'></i>{client_certificate}</span>";
    }
    if($p["iscontent"]==1){
        $js="Loadjs('fw.nginx.directories.content.php?directory_id={$p["ID"]}&function=$function&serviceid=$serviceid')";
        $h.="<a href=\"javascript:blur();\" class='ngxp-flag' OnClick=\"$js\"><i class='".ico_html."'></i>{content}</a>";
    }
    return $h;
}

// Ou part la requete : redirection, back-ends, ou rien.
function ngxp_path_target($p):string{
    $arrow="<i class='fa-solid fa-arrow-right ngxp-arrow'></i>";
    if($p["redirect"]<>""){
        $label="{redirect_to}";
        if(substr($p["redirect"],0,1)=="/"){$label="{redirect_to} {alias}";}
        return "<div class='ngxp-target'>$arrow<span class='ngxp-target-lbl'>$label</span><span class='ngxp-url'>".ngxp_h($p["redirect"])."</span></div>";
    }
    if(count($p["backends"])>0){
        $urls="";
        foreach ($p["backends"] as $url){
            $urls.="<span class='ngxp-url'>".ngxp_h($url)."</span>";
        }
        return "<div class='ngxp-target'>$arrow<span class='ngxp-target-lbl'>{backend}</span>$urls</div>";
    }
    // Un chemin qui sert du contenu, qui relaie le service mTLS d'administration,
    // ou qui interdit tout, n'a pas besoin de back-end.
    if($p["iscontent"]==1){return "";}
    if($p["admins_redirector"]==1){return "";}
    if($p["deny"]==1 && count($p["items"])==0){return "";}
    return "<div class='ngxp-hint'><i class='".ico_info."'></i>{ngx_paths_no_backend}</div>";
}

// Reglages secondaires, en petites puces : ce que l'ancienne ligne ecrivait en
// toutes lettres sous le chemin.
function ngxp_path_options($p):string{
    $o=array();
    if(count($p["backends"])>0){
        // 1.0 est la valeur par defaut : seule une version differente merite une puce.
        if($p["http_version"]<>"1.0"){
            $o[]="<span class='ngxp-opt' title=\"{proxy_http_version}\">HTTP/".ngxp_h($p["http_version"])."</span>";
        }
        if($p["websockets"]==1){
            $o[]="<span class='ngxp-opt' title=\"{websockets_support}\">WebSockets</span>";
        }
        if($p["host_header"]<>""){
            $o[]="<span class='ngxp-opt'>Host: ".ngxp_h($p["host_header"])."</span>";
        }
    }
    if($p["cachetime"]>0){
        $args=$p["cacheargs"]==1?" (+args)":"";
        $o[]="<span class='ngxp-opt' title=\"{cachetime}\"><i class='".ico_database."'></i> {$p["cachetime"]} {minutes}$args</span>";
    }
    $bc=array();
    if($p["bcacheimages"]>0){$bc[]="{cache_images}: {$p["bcacheimages"]}h";}
    if($p["bcachehtml"]>0){$bc[]="{cache_htmlext}: {$p["bcachehtml"]}h";}
    if($p["bcachebinaries"]>0){$bc[]="{cache_binaries}: {$p["bcachebinaries"]}h";}
    if(count($bc)>0){
        $o[]="<span class='ngxp-opt' title=\"".implode(" / ",$bc)."\">{browser_caching}</span>";
    }
    if($p["content_type"]<>""){
        $o[]="<span class='ngxp-opt'>Content-Type: ".ngxp_h($p["content_type"])."</span>";
    }
    if(count($o)==0){return "";}
    return "<div class='ngxp-opts'>".implode("",$o)."</div>";
}

function ngxp_cell_waf($p,$ctx):string{
    if(!$ctx["waf_engine"]){
        return "<td class='ngxp-c-waf'><span class='ngxp-waf-na'>{ngx_paths_waf_na}</span></td>";
    }
    if($p["wafwhite"]==1){
        return "<td class='ngxp-c-waf'><span class='ak-tag ak-off' title=\"{wafwhitepathoff}\">{ngx_paths_waf_off}</span></td>";
    }
    return "<td class='ngxp-c-waf'><span class='ak-tag ak-ok' title=\"{wafwhitepathon}\">{ngx_paths_waf_on}</span></td>";
}

// Interrupteur : un lien, pas une case a cocher -- il ne bascule jamais de lui-
// meme. Le handler repond par le rafraichissement du tableau, qui le redessine
// depuis la base ; une confirmation annulee le laisse donc exact.
function ngxp_switch($on,$js,$writable):string{
    $cls="ak-sw".($on?" on":"");
    $aria=$on?"true":"false";
    if(!$writable || $js==""){
        return "<span class='$cls ak-locked' role='switch' aria-checked='$aria' aria-disabled='true'></span>";
    }
    return "<a href=\"javascript:blur();\" class='$cls' role='switch' aria-checked='$aria' OnClick=\"$js\"></a>";
}

function ngxp_cell_enabled($p,$function,$ctx):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?enabled={$p["ID"]}&function=$function')";
    return "<td class='ngxp-c-toggle'>".ngxp_switch($p["enabled"]==1,$js,$ctx["can_write"])."</td>";
}

function ngxp_act_edit($js):string{
    return "<a href=\"javascript:blur();\" class='ak-act' title=\"{modify}\" OnClick=\"$js\"><i class='fa-regular fa-pen'></i></a>";
}

function ngxp_act_delete($js):string{
    return "<a href=\"javascript:blur();\" class='ak-act ak-danger' title=\"{delete}\" OnClick=\"$js\"><i class='fa-regular fa-trash-can'></i></a>";
}

function ngxp_cell_actions($p,$serviceid,$function,$md5,$ctx):string{
    $page=CurrentPageName();
    $ID=$p["ID"];
    $h=ngxp_act_edit("Loadjs('$page?directory-js=$ID&serviceid=$serviceid&md5=$md5&function=$function')");
    if($ctx["can_write"]){
        $h.=ngxp_act_delete("Loadjs('$page?delete-js=$ID&function=$function')");
    }
    return "<td class='ngxp-c-acts'><div class='ngxp-acts'>$h</div></td>";
}


// ---------------------------------------------------------------------------
// Export JSON
// ---------------------------------------------------------------------------

function export_json():bool{
    $serviceid=intval($_GET["export-json"]);
    if(!ngxp_can_read()){
        header("HTTP/1.1 403 Forbidden");
        header("Content-Type: text/plain; charset=utf-8");
        echo "Forbidden";
        return false;
    }
    $ctx=ngxp_context($serviceid);
    $paths=ngxp_collect($serviceid,$ctx);
    $sock=new socksngix($serviceid);

    $out=array(
        "serviceid"=>$serviceid,
        "servicename"=>$sock->GetServiceName(),
        "exported_at"=>date("c"),
        "bulk_redirects"=>array("enabled"=>$ctx["bulk_on"],"count"=>$ctx["bulk_count"]),
        "paths"=>array(),
    );
    foreach ($paths as $p){
        $out["paths"][]=array(
            "id"=>$p["ID"],
            "path"=>$p["directory"],
            "enabled"=>$p["enabled"]==1,
            "live"=>($ctx["harmp"] || $ctx["live_error"]<>"")?null:$p["live"],
            "access"=>array(
                "default"=>$p["deny"]==1?"deny":"allow",
                "except"=>$p["items"],
            ),
            "redirect"=>$p["redirect"],
            "backends"=>$p["backends"],
            "proxy_http_version"=>$p["http_version"],
            "websockets"=>$p["websockets"]==1,
            "host_header"=>$p["host_header"],
            "waf"=>$ctx["waf_engine"]?($p["wafwhite"]==0):null,
            "verify_client_certificate"=>$p["sslclient"]==1,
            "admins_redirector"=>$p["admins_redirector"]==1,
            "content"=>$p["iscontent"]==1,
            "content_type"=>$p["content_type"],
            "cache"=>array(
                "minutes"=>$p["cachetime"],
                "with_arguments"=>$p["cacheargs"]==1,
            ),
            "browser_cache_hours"=>array(
                "images"=>$p["bcacheimages"],
                "html"=>$p["bcachehtml"],
                "binaries"=>$p["bcachebinaries"],
            ),
        );
    }
    $json=json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR);
    header("Content-Type: application/json; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"reverse-proxy-$serviceid-paths.json\"");
    header("X-Content-Type-Options: nosniff");
    header("Cache-Control: no-store");
    echo $json;
    return true;
}


// ---------------------------------------------------------------------------
// Feuille de style (hors _ENGINE_parse_body : chaque « {…} » y serait un jeton)
// ---------------------------------------------------------------------------

// Accents de la maquette (emeraude), remplaces par ceux de la marque quand une
// marque blanche est posee -- meme regle que table_form_*.
function ngxp_palette():array{
    $p=array(
        "PRIMARY"=>"#059669",
        "RING"=>"rgba(16,185,129,.25)",
    );
    if(!function_exists("brand_palette") || !function_exists("brand_ui_accents") || !function_exists("brand_shade")){
        return $p;
    }
    $bp=brand_palette();
    if(empty($bp["colors_on"])){return $p;}
    $ac=brand_ui_accents($bp);
    $p["PRIMARY"]=$ac["accent"];
    $p["RING"]=$ac["accent_soft"];
    return $p;
}

function ngxp_css():string{
    if(isset($GLOBALS["NGXP_CSS_DONE"])){return "";}
    $GLOBALS["NGXP_CSS_DONE"]=true;
    $css=<<<'CSS'
<style>
.ngxp-host{container-type:inline-size}
.ngxp{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,448px);column-gap:16px;margin-top:15px;color:#1e293b}
.ngxp-kpis{grid-column:1/-1;grid-row:1;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px;min-height:72px}
.ngxp-kpis .ak-fig-v{color:var(--ak-tfg);white-space:nowrap}
.ngxp-kpis .ak-fig-v small{font-size:13px;font-weight:400;color:#94a3b8;margin-left:2px}
.ngxp-kpis .ak-fig-v small.is-on{color:%PRIMARY%;font-weight:600}
.ngxp .ngxp-na{color:#94a3b8;font-weight:400}
.ngxp-toolbar{grid-column:1/-1;grid-row:2;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.ngxp-actions{grid-column:1;grid-row:2;align-self:center;display:flex;flex-wrap:wrap;align-items:center;gap:12px;padding:16px 0 16px 16px;min-height:72px}
.ngxp-btn-build,.ngxp-btn-build:hover{background:#fef3c7;color:#78350f !important;border-color:#fcd34d}
.ngxp-btn-build:hover{background:#fde68a}
.ngxp-btn-build .svg-inline--fa,.ngxp-btn-build i{color:#b45309}
.ngxp .sb-stitch,.ngxp .sb-stitch>.ibox-content{display:contents}
/* Le clearfix de .row genere des pseudo-elements qui, sous display:contents, deviendraient des cellules de la grille. */
.ngxp .sb-stitch:before,.ngxp .sb-stitch:after,.ngxp .sb-stitch>.ibox-content:before,.ngxp .sb-stitch>.ibox-content:after{content:none !important;display:none !important}
.ngxp .sb-stitch .input-group{grid-column:2;grid-row:2;align-self:center;position:relative;display:flex !important;align-items:center;gap:8px;width:auto;max-width:none;height:auto;margin:16px 16px 16px 0;padding:0;border:0;border-radius:0;background:transparent;box-shadow:none}
.ngxp .sb-stitch .input-group:focus-within{box-shadow:none}
.ngxp .sb-stitch .sb-ico{position:absolute;left:0;top:0;bottom:0;display:flex;align-items:center;padding:0 0 0 13px;color:#94a3b8;font-size:14px;pointer-events:none;z-index:3}
.ngxp .sb-stitch .form-control{flex:1 1 0%;width:auto !important;min-width:0;float:none;height:38px !important;line-height:20px !important;padding:8px 12px 8px 38px !important;font-size:14px !important;color:#1e293b;background:#f8fafc !important;border:1px solid #cbd5e1 !important;border-radius:8px !important;box-shadow:none !important;transition:background .15s,border-color .15s,box-shadow .15s}
.ngxp .sb-stitch .form-control::placeholder{color:#94a3b8}
.ngxp .sb-stitch .form-control:focus{background:#fff !important;border-color:%PRIMARY% !important;box-shadow:0 0 0 3px %RING% !important}
.ngxp .sb-stitch .input-group-btn{display:flex !important;flex:0 0 auto;width:auto}
.ngxp .sb-stitch .sb-go{height:38px !important;line-height:38px !important;padding:0 14px !important;border-radius:8px !important;background:#1e293b !important;color:#fff !important;font-size:13px !important;font-weight:600;letter-spacing:.02em;text-transform:uppercase}
.ngxp .sb-stitch .sb-go:hover{background:#0f172a !important}
.ngxp .sb-stitch .ibox-content>div[id^='results-']{grid-column:1/-1;grid-row:3;margin-top:22px !important;min-width:0}
.ngxp-scroll{overflow-x:auto}
.ngxp-tbl{width:100%;border-collapse:collapse;text-align:left;color:#1e293b}
.ngxp-tbl thead{background:rgba(248,250,252,.75)}
.ngxp-tbl thead th{padding:14px 16px;font-size:12px;line-height:16px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;border-bottom:1px solid #e2e8f0}
.ngxp-tbl th:first-child,.ngxp-tbl td:first-child{padding-left:24px}
.ngxp-tbl th:last-child,.ngxp-tbl td:last-child{padding-right:24px}
.ngxp-tbl tbody tr{border-top:1px solid #f1f5f9;transition:background .15s}
.ngxp-tbl tbody tr:first-child{border-top:0}
.ngxp-tbl tbody tr:hover{background:rgba(248,250,252,.7)}
.ngxp-tbl tbody td{padding:16px;vertical-align:top}
.ngxp-tbl .ngxp-c-status{width:96px;white-space:nowrap}
.ngxp-tbl .ngxp-c-waf{white-space:nowrap}
.ngxp-tbl .ngxp-c-toggle{width:104px;text-align:center;white-space:nowrap}
.ngxp-tbl .ngxp-c-acts{width:104px;text-align:right;white-space:nowrap}
.ngxp-line{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.ngxp-path{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;line-height:18px;font-weight:700;color:#0f172a !important;background:#f1f5f9;padding:3px 10px;border-radius:4px;border:1px solid #e2e8f0;text-decoration:none !important;word-break:break-all;transition:border-color .15s,background .15s}
a.ngxp-path:hover{border-color:%PRIMARY%;background:#fff}
.ngxp-tbl tr.is-off .ngxp-path{color:#64748b !important;font-weight:600}
.ngxp-access{display:inline-flex;flex-wrap:wrap;align-items:center;gap:6px}
.ngxp-flag{display:inline-flex;align-items:center;gap:6px;font-size:12px;line-height:16px;font-weight:500;color:#334155 !important;background:#f1f5f9;padding:2px 8px;border-radius:4px;border:1px solid #e2e8f0;white-space:nowrap;text-decoration:none !important}
a.ngxp-flag:hover{border-color:#94a3b8;background:#fff}
.ngxp-flag .t-win{color:#2563eb}
.ngxp-count{font-size:14px;font-weight:500;color:#334155}
.ngxp-tbl tr.is-off .ngxp-count{color:#64748b}
.ngxp-bad{font-size:12px;line-height:16px;font-weight:600;color:#fff;background:#dc2626;padding:2px 8px;border-radius:4px;white-space:nowrap}
.ngxp-target{margin-top:6px;display:flex;flex-wrap:wrap;align-items:center;gap:6px;font-size:12px;line-height:16px;color:#64748b}
.ngxp-target-lbl{font-size:11px;color:#94a3b8}
.ngxp-arrow{color:#94a3b8}
.ngxp-url{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;line-height:16px;color:#334155;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;padding:2px 6px;word-break:break-all}
.ngxp-hint{margin-top:6px;display:flex;align-items:center;gap:6px;font-size:12px;line-height:16px;color:#94a3b8}
.ngxp-opts{margin-top:6px;display:flex;flex-wrap:wrap;gap:6px}
.ngxp-opt{display:inline-flex;align-items:center;gap:4px;font-size:11px;line-height:15px;color:#475569;background:#fff;border:1px solid #e2e8f0;border-radius:4px;padding:1px 6px;white-space:nowrap}
.ngxp-opt .svg-inline--fa,.ngxp-opt i{color:#94a3b8}
.ngxp-waf-na{font-size:12px;font-style:italic;color:#94a3b8;white-space:nowrap}
.ngxp-acts{display:inline-flex;align-items:center;justify-content:flex-end;gap:4px}
.ngxp a.ak-act.ak-danger{color:#f43f5e}
.ngxp-foot{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;padding:12px 24px;background:rgba(248,250,252,.8);border-top:1px solid #e2e8f0;font-size:12px;line-height:16px;color:#64748b}
.ngxp-foot strong{color:#334155}
.ngxp-foot a{display:inline-flex;align-items:center;gap:6px;color:#475569 !important;font-weight:500;text-decoration:none !important}
.ngxp-foot a:hover{color:#0f172a !important}
.ngxp-tbl td.ngxp-empty{padding:32px 24px;text-align:center;color:#64748b;font-size:14px}
@container (max-width:1000px){
 .ngxp-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@container (max-width:820px){
 .ngxp{grid-template-columns:minmax(0,1fr)}
 .ngxp-toolbar{grid-row:2/4}
 .ngxp-actions{padding:16px 16px 0}
 .ngxp .sb-stitch .input-group{grid-column:1;grid-row:3;margin:12px 16px 16px}
 .ngxp .sb-stitch .ibox-content>div[id^='results-']{grid-row:4}
 .ngxp-tbl .ngxp-c-waf{display:none}
}
</style>
CSS;
    $p=ngxp_palette();
    foreach ($p as $key=>$value){
        $css=str_replace("%$key%",$value,$css);
    }
    return $css;
}
