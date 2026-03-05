<?php
// Network Agents Management logs page
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["start-point"])){start_point();exit;}
if(isset($_GET["syslog-point"])){syslog_point();exit;}
if(isset($_GET["syslog-search"])){syslog_search();exit;}
if(isset($_GET["search"])){search();exit;}
js();

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    return $tpl->js_dialog11("{events}","$page?tabs=$id",1024);
}
function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["tabs"]);
    $filepath=urlencode(base64_encode("/var/log/debian-agent/agent.log"));
    $tabs["{APP_ARTICA_AGENT}"]="$page?start-point=$id&filepath=$filepath";
    $filepath=urlencode(base64_encode("/var/log/syslog"));
    $tabs["{system_log}"]="$page?syslog-point=$id&filepath=$filepath";
    $html[]="<div style='margin-top:10px'>";
    $html[]=$tpl->tabs_default($tabs);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function start_point():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["start-point"]);
    $filepath=urlencode($_GET["filepath"]);
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&id=$id&filepath=$filepath");
    echo "</div>";
    return true;
}
function syslog_point():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["syslog-point"]);
    $filepath=urlencode($_GET["filepath"]);
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&syslog-search=yes&id=$id&filepath=$filepath");
    echo "</div>";
    return true;
}
function search():bool{
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $filepath=base64_decode($_GET["filepath"] ?? "");
    $search=trim($_GET["search"] ?? "");
    $rows=200;

    if(strlen($search)<2){
        $data=["filepath"=>$filepath,"rows"=>$rows];
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/logs/tail/$id",$data));
    }else{
        $data=["filepath"=>$filepath,"rows"=>$rows,"search"=>$search];
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/logs/search/$id",$data));
    }

    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?htmlspecialchars($json->Error??"{unknown_error}"):"{failed_to_contact_agent}<br>{check_agent_version_explain}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return false;
    }
    if(!$json->success){
        $err=htmlspecialchars($json->message??"{unknown_error}");
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return false;
    }
    $TRCLASS="";
    $lines=$json->lines??[];

    if(empty($lines)){
        $html[]=$tpl->div_info("{no_results}");
        $html[]="<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $count=intval($json->count??count($lines));

    $html[]="<table class='table table-striped table-hover' id='table-agents'>";
    $html[]="<thead><tr>";
    $html[]="  <th>{date}</th>";
    $html[]="  <th>{type}</th>";
    $html[]="  <th>{method}</th>";
    $html[]="  <th nowrap>{query}</th>";
    $html[]="  <th nowrap>{status}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody>";
    $wd1="style='width:1%' nowrap";
    $LEVELS["INFO"]="<span class='label label-default'>INFO</span>";
    $LEVELS["WARNING"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["WARN"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["FATAL"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["DEBUG"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["NOTICE"]="<span class='label label-default'>INFO</span>";
    $LEVELS["TRACE"]="<span class='label label-default'>TRACE</span>";

    foreach ($lines as $line){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $json=json_decode($line,true);

        if(!$json){continue;}
        $date="";
        $level="";
        $method="{none}";
        $query="";
        $textCl="text-muted";
        if(isset($json["time"])){
            $date=$tpl->time_to_date($tpl->GoToTimestamp($json["time"]),true);
        }
        if(isset($json["level"])){
            $levelL=strtoupper($json["level"]);
            $level=$LEVELS[$levelL];
        }
        if(isset($json["method"])){
            $method=$json["method"];
        }
        if(isset($json["query"])){
            $query=$json["query"];
        }
        if(isset($json["message"])){
            $query=$json["message"];
            $textCl="";
        }
        if(isset($json["path"])){
            $query=$query." <small>{$json["path"]}</small>";
        }

        if(isset($json["status"])){
            $status=$json["status"];
        }
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td $wd1 class='$textCl'><strong>$date</strong></td>";
        $html[]="<td $wd1 class='$textCl'>$level</td>";
        $html[]="<td $wd1 class='$textCl'>$method</td>";
        $html[]="<td class='$textCl'>$query</td>";
        $html[]="<td $wd1 class='$textCl'>$status</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
	$html[]="NoSpinner();";
	$html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function syslog_search():bool{
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $filepath=base64_decode($_GET["filepath"] ?? "");
    $search=trim($_GET["search"] ?? "");
    $rows=200;

    if(strlen($search)<2){
        $data=["filepath"=>$filepath,"rows"=>$rows];
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/logs/tail/$id",$data));
    }else{
        $data=["filepath"=>$filepath,"rows"=>$rows,"search"=>$search];
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/logs/search/$id",$data));
    }

    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?htmlspecialchars($json->Error??"{unknown_error}"):"{failed_to_contact_agent}<br>{check_agent_version_explain}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return false;
    }
    if(!$json->success){
        $err=htmlspecialchars($json->message??"{unknown_error}");
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return false;
    }
    $TRCLASS="";
    $lines=$json->lines??[];

    if(empty($lines)){
        $html[]=$tpl->div_info("{no_results}");
        $html[]="<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $count=intval($json->count??count($lines));

    $html[]="<table class='table table-striped table-hover' id='table-agents'>";
    $html[]="<thead><tr>";
    $html[]="  <th>{date}</th>";
    $html[]="  <th>{service}</th>";
    $html[]="  <th nowrap>{pid}</th>";
    $html[]="  <th nowrap>{event}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody>";
    $wd1="style='width:1%' nowrap";
    $LEVELS["INFO"]="<span class='label label-default'>INFO</span>";
    $LEVELS["WARNING"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["WARN"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["FATAL"]="<span class='label label-danger'>ERROR</span>";
    $LEVELS["DEBUG"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["NOTICE"]="<span class='label label-default'>INFO</span>";
    $LEVELS["TRACE"]="<span class='label label-default'>TRACE</span>";

    foreach ($lines as $line){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $m=sFind($line);
        $time=$tpl->time_to_date(syslogToTimestamp("$m[1] $m[2] $m[3]"),true);
        $service=$m[4];
        $pid=$m[5];
        $event=$m[6];
        $textCl="text-muted";

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td $wd1 class='$textCl'><strong>$time</strong></td>";
        $html[]="<td $wd1 class='$textCl'>$service</td>";
        $html[]="<td $wd1 class='$textCl'>$pid</td>";
        $html[]="<td class='$textCl'>$event</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function syslogToTimestamp(string $dateString, ?int $year = null): int|false
{
    if ($year === null) {
        $year = date('Y');
    }

    // Build full date string with year
    $fullDate = $year . ' ' . $dateString;

    // Create DateTime object
    $dt = DateTime::createFromFormat('Y M d H:i:s', $fullDate);

    if ($dt === false) {
        return false;
    }

    return $dt->getTimestamp();
}
function sFind($event):array{
    if(preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\s+(.+?)\[([0-9]+)\]: (.+)#",$event,$m)){
        return $m;
    }

    $z=array();
    if(preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+) .*? ([A-Za-z]+): (.+)#",$event,$m)){

        $z[1]=$m[1];
        $z[2]=$m[2];
        $z[3]=$m[3];
        $z[4]=$m[4];
        $z[5]=0;
        $z[6]=$m[5];
        return $z;
    }
    echo "<li class='text-danger'>$event</li>";
    return array();
}