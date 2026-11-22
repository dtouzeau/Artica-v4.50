<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
// Groupe edns de PUT /unbound/api/settings (spec 019 lot 4c).
include_once(dirname(__FILE__)."/ressources/class.unbound.api.inc");
include_once(dirname(__FILE__)."/ressources/class.unbound.settings.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["UnboundEDNS"])){Save();exit;}
if(isset($_GET["api-notices-js"])){api_notices_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
// (les dispatchs ?delete-js= et ?zoom= appelaient delete_js() et zoom(), deux
// fonctions qui n'existent NI ici NI dans les includes: toute requete les
// portant mourait sur un "Call to undefined function". Personne ne les
// appelait -- le seul appelant de cette page est le crayon EDNS de
// fw.dns.unbound.php, qui l'ouvre sans parametre.)

js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("EDNS","$page?popup=yes");
    return true;
}

function popup(){

    $tpl=new template_admin();
    $UnboundEDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEDNS"));
    $UnboundEDNSNetworks=explode("||",$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEDNSNetworks"));
    $ClientSubnetAlwaysForward=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClientSubnetAlwaysForward"));
    $form[]=$tpl->field_checkbox("UnboundEDNS","{enable}",$UnboundEDNS,true);
    $form[]=$tpl->field_tags("UnboundEDNSNetworks","{frontends}",@implode(",",$UnboundEDNSNetworks));
    $form[]=$tpl->field_checkbox("ClientSubnetAlwaysForward","{ClientSubnetAlwaysForward}",$ClientSubnetAlwaysForward);

    $jsafter[]="LoadAjax('unbound-table-start','fw.dns.unbound.php?table=yes')";
    $jsafter[]="dialogInstance2.close()";
    $jsafter[]="Loadjs('".CurrentPageName()."?api-notices-js=yes')";
    $jsafterText=@implode(";",$jsafter);
    $html=$tpl->form_outside("",$form,"","{apply}",$jsafterText,"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;


}
/**
 * Un frontal EDNS: une adresse ou un reseau, IPv4 ou IPv6, avec un prefixe
 * optionnel.
 *
 * La liste etait enregistree telle quelle, puis reaffichee sur la fiche d'etat
 * de fw.dns.unbound.php par table_form_field_text(), qui n'echappe pas: une
 * valeur porteuse de balisage devenait un XSS stocke dans la console. On refuse
 * donc a l'ECRITURE, et la lecture echappe de son cote -- les deux moities sont
 * necessaires, la valeur pouvant avoir ete posee avant ce controle.
 */
function edns_valid_network($v):bool{
    return preg_match('#^[0-9A-Fa-f:.]+(/[0-9]{1,3})?$#',(string)$v)==1;
}

function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    // form_outside ne fait que masquer le bouton : controle cote serveur.
    if(!$tpl->IsSecurity("AsDnsAdministrator")){ echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false; }

    // Seul UnboundEDNS est garanti present: c'est lui qui declenche ce
    // gestionnaire. Les deux autres etaient lus sans isset(), donc un POST
    // partiel ecrasait un reglage vivant par une valeur nulle -- en affichant
    // le chemin du succes.
    $UnboundEDNS=intval($_POST["UnboundEDNS"]);
    $raw=(string)(isset($_POST["UnboundEDNSNetworks"])?$_POST["UnboundEDNSNetworks"]:"");
    $raw=str_replace(array(" ",","),"||",$raw);

    $nets=array();
    foreach(explode("||",$raw) as $net){
        $net=trim($net);
        if($net===""){continue;}
        if(!edns_valid_network($net)){
            // Contrat form_outside: le prefixe jserror: ouvre une vraie boite de
            // dialogue. Un echo simple atterrirait dans une banniere en haut de
            // la modale, hors ecran sur un formulaire long.
            echo $tpl->post_error("{invalid_ipaddr}: $net");
            return false;
        }
        $nets[]=$net;
    }

    // PUT /unbound/api/settings/edns : valide, verifie par unbound-checkconf,
    // recharge et prouve ; un refus (409 esclave ou cache arrete, 400, 422)
    // s'affiche et rien n'est ecrit.
    $values=array("UnboundEDNS"=>$UnboundEDNS,"UnboundEDNSNetworks"=>$nets);
    if(isset($_POST["ClientSubnetAlwaysForward"])){
        $values["ClientSubnetAlwaysForward"]=intval($_POST["ClientSubnetAlwaysForward"]);
    }
    $split=unbound_settings::split($values,array());
    $res=unbound_settings::apply(new unbound_api(),$split["groups"]);
    if(!$res["ok"]){ echo $tpl->post_error($res["error"]);return false; }
    unbound_settings::queue($res);
    return admin_tracks_post("Saving EDNS feature in DNS Cache service");
}

function api_notices_js(){
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    echo unbound_api::notices_js($tpl->javascript_parse_text("{warning}",0,true),function($s) use ($tpl){
        return $tpl->javascript_parse_text($s,0,true);
    });
}