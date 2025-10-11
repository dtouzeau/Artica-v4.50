<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

foreach ($_GET as $key=>$val){
    if(preg_match("#template-error-code-([0-9]+)-id-([0-9]+)#",$key,$re)){
        template_error_js($re[1],$re[2]);
        exit;
    }
}
if(isset($_GET["template-popup"])){template_popup();exit;}
if(isset($_POST["template_error"])){template_save();exit;}
if(isset($_POST["service_id"])){save();exit;}
if(isset($_GET["main"])){page();exit;}

start();


function start():bool {
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["service"]);
    echo "<div id='nginx-options-$serviceid'></div>
    <script>LoadAjaxSilent('nginx-options-$serviceid','$page?main=yes&service=$serviceid');</script>";
    return true;

}

function template_error_js($code,$service_id){
    $tpl    = new template_admin();$tpl->CLUSTER_CLI=true;
    $page   = CurrentPageName();
    $tpl->js_dialog7("{template} $code","$page?template-popup=$service_id&code=$code",1048);
}
function template_popup():bool{
    $serviceid  = intval($_GET["template-popup"]);
    $error_code = intval($_GET["code"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ngx_tpls   = new nginx_templates();
    $data       = $ngx_tpls->LoadTemplate($error_code,$serviceid);
    $data=str_replace("}","}\n",$data);
    $data=str_replace(".h5 small","\n\t.h5 small",$data);
    $data=str_replace("h4 small,","\n\t.h4 small,",$data);
    $data=str_replace(",blockquote.pull-right","\n\t,blockquote.pull-right",$data);
    $tpl->field_hidden("template_error",$error_code);
    $tpl->field_hidden("template_id",$serviceid);
    if($error_code==901){
        $form[]=$tpl->field_textarea("template_data",null,$data);
    }else{
        $form[]=$tpl->field_textareacode("template_data",null,$data);
    }

    echo $tpl->form_outside("{template} {error} $error_code", $form,"","{apply}","","AsSystemWebMaster");
    return true;

}
function template_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $code       = intval($_POST["template_error"]);
    $serviceid  = intval($_POST["template_id"]);
    $data       = $_POST["template_data"];
    $key        = "error_page.$code.".$serviceid;
    $sock       = new socksngix($serviceid);
    writelogs("Saving template key=$key data=".strlen($data),__FUNCTION__,__FILE__,__LINE__);
    $sock->SET_INFO($key,$data);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;
}

function page():bool{
    $rewrite_rules      = array();
    $sub_filters        = array();
    $add_header_rules   = array();
    $tpl                = new template_admin();$tpl->CLUSTER_CLI=true;
    $serviceid          = intval($_GET["service"]);
    $sock               = new socksngix($serviceid);
    $NginxHTTPSubModule = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPSubModule"));
    $sub_filters_data   = base64_decode($sock->GET_INFO("sub_filters"));
    $rewrite_rules_data = base64_decode($sock->GET_INFO("rewrite_rules"));
    $add_header_data    = base64_decode($sock->GET_INFO("header_rules"));

    if($sub_filters_data<>null){$sub_filters=unserialize($sub_filters_data);}
    if($rewrite_rules_data<>null){$rewrite_rules=unserialize($rewrite_rules_data);}
    if($add_header_data<>null){$add_header_rules=unserialize($add_header_data);}

    VERBOSE("NginxHTTPSubModule = $NginxHTTPSubModule",__LINE__);
    $form[]=$tpl->field_hidden("service_id",$serviceid);



    $sub_filters_count      = count($sub_filters);
    $rewrite_rules_count    = count($rewrite_rules);
    $add_header_count       = count($add_header_rules);


    $sub_filters_text="{rules}";
    if($sub_filters_count<2){
        $sub_filters_text="{rule}";
    }
    $rewrite_rules_text="{rules}";
    if($rewrite_rules_count<2){
        $rewrite_rules_text="{rule}";
    }
    $add_header_text="{rules}";
    if($add_header_count<2){
        $add_header_text="{rule}";
    }

    $tpl->table_form_section("{content_replacement}");
    $tpl->table_form_field_js("Loadjs('fw.nginx.rules.headers.php?service-js=$serviceid')","AsWebMaster");
    $tpl->table_form_field_text("{header_checks}","$add_header_count $add_header_text",ico_html);
    // rewrite_rules_fdb_explain
    $tpl->table_form_field_js("Loadjs('fw.nginx.rules.rewrite.php?service-js=$serviceid')","AsWebMaster");
    $tpl->table_form_field_text("{rewrite_rules}","$rewrite_rules_count $rewrite_rules_text",ico_html);

    //nginx_replace_explain
    $tpl->table_form_field_js("Loadjs('fw.nginx.rules.replace.php?service-js=$serviceid')","AsWebMaster");
    $tpl->table_form_field_text("{replace_rules}","$sub_filters_count $sub_filters_text",ico_html);
    $tpl->table_form_section("{templates} {errors}");





    $ngx_tpls=new nginx_templates();
    foreach ($ngx_tpls->valid_error_codes as $code){
        $len=strlen($ngx_tpls->LoadTemplate($code,$serviceid));
        $len=round($len/1024,2)." KB";
        if($code==901){
            $form[]=$tpl->field_text_button("template-error-code-{$code}-id-$serviceid","{template} {maintenance}",$len,false,null);
            continue;
        }
        $form[]=$tpl->field_text_button("template-error-code-{$code}-id-$serviceid","{template} {error} $code",$len,false,null);

    }

    $js="NgixSitesReload();";

    $html[]=$tpl->table_form_compile();
    $html[]=$tpl->form_outside(null, $form,"","{apply}","$js","AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function save(){
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();

    $KeyToRemove[]="hide_headers";
    $KeyToRemove[]="header_rules";
    $KeyToRemove[]="rewrite_rules";
    $KeyToRemove[]="replace_rules";
    $KeyToRemove[]="gzip_rules";

    foreach ($KeyToRemove as $supkey){
        unset($_POST[$supkey]);
    }

    $service_id=$_POST["service_id"];
    $sock=new socksngix($service_id);
    unset($_POST["service_id"]);
    foreach ($_POST as $key=>$val){
        $sock->SET_INFO($key,$val);
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$service_id");
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
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}