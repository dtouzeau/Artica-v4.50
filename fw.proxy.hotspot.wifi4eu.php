<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
if (!isset($GLOBALS["CLASS_SOCKETS"])) {
    if (!class_exists("sockets")) {
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"] = new sockets();
}

include_once("/usr/share/artica-postfix/ressources/class.wifidog.templates.inc");

$users = new usersMenus();
if (!$users->AsSquidAdministrator) {
    $users->pageDie();
}
if (isset($_GET["verbose"])) {
    $GLOBALS["VERBOSE"] = true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string', null);
    ini_set('error_append_string', null);
}
if (isset($_POST["WIFI4EUTEXTH1"])) {
    Save();
    exit;
}
if (isset($_GET["table"])) {
    table();
    exit;
}
if (isset($_GET["tabs"])) {
    tabs();
    exit;
}

page();


function page()
{
    $tpl = new template_admin();
    $sock = new wifidog_templates();
    //WIFI4UE
    $HotSpotWIFI4EU_ENABLE = intval($sock->HotSpotWIFI4EU_ENABLE);
    if ($HotSpotWIFI4EU_ENABLE == 0) {
        echo $tpl->_ENGINE_parse_body("<b style='color:red'>WIFI4EU {disable}</b>");
        return false;
    }
    $textsixe[12] = "12";
    $textsixe[13] = "13";
    $textsixe[14] = "14";
    $textsixe[15] = "15";
    $textsixe[16] = "16";
    $textsixe[17] = "17";
    $textsixe[18] = "18";
    $textsixe[19] = "19";
    $textsixe[20] = "20";
    $textsixe[21] = "21";
    $textsixe[22] = "22";
    $textsixe[23] = "23";
    $textsixe[24] = "24";
    $textsixe[25] = "25";
    $textsixe[26] = "26";
    $textsixe[27] = "27";
    $textsixe[28] = "28";
    $textsixe[29] = "29";
    $textsixe[30] = "30";
    $textsixe[31] = "31";
    $textsixe[32] = "32";


    $form[] = $tpl->field_section("{APP_WIFI4EU_TEMPLATE}", null);
    $form[] = $tpl->field_textarea("WIFI4EUTEXTH1", "{WIFI4EUTEXTH1}", $sock->WIFI4EUTEXTH1, "100%", "150");
    $form[] = $tpl->field_textarea("WIFI4EUTEXTH2", "{WIFI4EUTEXTH2}", $sock->WIFI4EUTEXTH2, "100%", "150");
    $form[] = $tpl->field_text("WIFI4EUTEXTBTN", "{WIFI4EUTEXTBTN}", $sock->WIFI4EUTEXTBTN);
    $form[] = $tpl->field_checkbox("WIFI4UEENABLETERMS", "{WIFI4UEENABLETERMS}", $sock->WIFI4UEENABLETERMS, "WIFI4UETERMSTEXT,WIFI4UETERMSCONTENT");
    $form[] = $tpl->field_text("WIFI4UETERMSTEXT", "{WIFI4UETERMSTEXT}", $sock->WIFI4UETERMSTEXT);
    $form[] = $tpl->field_textarea("WIFI4UETERMSCONTENT", "{WIFI4UETERMSCONTENT}", $sock->WIFI4UETERMSCONTENT, "100%", "150");
    $form[] = $tpl->field_checkbox("WIFI4UEENABLEPRIVACY", "{WIFI4UEENABLEPRIVACY}", $sock->WIFI4UEENABLEPRIVACY, "WIFI4UEPRIVACYTEXT,WIFI4UEPRIVACYCONTENT");
    $form[] = $tpl->field_text("WIFI4UEPRIVACYTEXT", "{WIFI4UEPRIVACYTEXT}", $sock->WIFI4UEPRIVACYTEXT);
    $form[] = $tpl->field_textarea("WIFI4UEPRIVACYCONTENT", "{WIFI4UEPRIVACYCONTENT}", $sock->WIFI4UEPRIVACYCONTENT, "100%", "150");
    $form[] = $tpl->field_text("WIFI4UEERRORTEXT", "{WIFI4UEERRORTEXT}", $sock->WIFI4UEERRORTEXT);

    $form[] = $tpl->field_color("WIFI4EUTEXTBODYCOLOR", "{font_color} body ($sock->WIFI4EUTEXTBODYCOLOR)", $sock->WIFI4EUTEXTBODYCOLOR);
    $form[] = $tpl->field_color("WIFI4EUTEXTH1COLOR", "{font_color} {WIFI4EUTEXTH1} ($sock->WIFI4EUTEXTH1COLOR)", $sock->WIFI4EUTEXTH1COLOR);
    $form[] = $tpl->field_color("WIFI4EUTEXTH2COLOR", "{font_color} {WIFI4EUTEXTH2} ($sock->WIFI4EUTEXTH2COLOR)", $sock->WIFI4EUTEXTH2COLOR);
    $form[] = $tpl->field_color("WIFI4EUTEXTBTNCOLOR", "{font_color} {WIFI4EUTEXTBTN} ($sock->WIFI4EUTEXTBTNCOLOR)", $sock->WIFI4EUTEXTBTNCOLOR);
    $form[] = $tpl->field_color("WIFI4EUBTNCOLOR", "{color} {button} ($sock->WIFI4EUBTNCOLOR)", $sock->WIFI4EUBTNCOLOR);
    $form[] = $tpl->field_color("WIFI4EULINKCOLOR", "{color} {link} ($sock->WIFI4EULINKCOLOR)", $sock->WIFI4EULINKCOLOR);
    $form[]=$tpl->field_array_hash($textsixe,"WIFI4EUTEXTH1SIZE","{WIFI4EUTEXTH1SIZE}",$sock->WIFI4EUTEXTH1SIZE,false);
    $form[]=$tpl->field_array_hash($textsixe,"WIFI4EUTEXTH2SIZE","{WIFI4EUTEXTH2SIZE}",$sock->WIFI4EUTEXTH2SIZE,false);
    $form[]=$tpl->field_array_hash($textsixe,"WIFI4EUTEXTBODYSIZE","{WIFI4EUTEXTBODYSIZE}",$sock->WIFI4EUTEXTBODYSIZE,false);
    $form[]=$tpl->field_array_hash($textsixe,"WIFI4EUTEXTBUTTONSIZE","{WIFI4EUTEXTBUTTONSIZE}",$sock->WIFI4EUTEXTBUTTONSIZE,false);
    echo $tpl->form_outside(null, $form, null, "{apply}", "", "AsHotSpotManager", true);


}

function Save()
{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $SockTemplate = new wifidog_settings();


    foreach ($_POST as $key => $value) {
        $SockTemplate->SET_INFO($key, $value);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hotspot/templates");

}