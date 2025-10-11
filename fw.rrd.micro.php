<?php

$GLOBALS["MAIN_TITLE"]["filedesc"]="{APP_SQUID} {file_descriptors}";
$GLOBALS["MAIN_TITLE"]["squid_cache"]="{APP_SQUID} {requests}";
$GLOBALS["MAIN_TITLE"]["squidmem"]="{proxy_memory}";
$GLOBALS["MAIN_TITLE"]["system_cpu"]="{system} {cpu} {percentage}";
$GLOBALS["MAIN_TITLE"]["system_memory"]="{system} {memory} MB";
$GLOBALS["MAIN_TITLE"]["proxy_users"]="{APP_PROXY} {members}";


include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["dhclient_mac"])){tests();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["results-popup"])){results_popup();exit;}
if(isset($_GET["memory"])){popup_memory();exit;}
if(isset($_GET["cpu"])){popup_cpu();exit;}
if(isset($_GET["popup-explain-load"])){popup_load();exit;}
if(isset($_GET["zoom"])){zoom_js();exit;}
if(isset($_GET["zoom-popup"])){zoom_popup();exit;}
if(isset($_GET["memory-start"])){popup_memory_start();exit;}


js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $key=$_GET["key"];
    if($key=="SQUID_AD_RESTFULL"){$key="APP_ACTIVE_DIRECTORY_REST";}
    $title="{{$key}}";
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}
	$tpl->js_dialog6($title, "$page?tabs=$key$flat",1200);
}
function zoom_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $key=$_GET["zoom"];
    $img=$_GET["img"];
    $period=$_GET["period"];
    $title="{{$key}}";
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}
    $tpl->js_dialog7($title, "$page?zoom-popup=$key&img=$img&period=$period$flat",1200);
}
function tabs(){
    $key=$_GET["tabs"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}
    $array["{memory}"]="$page?memory-start=$key&period=hourly$flat";
    $array["{cpu}"]="$page?cpu=$key&period=yesterday$flat";
    echo $tpl->tabs_default($array);
}
function popup_cpu(){
    $key=$_GET["cpu"];
    $img="cpu";
    popup_gen($key,$img);
}
function popup_gen($key,$img){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Periods["{this_hour}"]="hourly";
    $Periods["{today}"]="day";
    $Periods["{yesterday}"]="yesterday";
    $Periods["{this_week}"]="week";
    $Periods["{this_month}"]="month";
    $Periods["{this_year}"]="year";

    $html[]="<table style='width:100%;'>";
    $html[]="<tr>";
    $c=0;$d=0;
    foreach($Periods as $title=>$period){
        $path=dirname(__FILE__)."/img/squid/$key-$img-$period.micro.png";
        if(!file_exists($path)){
            VERBOSE("$path not found",__LINE__);
            continue;
        }
        $d++;
        $c++;
        $t=time();
        $Link="OnClick=\"Loadjs('$page?zoom=$key&period=$period&img=$img')\" OnMouseOver=\"this.style.cursor='pointer' OnMouseOut=\";this.style.cursor='default';\"";

        $webpath="/img/squid/$key-$img-$period.micro.png?t=$t";
        $html[]="<td><img src='$webpath' alt='$period' title='$period' $Link></td>";
        if($c>2){
            $html[]="</tr>";
            $html[]="<tr>";
            $c=0;
        }
    }

    VERBOSE("D == $d KEY=$key",__LINE__);
    if($d==0){
        $t=time();
        if ($img=="mlock" OR $img=="memory") {
            echo "<div id='progress-rrd'></div>";

            $js = $tpl->framework_buildjs("/rrd/allimages", "progress-rrd.compile",
                "progress-rrd.log","progress-rrd","LoadAjax('popup-memory-start','$page?memory=$key&period=hourly');");
            echo "<script>$js</script>";
            }
    }

    echo $tpl->_ENGINE_parse_body($html);
}
function popup_memory_start(){
    $page=CurrentPageName();
    $flat="";
    if(isset($_GET["flat"])){$flat="&flat=yes";}
    $key=$_GET["memory-start"];
    echo "<div id='popup-memory-start'></div>\n";
    echo "<script>LoadAjax('popup-memory-start','$page?memory=$key&period=hourly$flat');</script>";
}
function popup_memory(){
    $key=$_GET["memory"];
    $img="mem";
    popup_gen($key,$img);
    popup_gen($key,"mlock");
}
function zoom_popup(){
    $key=$_GET["zoom-popup"];
    $img=$_GET["img"];
    $period=$_GET["period"];
    $t=time();
    echo "<div class='center'><img src='img/squid/$key-$img-$period.png?t=$t'></div>";
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