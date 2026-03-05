<?php
// Network Agents Management logs page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["hostname"])){save();exit;}
if(isset($_GET["syslog-point"])){syslog_point();exit;}
if(isset($_GET["syslog-search"])){syslog_search();exit;}
if(isset($_GET["search"])){search();exit;}
js();

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    return $tpl->js_dialog11("{hostname}","$page?popup=$id",650);
}

function popup():bool{
    $id=intval($_GET["popup"]);
    $tpl=new template_admin();
    $agent=new ArticaNetAgents($id);
    $Crustatus=$agent->Status();
    echo $tpl->BigDomainField("id:$id|hostname","{hostname}","{hostname_explain}",$Crustatus["hostname"],"dialogInstance11.close();");
    return true;
}
function save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $id=$_POST["id"];
    $agent=new ArticaNetAgents($id);
    $Crustatus=$agent->Status();
    $hostnameOld=$Crustatus["hostname"];

    $data["hostname"] = $_POST["hostname"];

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/system/$id/network", $data
    ));
    if (!is_object($json)) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return false;
    }
    // Agent-level error (networkConfigResponse.success = false)
    if (isset($json->success) && !$json->success) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->error ?? "{error}")));
        return false;
    }
    // Middleware-level error (outFalse → Status = false)
    if (isset($json->Status) && !$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($json->Error ?? "{error}")));
        return false;
    }
    echo $tpl->_ENGINE_parse_body(
        $tpl->div_success("<i class='fas fa-check-circle'></i> {meta_wait_parameters}")
    );
    return admin_tracks("Artica Meta: Success change the hostname $hostnameOld to {$_POST["hostname"]}");
}