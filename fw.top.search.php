<?php
// fw.top.search.php — results of the top-bar search. The search itself is
// articarest's /console/search (module ConsoleSearch, helper
// class.console.search.inc): answered directly, no background job.
// Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.console.search.inc");
$tpl=new template_admin();if(!$tpl->xPrivs()){die();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

search();

// cs_esc: a value of the API is DATA — HTML-escaped and with its braces
// neutralised, since the page goes through _ENGINE_parse_body ({x} = token).
function cs_esc($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

function search(){
    $tpl=new template_admin();
    $text=trim(url_decode_special_tool(isset($_GET["search"])?$_GET["search"]:""));
    list($ans,$err)=console_search($text,array(),50);

    $html=array();
    $html[]="<div class=\"row border-bottom white-bg dashboard-header\">";
    $html[]="<div class=\"col-sm-12\" id='TABLEAU-TOP-RECHERCHE' data-search=\"".cs_esc($text)."\">";
    $html[]="<h1 class=ng-binding>{search} &laquo;".cs_esc($text)."&raquo;</h1></div>";
    $html[]="</div>";
    $html[]="<div class=row><div class='ibox-content'>";

    if($ans===null){
        $msg=$err=="api-missing"?"{cs_search_api_missing}":cs_esc($err);
        if($err=="search text must be 2 to 128 printable characters"){$msg="{cs_search_bad_text}";}
        $html[]="<div class='alert alert-warning'>$msg</div></div></div>";
        echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
        return;
    }

    $kinds=console_search_kinds();
    $rank=array_flip(array_keys($kinds));
    $rows=$ans["results"];
    usort($rows,function($a,$b) use ($rank){ return $rank[$a["kind"]]<=>$rank[$b["kind"]]; });

    // "create a computer" suggestion, as before (the text names a computer).
    $suggest=preg_match("#(computer|hostname|ordinateur|computador|ordenador|komputer)#i",$text);

    if(count($rows)==0 && !$suggest){
        $html[]="<div class='alert alert-info'>{cs_no_result} &laquo;".cs_esc($text)."&raquo;</div>";
    }else{
        $count=array();
        foreach($rows as $r){ $count[$r["kind"]]=($count[$r["kind"]]??0)+1; }
        $badges=array();
        foreach($count as $k=>$n){ $badges[]="<span class='label label-default' style='margin-right:6px'><i class='{$kinds[$k][1]}'></i> {$kinds[$k][0]}: $n</span>"; }
        if(count($badges)>0){ $html[]="<div style='margin-bottom:10px'>".implode("",$badges)."</div>"; }
        $html[]="<table id='table-top-search' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
        $html[]="<thead><tr>";
        $html[]="<th data-sortable=true data-type='text'>{type}</th>";
        $html[]="<th data-sortable=true data-type='text'>{items}</th>";
        $html[]="<th data-sortable=true data-type='text'>{cs_col_info}</th>";
        $html[]="<th data-sortable=true data-type='text'>{details}</th>";
        $html[]="</tr></thead><tbody>";
        if($suggest){
            $js="Loadjs('fw.add.computer.php');";
            $html[]="<tr><td style='width:1%;white-space:nowrap'><i class='fa fa-desktop'></i> {cs_kind_computer}</td>";
            $html[]="<td><strong>".$tpl->td_href("{create_new_computer}",null,$js)."</strong></td><td>{action}</td><td>&nbsp;</td></tr>";
        }
        foreach($rows as $r){
            $k=$r["kind"];
            $js=console_search_open_js($r);
            $title=cs_esc($r["title"]!==""?$r["title"]:$r["id"]);
            if($js!==""){
                $title="<a href=\"javascript:blur();\" OnClick=\"$js\" style='text-decoration:underline'>$title</a>";
            }
            $html[]="<tr>";
            $html[]="<td style='width:1%;white-space:nowrap'><i class='{$kinds[$k][1]}'></i> {$kinds[$k][0]}</td>";
            $html[]="<td><strong>$title</strong></td>";
            $html[]="<td>".cs_esc($r["subtitle"])."</td>";
            $html[]="<td><small class='text-muted'>".cs_esc($r["detail"])."</small></td>";
            $html[]="</tr>";
        }
        $html[]="</tbody><tfoot><tr><td colspan='4'><ul class='pagination pull-right'></ul></td></tr></tfoot></table>";
    }

    $failed=array();
    foreach($ans["sources"] as $s){ if($s["error"]!==""){ $failed[]=cs_esc($s["name"]).": ".cs_esc($s["error"]); } }
    if(count($failed)>0){
        $html[]="<div class='text-muted' style='margin-top:8px'><small><i class='fas fa-exclamation-triangle'></i> {cs_sources_failed}: ".implode(" &middot; ",$failed)."</small></div>";
    }
    $html[]="<div class='text-muted'><small>".intval($ans["took_ms"])." ms</small></div>";
    $html[]="</div></div>";
    $html[]="<script>NoSpinner();\n$(document).ready(function(){ $('#table-top-search').footable({\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true}}); });</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}
