<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.artica-samba.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$user=new usersMenus();
if(!$user->AsSambaAdministrator){die();}

if(isset($_GET["start"])){start();exit;}
if(isset($_GET["sessions"])){sessions_table();exit;}
if(isset($_GET["io-stats"])){io_stats();exit;}
if(isset($_GET["io-chart-js"])){io_chart_js();exit;}
if(isset($_GET["top-consumers"])){top_consumers();exit;}
if(isset($_GET["top-chart-js"])){top_chart_js();exit;}

page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_SAMBA} &raquo;&raquo; {statistics}",
        "fad fa-chart-bar",
        "{samba_statistics_explain}",
        "$page?start=yes","samba-stats","progress-samba-stats",false,"table-samba-stats");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_SAMBA} - {statistics}",$html);
        echo $tpl->build_firewall();
        return;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
}

function start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $samba=new ArticaSamba();
    try{
        $instances=$samba->listInstances();
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    if(count($instances)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{no_fs_instances}"));
        return;
    }

    $options=[];
    $firstId="";
    foreach($instances as $inst){
        $id=$inst["id"] ?? "";
        $name=htmlspecialchars($inst["name"] ?? $id);
        $state=$inst["state"] ?? "stopped";
        $options[$id]="$name ($state)";
        if($firstId===""){$firstId=$id;}
    }

    $selectedId=isset($_GET["instance-id"])?$_GET["instance-id"]:$firstId;

    $html[]="<div style='margin-bottom:15px'>";
    $html[]="<label style='margin-right:10px'>{instance}:</label>";
    $html[]="<select id='samba-stats-instance-selector' class='form-control' style='width:300px;display:inline-block' OnChange=\"SambaStatsReload();\">";
    foreach($options as $oid=>$olabel){
        $sel=($oid==$selectedId)?"selected":"";
        $html[]="<option value='$oid' $sel>$olabel</option>";
    }
    $html[]="</select>";
    $html[]="</div>";

    $array["{sessions}"]="$page?sessions=yes&instance-id=$selectedId";
    $array["{io_statistics}"]="$page?io-stats=yes&instance-id=$selectedId";
    $array["{top_consumers}"]="$page?top-consumers=yes&instance-id=$selectedId";

    $html[]="<div id='stats-tabs-area'></div>";
    $html[]="<script>";
    $html[]="function SambaStatsReload(){";
    $html[]="  var iid=document.getElementById('samba-stats-instance-selector').value;";
    $html[]="  LoadAjax('stats-tabs-area','$page?sessions=yes&instance-id='+iid);";
    $html[]="}";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

    $tabArray=[];
    $tabArray["{sessions}"]="$page?sessions=yes&instance-id=$selectedId";
    $tabArray["{io_statistics}"]="$page?io-stats=yes&instance-id=$selectedId";
    $tabArray["{top_consumers}"]="$page?top-consumers=yes&instance-id=$selectedId";
    echo $tpl->tabs_default($tabArray);
}

// ── Sessions ─────────────────────────────────────────────────

function sessions_table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceId=trim($_GET["instance-id"] ?? "");

    if(strlen($instanceId)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{select_instance}"));
        return;
    }

    $samba=new ArticaSamba();
    try{
        $sessions=$samba->getSessions($instanceId);
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    $tdStyle1="style='width:1%' nowrap";
    $t=time();
    $html[]="<table id='table-sessions-$t' class='footable table table-stripped' data-page-size='50'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{username}</th>";
    $html[]="<th data-sortable=true data-type='text'>{client_ip}</th>";
    $html[]="<th data-sortable=true data-type='text'>{share_name}</th>";
    $html[]="<th data-sortable=true data-type='number'>{read}</th>";
    $html[]="<th data-sortable=true data-type='number'>{write}</th>";
    $html[]="<th data-sortable=true data-type='text'>{session_start}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    foreach($sessions as $sess){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $user=htmlspecialchars($sess["user"] ?? "");
        $clientIp=htmlspecialchars($sess["client_ip"] ?? "");
        $share=htmlspecialchars($sess["share"] ?? "");
        $readBytes=samba_format_bytes($sess["read_bytes"] ?? 0);
        $writeBytes=samba_format_bytes($sess["write_bytes"] ?? 0);
        $sessionStart=htmlspecialchars($sess["session_start"] ?? "");

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td><i class='".ico_member."'></i>&nbsp;$user</td>";
        $html[]="<td $tdStyle1>$clientIp</td>";
        $html[]="<td><i class='fas fa-folder'></i>&nbsp;$share</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>$readBytes</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>$writeBytes</td>";
        $html[]="<td $tdStyle1>$sessionStart</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){ $('#table-sessions-$t').footable({";
    $html[]="  \"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":50}";
    $html[]="});});</script>";
    $html[]="<script>".$tpl->RefreshInterval_js("stats-tabs-area",$page,"sessions=yes&instance-id=$instanceId",10)."</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

// ── IO Statistics ────────────────────────────────────────────

function io_stats(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceId=trim($_GET["instance-id"] ?? "");
    $period=isset($_GET["period"])?$_GET["period"]:"1h";
    $userFilter=isset($_GET["user-filter"])?$_GET["user-filter"]:"";

    if(strlen($instanceId)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{select_instance}"));
        return;
    }

    $periods=["1h"=>"1 {hour}","24h"=>"24 {hours}","7d"=>"7 {days}","30d"=>"30 {days}"];
    $html[]="<div style='margin-bottom:15px'>";
    $html[]="<label style='margin-right:10px'>{period}:</label>";
    foreach($periods as $pk=>$plabel){
        $cls=($pk==$period)?"btn-primary":"btn-default";
        $html[]="<button class='btn $cls btn-sm' style='margin-right:5px' OnClick=\"LoadAjax('stats-tabs-area','$page?io-stats=yes&instance-id=$instanceId&period=$pk&user-filter=".urlencode($userFilter)."');\">$plabel</button>";
    }
    $html[]="</div>";

    $samba=new ArticaSamba();
    try{
        $ioData=$samba->getIO($instanceId,$period,$userFilter);
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    $html[]="<div id='io-chart-container' style='height:300px;margin-bottom:20px'><canvas id='io-chart-canvas'></canvas></div>";

    $tdStyle1="style='width:1%' nowrap";
    $t=time();
    $html[]="<table id='table-io-$t' class='footable table table-stripped' data-page-size='50'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true data-type='text'>{username}</th>";
    $html[]="<th data-sortable=true data-type='text'>{share_name}</th>";
    $html[]="<th data-sortable=true data-type='number'>{read}</th>";
    $html[]="<th data-sortable=true data-type='number'>{write}</th>";
    $html[]="<th data-sortable=true data-type='number'>{operations}</th>";
    $html[]="<th data-sortable=true data-type='number'>{total}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;

    $chartLabels=[];
    $chartRead=[];
    $chartWrite=[];
    $count=0;

    foreach($ioData as $row){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $user=htmlspecialchars($row["user"] ?? "");
        $share=htmlspecialchars($row["share"] ?? "");
        $readB=$row["read_bytes"] ?? 0;
        $writeB=$row["write_bytes"] ?? 0;
        $opCount=$row["op_count"] ?? 0;
        $totalB=$row["total_bytes"] ?? 0;

        if($count<10){
            $chartLabels[]=($user!=""?$user:"?")."/".($share!=""?$share:"?");
            $chartRead[]=$readB;
            $chartWrite[]=$writeB;
        }
        $count++;

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td><i class='".ico_member."'></i>&nbsp;$user</td>";
        $html[]="<td><i class='fas fa-folder'></i>&nbsp;$share</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>".samba_format_bytes($readB)."</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>".samba_format_bytes($writeB)."</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>".number_format($opCount)."</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>".samba_format_bytes($totalB)."</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){ $('#table-io-$t').footable({";
    $html[]="  \"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},\"paging\":{\"size\":50}";
    $html[]="});});</script>";

    // Inline chart rendering
    $labelsJson=json_encode($chartLabels);
    $readJson=json_encode($chartRead);
    $writeJson=json_encode($chartWrite);
    $html[]="<script>";
    $html[]="(function(){";
    $html[]="var ctx=document.getElementById('io-chart-canvas');";
    $html[]="if(!ctx)return;";
    $html[]="new Chart(ctx,{";
    $html[]="  type:'bar',";
    $html[]="  data:{";
    $html[]="    labels:$labelsJson,";
    $html[]="    datasets:[";
    $html[]="      {label:'Read',data:$readJson,backgroundColor:'rgba(26,179,148,0.7)'},";
    $html[]="      {label:'Write',data:$writeJson,backgroundColor:'rgba(28,132,198,0.7)'}";
    $html[]="    ]";
    $html[]="  },";
    $html[]="  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},";
    $html[]="    scales:{y:{beginAtZero:true,ticks:{callback:function(v){return formatBytesJS(v);}}}}}";
    $html[]="});";
    $html[]="})();";
    $html[]="function formatBytesJS(b){if(b===0)return '0 B';var k=1024;var s=['B','KB','MB','GB','TB'];var i=Math.floor(Math.log(b)/Math.log(k));return parseFloat((b/Math.pow(k,i)).toFixed(2))+' '+s[i];}";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

// ── Top Consumers ────────────────────────────────────────────

function top_consumers(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceId=trim($_GET["instance-id"] ?? "");
    $sort=isset($_GET["sort"])?$_GET["sort"]:"total_bytes";
    $limit=isset($_GET["limit"])?intval($_GET["limit"]):10;

    if(strlen($instanceId)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{select_instance}"));
        return;
    }

    $sortOptions=["total_bytes"=>"{total}","read_bytes"=>"{read}","write_bytes"=>"{write}","op_count"=>"{operations}"];
    $html[]="<div style='margin-bottom:15px'>";
    $html[]="<label style='margin-right:10px'>{sort_by}:</label>";
    foreach($sortOptions as $sk=>$slabel){
        $cls=($sk==$sort)?"btn-primary":"btn-default";
        $html[]="<button class='btn $cls btn-sm' style='margin-right:5px' OnClick=\"LoadAjax('stats-tabs-area','$page?top-consumers=yes&instance-id=$instanceId&sort=$sk&limit=$limit');\">$slabel</button>";
    }
    $html[]="</div>";

    $samba=new ArticaSamba();
    try{
        $topData=$samba->getTop($instanceId,$limit,$sort);
    }catch(\RuntimeException $e){
        echo $tpl->_ENGINE_parse_body($tpl->div_error(htmlspecialchars($e->getMessage())));
        return;
    }

    $html[]="<div style='display:flex;flex-wrap:wrap'>";
    $html[]="<div style='flex:1;min-width:400px'>";

    $tdStyle1="style='width:1%' nowrap";
    $t=time();
    $html[]="<table id='table-top-$t' class='footable table table-stripped' data-page-size='50'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>#</th>";
    $html[]="<th data-sortable=true data-type='text'>{username}</th>";
    $html[]="<th data-sortable=true data-type='number'>{read}</th>";
    $html[]="<th data-sortable=true data-type='number'>{write}</th>";
    $html[]="<th data-sortable=true data-type='number'>{operations}</th>";
    $html[]="<th data-sortable=true data-type='number'>{total}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $rank=0;

    $doughnutLabels=[];
    $doughnutValues=[];
    $doughnutColors=["#1ab394","#1c84c6","#f8ac59","#ed5565","#ab7df6","#23c6c8","#676a6c","#e7eaec","#4fc1e9","#a0d468"];

    foreach($topData as $row){
        $rank++;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $user=htmlspecialchars($row["user"] ?? "unknown");
        $readB=$row["read_bytes"] ?? 0;
        $writeB=$row["write_bytes"] ?? 0;
        $opCount=$row["op_count"] ?? 0;
        $totalB=$row["total_bytes"] ?? 0;

        if($rank<=5){
            $doughnutLabels[]=$user;
            $doughnutValues[]=$totalB;
        }

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td $tdStyle1>$rank</td>";
        $html[]="<td><i class='".ico_member."'></i>&nbsp;$user</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>".samba_format_bytes($readB)."</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>".samba_format_bytes($writeB)."</td>";
        $html[]="<td $tdStyle1 style='text-align:right'>".number_format($opCount)."</td>";
        $html[]="<td $tdStyle1 style='text-align:right'><strong>".samba_format_bytes($totalB)."</strong></td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="</div>";

    // Doughnut chart
    $html[]="<div style='width:300px;margin-left:30px'>";
    $html[]="<canvas id='top-chart-canvas' style='max-height:300px'></canvas>";
    $html[]="</div>";
    $html[]="</div>";

    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function(){ $('#table-top-$t').footable({";
    $html[]="  \"sorting\":{\"enabled\":true},\"paging\":{\"size\":50}";
    $html[]="});});</script>";

    // Inline doughnut chart
    $dlJson=json_encode($doughnutLabels);
    $dvJson=json_encode($doughnutValues);
    $dcJson=json_encode(array_slice($doughnutColors,0,count($doughnutLabels)));
    $html[]="<script>";
    $html[]="(function(){";
    $html[]="var ctx=document.getElementById('top-chart-canvas');";
    $html[]="if(!ctx)return;";
    $html[]="new Chart(ctx,{";
    $html[]="  type:'doughnut',";
    $html[]="  data:{labels:$dlJson,datasets:[{data:$dvJson,backgroundColor:$dcJson}]},";
    $html[]="  options:{responsive:true,plugins:{legend:{position:'bottom'}}}";
    $html[]="});";
    $html[]="})();";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

// ── Helpers ──────────────────────────────────────────────────

function samba_format_bytes(int $bytes):string{
    if($bytes==0){return "0 B";}
    $units=["B","KB","MB","GB","TB"];
    $i=floor(log(abs($bytes),1024));
    if($i>=count($units)){$i=count($units)-1;}
    return number_format($bytes/pow(1024,$i),2)." ".$units[$i];
}
