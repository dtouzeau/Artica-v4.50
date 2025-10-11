<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["list"])){list_ports();exit;}
if(isset($_GET["js"])){js();exit;}
page();

function js(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog10("{open_ports}","$page?byjs=yes",1200);

}

function page(){
    $page   = CurrentPageName();
    $t      = time();
    $addon="";
    if(isset($_GET["byjs"])){
        $addon="&byjs=yes";
    }
    echo "<div id='fw-open-ports-$t'></div>
    <script>LoadAjax('fw-open-ports-$t','$page?list=yes&t=$t$addon');</script>
    ";

}

function list_ports(){
    $fname      =PROGRESS_DIR."/ports.txt";
    $TRCLASS    = null;
    $t          = time();
    $tpl        = new template_admin();
    $tt         = $_GET["t"];
    $page       = CurrentPageName();
    $ByJs=false;
    if(isset($_GET["byjs"])){
        $ByJs=true;
    }

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("network.php?open-ports=yes");
    $results=explode("\n",@file_get_contents($fname));

    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btn[] = "<label class=\"btn btn btn-primary\" OnClick=\"LoadAjax('fw-open-ports-$tt','$page?list=yes&t=$tt');\"><i class='fas fa-sync-alt'></i> {refresh} </label>";
    $btn[]="</div>";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{port}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>PROTO</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap=''>{interface}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{processes}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{PID}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($results as $line){
        if(!preg_match("#^(.*?)\s+([0-9]+).*?(IPv4|IPv6).*?(UDP|TCP)\s+(.*?):([0-9]+)\s+#",$line,$re)){
            continue;
        }
        $process=$re[1];
        $pid=$re[2];
        $proto=$re[4];
        $ipaddr=$re[5];
        $ipaddr=str_replace("[","",$ipaddr);
        $ipaddr=str_replace("]","",$ipaddr);
        $port=$re[6];
        if($proto=="TCP"){
            if(!preg_match("#LISTEN#",$line)){continue;}
        }
        if(strpos($ipaddr,"->")>0){continue;}
        if($ipaddr=="127.0.0.1" OR $ipaddr=="::1"){continue;}
        $MAIN[$port]["PROCESSES"][$process]=true;
        $MAIN[$port]["PID"][$pid]=true;
        $MAIN[$port]["PROTO"][$proto]=true;
        if($ipaddr=="*"){$ipaddr="{all}";}
        $MAIN[$port]["IP"][$ipaddr]=true;
    }

    foreach ($MAIN as $listen_port=>$array){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $prs=array();
        $pids=array();
        $protos=array();
        $ipaddrs=array();

        foreach ($array["PROCESSES"] as $ppr=>$none){$prs[]=$ppr;}
        foreach ($array["PID"] as $ppid=>$none){$pids[]=$ppid;}
        foreach ($array["PROTO"] as $pproto=>$none){$protos[]=$pproto;}
        foreach ($array["IP"] as $ppaddr=>$none){$ipaddrs[]=$ppaddr;}
        $md=md5($listen_port.serialize($array));
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td nowrap style='width:1%;text-align: right;vertical-align: top !important;'><strong style='font-size:18px'>$listen_port</strong></td>";
        $html[]="<td width=1% nowrap><strong style='font-size:18px'>".@implode(",&nbsp;",$protos)."</strong></td>";
        $html[]="<td width=1% nowrap>".@implode("<br>",$ipaddrs)."</td>";
        $html[]="<td width=50% nowrap>".@implode(", ",$prs)."</td>";
        $html[]="<td width=1% nowrap>".@implode(", ",$pids)."</td>";
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

    $TINY_ARRAY["TITLE"]="{network_interfaces}: {open_ports}";
    $TINY_ARRAY["ICO"]="fa-solid fa-meter-fire";
    $TINY_ARRAY["EXPL"]="{network_interfaces_explain}";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    if($ByJs){
        $jstiny="";
    }
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}


