<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

include_once("/usr/share/artica-postfix/ressources/class.wifidog.templates.inc");

$users=new usersMenus();if(!$users->AsSquidAdministrator){$users->pageDie();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["MainTitle"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["skin-tabs"])){skin_tabs_js();exit;}
if(isset($_GET["skin-tabs-popup"])){skin_tabs_popup();exit;}
if(isset($_POST["save-tabs-order"])){save_tabs_order();exit;}
page();


function page(){
    $tpl=new template_admin();
    $sock=new wifidog_templates();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/articaweb/chown");
    $form[]=$tpl->field_textarea("MainTitle","{title2}",$sock->MainTitle,"100%","50px");
    $form[]=$tpl->field_textarea("LoginTitle","Login {title2}",$sock->LoginTitle,"100%","50px");
    $form[]=$tpl->field_textarea("LabelUsername","{label} {username}",$sock->LabelUsername,"100%","50px");
    $form[]=$tpl->field_textarea("LabelPassword","{label} {password}",$sock->LabelPassword,"100%","50px");
    $form[]=$tpl->field_textarea("WelcomeMessage","{welcome_message}",$sock->WelcomeMessage,"100%","250px");
    $form[]=$tpl->field_textarea("SubWelcome","{welcome_message} (2)",$sock->SubWelcome,"100%","250px");
    $form[]=$tpl->field_textarea("SuccessTitle","{success} {title2}",$sock->SuccessTitle,"100%","250px");
    $form[]=$tpl->field_textarea("SuccessWelcome","{welcome_message} {success}",$sock->SuccessWelcome,"100%","250px");



    $form[]=$tpl->field_section("Active Directory");
    $form[]=$tpl->field_textarea("DomainAccount","{label} {username} (Active Directory)",$sock->DomainAccount,"100%","50px");
    $form[]=$tpl->field_textarea("WelcomeMessageActiveDirectory","{welcome_message} (Active Directory)",$sock->WelcomeMessageActiveDirectory,"100%","250px");

    $form[]=$tpl->field_section("{vouchers_mananger}");
    $form[]=$tpl->field_textarea("LabelVoucher","{label} {username}",$sock->LabelVoucher,"100%","50px");
    $form[]=$tpl->field_textarea("VoucherExplain","{welcome_message}",$sock->VoucherExplain,"100%","250px");
    $form[]=$tpl->field_textarea("VoucherDevice","{lock} {computer}",$sock->VoucherDevice,"100%","250px");



    $form[]=$tpl->field_section("{register}");
    $form[]=$tpl->field_textarea("RegisterTitle","{register} {title2}",$sock->RegisterTitle,"100%","50px");
    $form[]=$tpl->field_textarea("RegisterButton","{register} {button}",$sock->RegisterButton,"100%","50px");
    $form[]=$tpl->field_textarea("REGISTER_MESSAGE_EXPLAIN","{register_explain}",$sock->REGISTER_MESSAGE_EXPLAIN,"100%","250px");
    $form[]=$tpl->field_textarea("REGISTER_MESSAGE_SUCCESS","{smtp_register_message_success}",$sock->REGISTER_MESSAGE_SUCCESS);

    $form[]=$tpl->field_textarea("REGISTER_MESSAGE_TIMEOUT","{register_explain} {timeout}",$sock->REGISTER_MESSAGE_TIMEOUT);




    $form[]=$tpl->field_textarea("LabelEmail","{label} {email}",$sock->LabelEmail,"100%","50px");
    $form[]=$tpl->field_textarea("ErrorInvalidMail","{error_email_invalid}",$sock->ErrorInvalidMail,"100%","50px");
    $form[]=$tpl->field_textarea("REGISTER_SUBJECT","{message}: {subject}",$sock->REGISTER_SUBJECT,"100%","50px");
    $form[]=$tpl->field_textarea("REGISTER_MESSAGE","{message}: {body}",$sock->REGISTER_MESSAGE,"100%","50px");

    $form[]=$tpl->field_section("{button}");
    $form[]=$tpl->field_textarea("LabelConfirm","{label} {confirm}",$sock->LabelConfirm,"100%","50px");
    $form[]=$tpl->field_textarea("ConnectionButton","{connection} {button}",$sock->ConnectionButton,"100%","50px");
    $form[]=$tpl->field_textarea("AcceptButton","{accept} {button}",$sock->AcceptButton,"100%","50px");
    $form[]=$tpl->field_textarea("SubmitButton","{submit} {button}",$sock->SubmitButton,"100%","50px");

    $form[]=$tpl->field_section("{Terms_Conditions}");
    $form[]=$tpl->field_textarea("TERMS_TITLE","{title2}",$sock->TERMS_TITLE,"100%","250px");
    $form[]=$tpl->field_textarea("TERMS_EXPLAIN","{Terms_Conditions_explain}",$sock->TERMS_EXPLAIN,"100%","250px");
    $form[]=$tpl->field_textarea("TERMS_CONDITIONS","{Terms_Conditions}",$sock->TERMS_CONDITIONS,"100%","250px");

    $form[]=$tpl->field_section("{errors}");
    $form[]=$tpl->field_textarea("authentication_failed","{authentication_failed}",$sock->authentication_failed,"100%","50px");
    $form[]=$tpl->field_textarea("ErrorThisAccountExists","{this_account_already_exists}",$sock->ErrorThisAccountExists,"100%","50px");
    $form[]=$tpl->field_textarea("PasswordMismatch","{password_mismatch}",$sock->PasswordMismatch,"100%","50px");
    $form[]=$tpl->field_textarea("SessionExpired","{session_expired}",$sock->SessionExpired,"100%","50px");

    $form[]=$tpl->field_textarea("ArticaSplashHotSpotRedirectText","{ArticaSplashHotSpotRedirectText}",$sock->ArticaSplashHotSpotRedirectText,"100%","250px");

    $form[]=$tpl->field_section("{template} {options}");
    $form[]=$tpl->field_textarea("FORM_HEAD","{header_form}",$sock->FORM_HEAD,"100%","250px");
    $form[]=$tpl->field_textarea("FooterText","{footer_text}",$sock->FooterText,"100%","250px");
    $form[]=$tpl->field_color("DivLeftColor","{font_color} {left} ($sock->DivLeftColor)",$sock->DivLeftColor);
    $form[]=$tpl->field_color("DivLeftBGColor","{background_color} {left} ($sock->DivLeftBGColor)",$sock->DivLeftBGColor);

    $form[]=$tpl->field_color("DivRigthColor","{font_color} {right} ($sock->DivRigthColor)",$sock->DivRigthColor);
    $form[]=$tpl->field_color("DivRigthBGColor","{background_color} {right} ($sock->DivRigthBGColor)",$sock->DivRigthBGColor);
    $form[]=$tpl->field_color("DivRigthButtonColor","{button_color} {right} ($sock->DivRigthButtonColor)",$sock->DivRigthButtonColor);
    $form[]=$tpl->field_color("DivRigthButtonTextColor","{button} {font_color} {right} ($sock->DivRigthButtonTextColor)",$sock->DivRigthButtonTextColor);
    $form[]=$tpl->field_color("DivRigthButtonHoverColor","{button_color} {right} (hover)($sock->DivRigthButtonHoverColor)",$sock->DivRigthButtonHoverColor);
    $form[]=$tpl->field_upload_image("DivBackgroundImage","{background_image}",$sock->DivBackgroundImage);
    $form[]=$tpl->field_checkbox("HotSpotBackToDefaults","{back_to_defaults}");


    $jsrestart = $tpl->framework_buildjs("/proxy/hotspot/install",
        "hotspot-web.progress",
        "hotspot-web.log",
        "progress-hotspot-restart");

    $HotSpotListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotListenPort"));
    $Host=$_SERVER["SERVER_ADDR"];
    $REMOTE_ADDR=$_SERVER["REMOTE_ADDR"];
    if(strpos($Host,":")>0){
        $tb=explode(":",$Host);
        $Host=$tb[0];
    }

    if($HotSpotListenPort==0){$HotSpotListenPort=8025;}

    $page=CurrentPageName();
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsrestart,"AsHotSpotManager",true);

    $uri="s_PopUpFull('http://$Host:$HotSpotListenPort/hotspot.php?info=eyJNQUMiOiIiLCJTUkMiOiIxOTIuMTY4LjEuMjQ4IiwiRU1BSUwiOiIiLCJLRVkiOiIxOTIuMTY4LjEuMjQ4In0=&ip=$REMOTE_ADDR&user=&url=http://www.google.com/',1024,768,'Monitor');";

    $topbuttons[] = array($uri, ico_loupe, "{view}:{portal_page}");
    $topbuttons[] = array("Loadjs('$page?skin-tabs=yes')", ico_list, "{tabs}");
    $topbuttons[] = array($jsrestart, ico_refresh, "{reconfigure}");




    $TINY_ARRAY["TITLE"]="{web_portal_authentication}: {skins}";
    $TINY_ARRAY["ICO"]="fad fa-pencil-alt";
    $TINY_ARRAY["EXPL"]="{hotspot_skin_explain}";
    $TINY_ARRAY["URL"]="hotspot-config";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";


}
function skin_tabs_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{tabs}","$page?skin-tabs-popup=yes");
}
function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $SockTemplate=new wifidog_settings();

    if(isset($_POST["HotSpotBackToDefaults"])) {
        if ($_POST["HotSpotBackToDefaults"] == 1) {
            unset($_POST["HotSpotBackToDefaults"]);
            foreach ($_POST as $key => $value) {
                $_POST[$key] = "";

            }
        }
    }

    foreach ($_POST as $key=>$value) {
        $value=utf8_encode_switch($value);
        $SockTemplate->SET_INFO($key,$value);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");
    return admin_tracks("Saving HotSpot skins");
}

function skin_tabs_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new wifidog_templates();
    $HotSpotTabsOrder=$sock->HotSpotTabsOrder;
    if(strlen($HotSpotTabsOrder)==0){
        $HotSpotTabsOrder="ad,voucher,selfregister,google";
    }
    $currentOrder=array_filter(array_map('trim',explode(",",$HotSpotTabsOrder)));

    $tabMeta=array(
        "ad"            =>array("icon"=>"fas fa-building",     "label"=>"Active Directory / LDAP", "color"=>"#1c84c6"),
        "voucher"       =>array("icon"=>"fas fa-ticket-alt",   "label"=>"{vouchers_mananger}",     "color"=>"#1ab394"),
        "selfregister"  =>array("icon"=>"fas fa-user-plus",    "label"=>"{register}",              "color"=>"#f8ac59"),
        "google"        =>array("icon"=>"fab fa-google",       "label"=>"Google OAuth",            "color"=>"#ed5565"),
    );
    $allKeys=array("ad","voucher","selfregister","google");

    // Ensure all known tabs are in the list (append missing ones at the end)
    foreach($allKeys as $k){
        if(!in_array($k,$currentOrder)) $currentOrder[]=$k;
    }
    // Remove unknown entries
    $currentOrder=array_values(array_intersect($currentOrder,$allKeys));

    $h=array();
    $h[]="<div id='tabs-save-result'></div>";
    $h[]="<p class='text-muted' style='margin-bottom:12px'><i class='fas fa-arrows-alt-v'></i> {drag_to_reorder}</p>";
    $h[]="<ul id='tabs-sortable' style='list-style:none;padding:0;margin:0'>";

    foreach($currentOrder as $key){
        $m=$tabMeta[$key];
        $icon=$m["icon"];
        $label=$m["label"];
        $color=$m["color"];
        $eKey=htmlspecialchars($key);
        $h[]="<li data-key='$eKey' style='margin-bottom:8px;cursor:move;'>";
        $h[]="  <div style='display:flex;align-items:center;padding:12px 15px;background:#fff;border:1px solid #e7eaec;border-left:4px solid $color;border-radius:4px'>";
        $h[]="    <i class='fas fa-grip-vertical' style='color:#ccc;margin-right:12px;font-size:16px'></i>";
        $h[]="    <i class='$icon' style='color:$color;margin-right:10px;font-size:18px'></i>";
        $h[]="    <span style='font-weight:600;font-size:14px'>$label</span>";
        $h[]="    <span class='badge' style='margin-left:auto;background:$color;color:#fff'>$eKey</span>";
        $h[]="  </div>";
        $h[]="</li>";
    }
    $h[]="</ul>";

    $h[]="<div style='margin-top:15px;text-align:right'>";
    $h[]="  <button class='btn btn-default' style='margin-right:8px' onclick='TabsReset()'>";
    $h[]="    <i class='fas fa-undo'></i> {back_to_defaults}</button>";
    $h[]="  <button class='btn btn-primary' onclick='TabsSave()'>";
    $h[]="    <i class='fas fa-save'></i> {save}</button>";
    $h[]="</div>";

    // jQuery UI Sortable is available via the angular plugins
    $h[]="<link rel='stylesheet' href='css/jquery-ui/jquery-ui.min.css'>";
    $h[]="<script src='js/jquery-ui.min.js'></script>";
    $h[]="<script>";
    $h[]="  \$('#tabs-sortable').sortable({axis:'y',handle:'.fa-grip-vertical',placeholder:'ui-sortable-placeholder',";
    $h[]="    start:function(e,ui){ ui.placeholder.height(ui.item.outerHeight()-4);ui.placeholder.css({'border':'2px dashed #1c84c6','border-radius':'4px','background':'#f0f8ff','margin-bottom':'8px'}); }";
    $h[]="  });";
    $h[]="  function _getOrder(){";
    $h[]="    var o=[];";
    $h[]="    \$('#tabs-sortable li').each(function(){ o.push(\$(this).data('key')); });";
    $h[]="    return o.join(',');";
    $h[]="  }";
    $h[]="  function TabsSave(){";
    $h[]="    \$('#tabs-save-result').html('<div class=\"alert alert-info\"><i class=\"fas fa-spinner fa-spin\"></i></div>');";
    $h[]="    \$.post('$page',{'save-tabs-order':_getOrder()},function(r){";
    $h[]="      \$('#tabs-save-result').html(r);";
    $h[]="    });";
    $h[]="  }";
    $h[]="  function TabsReset(){";
    $h[]="    var def=['ad','voucher','selfregister','google'];";
    $h[]="    var ul=\$('#tabs-sortable');";
    $h[]="    for(var i=0;i<def.length;i++){";
    $h[]="      ul.append(ul.find('li[data-key=\"'+def[i]+'\"]'));";
    $h[]="    }";
    $h[]="  }";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
    return true;
}

function save_tabs_order():void{
    $tpl=new template_admin();
    $order=trim($_POST["save-tabs-order"] ?? '');
    $valid=array("ad","voucher","selfregister","google");
    $parts=array_filter(array_map('trim',explode(",",$order)));
    // Sanitize: only allow known tab keys
    $clean=array();
    foreach($parts as $p){
        if(in_array($p,$valid) && !in_array($p,$clean)){
            $clean[]=$p;
        }
    }
    if(empty($clean)){
        $clean=$valid;
    }
    $value=implode(",",$clean);
    $SockTemplate=new wifidog_settings();
    $SockTemplate->SET_INFO("HotSpotTabsOrder",$value);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");

    echo $tpl->_ENGINE_parse_body($tpl->div_success("<i class='fas fa-check-circle'></i> {saved}: $value"));
    admin_tracks("HotSpot tabs order changed to: $value");
}

function utf8_encode_switch($string):string{
    if(is_null($string)){
        return "";
    }
    if(PHP_MAJOR_VERSION>7) {
        return $string;
    }
    $tpl=new template_admin();
    return $tpl->utf8_encode($string);

}