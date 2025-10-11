<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["license"])){license();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{license}", "$page?license=yes",800);
}

function license(){
    $sock=new sockets();
    $data=$sock->REST_API_NGINX("/reverse-proxy/license");
    $json=json_decode($data);
    $tpl=new template_admin();
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->post_error(json_last_error_msg());
        return false;
    }
    $trial="";

    $tpl->table_form_field_text("{license}",$json->license,ico_certificate);
    $tpl->table_form_field_text("{uuid}","<small>$json->uuid</small>",ico_certificate);

    if($json->company_id==1807){
        $trial=" ({trial_period})";
    }else{
        $tpl->table_form_field_text("{method}","Enterprise Edition",ico_params);
        $tpl->table_form_field_text("{company}",$json->company_name,ico_params);
    }

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


    echo $tpl->table_form_compile();
    return true;

}

