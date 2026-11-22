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
    if(preg_match("#\.php\?(.+)#",$Query,$re)){
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
    $Unser=unserialize($Decoded,array("allowed_classes"=>false));
    if(!$Unser){
        return array();
    }
    if(!is_array($Unser)){
        return array();
    }
    // Pentest 2026-11-04 : DIV/PAGE/QUERY etaient recopies sans encodage dans le JavaScript (XSS).
    // On ne garde que les caracteres d'un identifiant, d'un nom de page et d'une query string.
    $Unser["DIV"]=preg_replace('#[^A-Za-z0-9_-]#','',(string)($Unser["DIV"] ?? ""));
    $Unser["PAGE"]=preg_replace('#[^A-Za-z0-9._/-]#','',(string)($Unser["PAGE"] ?? ""));
    $Unser["QUERY"]=preg_replace('#[^A-Za-z0-9_.,:@/+=&%?;~*!()\[\]-]#','',(string)($Unser["QUERY"] ?? ""));
    $Unser["INTERVAL"]=intval($Unser["INTERVAL"] ?? 0);
    return $Unser;
}