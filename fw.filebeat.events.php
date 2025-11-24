<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){events_searcher();exit;}
if(isset($_GET["start"])){events_popup();exit;}

page();

function page(){
    $page=CurrentPageName();
    $Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FILEBEAT_VERSION");
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_FILEBEAT} {events}",ico_eye,
        "{APP_FILEBEAT_EXPLAIN}","$page?start=yes","filebeat-events","progress-filebeat-restart",
        false,"table-filebeat-status");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_FILEBEAT}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}


function events_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&events-searcher=yes");
    echo "</div>";


    $TINY_ARRAY["TITLE"]="{APP_FILEBEAT} {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_FILEBEAT_EXPLAIN}";
    $TINY_ARRAY["URL"]="filebeat-events";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script>$jstiny</script>";
    return true;
}


function events_searcher():bool{

    $tpl=new template_admin();
    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]="*";}
    $search["S"]=str_replace("%",".*",$search["S"]);
    $ss=urlencode(base64_encode($search["S"]));
    $MAX=intval($search["MAX"]);
    if($MAX==0){$MAX=250;}
    $EndPoint="/filebeat/events/$ss/$MAX";

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    $tooltips["paused"]="<label class='label label-warning'>{paused}</label>";
    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["warn"]="<label class='label label-warning'>{warn}</label>";
    $tooltips["warning"]="<label class='label label-warning'>{warn}</label>";
    $tooltips["stop"]="<label class='label label-warning'>{stopping}</label>";
    $tooltips["error"]="<label class='label label-danger'>{error}</label>";
    $tooltips["err"]="<label class='label label-danger'>{error}</label>";
    $tooltips["start"]="<label class='label label-primary'>{starting}</label>";
    $tooltips["stats"]="<label class='label label-info'>{statistics}</label>";
    $tooltips["info"]="<label class='label label-info'>{info}</label>";
    $tooltips["update"]="<label class='label label-primary'>{update2}</label>";

    $text["error"]="text-danger";
    $text["warn"]="text-warning font-bold";
    $text["info"]="text-muted";

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{level}</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    foreach ($json->Logs as $line){

        $sline=json_decode($line);
        if (json_last_error()> JSON_ERROR_NONE) {
            continue;
        }

        $textclass=null;
        $level=$sline->{"log.level"};
        $timestamp=$sline->{"@timestamp"};
        $message=$sline->message;
        $level_label=$tooltips[$level];
        $xtime=strtotime($timestamp);
        $FTime=$tpl->time_to_date($xtime,true);
        if(isset($text[$level])){
            $textclass=$text[$level];
        }

        $html[]="<tr>
				<td style='width:1%;' nowrap class='$textclass'>$FTime</td>
				<td style='width:1%;' nowrap class='$textclass'>$level_label</td>
    			<td class='$textclass'>$message</td>
				</tr>";

    }
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}