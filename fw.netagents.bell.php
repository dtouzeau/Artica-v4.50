<?php


/**
 * Artica Meta console
 * Tools to push any file from the repository by giving the type and the file path
 */
$GLOBALS["ALLOWED_TYPES"] = array("official" => true, "servicepack" => true, "hotfix" => true, "software" => true);
include_once dirname(__FILE__) . '/ressources/class.template-admin.inc';
include_once dirname(__FILE__) . '/ressources/class.sockets.inc';
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(isset($_GET["notification"])){notifications_popup();exit;}
js();

function js():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $users=new usersMenus();
    if(!$users->AsArticaMetaAdmin){
        return $tpl->js_error("{no_privileges}");
    }
    $id=$_GET["id"];
    $net=new ArticaNetAgents($id);
    $Name=$net->GetAgentHostname();
    return $tpl->js_dialog6("{notifications} $Name", "$page?notification=$id", 800);
}
function notifications_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $id=intval($_GET["notification"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    if(json_last_error()>JSON_ERROR_NONE){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning(json_last_error_msg()));
        return false;
    }

    if(!isset($json->artica_bell) || !is_object($json->artica_bell)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_notifications}"));
        return false;
    }


    $bell=$json->artica_bell;
    if(!isset($bell->notifications) || !is_array($bell->notifications) || count($bell->notifications)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_notifications}"));
        return false;
    }

    $t=time();
    $html=array();

    if(isset($bell->count)){
        $html[]="<H2>$bell->count {notifications}</H2>\n";
    }


    $html[]="<table id='table-bell-$t' class='table table-stripped' data-page-size='100' data-paging='true'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text' style='width:1%'>{severity}</th>";
    $html[]="<th data-sortable=true data-type='text'>{title}</th>";
    $html[]="<th data-sortable=true data-type='text'>{description}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    foreach($bell->notifications as $notif){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $title=isset($notif->title) ? htmlspecialchars($notif->title) : "-";
        $description=isset($notif->description) ? htmlspecialchars($notif->description) : "";
        $severity=isset($notif->severity) ? $notif->severity : "INFO";
        $severityBadge=_severity_badge($severity);

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%' nowrap>$severityBadge</td>";
        $html[]="<td><strong>$title</strong></td>";
        $html[]="<td style='width:60%'>$description</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr><td colspan='3'><ul class='pagination pull-right'></ul></td></tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

function _severity_badge(string $severity):string{
    switch($severity){
        case "DANGER":
            return "<span class='label label-danger'><i class='fas fa-exclamation-triangle'></i> DANGER</span>";
        case "WARN":
            return "<span class='label label-warning' style='color:#fff'><i class='fas fa-exclamation-circle'></i> WARN</span>";
        case "INFO":
            return "<span class='label label-info'><i class='fas fa-info-circle'></i> INFO</span>";
        default:
            return "<span class='label label-default'>$severity</span>";
    }
}
