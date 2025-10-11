<?php

$GLOBALS["MAIN_TITLE"]["filedesc"]="{APP_SQUID} {file_descriptors}";
$GLOBALS["MAIN_TITLE"]["squid_cache"]="{APP_SQUID} {requests}";
$GLOBALS["MAIN_TITLE"]["squidmem"]="{proxy_memory}";
$GLOBALS["MAIN_TITLE"]["system_cpu"]="{system} {cpu} {percentage}";
$GLOBALS["MAIN_TITLE"]["system_memory"]="{system} {memory} MB";
$GLOBALS["MAIN_TITLE"]["proxy_users"]="{APP_PROXY} {members}";


include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
if(isset($_GET["popup"])){popup_start();exit;}
if(isset($_GET["popup2"])){popup();exit;}
if(isset($_POST["dhclient_mac"])){tests();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["results-popup"])){results_popup();exit;}
if(isset($_GET["popup-latency"])){popup_latency();exit;}
if(isset($_GET["popup-explain-load"])){popup_load();exit;}
js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $img=$_GET["img"];
    $title=$GLOBALS["MAIN_TITLE"][$img];
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}
	$tpl->js_dialog6($title, "$page?tabs=$img$flat",1200);
}
function tabs(){
    $img=$_GET["tabs"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}


    if($img=="load"){
        $array["{load}"]="$page?popup-explain-load=yes";
    }

    $array["{this_hour}"]="$page?popup=$img&period=hourly$flat";
    $array["{today}"]="$page?popup=$img&period=day$flat";
    $array["{yesterday}"]="$page?popup=$img&period=yesterday$flat";
    $array["{this_week}"]="$page?popup=$img&period=week$flat";
    $array["{this_month}"]="$page?popup=$img&period=month$flat";
    $array["{this_year}"]="$page?popup=$img&period=year$flat";
    if($DisablePostGres==0) {
        if ($img == "squidlatency") {
            $array["{data}"] = "$page?popup-latency=yes";
        }
    }

    echo $tpl->tabs_default($array);
}
function popup_load(){
    $tpl=new template_admin();
    $div=$tpl->div_explain("{load_avg}||<p style='font-size:16px;line-height: 28pt;padding:20px'>{sysloadexplain}</p>");
    $html[]="<div style='margin-top:48px;padding-left: 91px;padding-right: 91px;'>";
    $html[]="$div";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function popup_start():bool{
    $page=CurrentPageName();
    $img=$_GET["popup"];
    $period=$_GET["period"];
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}
    echo "<div id='progress-rrd-task'></div><div id='popup-rrd-load'></div><script>LoadAjax('popup-rrd-load','$page?popup2=$img&period=$period$flat')</script>";
    return true;
}
function popup(){
    $t=time();
	$tpl=new template_admin();
	$page=CurrentPageName();
    $sock=new sockets();
    $img=$_GET["popup2"];
    $period=$_GET["period"];
    $flat="";$flat2="";
    if(isset($_GET["flat"])){$flat=".flat";$flat2="&flat=yes";}
    $path=dirname(__FILE__)."/img/squid/$img-$period$flat.png";

    if($img=="squidlatency"){
        if(!is_file($path)){
            $sock->REST_API("/proxy/graphs/latency");
        }
    }

    $js = $tpl->framework_buildjs("/rrd/allimages", "progress-rrd.compile",
        "progress-rrd.log","progress-rrd-task","LoadAjax('popup-rrd-load','$page?popup2=yes$img&period=$period$flat2');");


    $gengraphs=$tpl->button_autnonome("{run_this_task_now}",$js,ico_run,null,450,"btn-danger");

    if(!is_file($path)){
        echo $tpl->div_error("{error_graphic_is_not_generated}<br><strong>img/squid/$img-$period.png {no_such_file}</strong><div style='margin: 30px;text-align: right'>$gengraphs</div>");
        return false;
    }
    $title=$GLOBALS["MAIN_TITLE"][$img];
    $html="
<H2>$title</H2>
<center style='margin:20px'><img src='/img/squid/$img-$period$flat.png?t=$t'></center>";
echo $tpl->_ENGINE_parse_body($html);
return true;
}

function popup_latency(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $TRCLASS=null;
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sitename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{latency}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new postgres_sql();
    $results=$q->QUERY_SQL("SELECT sitename,AVG(latency) as latency FROM statscom_latency GROUP BY sitename ORDER BY latency DESC LIMIT 100");
    $class_text="";
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }

    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $site=$ligne["sitename"];
        $latency=$ligne["latency"];
        $latency=round($latency,2);
        $latency=msToString($latency);


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' nowrap><span class='$class_text'>$site</span></td>";
        $html[]="<td style='width:1%' class='right' nowrap>$latency</center></td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";



    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body($html);

}
function secondsToMinutes($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return [$minutes, $remainingSeconds];
}

function msToString($ms){
    $unit="ms";
    if($ms>0){
        $unit="s";
        $ms=$ms /1000;
    }


    if($ms>60){

        list($minutes, $remainingSeconds) = secondsToMinutes($ms);
        if($remainingSeconds>0) {
            $unit="";
            $ms = "{$minutes}mn,{$remainingSeconds}s";
        }else{
            $ms=$minutes;
            $unit="mn";
        }
    }
    return "$ms{$unit}";
}