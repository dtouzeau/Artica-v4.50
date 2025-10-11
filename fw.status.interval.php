<?php

if(isset($_GET["Start"])){Start();exit;}
if(isset($_GET["Loadjs"])){Loadjs();exit;}

function Loadjs(){

    $Main = CurUnserializeb64($_GET["Loadjs"]);
    $div = $Main["DIV"];
    $page = $Main["PAGE"];
    $Query = $Main["QUERY"];
    $Interval = intval($Main["INTERVAL"]);
    if($Interval<3){
        $Interval=3;
    }
    if(preg_match("#^\?(.+)#",$Query,$re)){
        $Query=$re[1];
    }
    $Interval=$Interval*1000;
    $MemoryVal = "Mem" . md5("$page?$Query");
    $MemoryFunc = "Func" . md5("$page?$Query");
    $html[] = "function $MemoryFunc(){";
    $html[]="\tif( document.getElementById('$div') ){";
    $html[] = "\t\tLoadjs('$page?$Query');";
    $html[] = "\t} else {";
    $html[]="\t\tclearInterval(window.$MemoryVal);";
    $html[]="\t\twindow.$MemoryVal = null;";
    $html[]="\t}";
    $html[]="}";

    $html[] = "\tif (typeof window.$MemoryVal == 'undefined') {";
    $html[] = "\t\twindow.$MemoryVal = null;";
    $html[] = "\t}";

    $html[] = "\tif (window.$MemoryVal === null) {";
    $html[] = "\t\twindow.$MemoryVal = setInterval($MemoryFunc, $Interval);";
    $html[] = "\t\tLoadjs('$page?$Query');";
    $html[] = "}";
    /*
    $html[] = "else{";
    $html[]="\t\tclearInterval(window.$MemoryVal);";
    $html[]="\t\twindow.$MemoryVal = null;";
    $html[] = "\t\twindow.$MemoryVal = setInterval($MemoryFunc, $Interval);";
    $html[] = "\t\tLoadAjaxSilent('$div','$page?$Query');";
    $html[]="}";
    */
    header("content-type: application/x-javascript");
    echo @implode("\n",$html);
    return true;

}
function Start(){

    $Main =  CurUnserializeb64($_GET["Start"]);
    $div = $Main["DIV"];
    $page = $Main["PAGE"];
    $Query = $Main["QUERY"];
    $Interval = intval($Main["INTERVAL"]);
    if($Interval<3){
        $Interval=3;
    }
    if(preg_match("#^\?(.+)#",$Query,$re)){
        $Query=$re[1];
    }
    $Interval=$Interval*1000;
    $MemoryVal = "Mem" . md5("$page?$Query");
    $MemoryFunc = "Func" . md5("$page?$Query");
    $html[] = "function $MemoryFunc(){";
    $html[]="\tif( document.getElementById('$div') ){";
    $html[] = "\t\tLoadAjaxSilent('$div','$page?$Query');";
    $html[] = "\t} else {";
    $html[]="\t\tclearInterval(window.$MemoryVal);";
    $html[]="\t\twindow.$MemoryVal = null;";
    $html[]="\t}";
    $html[]="}";

    $html[] = "\tif (typeof window.$MemoryVal == 'undefined') {";
    $html[] = "\t\twindow.$MemoryVal = null;";
    $html[] = "\t}";

    $html[] = "\tif (window.$MemoryVal === null) {";
    $html[] = "\t\twindow.$MemoryVal = setInterval($MemoryFunc, $Interval);";
    $html[] = "\t\tLoadAjaxSilent('$div','$page?$Query');";
    $html[] = "}";
    /*
    $html[] = "else{";
    $html[]="\t\tclearInterval(window.$MemoryVal);";
    $html[]="\t\twindow.$MemoryVal = null;";
    $html[] = "\t\twindow.$MemoryVal = setInterval($MemoryFunc, $Interval);";
    $html[] = "\t\tLoadAjaxSilent('$div','$page?$Query');";
    $html[]="}";
    */
    header("content-type: application/x-javascript");
    echo @implode("\n",$html);
    return true;

}
function CurUnserializeb64($data):array{
    if(is_null($data)){
        return array();
    }
    if(strlen($data)<3){
        return array();
    }
    $Decoded=base64_decode($data);
    if(!$Decoded){
        return array();
    }
    $Unser=unserialize($Decoded);
    if(!$Unser){
        return array();
    }
    return $Unser;
}