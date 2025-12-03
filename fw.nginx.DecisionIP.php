<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_POST["register"])){register_save();exit;}
if(isset($_GET["register-js"])){register_js();exit;}
if(isset($_GET["register-popup"])){register_popup();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["center-flat"])){flat_config();exit;}

page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;


    $html=$tpl->page_header("DecisionIP","fas fa-helmet-battle",
        "{decisionIPAbout}","$page?table-start=yes",
        "decisionip","progress-decisionip-restart",false);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("DecisionIP",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function register_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{register}","$page?register-popup=yes",650);
}
function table_start():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:250px;vertical-align: top'></td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>
    <div id='decision-center'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$tpl->RefreshInterval_js("decision-center",$page,"center-flat=yes");
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function flat_config():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsonData=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DecisionIPJson");
    echo "<H1>DATA:!$jsonData</H1>";
    if(strlen($jsonData)<5){
        $go=$tpl->button_autnonome("{register}","Loadjs('$page?register-js=yes');",ico_field,"AsWebMaster",335,"btn-warning");
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{freeintegration}||<p style='font-size:16px'>{decisionIPAboutErr1}</p><div style='text-align:right;margin:30px'>$go</div>"));
        return true;
    }
    $json=json_decode($jsonData);
    var_dump($json);


    return true;
}
function register_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $form[]=$tpl->field_hidden("register","yes");
    $form[]=$tpl->field_email("email","{email}","",true);


    echo "<div id='register-progress' style='margin-bottom: 10px'></div>";

    $js=$tpl->framework_buildjs("/decisionip/register","decisionip.progress",
        "decisionip.log","register-progress","dialogInstance1.close();");

    echo $tpl->form_outside("",$form,"{registereMailVerif}","{apply}",$js);
    return true;
}
function register_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DecisionIPeMail",$_POST["email"]);
    return admin_tracks("Set {$_POST["email"]} for DecisionIP registration process");
}