<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["license"])){license();exit;}
if(isset($_GET["license-start"])){license_start();exit;}
js();
function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{license}", "$page?license-start=yes",800);
}
function license_start(){
    $page=currentPageName();
    echo "<div id='ReverseProxy-license'></div><script>LoadAjax('ReverseProxy-license','$page?license=yes')</script>";
}

function license(){
    $page=CurrentPageName();
    $sock=new sockets();
    // When ?refresh=yes is requested, force articarest to re-validate the
    // license and rebuild its cache before reading it back. Otherwise just
    // read the currently cached value.
    if(isset($_GET["refresh"])){
        $data=$sock->REST_API_NGINX("/reverse-proxy/license/refresh");
    }else{
        $data=$sock->REST_API_NGINX("/reverse-proxy/license");
    }
    $tpl=new template_admin();
    $json=json_decode($data);

    // Le daemon absent ne produit PAS une erreur de decodage : REST_API_NGINX rend
    // une enveloppe JSON parfaitement valide, {"Status":false,"Error":"Error
    // Connecting to the REST API server 7 ..."}. json_last_error() vaut donc 0, et
    // la page lisait ensuite $json->license sur un objet qui n'a pas ce champ :
    // elle affichait un tableau de valeurs VIDES au lieu de dire que le service
    // etait injoignable. C'est l'enveloppe qu'il faut lire, pas le code d'erreur.
    if(json_last_error()>JSON_ERROR_NONE){
        echo $tpl->div_error("{reverse_proxy} : ".json_last_error_msg());
        return false;
    }
    if(!is_object($json)){
        echo $tpl->div_error("{reverse_proxy} : {unable_to_connect_to_service}");
        return false;
    }
    if(isset($json->Status) && !$json->Status){
        $err=property_exists($json,"Error")?(string)$json->Error:"{unable_to_connect_to_service}";
        echo $tpl->div_error("{reverse_proxy} : ".htmlspecialchars($err));
        return false;
    }
    // Une enveloppe saine mais sans le champ attendu signifie une route absente du
    // binaire deploye -- cas frequent quand la console est plus recente que lui.
    if(!property_exists($json,"license")){
        echo $tpl->div_error("{reverse_proxy} : {route_not_found} /reverse-proxy/license");
        return false;
    }
    $trial="";

    $tpl->table_form_section("{license}");
    $tpl->table_form_field_text("{license}",$json->license,ico_certificate);
    $tpl->table_form_field_text("{uuid}","<small>$json->uuid</small>",ico_certificate);

    if($json->company_id==1807){
        $trial=" ({trial_period})";
    }else{
        $tpl->table_form_field_text("{method}","Enterprise Edition",ico_params);
        $tpl->table_form_field_text("{company}",$json->company_name,ico_params);
    }

    $tpl->table_form_section("{validity}");
    $expire=$json->expire;
    if($expire>time()){

        $tpl->table_form_field_text("{expire}",distanceOfTimeInWords(time(),$expire).$trial,ico_timeout);
    }else{
        $tpl->table_form_field_text("{expire}","{expired}",ico_timeout,true);
    }
    if($json->ActiveRules > $json->max_websites){
        $tpl->table_form_field_text("{max_websites}",$tpl->FormatNumber($json->ActiveRules)."/".$tpl->FormatNumber($json->max_websites)." <small>{Licence_overrun}</small>",ico_server,true);

    }else{
        $tpl->table_form_field_text("{max_websites}",$tpl->FormatNumber($json->ActiveRules)."/".$tpl->FormatNumber($json->max_websites),ico_server);
    }

    // Force articarest to re-validate and rebuild its cached license, then
    // reload this dialog with the fresh value. Use when the status above is
    // stale/expired but the license is actually valid on articarest.
    $tpl->table_form_button("{refresh}","LoadAjax('ReverseProxy-license','$page?license=yes&refresh=yes')",null,"fa-solid fa-rotate");

    echo $tpl->table_form_compile(2);
    return true;

}

