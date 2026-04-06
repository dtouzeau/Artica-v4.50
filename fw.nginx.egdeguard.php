<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__) . "/ressources/class.logfile_daemon.inc");
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["file-uploaded"])){saveOTP();exit;}
if(isset($_POST["edgeguard_central_ip"])){save();exit;}
if(isset($_GET["callback"])){save_callback();exit;}
if(isset($_POST["edgeguard_unlink"])){unlinkAgent();exit;}


page();
function unlinkAgent(){

}


function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();

    $json=json_decode($sock->REST_API_NGINX("/edgeguard/status"));

    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);

    $ARTICA_EDGEGUARD_AGENT_VER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_EDGEGUARD_AGENT_VER"));

    $restartService=$tpl->framework_buildjs(
        "nginx:/edgeguard/restart",
        "edgeguard.restart.progress",
        "edgeguard.restart.progress.logs",
        "progress-edgeguard-restart",
        "LoadAjax('edgeguard-table','$page?table=yes');"

    );


    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_EDGEGUARD_AGENT",$restartService,$ARTICA_EDGEGUARD_AGENT_VER);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/edgeguard/agent/info"),true);
    if(!empty($json['sites'])){
        if($json['sites'][0]['Status']=="active"){
            $final[]=$tpl->widget_vert("{success}","<b>{status}:</b> ".$json['sites'][0]['Status']."<br><b>{last_update}:</b> ".$json['sites'][0]['UpdatedAt']['String']);

        }
        else{
            $final[]=$tpl->widget_rouge("{warning}","<b>{status}:</b> ".$json['sites'][0]['Status']."<br><b>{last_update}:</b> ".$json['sites'][0]['UpdatedAt']['String']);

        }
    }

    echo $tpl->_ENGINE_parse_body($final);

};
function int_or_default($value, $default, $min = null, $max = null) {
    $value = (int)$value;

    if ($min !== null && $value < $min) return $default;
    if ($max !== null && $value > $max) return $default;

    return $value ?: $default;
}
function save()
{
    $page=CurrentPageName();
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    if (empty($_POST['edgeguard_central_ip'])) {
        echo "jserror:Central IP/Hostname cannot be empty.";
        return;
    }
    if (strlen($_POST['edgeguard_central_ip'])<4) {
        echo "jserror:Central IP/Hostname length must be at least 4 characters.";
        return;
    }
    if (intval($_POST['edgeguard_central_port'])==0) {
        echo "jserror:Central port can not be zero.";
    }
    if (strlen($_POST['edgeguard_otp'])<4) {
        echo "jserror:OTP length must be at least 4 characters.";
        return;
    }
    if (strlen($GLOBALS["CLASS_SOCKETS"]->GET_INFO("edgeguard_agent_enroll_file"))<4) {
        echo "jserror:Enroll File not uploaded!";
        return;
    }
    $_POST['edgeguard_central_port']     = int_or_default($_POST['edgeguard_central_port'] ?? 0, 8082, 1, 65535);
    $url="https://".$_POST['edgeguard_central_ip'].":".$_POST['edgeguard_central_port'];

    $_SESSION["EDGEGUARD_URL"]=array($url,$_POST["edgeguard_otp"]);

}

function save_callback():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    if(!isset($_SESSION["EDGEGUARD_URL"])){
        echo "jserror:EdgeGuard URL cannot be empty.;";
        return false;
    }
    $tpl=new template_admin();

    list($url,$edgeguard_otp)=$_SESSION["EDGEGUARD_URL"];
    $url=urlencode($url);
    echo $tpl->framework_buildjs(
        "nginx:/edgeguard/link/agent/$url/$edgeguard_otp","edgeguard.restart.progress","edgeguard.restart.progress.logs",
        "progress-edgeguard-restart","LoadAjax('edgeguard-table','$page?table=yes');"

    );
    unset($_SESSION["EDGEGUARD_URL"]);
    return true;
}

function saveOTP()
{
    $page=CurrentPageName();
    $tpl = new template_admin();
    $otp=$_GET["file-uploaded"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("edgeguard_agent_enroll_file",$otp);
    echo $tpl->js_display_results("{upload_enroll_success}",false,"{upload_success}");

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{edgeguard} {agent}",
        "fa-solid fa-block-brick-fire","{edgeguard_explain}","$page?table=yes","edgeguard-agent","progress-edgeguard-restart");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    echo $tpl->_ENGINE_parse_body($html);

}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $users=new usersMenus();
    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:400px;vertical-align:top;'>";
    $html[]="<div id='cluster-status' style='width:400px'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>";
    $html[]="<div id='edgeguard-table'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $w=270;
    $btn2[]=$tpl->button_autnonome("WIKI","s_PopUp('https://wiki.articatech.com/en/reverse-proxy/edgeguard','1024','800')",ico_support,null,$w,"btn-warning",$w);

    $TINY_ARRAY["TITLE"]="{edgeguard} {agent}";
    $TINY_ARRAY["ICO"]="fa-solid fa-block-brick-fire";
    $TINY_ARRAY["EXPL"]="{edgeguard_explain}";
    $TINY_ARRAY["URL"]="edgeguard-agent";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn2);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $jsRefresh=$tpl->RefreshInterval_js("cluster-status",$page,"status=yes");
    $html[]="<script>";
    $html[]="LoadAjax('edgeguard-table','$page?table1=yes');";
    $html[]=$jstiny;
    $html[]=$jsRefresh;
    $html[]="</script>";
    echo @implode("",$html);
    return true;
}

function table1():bool
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=True;
    $IS_LICENSE=IS_LICENSE();
    $security="AsSystemAdministrator";
    $disableForm=false;
    if(!IS_LICENSE()){
        $form[]=$tpl->div_error("{license_invalid}");
        $disableForm=true;
    }
    else{
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/edgeguard/agent/info"),true);
        if(intval($json["NginxVersionMinor"])>=27){
            if(empty($json['sites'])){
                $form[]=$tpl->field_text("edgeguard_central_ip","{central_ip}","",true);
                $form[]=$tpl->field_numeric("edgeguard_central_port","{central_port}","8082");
                $form[]=$tpl->field_text("edgeguard_otp","{otp}","",true);
                $form[] = $tpl->field_button_upload("{edguard_enroll_file}","file-uploaded",".edgeguard");
                $text="{link}";
                $callback="Loadjs('$page?callback=yes');";
            }
            else{
                //Array ( [Status] => 1 [sites] => Array ( [0] => Array ( [AgentID] => 0dbe28e9-1274-4dac-ac98-ca808e03731b [AgentName] => Agent1 [OrgID] => Array ( [Int64] => 1 [Valid] => 1 ) [CentralURL] => https://192.168.60.36:8082 [CertFingerprint] => Array ( [String] => [Valid] => 1 ) [EnrolledAt] => Array ( [String] => [Valid] => 1 ) [UpdatedAt] => Array ( [String] => 2026-03-26 16:18:07 [Valid] => 1 ) ) ) [Error] => )
                //print_r($json['sites']);
                if($json['sites'][0]['Status']=="active"){
                    $callback=$tpl->framework_buildjs(
                        "nginx:/edgeguard/unlink/agent","edgeguard.restart.progress","edgeguard.restart.progress.logs",
                        "progress-edgeguard-restart","LoadAjax('edgeguard-table','$page?table=yes');"

                    );
                }
                else{
                    $disableForm=true;
                    $callback=null;
                }
                $form[]=$tpl->field_hidden("edgeguard_unlink","");
                $form[]=$tpl->field_text("edgeguard_name","{agent_name}",$json['sites'][0]['AgentName'],true,null,true,true);
                $form[]=$tpl->field_text("edgeguard_id","{agent_id}",$json['sites'][0]['AgentID'],true,null,true,true);
                $form[]=$tpl->field_text("edgeguard_central_ip","{central_ip}",$json['sites'][0]['CentralURL'],true,null,true,true);
                $text="{unlink}";


            }
        }
        else {
            $form[]=$tpl->div_error("{nginx_version_not_supported}");
            $disableForm=true;
        }
    }
    $html[]=$tpl->form_outside("{general_settings}",
        @implode("\n", $form),null,$text,$callback,$security,false,$disableForm);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    //$html[]="<script>LoadAjaxTiny('cluster-status','$page?status=yes');</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


    return true;

}
function IS_LICENSE():bool{
    VERBOSE("IS_LICENSE",__LINE__);
    $IS_LICENSE=true;
    $LICJSON=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/count/license"));
    if (json_last_error()== JSON_ERROR_NONE) {
        if(!property_exists($LICJSON,"ActiveRules")){
            return true;
        }

        if (intval($LICJSON->ActiveRules)<2){
            return true;
        }
        return $LICJSON->Status;
    }
    return $IS_LICENSE;
}