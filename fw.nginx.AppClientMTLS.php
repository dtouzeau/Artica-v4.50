<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// cert_view_*() : vue en trois onglets d'un certificat, partagee avec fw.certificates-center.php
include_once(dirname(__FILE__)."/ressources/class.certificates.view.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["acm-host"])){acm_host();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_POST["mtls_listen_save"])){parameters_save();exit;}
if(isset($_GET["generate-js"])){generate_js();exit;}
if(isset($_GET["generate-popup"])){generate_popup();exit;}
if(isset($_POST["email"]) && (isset($_POST["service_ids"]) || isset($_POST["service_id"]))){generate_save();exit;}
if(isset($_POST["delete-config"])){delete_perform();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["certificate-view-js"])){certificate_view_js();exit;}
if(isset($_GET["certificate-view-popup"])){certificate_view_popup();exit;}
if(isset($_GET["certificate-view-overview"])){certificate_view_overview();exit;}
if(isset($_GET["certificate-view-key"])){certificate_view_key();exit;}
if(isset($_GET["certificate-view-raw"])){certificate_view_raw();exit;}
if(isset($_GET["start"])){start();exit;}
page();

/**
 * Droit d'ecriture sur cette page.
 *
 * Les boutons sont rendus sous "AsWebMaster" (icon_download, icon_delete,
 * form_outside) mais les handlers ne le verifiaient pas: un ?download=<ID>
 * tape a la main rendait le .enc -- donc la CLE PRIVEE du certificat client,
 * laissez-passer vers l'API complete du daemon -- a n'importe quel utilisateur
 * de console authentifie. La garde est posee dans chaque handler d'ecriture et
 * de telechargement, comme le fait fw.nginx.directories.php (ngxp_can_write).
 */
function mtls_can_write():bool{
    if(isset($GLOBALS["MTLS_CAN_WRITE"])){return $GLOBALS["MTLS_CAN_WRITE"];}
    $users=new usersMenus();
    $GLOBALS["MTLS_CAN_WRITE"]=(bool)$users->AsWebMaster;
    return $GLOBALS["MTLS_CAN_WRITE"];
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{admin_clients}",ico_admin,
        "{acm_page_explain}","$page?start=yes",
        "reverse-proxy-admin-clients","progress-appreverse-restart",false);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{admin_clients}",$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);
}

// Contenu de la page (maquette stitch, refonte 2026-11-19) : un hote recharge apres chaque
// action (AcmRefresh), le TINY emis une seule fois ici.
function start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo acm_style();
    echo "<div id='acm-host'>";
    acm_host();
    echo "</div>";
    echo acm_js().acm_tiny();
}

/**
 * Nom de la fonction JavaScript de rafraichissement fournie par search_block.
 *
 * Liste blanche au point de lecture: cette valeur repart telle quelle dans un
 * OnClick et dans le $jsafter de form_outside, donc dans du JavaScript execute.
 * search_block n'envoie jamais qu'un "ss<horodatage>", mais l'URL est publique.
 */
function mtls_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}

function _fetch_sites_hash(){
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/ids");
    $json=json_decode($raw);
    $sites=array();
    if(isset($json->Status) && $json->Status && isset($json->sites)){
        foreach($json->sites as $id=>$host){
            $sites[intval($id)]=htmlspecialchars($host)." (#$id)";
        }
    }
    return $sites;
}

// Noms des sites reverse-proxy par identifiant (/sites/ids), sans le suffixe
// « (#id) » que _fetch_sites_hash() ajoute pour le selecteur du formulaire.
// Tableau vide = liste indisponible OU aucun site : l'appelant garde alors les
// identifiants tels quels plutot que d'afficher une colonne vide.
function mtls_site_names():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/ids"));
    $out=array();
    if(isset($json->Status) && $json->Status && isset($json->sites)){
        foreach($json->sites as $id=>$host){
            $id=intval($id);
            $host=trim((string)$host);
            if($id<=0 || $host==""){continue;}
            $out[$id]=$host;
        }
    }
    return $out;
}

/**
 * Chemins de redirector armes, tels que le daemon les voit.
 *
 * $Status==false n'est PAS "aucun chemin arme": c'est "on ne sait pas". Le
 * champ "reached" distingue les deux cas pour l'appelant, qui ne doit jamais
 * afficher une liste vide comme si elle etait autoritaire quand la route est
 * injoignable ou en erreur (meme discipline que le reste du module: une
 * information qu'on n'a pas pu obtenir ne se deguise pas en information
 * rassurante).
 *
 * Retourne array("reached"=>bool,"paths"=>array(path=>ArmedPath stdClass)).
 */
/**
 * Premier hote REEL d'une valeur "hosts" de nginx_services.
 *
 * Le daemon rend cette colonne BRUTE -- AdminsRedirector/Paths.go la lit telle
 * quelle, et son test l'exige (« hosts doit etre rendu brut »). Le parsing
 * incombe donc au consommateur, et le format est double :
 *
 *   - les entrees sont separees par "||"   (cf. fw.nginx.privileges.php,
 *                                           fw.browse-websites.php)
 *   - chaque entree peut porter une notation d'alias "alias>canonique",
 *     dont le COTE GAUCHE est l'hote que le vhost sert reellement.
 *
 * Ce second point n'est pas une interpretation : c'est le choix deja fait par
 * nginxWatchdog/stripAliasNotation(), par Frontends.go (resolution DNS) et par
 * ModSecArticaRules. Le commentaire de stripAliasNotation() documente ce que
 * coute l'oubli -- « http2: invalid Host header » AVANT tout echange reseau,
 * 78 000 erreurs en 23 jours sur une appliance client, sept sites supervises a
 * l'aveugle et un SLA faux.
 *
 * Un "*" (catch-all) est ecarte : ce n'est pas une adresse joignable, et
 * Frontends.go:412 l'ecarte deja pour la meme raison.
 *
 * Rend "" si rien d'exploitable -- l'appelant doit traiter ce cas, jamais
 * retomber sur la valeur brute.
 */
function _admrd_first_host($raw):string{
    $raw=(string)$raw;
    if(trim($raw)===""){return "";}
    // "||" d'abord, puis les separateurs libres historiques.
    $parts=preg_split('#\|\||[\n,;]+#',$raw);
    if(!is_array($parts)){return "";}
    foreach($parts as $part){
        // Notation d'alias : ne garder que la gauche.
        $p=strpos($part,">");
        if($p!==false){$part=substr($part,0,$p);}
        $part=trim($part);
        if($part===""){continue;}
        if(strpos($part,"*")!==false){continue;}   // catch-all : pas joignable
        return $part;
    }
    return "";
}

/**
 * Un target_server acceptable : une IP, ou un nom d'hote DNS.
 *
 * Le controle est ICI et pas seulement dans le JS parce que CLEAN_BAD_XSS()
 * retire "|" et ">" sans rien signaler : une valeur composite arrivant par le
 * POST n'etait pas rejetee, elle etait RECOLLEE en un seul nom absurde qui
 * partait dans l'OID du certificat. Un refus visible vaut mieux qu'un
 * certificat d'apparence normale et inutilisable.
 */
function _admrd_valid_host($h):bool{
    $h=trim((string)$h);
    if($h===""){return false;}
    if(strlen($h)>253){return false;}
    if(filter_var($h,FILTER_VALIDATE_IP)!==false){return true;}
    return (bool)preg_match('#^[A-Za-z0-9]([A-Za-z0-9\-]*[A-Za-z0-9])?(\.[A-Za-z0-9]([A-Za-z0-9\-]*[A-Za-z0-9])?)*$#',$h);
}

function _fetch_admins_redirector_paths(){
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/admins-redirector/paths");
    $json=json_decode($raw);
    $out=array("reached"=>false,"paths"=>array());
    // isset() sur une propriete dont la valeur JSON est null rend FALSE en PHP.
    // {"Status":true,"paths":null} est pourtant la reponse NOMINALE d'une
    // appliance sans chemin arme -- property_exists() + un test explicite de
    // null distinguent enfin les trois cas: route injoignable/erreur (jamais
    // "reached"), Status:true avec paths null/vide (reached, liste vide),
    // Status:true avec des entrees (reached, liste remplie).
    if(is_object($json) && property_exists($json,"Status") && $json->Status){
        $out["reached"]=true;
        if(property_exists($json,"paths") && is_array($json->paths)){
            foreach($json->paths as $p){
                $path=(string)($p->path ?? "");
                if($path==""){continue;} // le "" est reserve a l'acces direct, jamais une entree du daemon
                $out["paths"][$path]=$p;
            }
        }
    }
    return $out;
}

/**
 * Ou ecoute le service mTLS, tel que le daemon le sait (GET /admins-redirector/listen).
 * reached=false quand la route n'a pas repondu Status:true : le lecteur doit le
 * dire, jamais afficher 8080 comme si c'etait un fait (Pattern #54).
 */
function _fetch_mtls_listen(){
    $out=array("reached"=>false,"port"=>8080,"unix_socket"=>false,"socket_path"=>"/var/run/nginx/mtls-admins.sock","running"=>false,"listen"=>"");
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/admins-redirector/listen");
    $json=json_decode($raw);
    if(!isset($json->Status) || !$json->Status){return $out;}
    $out["reached"]=true;
    $out["port"]=intval($json->port ?? 8080);
    $out["unix_socket"]=!empty($json->unix_socket);
    $out["socket_path"]=(string)($json->socket_path ?? $out["socket_path"]);
    $out["running"]=!empty($json->running);
    $out["listen"]=(string)($json->listen ?? "");
    return $out;
}

function parameters_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=mtls_function();
    $tpl->js_dialog7("{parameters}","$page?parameters-popup=yes&function=$function",650);
}

function parameters_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=mtls_function();
    $l=_fetch_mtls_listen();
    $html=array();
    $html[]=$tpl->div_explain("{parameters}||{acm_params_explain}");
    if(!$l["reached"]){
        $html[]=$tpl->div_warning("{admins_mtls_listen_unavailable}");
    }
    $form=array();
    $form[]=$tpl->field_hidden("mtls_listen_save","1");
    $form[]=$tpl->field_numeric("ReverseAppClientsPort","{acm_listen_port}",$l["port"],"{acm_listen_port_help}");
    // L'explication porte le chemin de la socket tel que le daemon le rend :
    // il n'est pas recopie ici, il est LU.
    $form[]=$tpl->field_checkbox("ReverseAppClientsUnixSocket","{listen_unix_socket}",
        $l["unix_socket"]?1:0,false,"{acm_unix_help}<br><span style='font-family:monospace'>".htmlspecialchars($l["socket_path"])."</span>");
    if(!$l["unix_socket"]){
        $form[]=$tpl->div_warning("{acm_unix_warn}");
    }
    $jsafter="dialogInstance7.close();";
    $jsafter.="if(typeof AcmRefresh==='function'){AcmRefresh();}";
    $html[]=$tpl->form_outside("",$form,"","{apply}",$jsafter,"AsWebMaster");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

/**
 * Ecrit l'ecoute. Le daemon relance le service mTLS ET reconstruit les sites
 * qui portent un chemin arme (synchrone) : une erreur remonte ici en popup,
 * jamais en fermeture silencieuse de la fenetre.
 */
function parameters_save(){
    $tpl=new template_admin();
    if(!mtls_can_write()){
        echo $tpl->post_error("{ERROR_NO_PRIVS}");
        return;
    }
    $tpl->CLEAN_POST();
    $data=array(
        "port"        => intval($_POST["ReverseAppClientsPort"] ?? 0),
        "unix_socket" => intval($_POST["ReverseAppClientsUnixSocket"] ?? 0)==1,
    );
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/admins-redirector/listen",$data);
    $json=json_decode($raw);
    if(!is_array((array)$json) || !isset($json->Status)){
        $r=trim(str_replace(array("'","\\"),array(" "," "),(string)$raw));
        echo $tpl->post_error($r==""?"{error}":substr($r,0,300));
        return;
    }
    if(!$json->Status){
        $msg=(string)($json->Error ?? "{error}");
        if(isset($json->failed_sites) && count((array)$json->failed_sites)>0){
            $msg.=" ({rules}: ".implode(",",(array)$json->failed_sites).")";
        }
        echo $tpl->post_error($msg);
        return;
    }
    admin_tracks("mTLS admins listen set to ".(string)($json->listen ?? ""));
}

function generate_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=mtls_function();
    $tpl->js_dialog7("{new_client}","$page?generate-popup=yes&function=$function",700);
}

function generate_popup(){
    $tpl=new template_admin();
    $function=mtls_function();
    $host=(string)($_SERVER["HTTP_HOST"] ?? "");
    if(preg_match('#^\[([0-9a-fA-F:.]+)\](?::[0-9]+)?$#',$host,$re6)){
        // [v6]:port ou [v6] : on garde l'adresse nue
        $host=$re6[1];
    }elseif(filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)){
        // IPv6 nue sans crochets : pas de port a retirer
    }elseif(strpos($host,":")>0){
        $tb=explode(":",$host);
        $host=$tb[0];
    }

    $sites_hash=_fetch_sites_hash();

    // Trois sections, parce que le formulaire repond a trois questions sans
    // rapport: QUOI le certificat a le droit d'administrer, QUI il represente,
    // et OU le client Windows doit se connecter.
    $form=array();
    $form[]=$tpl->field_section("{acm_step_who}");
    // field_email et non field_text: il pose un <input type=email> et refuse
    // l'envoi cote navigateur. Sans lui, une adresse invalide faisait l'aller-
    // retour jusqu'au filter_var() de generate_save() pour revenir en banniere.
    $form[]=$tpl->field_email("email","{acm_email}",null,true,"{acm_email_help}");
    $form[]=$tpl->field_text("username","{username}",null,false,"{acm_username_help}");
    $form[]=$tpl->field_section("{acm_step_sites}");
    if(count($sites_hash)==0){
        // La liste des sites n'est pas revenue du daemon. On garde la saisie
        // manuelle, mais on DIT pourquoi: sans explication, un champ numerique
        // intitule "Regle" avec 1 par defaut se valide sans que personne ne se
        // demande a quel site il correspond.
        $form[]=$tpl->field_numeric("service_id","{rule}",1,"{admin_client_sites_unavailable}");
    }else{
        $form[]=$tpl->field_multiselect($sites_hash,"service_ids","{acm_sites_label}",array(),"{acm_sites_help}");
    }


    // Les chemins armes, tels que le daemon les voit. L'entree vide reste en
    // TETE : sans elle on casserait l'usage de tous ceux qui n'ont pas de
    // redirector. hosts/ports sont rendus BRUTS par la route (separateurs
    // \n , ;) -- c'est le JS ci-dessous qui en tire le premier hote/port.
    $rd=_fetch_admins_redirector_paths();
    $listen=_fetch_mtls_listen();
    $default_port=intval($listen["port"]);
    if($default_port<=0){$default_port=8080;}
    // En mode socket Unix il n'y a AUCUN port TCP a joindre : l'entree
    // « acces direct » disparait, seul un chemin de redirector fonctionne.
    $redirector=array();
    if(!$listen["unix_socket"]){
        $redirector[""]="{direct_access_port_8080}";
    }
    $rdmeta=array();
    foreach($rd["paths"] as $path=>$p){
        $label=htmlspecialchars($p->service_name)." — ".htmlspecialchars($path);
        // La cle devient l'attribut value="" du <option> SANS passer par
        // field_array_hash (seul le libelle y est echappe) -- l'echapper ici.
        // _admRdMeta reste indexe sur $path BRUT: le navigateur decode
        // l'entite HTML de l'attribut avant de rendre $(this).val(), donc la
        // valeur JS lue au changement redevient le chemin original.
        $redirector[htmlspecialchars($path, ENT_QUOTES)]=$label;
        $rdmeta[$path]=array("hosts"=>(string)($p->hosts ?? ""),"ports"=>(string)($p->ports ?? ""));
    }

    $form[]=$tpl->field_section("{acm_step_connect}");
    if(!$rd["reached"]){
        // La route n'a pas repondu Status:true -- on ne sait pas quels chemins
        // sont armes. Le dire, et laisser l'acces direct au port 8080
        // disponible (Pattern #54 -- une lecture ratee n'est pas une absence).
        $form[]=$tpl->div_warning("{admins_redirector_unavailable}");
    }
    if($listen["unix_socket"]){
        $form[]=$tpl->div_info("{direct_access_unavailable_unix_socket}");
    }
    // $defnull=false (8e argument) : sans lui, field_array_hash rajoute une
    // option value="" libellee {default_or_null} DES QUE la cle vide est
    // absente du hash -- exactement le cas du mode socket Unix, ou l'entree
    // « acces direct » vient d'etre retiree. Elle revenait donc preselectionnee,
    // et generate_save() tient le vide pour licite : on generait un .enc en
    // acces direct sur un service qui n'ecoute plus en TCP, juste sous le
    // message disant que c'est impossible. En mode TCP la cle "" est presente,
    // l'option n'etait pas ajoutee : ce passage ne change rien de ce cote.
    $form[]=$tpl->field_array_hash($redirector,"base_path","{admins_redirector}","",false,
              "{acm_redirector_help}","adminsRedirectorChange",false);
    $form[]=$tpl->field_text("target_server","{acm_target_label}",$host,true,"{acm_target_help}");
    $form[]=$tpl->field_numeric("remote_port","{acm_listen_port}",$listen["unix_socket"]?443:$default_port,"{acm_port_help}");
    $form[]=$tpl->field_numeric("max_days","{validity_days}",365,"{acm_validity_help}");

    // Les id md5(name.suffixid) sont stables pour la duree de vie de $tpl (le
    // suffixid est fixe au constructeur) -- on peut donc les recalculer ici
    // pour cibler les deux champs depuis le changement de base_path.
    $id_target=md5("target_server$tpl->suffixid");
    $id_port=md5("remote_port$tpl->suffixid");
    $host_js=$tpl->javascript_parse_text($host);
    $rdmeta_json=json_encode($rdmeta);
    $default_port_js=intval($default_port);

    $prefill_js="<script>
var _admRdMeta=$rdmeta_json;
// Jumeau JS de _admrd_first_host() -- MEMES regles, et elles ne sont pas
// arbitraires : la colonne hosts separe ses entrees par \"||\", et chaque
// entree peut porter une notation d alias \"alias>canonique\" dont seule la
// GAUCHE est l hote reel (cf. nginxWatchdog/stripAliasNotation cote daemon).
// Sans ce decoupage la chaine composite entiere partait dans target_server,
// puis CLEAN_BAD_XSS() y retirait \"|\" et \">\" -- recollant tous les domaines
// en un seul nom absurde, qui finissait dans l OID du certificat.
function _admRdFirst(raw){
    if(typeof raw!=='string'){return '';}
    var parts=raw.split(/\\|\\||[\\n,;]+/);
    for(var i=0;i<parts.length;i++){
        var v=parts[i];
        var g=v.indexOf('>');
        if(g>=0){v=v.substring(0,g);}
        v=v.replace(/^\\s+|\\s+\$/g,'');
        if(v.length===0){continue;}
        if(v.indexOf('*')>=0){continue;}   // catch-all : pas une adresse joignable
        return v;
    }
    return '';
}
function adminsRedirectorChange(path){
    var elTarget=document.getElementById('$id_target');
    var elPort=document.getElementById('$id_port');
    if(!elTarget || !elPort){return;}
    if(path=='' || !_admRdMeta.hasOwnProperty(path)){
        elTarget.value='$host_js';
        elPort.value='$default_port_js';
        return;
    }
    var meta=_admRdMeta[path];
    var h=_admRdFirst(meta.hosts);
    var p=_admRdFirst(meta.ports);
    // Un hote/port introuvable (vide, ou fait uniquement de separateurs) ne
    // doit JAMAIS laisser le residu du choix precedent: l'operateur verrait
    // le serveur d'un autre chemin affiche comme s'il appartenait a
    // celui-ci. Restaurer explicitement le defaut, une valeur qui se
    // remarque, plutot qu'une valeur plausible mais fausse.
    //
    // I-5 (PHP) : le repli de PORT n'est PAS le meme que pour l'entree vide.
    // Un chemin de redirector NON VIDE est choisi -> le relais est sur 443
    // (le 7e refus cote Go garantit que le site a un frontend SSL des qu'une
    // ligne peut etre armee), jamais 8080 -- 8080 est le port de l'acces
    // DIRECT, celui que $target ne peut PAS atteindre a travers un chemin de
    // redirector. Choisir un chemin puis garder 8080 est la combinaison qui
    // ne fonctionne pas ET ne se remarque pas.
    elTarget.value=(h.length>0)?h:'$host_js';
    elPort.value=(p.length>0)?p:'443';
}
</script>";

    // Le rappel ne part que si la fonction existe vraiment: la fenetre peut etre
    // ouverte depuis une URL directe, ou le tableau avoir ete redessine entre
    // temps, et un appel a une fonction absente laisserait la fenetre ouverte
    // apres un enregistrement pourtant reussi.
    $jsafter="dialogInstance7.close();";
    $jsafter.="if(typeof AcmRefresh==='function'){AcmRefresh();}";

    echo $tpl->_ENGINE_parse_body($tpl->form_outside("",$form,
        "","{generate_certificate}",$jsafter,"AsWebMaster")).$prefill_js;
}

function generate_save(){
    $tpl=new template_admin();
    if(!mtls_can_write()){
        echo $tpl->post_error("{ERROR_NO_PRIVS}");
        return;
    }
    $tpl->CLEAN_POST();
    $_POST["label"]="{created}: ".date("Y-m-d H:i:s");

    // La liste a choix multiples poste "1,2,3"; le champ numerique de repli poste
    // service_id. La presence de la CLEF service_ids dit laquelle des deux a ete
    // affichee -- elle est postee meme vide.
    $picker_shown=isset($_POST["service_ids"]);
    $service_ids_raw=trim($_POST["service_ids"] ?? "");
    $service_ids_array=array();
    if(strlen($service_ids_raw)>0){
        foreach(explode(",",$service_ids_raw) as $s){
            $v=intval(trim($s));
            if($v>0){$service_ids_array[]=$v;}
        }
    }
    // Selection vide alors que la liste etait proposee: on REFUSE. Le repli sur
    // service_id=1 aurait genere un certificat pour la premiere regle venue en
    // affichant un succes -- l'operateur n'aurait rien vu passer.
    if($picker_shown && count($service_ids_array)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{admin_client_no_rule_selected}"));
        return;
    }
    // Repli historique: le champ numerique quand la liste des sites est indisponible.
    if(count($service_ids_array)==0){
        $v=intval($_POST["service_id"] ?? 1);
        if($v>0){$service_ids_array[]=$v;}
    }

    $first_id=count($service_ids_array)>0?$service_ids_array[0]:1;

    // base_path ne doit JAMAIS venir d'une saisie libre: on ne retient que ce
    // que la route /admins-redirector/paths rend REELLEMENT au moment du
    // save (jamais ce que le POST pretend) -- le daemon la revalide de toute
    // facon (tache 5), mais une valeur libre acceptee ici produirait un
    // certificat inutilisable sans le moindre message. L'entree vide (acces
    // direct) est toujours licite.
    $base_path_posted=trim((string)($_POST["base_path"] ?? ""));
    $base_path="";
    if($base_path_posted!==""){
        $rd=_fetch_admins_redirector_paths();
        if(isset($rd["paths"][$base_path_posted])){
            $base_path=$base_path_posted;
        }
        // sinon: chemin inconnu/plus arme -- on retombe silencieusement sur
        // l'acces direct, jamais sur la valeur postee.
    }

    $data=array(
        "service_id"    => $first_id,
        "service_ids"   => $service_ids_array,
        "email"         => trim($tpl->CLEAN_BAD_XSS($_POST["email"] ?? "")),
        "label"         => trim($tpl->CLEAN_BAD_XSS($_POST["label"] ?? "")),
        "username"      => trim($tpl->CLEAN_BAD_XSS($_POST["username"] ?? "")),
        "max_days"      => intval($_POST["max_days"] ?? 365),
        // L ORDRE compte : _admrd_first_host() AVANT CLEAN_BAD_XSS(). Ce dernier
        // retire \"|\" et \">\" sans rien signaler, donc une valeur composite
        // arrivant par le POST n etait pas rejetee mais RECOLLEE en un seul nom
        // -- et un nom recolle reste syntaxiquement valide, aucune validation
        // posterieure ne peut le rattraper. Normaliser d abord est la seule
        // parade.
        "target_server" => trim($tpl->CLEAN_BAD_XSS(_admrd_first_host($_POST["target_server"] ?? ""))),
        "remote_port"   => intval($_POST["remote_port"] ?? 0),
        "base_path"     => $base_path,
    );

    // Un target_server non conforme produisait jusqu ici un certificat
    // d apparence normale, portant un OID absurde, sans le moindre message.
    if(!_admrd_valid_host($data["target_server"])){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{admins_redirector_bad_target}"));
        return;
    }
    if(!filter_var($data["email"], FILTER_VALIDATE_EMAIL)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{email}: {$data["email"]} error"));
        return;
    }

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/appclient-mtls/generate",$data);
    $json=json_decode($raw);

    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->Error ?? "{error}"));
        return;
    }

    if(isset($json->error)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->error));
        return;
    }

    $ids_str=implode(",",$service_ids_array);
    admin_tracks("Generated mTLS config for {$data["email"]} service_ids=$ids_str");
}

function delete_perform(){
    $tpl=new template_admin();
    if(!mtls_can_write()){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS}"));
        return;
    }
    $ID=intval($_POST["delete-config"]);
    if($ID==0){return;}

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_DELETE("/appclient-mtls/delete/$ID");
    $json=json_decode($raw);

    if(isset($json->Status) && !$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->Error ?? "{error}"));
        return;
    }

    if(isset($json->error)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->error));
        return;
    }

    admin_tracks("Deleted mTLS config #$ID");
}

function download(){
    if(!mtls_can_write()){
        // Le .enc porte la cle privee du certificat client: pas de fichier,
        // pas d'indice sur son existence, un 403 sec.
        header("HTTP/1.1 403 Forbidden");
        return;
    }
    $ID=intval($_GET["download"]);
    if($ID==0){return;}

    $unixSocketPath="/run/reverse-proxy.sock";
    $uri="http://localhost/appclient-mtls/download/$ID";

    $ch=curl_init();
    curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $unixSocketPath);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $resp=curl_exec($ch);
    $http_code=curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type=curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if($http_code!=200 || $resp===false){
        $json=json_decode($resp);
        $err="Download failed (HTTP $http_code)";
        if(isset($json->Error)){$err=$json->Error;}
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return;
    }

    // Le daemon refuse en HTTP 200 avec {"Status":false,"Error":"..."} (verifie
    // sur un identifiant inexistant) : sans ce controle, la console livrait ce
    // refus comme un fichier .enc de 57 octets, que le client Windows rejetait
    // ensuite sans dire pourquoi. Un .enc est du binaire chiffre, il ne peut
    // jamais se decoder en JSON : le test ne peut pas se declencher a tort.
    $refusal=json_decode($resp);
    if(is_object($refusal) && isset($refusal->Status) && !$refusal->Status){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($tpl->div_error($refusal->Error ?? "{error}"));
        return;
    }

    $filename="mtls-config-$ID.enc";
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Content-Length: ".mb_strlen($resp,'8bit'));
    header("Cache-Control: no-cache, no-store, must-revalidate");
    echo $resp;
}

function certificate_view_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["certificate-view-js"]);
    $tpl->js_dialog7("{view_certificate}","$page?certificate-view-popup=$ID",950);
}

// ---------------------------------------------------------------------------
// Vue d'un certificat client mTLS : le rendu en trois onglets vit dans
// ressources/class.certificates.view.inc (partage avec le centre de
// certificats). Cette page ne fait que retrouver le PEM de l'enregistrement.
// ---------------------------------------------------------------------------

function certificate_view_popup(){
    $page=CurrentPageName();
    $ID=intval($_GET["certificate-view-popup"]);
    echo cert_view_tabs(array(
        "overview"=>"$page?certificate-view-overview=$ID",
        "key"=>"$page?certificate-view-key=$ID",
        "raw"=>"$page?certificate-view-raw=$ID",
    ));
}

// Le PEM d'un enregistrement, ou "" : /appclient-mtls/list est la seule route qui le rend.
function mtls_cert_pem($ID):string{
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/appclient-mtls/list");
    $json=json_decode($raw);
    if(!isset($json->Data) || !is_array($json->Data)){return "";}
    foreach($json->Data as $rec){
        if(!is_object($rec)){continue;}
        if(intval(isset($rec->id)?$rec->id:0)!=intval($ID)){continue;}
        return isset($rec->cert_pem)?(string)$rec->cert_pem:"";
    }
    return "";
}

function certificate_view_overview(){
    $ID=intval($_GET["certificate-view-overview"]);
    $pem=mtls_cert_pem($ID);
    $error="";
    $x509=cert_view_parse_pem($pem,$error);
    if($error<>""){echo $error;return;}
    echo cert_view_overview_html($pem,$x509,"mtls-$ID");
}

function certificate_view_key(){
    $ID=intval($_GET["certificate-view-key"]);
    $pem=mtls_cert_pem($ID);
    $error="";
    $x509=cert_view_parse_pem($pem,$error);
    if($error<>""){echo $error;return;}
    echo cert_view_key_html($pem,$x509,"mtls-$ID");
}

function certificate_view_raw(){
    $ID=intval($_GET["certificate-view-raw"]);
    $pem=mtls_cert_pem($ID);
    $error="";
    $x509=cert_view_parse_pem($pem,$error);
    if($error<>""){echo $error;return;}
    echo cert_view_raw_html($pem,$x509,"mtls-$ID");
}

// =============================================================================================
// Refonte stitch (2026-11-19) : kit acm_* — un modele unique, une fonction par widget.
// =============================================================================================

// Liste des sites inscrite dans le certificat (OID 1.3.6.1.4.1.99999.37). PHP ignore les OID
// inconnus du sujet (openssl_x509_parse les retire) : on la lit dans le DER. Depuis le
// cloisonnement cote daemon (reverse-proxy HTTPServ/mtls_scope.go), un certificat SANS cette
// liste est refuse : la page doit le dire. parsed=false : PEM illisible, on ne conclut rien.
function acm_cert_sites(string $pem):array{
    $out=array("parsed"=>false,"has"=>false,"ids"=>array());
    if(!preg_match('#-----BEGIN CERTIFICATE-----(.+?)-----END CERTIFICATE-----#s',$pem,$m)){return $out;}
    $der=base64_decode(preg_replace('/\s+/','',$m[1]),true);
    if($der===false || strlen($der)<32){return $out;}
    $out["parsed"]=true;
    $oid="\x06\x09\x2B\x06\x01\x04\x01\x86\x8D\x1F\x25";
    $p=strpos($der,$oid);
    if($p===false){return $out;}
    $p+=strlen($oid);
    if($p+2>strlen($der)){return $out;}
    $len=ord($der[$p+1]);
    if($len>0x7f){return $out;}
    foreach(explode(",",substr($der,$p+2,$len)) as $v){
        $v=intval(trim($v));
        if($v>0){$out["ids"][]=$v;}
    }
    $out["has"]=count($out["ids"])>0;
    return $out;
}

function acm_ts($s):int{
    $s=trim((string)$s);
    if($s==""){return 0;}
    $t=strtotime($s);
    return $t===false?0:intval($t);
}

function acm_model():array{
    $m=array("feature"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ReverseAppClients"))==1,
        "listen"=>_fetch_mtls_listen(),"paths"=>_fetch_admins_redirector_paths(),
        "sites"=>mtls_site_names(),"list_ok"=>true,"list_error"=>"","clients"=>array(),
        "active"=>0,"expiring"=>0,"expired"=>0,"legacy"=>0,"unreachable"=>0,"delegated"=>0,
        "can_write"=>mtls_can_write());
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/appclient-mtls/list"));
    if(!is_object($json) || (isset($json->Status) && !$json->Status)){
        $m["list_ok"]=false;
        $m["list_error"]=is_object($json)?(string)($json->Error ?? ""):"";
        return $m;
    }
    $now=time();
    $deleg=array();
    $published=array();
    foreach($m["paths"]["paths"] as $path=>$p){$published[$path]=true;}
    $rows=(isset($json->Data) && is_array($json->Data))?$json->Data:array();
    foreach($rows as $r){
        $pem=(string)($r->cert_pem ?? "");
        $x=strlen($pem)>10?@openssl_x509_parse($pem):false;
        $from=is_array($x)?intval($x["validFrom_time_t"] ?? 0):0;
        $to=is_array($x)?intval($x["validTo_time_t"] ?? 0):0;
        if($from==0){$from=acm_ts($r->created_at ?? "");}
        if($to==0){$to=acm_ts($r->expires_at ?? "");}
        $ids=array();
        $raw=trim((string)($r->service_ids ?? ""));
        if($raw==""){$raw=(string)intval($r->service_id ?? 0);}
        foreach(explode(",",$raw) as $v){$v=intval(trim($v)); if($v>0){$ids[]=$v;}}
        $cs=acm_cert_sites($pem);
        $legacy=$cs["parsed"] && !$cs["has"];
        if($cs["has"]){$ids=$cs["ids"];}
        $base=trim((string)($r->base_path ?? ""));
        $issue="";
        if($legacy){$issue="legacy";}
        elseif($base=="" && $m["listen"]["reached"] && $m["listen"]["unix_socket"]){$issue="noport";}
        elseif($base!="" && $m["paths"]["reached"] && !isset($published[$base])){$issue="nopath";}
        $c=array("id"=>intval($r->id ?? 0),"email"=>(string)($r->email ?? ""),
            "username"=>is_array($x)?(string)($x["subject"]["CN"] ?? ""):"","ids"=>$ids,"base"=>$base,
            "from"=>$from,"to"=>$to,"days"=>$to>0?intval(floor(($to-$now)/86400)):null,
            "total"=>($to>$from && $from>0)?intval(round(($to-$from)/86400)):0,"cert"=>strlen($pem)>10,
            "issue"=>$issue);
        $c["state"]="ok";
        if($to>0 && $to<$now){$c["state"]="expired";}
        elseif($to>0 && ($to-$now)<30*86400){$c["state"]="soon";}
        if($c["state"]=="expired"){$m["expired"]++;}elseif(!$legacy){
            $m["active"]++;
            if($c["state"]=="soon"){$m["expiring"]++;}
            foreach($ids as $i){$deleg[$i]=true;}
        }
        if($legacy){$m["legacy"]++;}
        if($issue=="noport" || $issue=="nopath"){$m["unreachable"]++;}
        $m["clients"][]=$c;
    }
    $m["delegated"]=count($deleg);
    usort($m["clients"],"acm_cmp");
    return $m;
}

// Ce qui demande une action d'abord (fichier refuse, injoignable, expire, bientot), puis par e-mail.
function acm_cmp($a,$b){
    $rank=function($c){
        if($c["issue"]!=""){return 0;}
        if($c["state"]=="expired"){return 1;}
        if($c["state"]=="soon"){return 2;}
        return 3;
    };
    $ra=$rank($a);$rb=$rank($b);
    if($ra!=$rb){return $ra<$rb?-1:1;}
    return strcasecmp($a["email"],$b["email"]);
}

function acm_t(string $k,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$k."}");
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr_replace($s,(string)$a,$p,2);
    }
    return $s;
}

function acm_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

function acm_host(){
    $tpl=new template_admin();
    $m=acm_model();
    $h=array();
    $h[]="<div class='acm'>";
    $h[]=acm_banner($m);
    $h[]=acm_alert($m);
    $h[]=acm_status_strip($m);
    $h[]="<div class='acm-kpis'>".acm_kpi_active($m).acm_kpi_expiring($m).acm_kpi_expired($m).acm_kpi_sites($m)."</div>";
    $h[]=acm_clients($m);
    $h[]=acm_sheet();
    $h[]="</div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h))."<script>if(typeof AcmFilter==='function'){AcmFilter();}</script>";
}

function acm_banner(array $m):string{
    $page=CurrentPageName();
    $btn=$m["can_write"]?"<button type='button' class='acm-btn acm-btn-primary' OnClick=\"Loadjs('$page?generate-js=yes')\"><span class='acm-i'><i class='fas fa-plus'></i></span>{new_client}</button>":"";
    return "<section class='acm-hero'><span class='acm-hero-ico'><i class='fas fa-laptop-code'></i></span>"
        ."<div class='acm-hero-txt'><h2>{acm_hero_title}</h2><p>{acm_hero_text}</p>"
        ."<div class='acm-chips'>"
        ."<span class='acm-chip'><span class='acm-i'><i class='fas fa-id-card'></i></span>{acm_chip_personal}</span>"
        ."<span class='acm-chip'><span class='acm-i'><i class='fas fa-filter'></i></span>{acm_chip_sites}</span>"
        ."<span class='acm-chip'><span class='acm-i'><i class='fas fa-hourglass-half'></i></span>{acm_chip_expiry}</span>"
        ."<span class='acm-chip'><span class='acm-i'><i class='fas fa-lock'></i></span>{acm_chip_mtls}</span>"
        ."</div></div>"
        ."<div class='acm-hero-act'>$btn<a href='#' class='acm-link' OnClick=\"AcmHelp();return false;\"><span class='acm-i'><i class='fas fa-book-open'></i></span>{acm_how}</a></div></section>";
}

// Une alerte seulement quand quelque chose empeche les fichiers de fonctionner.
function acm_alert(array $m):string{
    $page=CurrentPageName();
    $l=$m["listen"];
    if(!$m["feature"]){
        $btn=$m["can_write"]?"<button type='button' class='acm-btn acm-btn-primary' OnClick=\"Loadjs('fw.nginx.status.php?mtls-install=yes')\">{acm_turn_on}</button>":"";
        return "<div class='acm-alert acm-alert-off'><span class='acm-i'><i class='fas fa-power-off'></i></span><div><b>{acm_off_title}</b><div>{acm_off_text}</div></div>$btn</div>";
    }
    if($l["reached"] && !$l["running"]){
        return "<div class='acm-alert acm-alert-err'><span class='acm-i'><i class='fas fa-circle-xmark'></i></span><div><b>{acm_stopped_title}</b><div>{acm_stopped_text}</div></div></div>";
    }
    if($l["reached"] && $l["unix_socket"] && $m["paths"]["reached"] && count($m["paths"]["paths"])==0){
        $btn=$m["can_write"]?"<button type='button' class='acm-btn' OnClick=\"Loadjs('$page?parameters-js=yes')\">{parameters}</button>":"";
        return "<div class='acm-alert acm-alert-err'><span class='acm-i'><i class='fas fa-route'></i></span><div><b>{acm_noway_title}</b><div>{acm_noway_text}</div></div>$btn</div>";
    }
    if($m["legacy"]>0){
        return "<div class='acm-alert acm-alert-warn'><span class='acm-i'><i class='fas fa-triangle-exclamation'></i></span><div><b>".acm_t("acm_legacy_title",array($m["legacy"]))."</b><div>{acm_legacy_text}</div></div>"
            ."<button type='button' class='acm-btn' OnClick=\"AcmTab('issue')\">{acm_show_them}</button></div>";
    }
    return "";
}

function acm_status_strip(array $m):string{
    $page=CurrentPageName();
    $l=$m["listen"];
    if(!$m["feature"]){$svc="<span class='acm-dot acm-dot-off'></span><b>{acm_svc_off}</b>";}
    elseif(!$l["reached"]){$svc="<span class='acm-dot acm-dot-warn'></span><b>{acm_unknown}</b>";}
    elseif($l["running"]){$svc="<span class='acm-dot acm-dot-ok'></span><b class='acm-ok'>{acm_running}</b>";}
    else{$svc="<span class='acm-dot acm-dot-err'></span><b class='acm-err'>{acm_stopped}</b>";}
    if(!$l["reached"]){$how="<b>{acm_unknown}</b>";}
    elseif($l["unix_socket"]){$how="<b>{acm_via_paths}</b>";}
    else{$how="<b>".acm_t("acm_direct_port",array(intval($l["port"])))."</b>";}
    $p=$m["paths"];
    if(!$p["reached"]){$paths="<b class='acm-warn'>{acm_paths_unreadable}</b>";$sub="";}
    elseif(count($p["paths"])==0){$paths="<b>{acm_no_path}</b>";$sub="<span class='acm-sub'>".(($l["reached"] && $l["unix_socket"])?"{acm_no_path_unix}":"{acm_no_path_sub}")."</span>";}
    else{
        $sites=array();$names=array();
        foreach($p["paths"] as $path=>$o){$sites[intval($o->serviceid ?? 0)]=true;$names[]=acm_h((string)($o->service_name ?? ""))." (".acm_h($path).")";}
        $paths="<b>".acm_t("acm_paths_count",array(count($p["paths"]),count($sites)))."</b>";
        $sub="<span class='acm-sub' title='".acm_h(implode(", ",$names))."'>".implode(", ",array_slice($names,0,2)).(count($names)>2?" …":"")."</span>";
    }
    $btn=$m["can_write"]?"<button type='button' class='acm-btn' OnClick=\"Loadjs('$page?parameters-js=yes')\"><span class='acm-i'><i class='fas fa-sliders'></i></span>{parameters}</button>":"";
    return "<section class='acm-strip'>"
        ."<div class='acm-fact'><span class='acm-fk'>{acm_svc_label}</span><span class='acm-fv'>$svc</span></div>"
        ."<div class='acm-fact'><span class='acm-fk'>{acm_how_label}</span><span class='acm-fv'><span class='acm-i'><i class='fas ".($l["unix_socket"]?"fa-route":"fa-plug")."'></i></span>$how</span><span class='acm-sub'>{acm_how_sub}</span></div>"
        ."<div class='acm-fact'><span class='acm-fk'>{acm_paths_label}</span><span class='acm-fv'>$paths</span>$sub</div>"
        .($btn!=""?"<div class='acm-fact acm-fact-act'>$btn</div>":"")."</section>";
}

function acm_kpi(string $cls,string $ico,string $label,int $v,string $cap):string{
    return "<div class='acm-kpi acm-kpi-$cls'><div class='acm-kpi-h'><span>$label</span><span class='acm-kpi-ico'><i class='$ico'></i></span></div>"
        ."<div class='acm-kpi-v'>$v</div><div class='acm-kpi-c'>$cap</div></div>";
}
function acm_kpi_active(array $m):string{
    return acm_kpi($m["active"]>0?"ok":"off","fas fa-user-check","{acm_kpi_active}",$m["active"],"{acm_kpi_active_cap}");
}
function acm_kpi_expiring(array $m):string{
    return acm_kpi($m["expiring"]>0?"warn":"off","fas fa-clock","{acm_kpi_soon}",$m["expiring"],"{acm_kpi_soon_cap}");
}
function acm_kpi_expired(array $m):string{
    return acm_kpi($m["expired"]>0?"err":"off","fas fa-calendar-xmark","{acm_kpi_expired}",$m["expired"],"{acm_kpi_expired_cap}");
}
function acm_kpi_sites(array $m):string{
    return acm_kpi("off","fas fa-globe","{acm_kpi_sites}",$m["delegated"],"{acm_kpi_sites_cap}");
}

function acm_clients(array $m):string{
    if(!$m["list_ok"]){
        return "<section class='acm-card'><div class='acm-card-h'><h3>{acm_people}</h3></div>"
            ."<div class='acm-empty'><span class='acm-empty-i'><i class='fas fa-plug-circle-xmark'></i></span><div class='acm-empty-t'>{acm_list_error}</div>"
            ."<div class='acm-empty-s'>".acm_h($m["list_error"])."</div></div></section>";
    }
    if(count($m["clients"])==0){return acm_empty($m);}
    $n=count($m["clients"]);
    $issues=0;foreach($m["clients"] as $c){if($c["issue"]!=""){$issues++;}}
    $tabs="<div class='acm-tabs' role='tablist'>"
        ."<button type='button' role='tab' class='acm-tab' data-t='all' OnClick=\"AcmTab('all')\">{acm_f_all} <span>$n</span></button>"
        ."<button type='button' role='tab' class='acm-tab' data-t='soon' OnClick=\"AcmTab('soon')\">{acm_f_soon} <span>{$m["expiring"]}</span></button>"
        ."<button type='button' role='tab' class='acm-tab' data-t='expired' OnClick=\"AcmTab('expired')\">{acm_f_expired} <span>{$m["expired"]}</span></button>"
        .($issues>0?"<button type='button' role='tab' class='acm-tab acm-tab-warn' data-t='issue' OnClick=\"AcmTab('issue')\">{acm_f_issue} <span>$issues</span></button>":"")
        ."</div>";
    $rows=array();
    foreach($m["clients"] as $c){$rows[]=acm_row($c,$m);}
    return "<section class='acm-card'><div class='acm-card-h'><div><h3>{acm_people}</h3><p>{acm_people_help}</p></div></div>"
        ."<div class='acm-toolbar'><label class='acm-search'><span class='acm-i'><i class='fas fa-magnifying-glass'></i></span>"
        ."<input type='search' id='acm-q' placeholder='{acm_search_ph}' aria-label='{acm_search_ph}' OnInput=\"AcmFilter()\"></label>$tabs</div>"
        ."<div class='acm-scroll'><table class='acm-tbl'><thead><tr><th>{acm_th_person}</th><th>{acm_th_sites}</th><th>{acm_th_conn}</th><th>{acm_th_validity}</th><th class='acm-r'>{acm_th_actions}</th></tr></thead>"
        ."<tbody id='acm-rows'>".implode("",$rows)."</tbody></table></div>"
        ."<div class='acm-fempty' id='acm-fempty' style='display:none'>{acm_filter_empty}</div></section>";
}

function acm_initials(string $email):string{
    $local=preg_replace('/@.*$/','',$email);
    $parts=preg_split('/[._\-+]+/',$local,-1,PREG_SPLIT_NO_EMPTY);
    $s=count($parts)>1?substr($parts[0],0,1).substr($parts[1],0,1):substr($local,0,2);
    return strtoupper($s==""?"?":$s);
}

function acm_site_chips(array $ids,array $names):string{
    $out=array();$rest=array();
    foreach($ids as $id){
        $known=isset($names[$id]);
        $label=$known?$names[$id]:"#$id";
        if(count($out)<3){
            $out[]="<span class='acm-site".($known?"":" acm-site-unk")."' title='".($known?"#$id":acm_h(acm_t("admin_client_rule_unknown")))."'>"
                .($known?"":"<span class='acm-i'><i class='fas fa-circle-question'></i></span>").acm_h($label)."</span>";
        }else{$rest[]=$label;}
    }
    if(count($out)==0){return "<span class='acm-muted'>—</span>";}
    if(count($rest)>0){$out[]="<span class='acm-site acm-site-more' title='".acm_h(implode(", ",$rest))."'>+".count($rest)."</span>";}
    return "<div class='acm-sites'>".implode("",$out)."</div>";
}

function acm_conn_cell(array $c,array $m):string{
    if($c["base"]==""){
        $cell="<span class='acm-conn'><span class='acm-i'><i class='fas fa-plug'></i></span>".acm_t("acm_direct_port",array(intval($m["listen"]["port"])))."</span>";
    }else{
        $cell="<span class='acm-conn acm-mono'><span class='acm-i'><i class='fas fa-route'></i></span>".acm_h($c["base"])."</span>";
    }
    if($c["issue"]=="noport"){$cell.="<div class='acm-issue'>{acm_issue_noport}</div>";}
    if($c["issue"]=="nopath"){$cell.="<div class='acm-issue'>{acm_issue_nopath}</div>";}
    return $cell;
}

function acm_validity_cell(array $c):string{
    $tpl=new template_admin();
    $exact=$c["to"]>0?date("Y-m-d H:i",$c["to"]):"";
    if($c["to"]==0){return "<span class='acm-muted'>—</span>";}
    $d=intval($c["days"]);
    $created=$c["from"]>0?"<div class='acm-sub'>".acm_t("acm_created_on",array(date("Y-m-d",$c["from"])))."</div>":"";
    if($c["issue"]=="legacy"){
        // Refuse par le daemon quelle que soit sa date : ne pas l'afficher « valide » en vert.
        return "<div class='acm-val acm-val-err acm-wrap' title='$exact'><span class='acm-dot acm-dot-err'></span>{acm_issue_legacy}</div>".$created;
    }
    if($c["state"]=="expired"){
        $txt=acm_t("acm_expired_ago",array(max(1,-$d)));$cls="err";$pct=0;
    }else{
        $txt=$c["state"]=="soon"?acm_t("acm_expires_in",array(max(0,$d))):acm_t("acm_valid_left",array($d));
        $cls=$c["state"]=="soon"?"warn":"ok";
        $pct=$c["total"]>0?max(2,min(100,intval(round($d*100/$c["total"])))):0;
    }
    return "<div class='acm-val acm-val-$cls' title='$exact'><span class='acm-dot acm-dot-$cls'></span>$txt</div>"
        .($c["state"]!="expired"&&$pct>0?"<div class='acm-bar' role='progressbar' aria-valuemin='0' aria-valuemax='{$c["total"]}' aria-valuenow='".max(0,$d)."'><span class='acm-bar-$cls' style='width:$pct%'></span></div>":"")
        .$created;
}

function acm_row(array $c,array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=$c["id"];
    $email=acm_h($c["email"]);
    $user=($c["username"]!="" && $c["username"]!=$c["email"])?"<div class='acm-sub'>".acm_h($c["username"])."</div>":"";
    $names=array();foreach($c["ids"] as $i){if(isset($m["sites"][$i])){$names[]=$m["sites"][$i];}}
    $q=strtolower($c["email"]." ".$c["username"]." ".implode(" ",$names)." ".$c["base"]);
    $until=$c["to"]>0?date("Y-m-d",$c["to"]):"—";
    $acts=array();
    if($m["can_write"]){
        $acts[]="<button type='button' class='acm-ico-btn' aria-label='".acm_h(acm_t("acm_dl_for",array($c["email"])))."' title='{acm_dl}' OnClick=\"AcmAsk('download',$id,'$email','$until')\"><i class='fas fa-file-arrow-down'></i></button>";
    }
    if($c["cert"]){
        $acts[]="<button type='button' class='acm-ico-btn' aria-label='{view_certificate}' title='{view_certificate}' OnClick=\"Loadjs('$page?certificate-view-js=$id')\"><i class='fas fa-certificate'></i></button>";
    }
    if($m["can_write"]){
        $acts[]="<button type='button' class='acm-ico-btn acm-ico-del' aria-label='".acm_h(acm_t("acm_del_for",array($c["email"])))."' title='{acm_del}' OnClick=\"AcmAsk('delete',$id,'$email','$until')\"><i class='fas fa-trash-can'></i></button>";
    }
    $st=$c["issue"]!=""?"issue":$c["state"];
    return "<tr data-q='".acm_h($q)."' data-st='$st' data-state='{$c["state"]}' class='".($c["state"]=="expired"?"acm-row-off":"")."'>"
        ."<td><div class='acm-person'><span class='acm-av'>".acm_h(acm_initials($c["email"]))."</span><div><b>$email</b>$user</div></div></td>"
        ."<td>".acm_site_chips($c["ids"],$m["sites"])."</td>"
        ."<td>".acm_conn_cell($c,$m)."</td>"
        ."<td>".acm_validity_cell($c)."</td>"
        ."<td class='acm-r'><div class='acm-acts'>".implode("",$acts)."</div></td></tr>";
}

function acm_empty(array $m):string{
    $page=CurrentPageName();
    $btn=$m["can_write"]?"<button type='button' class='acm-btn acm-btn-primary' OnClick=\"Loadjs('$page?generate-js=yes')\"><span class='acm-i'><i class='fas fa-plus'></i></span>{new_client}</button>":"";
    $p=$m["can_write"]?"<a href='#' class='acm-link' OnClick=\"Loadjs('$page?parameters-js=yes');return false;\">{parameters}</a>":"";
    return "<section class='acm-card'><div class='acm-empty'><span class='acm-empty-i'><i class='fas fa-laptop-code'></i></span>"
        ."<div class='acm-empty-t'>{acm_empty_title}</div><div class='acm-empty-s'>{acm_empty_text}</div>"
        ."<ol class='acm-steps'><li><span>1</span><div><b>{acm_step1}</b>$p</div></li>"
        ."<li><span>2</span><div><b>{acm_step2}</b>$btn</div></li>"
        ."<li><span>3</span><div><b>{acm_step3}</b><div class='acm-sub'>{acm_step3_sub}</div></div></li></ol></div></section>";
}

// Feuille de confirmation de la page (telechargement d'une cle, retrait de la liste).
function acm_sheet():string{
    return "<div class='acm-sheet-bg' id='acm-sheet' hidden><div class='acm-sheet' role='dialog' aria-modal='true' aria-labelledby='acm-sheet-t'>"
        ."<span class='acm-sheet-ico acm-sheet-key' id='acm-ico-key'><i class='fas fa-key'></i></span><span class='acm-sheet-ico acm-sheet-del' id='acm-ico-del' hidden><i class='fas fa-trash-can'></i></span><h3 id='acm-sheet-t'></h3><p id='acm-sheet-p'></p>"
        ."<div class='acm-sheet-err' id='acm-sheet-err' hidden></div>"
        ."<div class='acm-sheet-act'><button type='button' class='acm-btn' OnClick=\"AcmClose()\">{acm_cancel}</button>"
        ."<button type='button' class='acm-btn acm-btn-primary' id='acm-sheet-go'></button></div></div></div>";
}

function acm_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $j=function($k)use($tpl){ return json_encode(trim(preg_replace('/\s*<br\s*\/?>\s*/i',' ',html_entity_decode($tpl->_ENGINE_parse_body("{".$k."}"),ENT_QUOTES,"UTF-8")))); };
    $wiki="https://wiki.articatech.com/en/reverse-proxy/admin-mtls";
    return "<script>
window.AcmState=window.AcmState||{tab:'all'};
var AcmTxt={dlT:".$j("acm_dl_title").",dlP:".$j("acm_dl_text").",dlGo:".$j("acm_dl").",delT:".$j("acm_del_title").",delP:".$j("acm_del_text").",delGo:".$j("acm_del_go")."};
function AcmRefresh(){ if(document.getElementById('acm-host')){LoadAjaxSilent('acm-host','$page?acm-host=yes');} }
function AcmHelp(){ s_PopUpFull('$wiki','1024','900'); }
function AcmTab(t){ window.AcmState.tab=t; AcmFilter(); }
function AcmFilter(){
    var q=document.getElementById('acm-q'), s=q?q.value.toLowerCase().trim():'', t=window.AcmState.tab||'all';
    var tabs=document.querySelectorAll('.acm-tab'); for(var i=0;i<tabs.length;i++){tabs[i].setAttribute('aria-selected',tabs[i].getAttribute('data-t')===t?'true':'false');}
    var tb=document.getElementById('acm-rows'); if(!tb){return;}
    var n=0; for(var j=0;j<tb.rows.length;j++){ var r=tb.rows[j];
        var okT=(t==='all')||(t==='issue'?r.getAttribute('data-st')==='issue':r.getAttribute('data-state')===t);
        var okQ=s===''||(r.getAttribute('data-q')||'').indexOf(s)>=0;
        r.style.display=(okT&&okQ)?'':'none'; if(okT&&okQ){n++;} }
    var e=document.getElementById('acm-fempty'); if(e){e.style.display=n===0?'':'none';}
}
function AcmFmt(t){ var a=Array.prototype.slice.call(arguments,1),i=0; return String(t).replace(/%s/g,function(){return i<a.length?a[i++]:'';}); }
function AcmAsk(kind,id,email,until){
    var sh=document.getElementById('acm-sheet'); if(!sh){return;}
    var go=document.getElementById('acm-sheet-go'), err=document.getElementById('acm-sheet-err');
    err.hidden=true; err.textContent='';
    document.getElementById('acm-ico-key').hidden=(kind==='delete'); document.getElementById('acm-ico-del').hidden=(kind!=='delete');
    if(kind==='download'){
        document.getElementById('acm-sheet-t').textContent=AcmTxt.dlT;
        document.getElementById('acm-sheet-p').textContent=AcmFmt(AcmTxt.dlP,email);
        go.textContent=AcmTxt.dlGo; go.className='acm-btn acm-btn-primary';
        go.onclick=function(){ AcmClose(); window.location.href='$page?download='+id; };
    }else{
        document.getElementById('acm-sheet-t').textContent=AcmFmt(AcmTxt.delT,email);
        document.getElementById('acm-sheet-p').textContent=AcmFmt(AcmTxt.delP,until);
        go.textContent=AcmTxt.delGo; go.className='acm-btn acm-btn-danger';
        go.onclick=function(){
            go.disabled=true;
            $.post('$page',{'delete-config':id}).done(function(r){
                go.disabled=false;
                var t=$('<div>').html(String(r||'')).text().trim();
                if(t.length>0){ err.textContent=t; err.hidden=false; return; }
                AcmClose(); AcmRefresh();
            }).fail(function(x){ go.disabled=false; err.textContent='HTTP '+x.status; err.hidden=false; });
        };
    }
    sh.hidden=false; go.focus();
}
function AcmClose(){ var sh=document.getElementById('acm-sheet'); if(sh){sh.hidden=true;} }
document.addEventListener('keydown',function(e){ if(e.key==='Escape'){AcmClose();} });
</script>";
}

function acm_tiny():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $b=array();
    if(mtls_can_write()){
        $b[]=array("Loadjs('$page?generate-js=yes');",ico_plus,"{new_client}");
        $b[]=array("Loadjs('$page?parameters-js=yes');",ico_configure,"{parameters}");
    }
    $b[]=array("AcmHelp();",ico_support,"{acm_how}");
    $T=array();
    $T["TITLE"]="{admin_clients}";
    $T["ICO"]=ico_admin;
    $T["EXPL"]="{acm_page_explain}";
    $T["URL"]="reverse-proxy-admin-clients";
    $T["BUTTONS"]=$tpl->table_buttons($b);
    return "<script>NoSpinner();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($T)))."');</script>";
}

function acm_style():string{
    $accent="#0d9488";$soft="rgba(13,148,136,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_dark"])){$accent=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.acm{max-width:1320px;margin-top:12px;color:#0f172a;font-size:14px;line-height:1.45}
.acm *{box-sizing:border-box}
.acm .acm-i{display:inline-flex;width:15px;height:15px;align-items:center;justify-content:center;font-size:13px}
.acm .acm-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:12.5px}
.acm-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid #d1d5db;background:#fff;color:#0f172a;border-radius:8px;padding:8px 14px;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap}
.acm-btn:hover{background:#f8fafc}
.acm-btn-primary{background:$accent;border-color:$accent;color:#fff}.acm-btn-primary:hover{background:$accent;filter:brightness(.95)}
.acm-btn-danger{background:#dc2626;border-color:#dc2626;color:#fff}.acm-btn-danger:hover{background:#b91c1c}
.acm-btn:focus-visible,.acm-ico-btn:focus-visible,.acm-tab:focus-visible,.acm-link:focus-visible,.acm-search input:focus-visible{outline:2px solid $accent;outline-offset:2px}
.acm-link{display:inline-flex;align-items:center;gap:6px;color:$accent;font-weight:600}
.acm-hero{display:flex;gap:24px;align-items:flex-start;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px 28px;margin-bottom:16px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.acm-hero-ico{flex:0 0 64px;width:64px;height:64px;border-radius:14px;background:$soft;color:$accent;display:inline-flex;align-items:center;justify-content:center;font-size:28px}
.acm-hero-txt{flex:1;min-width:0}
.acm-hero h2{font-size:22px;font-weight:600;margin:0 0 6px;line-height:1.3}
.acm-hero p{font-size:15px;color:#334155;margin:0 0 12px;max-width:860px}
.acm-chips{display:flex;flex-wrap:wrap;gap:8px}
.acm-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid #e2e8f0;border-radius:999px;padding:4px 12px;font-size:13px;color:#334155;background:#f8fafc}
.acm-chip .acm-i{color:$accent}
.acm-hero-act{display:flex;flex-direction:column;align-items:flex-end;gap:12px}
.acm-alert{display:flex;align-items:center;gap:14px;border-radius:10px;padding:12px 16px;margin-bottom:16px;border:1px solid}
.acm-alert>div{flex:1}.acm-alert b{display:block}
.acm-alert-err{background:#fef2f2;border-color:#fecaca;color:#7f1d1d}.acm-alert-err .acm-i{color:#dc2626}
.acm-alert-warn{background:#fffbeb;border-color:#fde68a;color:#78350f}.acm-alert-warn .acm-i{color:#d97706}
.acm-alert-off{background:#f8fafc;border-color:#e2e8f0;color:#334155}
.acm-strip{display:flex;gap:0;background:#fff;border:1px solid #e5e7eb;border-radius:12px;margin-bottom:16px}
.acm-fact{flex:1 1 0;padding:14px 18px;border-right:1px solid #e5e7eb;display:flex;flex-direction:column;gap:2px;min-width:0}
.acm-wrap{white-space:normal;align-items:flex-start}.acm-wrap .acm-dot{margin-top:5px}
.acm-fact:last-child{border-right:0}
.acm-fact-act{flex:0 0 auto;justify-content:center;align-items:flex-end}
.acm-fk{font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
.acm-fv{display:flex;align-items:center;gap:8px;font-size:14.5px}
.acm-sub{font-size:12.5px;color:#64748b;overflow:hidden;text-overflow:ellipsis}
.acm-ok{color:#15803d}.acm-err{color:#b91c1c}.acm-warn{color:#b45309}
.acm-dot{display:inline-block;width:9px;height:9px;border-radius:50%;flex:0 0 9px}
.acm-dot-ok{background:#16a34a}.acm-dot-err{background:#dc2626}.acm-dot-warn{background:#d97706}.acm-dot-off{background:#94a3b8}
.acm-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:16px}
.acm-kpi{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;min-width:0}
.acm-kpi-h{display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:600;color:#334155}
.acm-kpi-ico{width:30px;height:30px;border-radius:8px;background:#f1f5f9;color:#64748b;display:inline-flex;align-items:center;justify-content:center;font-size:14px}
.acm-kpi-v{font-size:28px;font-weight:700;margin-top:8px;color:#334155}
.acm-kpi-c{font-size:13px;color:#64748b}
.acm-kpi-ok .acm-kpi-ico{background:$soft;color:$accent}.acm-kpi-ok .acm-kpi-v{color:#0f172a}
.acm-kpi-warn{border-color:#fde68a}.acm-kpi-warn .acm-kpi-ico{background:#fef3c7;color:#d97706}.acm-kpi-warn .acm-kpi-v{color:#b45309}
.acm-kpi-err{border-color:#fecaca}.acm-kpi-err .acm-kpi-ico{background:#fee2e2;color:#dc2626}.acm-kpi-err .acm-kpi-v{color:#b91c1c}
.acm-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden}
.acm-card-h{padding:18px 22px 6px}
.acm-card-h h3{font-size:17px;font-weight:600;margin:0}.acm-card-h p{font-size:13.5px;color:#475569;margin:4px 0 0}
.acm-toolbar{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;padding:12px 22px 14px}
.acm-search{flex:1 1 280px;max-width:460px;display:flex;align-items:center;gap:8px;background:#f1f5f9;border-radius:8px;padding:0 12px;margin:0;font-weight:400;color:#64748b}
.acm-search input{border:0;background:transparent;height:38px;flex:1;font-size:14px;color:#0f172a;outline:none}
.acm-tabs{display:inline-flex;background:#f1f5f9;border-radius:8px;padding:3px;gap:2px;flex-wrap:wrap}
.acm-tab{border:0;background:transparent;border-radius:6px;padding:6px 12px;font-size:13.5px;color:#334155;cursor:pointer}
.acm-tab span{color:#64748b;margin-left:2px}
.acm-tab[aria-selected=true]{background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.12);font-weight:600;color:$accent}
.acm-tab-warn[aria-selected=true]{color:#b45309}
.acm-scroll{overflow-x:auto}
.acm-tbl{width:100%;border-collapse:collapse;color:#0f172a}
.acm-tbl th{background:#f8fafc;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b;text-align:left;padding:10px 16px;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;white-space:nowrap}
.acm-tbl td{padding:14px 16px;border-bottom:1px solid #eef2f7;vertical-align:middle;color:#0f172a}
.acm-tbl tr:last-child td{border-bottom:0}
.acm-tbl tbody tr{transition:background .15s}.acm-tbl tbody tr:hover{background:#f8fafc}
.acm-row-off td{opacity:.7}
.acm-r{text-align:right}
.acm-person{display:flex;align-items:center;gap:12px;min-width:0}
.acm-person b{font-weight:600;word-break:break-all}
.acm-av{flex:0 0 38px;width:38px;height:38px;border-radius:50%;background:$soft;color:$accent;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:13px}
.acm-sites{display:flex;flex-wrap:wrap;gap:6px}
.acm-site{display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;border-radius:6px;padding:3px 8px;font-size:13px;color:#0f172a}
.acm-site-unk{background:#fff7ed;color:#9a3412}.acm-site-more{color:#475569}
.acm-muted{color:#94a3b8}
.acm-conn{display:inline-flex;align-items:center;gap:6px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:3px 8px;font-size:13px;white-space:nowrap}
.acm-issue{margin-top:5px;font-size:12.5px;color:#b45309;font-weight:600}
.acm-val{display:flex;align-items:center;gap:6px;font-size:13.5px;font-weight:600;white-space:nowrap}
.acm-val-ok{color:#15803d}.acm-val-warn{color:#b45309}.acm-val-err{color:#b91c1c}
.acm-bar{height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin:6px 0 2px;max-width:220px}
.acm-bar span{display:block;height:100%}.acm-bar-ok{background:#16a34a}.acm-bar-warn{background:#d97706}
.acm-acts{display:inline-flex;gap:4px}
.acm-ico-btn{border:0;background:transparent;color:#64748b;width:34px;height:34px;border-radius:8px;cursor:pointer;font-size:15px;display:inline-flex;align-items:center;justify-content:center}
.acm-ico-btn:hover{background:#f1f5f9;color:$accent}.acm-ico-del:hover{color:#dc2626;background:#fef2f2}
.acm-fempty{padding:24px;text-align:center;color:#64748b}
.acm-empty{text-align:center;padding:40px 24px}
.acm-empty-i{display:inline-flex;width:56px;height:56px;font-size:40px;color:#94a3b8;align-items:center;justify-content:center}
.acm-empty-t{font-size:18px;font-weight:600;margin-top:8px}
.acm-empty-s{font-size:14px;color:#475569;max-width:620px;margin:6px auto 0}
.acm-steps{list-style:none;padding:0;margin:24px auto 0;display:flex;gap:16px;justify-content:center;flex-wrap:wrap;text-align:left}
.acm-steps li{display:flex;gap:10px;align-items:flex-start;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px 14px;width:280px}
.acm-steps li>span{flex:0 0 26px;width:26px;height:26px;border-radius:50%;background:$accent;color:#fff;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
.acm-steps b{display:block;margin-bottom:6px}
.acm-sheet-bg{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:2050;display:flex;align-items:center;justify-content:center}
.acm-sheet-bg[hidden]{display:none}
.acm-sheet{background:#fff;border-radius:14px;padding:24px 26px;width:480px;max-width:calc(100vw - 32px);box-shadow:0 20px 50px rgba(15,23,42,.25)}
.acm-sheet h3{font-size:18px;font-weight:600;margin:10px 0 6px}.acm-sheet p{font-size:14px;color:#334155;margin:0}
.acm-sheet-ico{width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center}
.acm-sheet-ico[hidden]{display:none}
.acm-sheet-key{background:#fef3c7;color:#b45309;font-size:18px}
.acm-sheet-del{background:#fee2e2;color:#dc2626;font-size:18px}
.acm-sheet-err{margin-top:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:8px;padding:8px 10px;font-size:13px}
.acm-sheet-act{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
@media (max-width:1200px){.acm-kpis{grid-template-columns:repeat(2,1fr)}.acm-strip{flex-direction:column}.acm-fact{border-right:0;border-bottom:1px solid #e5e7eb}.acm-hero{flex-wrap:wrap}.acm-hero-act{align-items:flex-start;flex-direction:row}}
@media (prefers-reduced-motion:reduce){.acm-tbl tbody tr{transition:none}}
</style>";
}
