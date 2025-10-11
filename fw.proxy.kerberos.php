<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["auth_param_ntlm_children"])){Save();exit;}
if(isset($_GET["js"])){js();exit;}

page();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{kerberos_authentication}","$page",1200);
    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidClientParams=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));
    if(!is_numeric($SquidClientParams["auth_param_ntlm_children"])){$SquidClientParams["auth_param_ntlm_children"]=20;}
    if(!is_numeric($SquidClientParams["auth_param_ntlm_startup"])){$SquidClientParams["auth_param_ntlm_startup"]=0;}
    if(!is_numeric($SquidClientParams["auth_param_ntlm_idle"])){$SquidClientParams["auth_param_ntlm_idle"]=1;}
    if($SquidClientParams["auth_param_ntlm_children"]<5){$SquidClientParams["auth_param_ntlm_children"]=5;}
    if($SquidClientParams["auth_param_ntlm_startup"]<5){$SquidClientParams["auth_param_ntlm_startup"]=5;}
    if($SquidClientParams["auth_param_ntlm_idle"]<1){$SquidClientParams["auth_param_ntlm_idle"]=1;}


    $jsrestart=$tpl->framework_buildjs("/proxy/nohup/reconfigure","squid.articarest.nohup","squid.articarest.log","progress-squidauth-restart",
        "LoadAjax('table-loader-squid-auth','fw.proxy.members.php?tabs=yes');");


   $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/auth/status"));


   if(property_exists($json,"Authenticators")) {
       $ACTIVE = $json->Authenticators->NumProcesses;
       $MAX = $json->Authenticators->MaxProcesses;

       $SENT = $json->Authenticators->AuthenticationQueries;
       $AVG = $json->Authenticators->AvgTime;

   }
   $PERC_ACT=0;
   $PERC_SENT=0;
   $PERC_TIMEOUT=0;


   if($MAX>0) {
       $PERC_ACT = round(($ACTIVE / $MAX) * 100, 0);
   }

    $P3="progress-bar";


    $SENT=FormatNumber($SENT);

    $f[]="<div class=\"ibox-content\" style='width:400px'>";
    $f[]="<div>";
    $f[]="<div>";
    $f[]="<span>{processes}</span>";
    $f[]="<small class=\"pull-right\">$ACTIVE/$MAX</small>";
    $f[]=" </div>";
    $f[]=" <div class=\"progress progress-small\">";
    $f[]="<div style=\"width: {$PERC_ACT}%;\" class=\"progress-bar\"></div>";
    $f[]=" </div>";
    $f[]="";
    $f[]=" <div>";
    $f[]="<span>{requests}: $SENT (($AVG))</span>";
    $f[]=" <div class=\"progress progress-small\">";
    $f[]="<div style=\"width: {$PERC_SENT}%;\" class=\"progress-bar\"></div>";
    $f[]=" </div>";
    $f[]="";
    $f[]=" <div>";
    $f[]=" </div>";
    $f[]=" <div class=\"progress progress-small\">";
    $f[]="<div style=\"width: {$PERC_TIMEOUT}%;\" class=\"$P3\"></div>";
    $f[]=" </div>";
    $f[]="";
    $f[]="   </div>";

    $left=@implode("\n",$f);
    //progress-squidauth-restart


    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='width:400px;vertical-align: top'>$left</td>";
    $html[]="<td style='width:80%;vertical-align: top'>";
    $html[]="<div class=\"ibox-content\">";

    $form[]=$tpl->field_numeric("auth_param_ntlm_children","{CHILDREN_MAX} ({processes})",$SquidClientParams["auth_param_ntlm_children"]);
    $form[]=$tpl->field_numeric("auth_param_ntlm_startup","{CHILDREN_STARTUP} ({processes})",$SquidClientParams["auth_param_ntlm_startup"]);
    $form[]=$tpl->field_numeric("auth_param_ntlm_idle","{CHILDREN_IDLE} ({processes})",$SquidClientParams["auth_param_ntlm_idle"]);

    $html[]=$tpl->form_outside("{squid_plugins}", $form,"{SquidClientParams_text}","{apply}","dialogInstance2.close();$jsrestart","AsSquidAdministrator");
    $html[]="</div></td>";
    $html[]="</tr>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $SquidClientParams=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidClientParams"));

    foreach ($_POST as $key=>$val){
        $SquidClientParams[$key]=$val;
    }
    $SquidClientParams_new=base64_encode(serialize($SquidClientParams));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidClientParams",$SquidClientParams_new);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
