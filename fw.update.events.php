<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog8("{update_events}","$page?popup=yes",1124);
}

function popup(){
    $tpl=new template_admin();
    $data=explode("\n",@file_get_contents("/var/log/artica.updater.log"));
    $t=time();


    if(count($data)==0){
        echo $tpl->div_error("{no_data}");
        return;
    }

    krsort($data);



    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";



    $html[]="<th data-sortable=false style='width:1%'>{date}</th>";
    $html[]="<th data-sortable=true style='width:1%'>PID</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{events}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    $c=0;
    foreach ($data as $line){
        $line=trim($line);

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        if(!preg_match("#(.+?)\s+\[([0-9]+)\]\s+exec\.nightly\.php:\s+(.+)#",$line,$re)){continue;}
        $c++;
        $date=strtotime($re[1]);
        $pid=$re[2];
        $event=$re[3];
        $zdate=$tpl->time_to_date($date,true);
        $class="text-primary";
        if(preg_match("#UP TO DATE#",$event)) {
            $class = "text-success";
        }
        if(preg_match("#disabled#",$event)){
            $class = "text-muted";
        }
        if(preg_match("#Running PID#",$event)){
            $class="text-info font-bold";
        }
        if(preg_match("#succes#i",$event)){
            $class="text-success";
        }
        if(preg_match("#(failed|error)#i",$event)){
            $class="text-danger";
        }

        $html[]="<tr class='$TRCLASS' id='row-parent-'>";
        $html[]="<td style='width: 1%' nowrap><span class='$class' >$zdate</span></td>";
        $html[]="<td class=\"center\" style='width: 1%'><span class='$class'>$pid</span></td>";
        $html[]="<td style='vertical-align:middle'><span class='$class'>$event</span></td>";
        $html[]="</tr>";
        if($c>$GLOBALS["FOOTABLE_PSIZE"]){break;}
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
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";

    echo $tpl->_ENGINE_parse_body($html);

}