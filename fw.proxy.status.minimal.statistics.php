<?php
/**
 * MicroStats Dashboard — Proxy access statistics with Chart.js.
 * Data from /microstats/* REST API (bbolt-backed, multi-tier aggregation).
 *
 * Features: Summary widgets, top sites/users/categories charts,
 *           group-by selector (user/site/category × hits/size),
 *           drill-down into specific IPs, domains, categories.
 */
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["dashboard"]))    { render_dashboard(); exit; }
if(isset($_GET["group-chart"]))  { render_group_chart(); exit; }
if(isset($_GET["drill-chart"]))  { render_drill_chart(); exit; }
if(isset($_GET["drill-table"]))  { render_drill_table(); exit; }
if(isset($_GET["sites-detail"])) { render_sites_detail(); exit; }
if(isset($_GET["users-detail"])) { render_users_detail(); exit; }
if(isset($_GET["bw-detail"]))    { render_bw_detail(); exit; }
page();

// ── Colors ──────────────────────────────────────────────────────────────────

function _ms_colors(){
    return array('#3498db','#e74c3c','#2ecc71','#f39c12','#9b59b6','#1abc9c','#e67e22','#34495e','#d35400','#c0392b','#16a085','#8e44ad');
}
function _ms_bg_colors(){
    return array('rgba(52,152,219,0.7)','rgba(231,76,60,0.7)','rgba(46,204,113,0.7)','rgba(243,156,18,0.7)','rgba(155,89,182,0.7)','rgba(26,188,156,0.7)','rgba(230,126,34,0.7)','rgba(52,73,94,0.7)','rgba(211,84,0,0.7)','rgba(192,57,43,0.7)','rgba(22,160,133,0.7)','rgba(142,68,173,0.7)');
}

function _ms_cat_name($id){
    if($id==='uncategorized' || $id==='') return 'Uncategorized';
    if(!is_numeric($id)) return $id;
    $catz=new mysql_catz();
    $name=$catz->CategoryIntToStr(intval($id));
    return (strlen($name)>0) ? $name : $id;
}

function _ms_format_bytes($bytes){
    if($bytes>=1073741824) return sprintf('%.1f GB',$bytes/1073741824);
    if($bytes>=1048576) return sprintf('%.1f MB',$bytes/1048576);
    if($bytes>=1024) return sprintf('%.1f KB',$bytes/1024);
    return $bytes.' B';
}

function _ms_api($path){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path));
    if(!is_object($json) || !property_exists($json,'success') || !$json->success) return null;
    return $json->data;
}

// ── Main page ────────────────────────────────────────────────────────────────

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $TINY_ARRAY["TITLE"]   = "{SQUID_STATS}";
    $TINY_ARRAY["ICO"]     = "fa-solid fa-chart-pie";
    $TINY_ARRAY["EXPL"]    = "{microstats_explain}";
    $TINY_ARRAY["URL"]     = "proxy-status";
    $TINY_ARRAY["BUTTONS"] = null;
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    $h=array();

    // Period buttons
    $periods=array('current'=>'{this_hour}','hourly'=>'{today}','daily'=>'{this_week}','weekly'=>'{this_month}','monthly'=>'{this_year}');
    $h[]="<div style='margin:10px 0 15px'>";
    $h[]="  <div class='btn-group' id='ms-period-btns'>";
    foreach($periods as $k=>$v){
        $active=($k==='current')?' active':'';
        $h[]="    <button class='btn btn-sm btn-default$active' onclick=\"MsPeriod('$k',this)\">$v</button>";
    }
    $h[]="  </div>";
    $h[]="  <button class='btn btn-sm btn-primary' style='margin-left:10px' onclick=\"MsRefresh();\">";
    $h[]="    <i class='fas fa-sync-alt'></i> {refresh}</button>";
    $h[]="</div>";

    $h[]="<div id='ms-dashboard'></div>";

    $h[]="<script>";
    $h[]="var _msPeriod='current';";
    $h[]="function MsPeriod(p,btn){";
    $h[]="  _msPeriod=p;";
    $h[]="  \$('#ms-period-btns .btn').removeClass('active');";
    $h[]="  \$(btn).addClass('active');";
    $h[]="  MsRefresh();";
    $h[]="};";
    $h[]="function MsRefresh(){";
    $h[]="  LoadAjax('ms-dashboard','$page?dashboard=yes&period='+_msPeriod);";
    $h[]="};";
    $h[]="MsRefresh();";
    $h[]="$jstiny";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
    return true;
}

// ── Dashboard ────────────────────────────────────────────────────────────────

function render_dashboard(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $period=trim($_GET["period"] ?? 'daily');
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));

    $h=array();
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.4.4.7.js'></script>";

    // ── Summary widgets ──
    $summary=_ms_api("/microstats/summary?period=$period");
    if(is_object($summary) && isset($summary->summary)){
        $s=$summary->summary;
        $h[]="<div class='row' style='margin-bottom:15px'>";
        $h[]=_msw_click('#3498db','fas fa-mouse-pointer',$requests_text,number_format(intval($s->total_hits)),"\$('#ms-drill-area').html('')");
        $h[]=_msw_click('#2ecc71','fas fa-download','{bandwidth}',_ms_format_bytes(intval($s->total_size)),"MsBwDetail()");
        $h[]=_msw_click('#9b59b6','fas fa-globe','{websites}',number_format(intval($s->unique_sites)),"MsSitesDetail()");
        $h[]=_msw_click('#e67e22','fas fa-users','{members}',number_format(intval($s->unique_ips)),"MsUsersDetail()");
        $h[]=_msw('#34495e','fas fa-tachometer-alt','{response_time}',sprintf('%.0f ms',intval($s->avg_response_time)/1000));
        $h[]="</div>";
    }

    // ── Drill-down area (above charts) ──
    $h[]="<div id='ms-drill-area'></div>";

    // ── Group-by selector ──
    $h[]="<div class='row'>";
    $h[]="<div class='col-md-6'>";
    $h[]="  <div class='ibox'><div class='ibox-title'>";
    $h[]="    <h5><i class='fas fa-chart-bar'></i>&nbsp; {top} 10</h5>";
    $h[]="    <div class='ibox-tools'>";
    $h[]="      <select id='ms-group-type' class='form-control' style='width:auto;display:inline-block;margin-right:5px' onchange='MsGroupRefresh()'>";
    $h[]="        <option value='sites'>{websites}</option>";
    $h[]="        <option value='users'>{members}</option>";
    $h[]="        <option value='categories'>{categories}</option>";
    $h[]="      </select>";
    $h[]="      <select id='ms-group-by' class='form-control' style='width:auto;display:inline-block' onchange='MsGroupRefresh()'>";
    $h[]="        <option value='hits'>$requests_text</option>";
    $h[]="        <option value='size'>{bandwidth}</option>";
    $h[]="      </select>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='ibox-content'><div id='ms-group-chart'></div></div></div>";
    $h[]="</div>";

    // ── Categories pie ──
    $h[]="<div class='col-md-6'>";
    $h[]="  <div class='ibox'><div class='ibox-title'><h5><i class='fas fa-chart-pie'></i>&nbsp; {categories}</h5></div>";
    $h[]="  <div class='ibox-content'><div id='ms-cat-pie'></div></div></div>";
    $h[]="</div>";
    $h[]="</div>";

    // ── JS ──
    $h[]="<script>";
    $h[]="function MsGroupRefresh(){";
    $h[]="  var t=\$('#ms-group-type').val();";
    $h[]="  var b=\$('#ms-group-by').val();";
    $h[]="  LoadAjax('ms-group-chart','$page?group-chart=yes&period=$period&type='+t+'&by='+b);";
    $h[]="};";
    $h[]="function MsDrill(dtype,key){";
    $h[]="  LoadAjax('ms-drill-area','$page?drill-chart=yes&period=$period&dtype='+dtype+'&key='+encodeURIComponent(key));";
    $h[]="};";
    $h[]="function MsDrillTable(dtype,key){";
    $h[]="  LoadAjax('ms-drill-area','$page?drill-table=yes&period=$period&dtype='+dtype+'&key='+encodeURIComponent(key));";
    $h[]="};";
    $h[]="function MsSitesDetail(){";
    $h[]="  LoadAjax('ms-drill-area','$page?sites-detail=yes&period=$period');";
    $h[]="};";
    $h[]="function MsUsersDetail(){";
    $h[]="  LoadAjax('ms-drill-area','$page?users-detail=yes&period=$period');";
    $h[]="};";
    $h[]="function MsBwDetail(){";
    $h[]="  LoadAjax('ms-drill-area','$page?bw-detail=yes&period=$period');";
    $h[]="};";
    $h[]="MsGroupRefresh();";
    $h[]="</script>";

    // Categories pie inline
    $h[]=_build_categories_pie($period);

    $h[]="<script>NoSpinner();</script>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function _msw($bg,$ico,$label,$val){
    return "<div class='col-md-2 col-sm-4'><div style='background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center'>"
         ."<div style='font-size:20px;font-weight:700'>$val</div>"
         ."<div style='font-size:11px'><i class='$ico'></i> $label</div></div></div>";
}

function _msw_click($bg,$ico,$label,$val,$onclick){
    return "<div class='col-md-2 col-sm-4'><div onclick=\"$onclick\" style='cursor:pointer;background:$bg;color:#fff;padding:12px 15px;border-radius:4px;text-align:center;transition:opacity .2s' onmouseover=\"this.style.opacity=0.85\" onmouseout=\"this.style.opacity=1\">"
         ."<div style='font-size:20px;font-weight:700'>$val</div>"
         ."<div style='font-size:11px'><i class='$ico'></i> $label <i class='fas fa-chevron-right' style='font-size:9px;margin-left:3px'></i></div></div></div>";
}

// ── Group bar chart ──────────────────────────────────────────────────────────

function render_group_chart(){

    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $page=CurrentPageName();
    $period=trim($_GET["period"] ?? 'daily');
    $type=trim($_GET["type"] ?? 'sites');
    $by=trim($_GET["by"] ?? 'hits');

    $allowed_types=array('sites','users','categories','ips');
    $allowed_by=array('hits','size');
    if(!in_array($type,$allowed_types)) $type='sites';
    if(!in_array($by,$allowed_by)) $by='hits';

    $data=_ms_api("/microstats/top/$type?period=$period&limit=10&by=$by");
    if(!is_object($data) || !is_array($data->entries) || empty($data->entries)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $labels=array(); $rawKeys=array(); $vals=array(); $secondVals=array();
    $colors=_ms_bg_colors();
    foreach($data->entries as $i=>$e){
        $rawKey=$e->key ?? '?';
        $rawKeys[]=$rawKey;
        $key=($type==='categories') ? htmlspecialchars(_ms_cat_name($rawKey)) : htmlspecialchars($rawKey);
        $labels[]=$key;
        if($by==='size'){
            $vals[]=sprintf('%.2f',intval($e->size ?? 0)/1048576);
            $secondVals[]=intval($e->hits ?? 0);
        } else {
            $vals[]=intval($e->hits ?? 0);
            $secondVals[]=sprintf('%.2f',intval($e->size ?? 0)/1048576);
        }
    }

    $labelsJS=json_encode($labels);
    $valsJS='['.implode(',',$vals).']';
    $colorsJS="['".implode("','",array_slice($colors,0,count($labels)))."']";
    $yLabel=($by==='size')?'MB':$requests_text;
    $uid='grp-'.uniqid();

    // Drill-down type mapping
    $drillType=($type==='users'||$type==='ips')?'ip':'site';
    if($type==='categories') $drillType='category';

    $h=array();

    // Chart (display only, no click)
    $h[]="<div style='position:relative;height:260px'><canvas id='$uid'></canvas></div>";
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid'),{type:'bar',";
    $h[]="data:{labels:$labelsJS,datasets:[{data:$valsJS,backgroundColor:$colorsJS,borderRadius:4}]},";
    $h[]="options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
    $h[]="plugins:{legend:{display:false}},";
    $h[]="scales:{x:{beginAtZero:true,title:{display:true,text:'$yLabel'}},y:{ticks:{font:{size:11}}}}}});";
    $h[]="</script>";

    // Clickable table below chart
    $h[]="<table class='table table-condensed' style='margin-top:5px'>";
    foreach($data->entries as $i=>$e){
        $rawKey=$rawKeys[$i];
        $displayKey=$labels[$i];
        $color=$colors[$i % count($colors)];
        $eRawKey=htmlspecialchars(addslashes($rawKey));
        $val1=$vals[$i];
        $val2=$secondVals[$i];
        $label1=($by==='size')?'MB':$requests_text;
        $label2=($by==='size')? $requests_text:'MB';

        $h[]="<tr style='cursor:pointer' onclick=\"MsDrill('$drillType','$eRawKey')\">";
        $h[]="<td style='width:4px;padding:6px 0'><span style='display:inline-block;width:4px;height:20px;background:$color;border-radius:2px'></span></td>";
        $h[]="<td style='font-weight:600'>$displayKey</td>";
        $h[]="<td style='text-align:right;white-space:nowrap'><strong>".number_format($val1).($by==='size'?' MB':'')."</strong></td>";
        $h[]="<td style='text-align:right;white-space:nowrap;color:#999'>$val2 $label2</td>";
        $h[]="</tr>";
    }
    $h[]="</table>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Categories pie ───────────────────────────────────────────────────────────

function _build_categories_pie($period){
    $data=_ms_api("/microstats/bandwidth/categories?period=$period&limit=10");
    if(!is_object($data) || !is_array($data->entries) || empty($data->entries)) return '';

    $labels=array(); $rawKeys=array(); $vals=array(); $hits=array();
    $colors=_ms_colors();
    foreach($data->entries as $e){
        $rk=$e->key ?? 'uncategorized';
        $rawKeys[]=$rk;
        $labels[]=htmlspecialchars(_ms_cat_name($rk));
        $vals[]=sprintf('%.2f',intval($e->size ?? 0)/1048576);
        $hits[]=intval($e->hits ?? 0);
    }

    $labelsJS=json_encode($labels);
    $valsJS='['.implode(',',$vals).']';
    $colorsJS="['".implode("','",array_slice($colors,0,count($labels)))."']";
    $uid='catpie-'.uniqid();

    $out=array();

    // Doughnut chart (display only)
    $out[]="<script>";
    $out[]="(function(){";
    $out[]="var div=document.getElementById('ms-cat-pie');";
    $out[]="if(!div) return;";
    $out[]="div.innerHTML='<div style=\"position:relative;height:200px\"><canvas id=\"$uid\"></canvas></div>';";
    $out[]="new Chart(document.getElementById('$uid'),{type:'doughnut',";
    $out[]="data:{labels:$labelsJS,datasets:[{data:$valsJS,backgroundColor:$colorsJS,borderWidth:0,hoverOffset:8}]},";
    $out[]="options:{responsive:true,maintainAspectRatio:false,";
    $out[]="plugins:{legend:{display:false},";
    $out[]="tooltip:{callbacks:{label:function(ctx){return ctx.label+': '+ctx.parsed.toFixed(1)+' MB';}}}}}});";
    $out[]="})();";
    $out[]="</script>";

    // Clickable list below pie
    $out[]="<script>";
    $out[]="(function(){";
    $out[]="var div=document.getElementById('ms-cat-pie');";
    $out[]="if(!div) return;";
    $out[]="var tbl=document.createElement('table');tbl.className='table table-condensed';tbl.style.marginTop='5px';";
    foreach($data->entries as $i=>$e){
        $rk=addslashes($rawKeys[$i]);
        $label=addslashes($labels[$i]);
        $color=$colors[$i % count($colors)];
        $mb=$vals[$i];
        $h=$hits[$i];
        $out[]="var r=tbl.insertRow();r.style.cursor='pointer';r.onclick=function(){MsDrill('category','$rk');};";
        $out[]="r.innerHTML='<td style=\"width:4px;padding:4px 0\"><span style=\"display:inline-block;width:4px;height:16px;background:$color;border-radius:2px\"></span></td>"
              ."<td style=\"font-size:12px\">$label</td>"
              ."<td style=\"text-align:right;font-size:12px;white-space:nowrap\"><strong>{$mb} MB</strong></td>"
              ."<td style=\"text-align:right;font-size:11px;color:#999;white-space:nowrap\">{$h}</td>';";
    }
    $out[]="div.appendChild(tbl);";
    $out[]="})();";
    $out[]="</script>";

    return implode("\n",$out);
}

// ── Drill-down charts ────────────────────────────────────────────────────────

function render_drill_chart(){
    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $page=CurrentPageName();
    $period=trim($_GET["period"] ?? 'daily');
    $dtype=trim($_GET["dtype"] ?? '');
    $key=trim($_GET["key"] ?? '');
    if($key==='') return;

    $eKey=htmlspecialchars($key);

    // Build query based on drill type
    $filter='';
    $title='';
    $icon='';
    switch($dtype){
        case 'ip':
            $filter="&ip=".urlencode($key);
            $title="<i class='fas fa-desktop'></i> $eKey";
            $icon='fas fa-desktop';
            break;
        case 'site':
            $filter="&site=".urlencode($key);
            $title="<i class='fas fa-globe'></i> $eKey";
            $icon='fas fa-globe';
            break;
        case 'category':
            // Use by-category endpoint with top_sites
            $catData=_ms_api("/microstats/by-category?period=$period&category=".urlencode($key));
            _render_category_drilldown($catData,$key,$period,$page);
            return;
        default:
            return;
    }

    // Query records for this IP or site
    $data=_ms_api("/microstats/query?period=$period$filter&limit=50");
    if(!is_object($data) || !is_array($data->records) || empty($data->records)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $records=$data->records;

    // Aggregate by the other dimension (if drilling by IP → show sites, if by site → show IPs)
    $agg=array();
    foreach($records as $r){
        if($dtype==='ip'){
            $k=$r->site ?? '?';
        } else {
            $k=$r->ip ?? '?';
            if(isset($r->user) && strlen($r->user)>0) $k=$r->user;
        }
        if(!isset($agg[$k])) $agg[$k]=array('hits'=>0,'size'=>0);
        $agg[$k]['hits']+=intval($r->hits ?? 0);
        $agg[$k]['size']+=intval($r->size ?? 0);
    }
    arsort($agg);
    $agg=array_slice($agg,0,15,true);

    $labels=array_keys($agg);
    $hitsArr=array(); $sizeArr=array();
    foreach($agg as $v){
        $hitsArr[]=$v['hits'];
        $sizeArr[]=sprintf('%.2f',$v['size']/1048576);
    }

    $labelsJS=json_encode($labels);
    $hitsJS='['.implode(',',$hitsArr).']';
    $sizeJS='['.implode(',',$sizeArr).']';
    $uid='drill-'.uniqid();
    $subLabel=($dtype==='ip')?'{websites}':'{members}';

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5>$title</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";
    $h[]="<div style='position:relative;height:320px'><canvas id='$uid'></canvas></div>";
    $h[]="</div></div>";

    // Detail table link
    $h[]="<div style='text-align:right;margin-bottom:10px'>";
    $h[]="<button class='btn btn-sm btn-default' onclick=\"MsDrillTable('$dtype','".addslashes($key)."')\">";
    $h[]="<i class='fas fa-table'></i> {show_table}</button>";
    $h[]="</div>";

    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid'),{type:'bar',";
    $h[]="data:{labels:$labelsJS,datasets:[";
    $h[]="{label:'$requests_text',data:$hitsJS,backgroundColor:'rgba(52,152,219,0.7)',borderRadius:3,yAxisID:'y'},";
    $h[]="{label:'{bandwidth} (MB)',data:$sizeJS,backgroundColor:'rgba(231,76,60,0.7)',borderRadius:3,yAxisID:'y1'}";
    $h[]="]},";
    $h[]="options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}}},";
    $h[]="scales:{y:{ticks:{font:{size:11}}},";
    $h[]="y1:{position:'top',beginAtZero:true,grid:{drawOnChartArea:false}}}}});";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

function _render_category_drilldown($catData,$key,$period,$page){

    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $eKey=htmlspecialchars(_ms_cat_name($key));

    if(!is_object($catData) || !is_array($catData->categories) || empty($catData->categories)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $cat=$catData->categories[0];
    $totalHits=intval($cat->hits ?? 0);
    $totalSize=_ms_format_bytes(intval($cat->size ?? 0));
    $uSites=intval($cat->unique_sites ?? 0);
    $uUsers=intval($cat->unique_users ?? 0);
    $uIps=intval($cat->unique_ips ?? 0);
    $topSites=(array)($cat->top_sites ?? array());

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-tag'></i>&nbsp; {category}: $eKey</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";

    // Mini summary
    $h[]="<div class='row' style='margin-bottom:15px'>";
    $h[]=_msw('#3498db','fas fa-mouse-pointer',$requests_text,number_format($totalHits));
    $h[]=_msw('#2ecc71','fas fa-download','{bandwidth}',$totalSize);
    $h[]=_msw('#9b59b6','fas fa-globe','{websites}',number_format($uSites));
    $h[]=_msw('#e67e22','fas fa-users','{members}',number_format(max($uUsers,$uIps)));
    $h[]="</div>";

    // Top sites bar
    if(!empty($topSites)){
        $labels=array(); $hitsArr=array(); $sizeArr=array();
        $colors=_ms_bg_colors();
        foreach(array_slice($topSites,0,10) as $ts){
            $labels[]=htmlspecialchars($ts->site ?? $ts->key ?? '?');
            $hitsArr[]=intval($ts->hits ?? 0);
            $sizeArr[]=sprintf('%.2f',intval($ts->size ?? 0)/1048576);
        }
        $labelsJS=json_encode($labels);
        $hitsJS='['.implode(',',$hitsArr).']';
        $colorsJS="['".implode("','",array_slice($colors,0,count($labels)))."']";
        $uid='catdrill-'.uniqid();

        $h[]="<div style='position:relative;height:280px'><canvas id='$uid'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('$uid'),{type:'bar',";
        $h[]="data:{labels:$labelsJS,datasets:[{label:'$requests_text',data:$hitsJS,backgroundColor:$colorsJS,borderRadius:4}]},";
        $h[]="options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
        $h[]="plugins:{legend:{display:false}},";
        $h[]="scales:{x:{beginAtZero:true},y:{ticks:{font:{size:11}}}}}});";
        $h[]="</script>";
    }

    $h[]="</div></div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Drill-down table ─────────────────────────────────────────────────────────

function render_drill_table(){
    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $period=trim($_GET["period"] ?? 'daily');
    $dtype=trim($_GET["dtype"] ?? '');
    $key=trim($_GET["key"] ?? '');
    if($key==='') return;

    $filter='';
    switch($dtype){
        case 'ip':   $filter="&ip=".urlencode($key); break;
        case 'site': $filter="&site=".urlencode($key); break;
        case 'category': $filter=""; break; // TODO: category filter not in query API
        default: return;
    }

    $data=_ms_api("/microstats/query?period=$period$filter&limit=100");
    if(!is_object($data) || !is_array($data->records) || empty($data->records)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $t=time();
    $eKey=htmlspecialchars($key);

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-table'></i>&nbsp; $eKey — {details}</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content' style='padding:0'>";

    $h[]="<table id='table-$t' class='footable table table-striped' data-page-size='50'>";
    $h[]="<thead><tr>";
    $h[]="<th data-sortable='true' data-type='text'>{ipaddr}</th>";
    $h[]="<th data-sortable='true' data-type='text'>{websites}</th>";
    $h[]="<th data-sortable='true' data-type='text'>{category}</th>";
    $h[]="<th data-sortable='true' data-type='number'>$requests_text</th>";
    $h[]="<th data-sortable='true' data-type='number'>{bandwidth}</th>";
    $h[]="<th data-sortable='true' data-type='number'>{response_time}</th>";
    $h[]="</tr></thead><tbody>";

    $TRCLASS=null;
    foreach($data->records as $r){
        if($TRCLASS==='footable-odd'){$TRCLASS=null;}else{$TRCLASS='footable-odd';}
        $ip=htmlspecialchars($r->ip ?? '');
        $user=htmlspecialchars($r->user ?? '');
        $site=htmlspecialchars($r->site ?? '');
        $cat=htmlspecialchars(_ms_cat_name($r->category ?? ''));
        $hits=number_format(intval($r->hits ?? 0));
        $size=_ms_format_bytes(intval($r->size ?? 0));
        $rt=sprintf('%.0f ms',intval($r->avg_rt ?? 0)/1000);
        $userLabel=($user!=='')?(" <small class='text-muted'>($user)</small>"):'';
        $ipClick="onclick=\"MsDrill('ip','".addslashes($ip)."')\" style='cursor:pointer;color:#1c84c6'";
        $siteClick="onclick=\"MsDrill('site','".addslashes($r->site ?? '')."')\" style='cursor:pointer;color:#1c84c6'";

        $h[]="<tr class='$TRCLASS'>";
        $h[]="<td><strong $ipClick>$ip</strong>$userLabel</td>";
        $h[]="<td><span $siteClick>$site</span></td>";
        $h[]="<td><span class='badge' style='background:#34495e;color:#fff'>$cat</span></td>";
        $h[]="<td>$hits</td>";
        $h[]="<td>$size</td>";
        $h[]="<td>$rt</td>";
        $h[]="</tr>";
    }

    $h[]="</tbody><tfoot><tr><td colspan='6'><ul class='pagination pull-right'></ul></td></tr></tfoot></table>";
    $h[]="</div></div>";
    $h[]="<script>NoSpinner();\n".implode("\n",$tpl->ICON_SCRIPTS);
    $h[]="\$(document).ready(function(){ \$('#table-$t').footable({";
    $h[]="\"filtering\":{\"enabled\":true},\"sorting\":{\"enabled\":true},";
    $h[]="\"paging\":{\"size\":".($GLOBALS["FOOTABLE_PSIZE"] ?? 50)."}});});</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Sites detail (websites widget drill-down) ────────────────────────────────

function render_sites_detail(){
    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $page=CurrentPageName();
    $period=trim($_GET["period"] ?? 'daily');

    $data=_ms_api("/microstats/query?period=$period&limit=1000");
    if(!is_object($data) || !is_array($data->records) || empty($data->records)){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}"));
        return;
    }

    $records=$data->records;

    // Aggregate by time window for timeline
    $timeBuckets=array();
    $statusCodes=array();
    $fmt='H:i';
    if($period==='daily'||$period==='weekly') $fmt='M d';
    if($period==='monthly') $fmt='M Y';

    foreach($records as $r){
        $ts=intval($r->first ?? 0);
        $bucket=date($fmt,$ts);
        if(!isset($timeBuckets[$bucket])){
            $timeBuckets[$bucket]=array('hits'=>0,'size'=>0,'ts'=>$ts);
        }
        $timeBuckets[$bucket]['hits']+=intval($r->hits ?? 0);
        $timeBuckets[$bucket]['size']+=intval($r->size ?? 0);

        $code=strval($r->status ?? 0);
        if(!isset($statusCodes[$code])) $statusCodes[$code]=0;
        $statusCodes[$code]+=intval($r->hits ?? 0);
    }

    uasort($timeBuckets,function($a,$b){ return $a['ts']-$b['ts']; });

    $labels=array_keys($timeBuckets);
    $hitsArr=array(); $sizeArr=array();
    foreach($timeBuckets as $b){
        $hitsArr[]=$b['hits'];
        $sizeArr[]=sprintf('%.2f',$b['size']/1048576);
    }

    $labelsJS=json_encode($labels);
    $hitsJS='['.implode(',',$hitsArr).']';
    $sizeJS='['.implode(',',$sizeArr).']';

    // Status code colors
    $codeColors=array(
        '200'=>'#2ecc71','204'=>'#27ae60','206'=>'#1abc9c',
        '301'=>'#3498db','302'=>'#2980b9','304'=>'#1c84c6',
        '400'=>'#f39c12','401'=>'#e67e22','403'=>'#d35400','404'=>'#e74c3c',
        '500'=>'#c0392b','502'=>'#962d22','503'=>'#7b241c',
    );
    arsort($statusCodes);
    $codeLabels=array(); $codeVals=array(); $codeClrs=array();
    $defaultClrs=_ms_colors();
    $ci=0;
    $totalHits=0;
    foreach($statusCodes as $code=>$hits){
        $codeLabels[]="HTTP $code";
        $codeVals[]=$hits;
        $totalHits+=$hits;
        $codeClrs[]=isset($codeColors[$code])?$codeColors[$code]:$defaultClrs[$ci % count($defaultClrs)];
        $ci++;
    }
    $codeLabelsJS=json_encode($codeLabels);
    $codeValsJS='['.implode(',',$codeVals).']';
    $codeClrsJS="['".implode("','",$codeClrs)."']";

    $uid1='sd-line-'.uniqid();
    $uid2='sd-codes-'.uniqid();

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-globe'></i>&nbsp; {websites} — {details}</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";

    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.4.4.7.js'></script>";

    // Row: timeline (left 8 cols) + doughnut (right 4 cols)
    $h[]="<div class='row'>";

    // Timeline
    $h[]="<div class='col-md-8'>";
    $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-line'></i> $requests_text &amp; {bandwidth}</h5>";
    $h[]="<div style='position:relative;height:280px'><canvas id='$uid1'></canvas></div>";
    $h[]="</div>";

    // Doughnut
    $h[]="<div class='col-md-4'>";
    $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-pie'></i> HTTP {status}</h5>";
    $h[]="<div style='position:relative;height:200px'><canvas id='$uid2'></canvas></div>";

    // Status codes legend table
    $h[]="<table class='table table-condensed' style='font-size:12px;margin-top:8px'>";
    foreach($statusCodes as $code=>$hits){
        $color=isset($codeColors[$code])?$codeColors[$code]:'#676a6c';
        $pct=$totalHits>0?sprintf('%.1f',$hits/$totalHits*100):'0';
        $h[]="<tr>";
        $h[]="<td style='width:4px;padding:3px 0'><span style='display:inline-block;width:4px;height:14px;background:$color;border-radius:2px'></span></td>";
        $h[]="<td><strong>$code</strong></td>";
        $h[]="<td style='text-align:right'>".number_format($hits)."</td>";
        $h[]="<td style='text-align:right;color:#999'>$pct%</td>";
        $h[]="</tr>";
    }
    $h[]="</table>";
    $h[]="</div>";
    $h[]="</div>";

    $h[]="</div></div>";

    // Charts JS
    $h[]="<script>";
    $h[]="new Chart(document.getElementById('$uid1'),{type:'line',";
    $h[]="data:{labels:$labelsJS,datasets:[";
    $h[]="{label:'$requests_text',data:$hitsJS,borderColor:'#3498db',backgroundColor:'rgba(52,152,219,0.08)',tension:0.3,fill:true,pointRadius:2,borderWidth:2,yAxisID:'y'},";
    $h[]="{label:'{bandwidth} (MB)',data:$sizeJS,borderColor:'#e74c3c',backgroundColor:'rgba(231,76,60,0.08)',tension:0.3,fill:true,pointRadius:2,borderWidth:2,yAxisID:'y1'}";
    $h[]="]},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
    $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}},";
    $h[]="tooltip:{callbacks:{label:function(c){return c.datasetIndex===0?c.dataset.label+': '+c.parsed.y.toLocaleString():c.dataset.label+': '+c.parsed.y.toFixed(2)+' MB';}}}},";
    $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:16}},";
    $h[]="y:{type:'linear',position:'left',beginAtZero:true,title:{display:true,text:'$requests_text'}},";
    $h[]="y1:{type:'linear',position:'right',beginAtZero:true,title:{display:true,text:'MB'},grid:{drawOnChartArea:false}}}}});";

    $h[]="new Chart(document.getElementById('$uid2'),{type:'doughnut',";
    $h[]="data:{labels:$codeLabelsJS,datasets:[{data:$codeValsJS,backgroundColor:$codeClrsJS,borderWidth:0,hoverOffset:8}]},";
    $h[]="options:{responsive:true,maintainAspectRatio:false,cutout:'55%',";
    $h[]="plugins:{legend:{display:false},";
    $h[]="tooltip:{callbacks:{label:function(ctx){return ctx.label+': '+ctx.parsed.toLocaleString();}}}}}});";

    $h[]="</script>";
    $h[]="<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Users detail (members widget drill-down) ─────────────────────────────────

function render_users_detail(){
    $tpl=new template_admin();
    $requests_text=replace_accents($tpl->_ENGINE_parse_body("{requests}"));
    $period=trim($_GET["period"] ?? 'daily');

    // Map microstats period to users-history hours
    $hoursMap=array('current'=>1,'hourly'=>24,'daily'=>168,'weekly'=>720,'monthly'=>2160);
    $hours=isset($hoursMap[$period])?$hoursMap[$period]:168;

    $chartData=_ms_api("/squid/users-history/chart?hours=$hours");
    $hasTimeline=(is_object($chartData) && is_array($chartData->labels) && !empty($chartData->labels));

    // Per-user bandwidth breakdown
    $bwData=_ms_api("/microstats/bandwidth/users?period=$period&limit=10");

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-users'></i>&nbsp; {members} — {details}</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";

    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.4.4.7.js'></script>";

    if($hasTimeline){
        $labels=json_encode($chartData->labels);
        $users='['.implode(',',(array)$chartData->users).']';

        $hasMax=isset($chartData->max_users) && is_array($chartData->max_users);
        $uid='ud-line-'.uniqid();

        $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-line'></i> {members}</h5>";
        $h[]="<div style='position:relative;height:280px'><canvas id='$uid'></canvas></div>";
        $h[]="<script>";
        $h[]="(function(){";
        $h[]="var ds=[";
        if($hasMax){
            $maxUsers='['.implode(',',(array)$chartData->max_users).']';
            $h[]="{label:'{members} (peak)',data:$maxUsers,borderColor:'rgba(230,126,34,0.35)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5},";
        }
        $h[]="{label:'{members}',data:$users,borderColor:'#e67e22',backgroundColor:'rgba(230,126,34,0.08)',tension:0.3,fill:true,pointRadius:2,borderWidth:2}";
        $h[]="];";
        $h[]="new Chart(document.getElementById('$uid'),{type:'line',";
        $h[]="data:{labels:$labels,datasets:ds},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
        $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}}},";
        $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:20}},";
        $h[]="y:{beginAtZero:true,title:{display:true,text:'{members}'}}}}});";
        $h[]="})();";
        $h[]="</script>";
    } else {
        $h[]=$tpl->div_info("{no_data}");
    }

    // Per-user horizontal bar
    if(is_object($bwData) && is_array($bwData->entries) && !empty($bwData->entries)){
        $uLabels=array(); $uHits=array(); $uSize=array();
        $colors=_ms_bg_colors();
        foreach($bwData->entries as $e){
            $uLabels[]=htmlspecialchars($e->key ?? '?');
            $uHits[]=intval($e->hits ?? 0);
            $uSize[]=sprintf('%.2f',intval($e->size ?? 0)/1048576);
        }
        $uLabelsJS=json_encode($uLabels);
        $uHitsJS='['.implode(',',$uHits).']';
        $uSizeJS='['.implode(',',$uSize).']';
        $uColorsJS="['".implode("','",array_slice($colors,0,count($uLabels)))."']";
        $uid2='ud-bar-'.uniqid();

        $h[]="<h5 style='margin-top:20px'><i class='fas fa-chart-bar'></i> {top} {members}</h5>";
        $h[]="<div style='position:relative;height:280px'><canvas id='$uid2'></canvas></div>";
        $h[]="<script>";
        $h[]="new Chart(document.getElementById('$uid2'),{type:'bar',";
        $h[]="data:{labels:$uLabelsJS,datasets:[";
        $h[]="{label:'$requests_text',data:$uHitsJS,backgroundColor:$uColorsJS,borderRadius:3,yAxisID:'y'},";
        $h[]="{label:'{bandwidth} (MB)',data:$uSizeJS,backgroundColor:'rgba(231,76,60,0.6)',borderRadius:3,yAxisID:'y1'}";
        $h[]="]},";
        $h[]="options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,";
        $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}}},";
        $h[]="scales:{y:{ticks:{font:{size:11}}},";
        $h[]="y1:{position:'top',beginAtZero:true,grid:{drawOnChartArea:false}}}}});";
        $h[]="</script>";
    }

    $h[]="</div></div>";
    $h[]="<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Bandwidth detail (bandwidth widget drill-down) ───────────────────────────

function render_bw_detail(){
    $tpl=new template_admin();
    $period=trim($_GET["period"] ?? 'daily');

    // Fetch records for bandwidth timeline
    $data=_ms_api("/microstats/query?period=$period&limit=1000");
    $hasTimeline=(is_object($data) && is_array($data->records) && !empty($data->records));

    // Top 10 users by bandwidth
    $usersData=_ms_api("/microstats/bandwidth/users?period=$period&limit=10");

    $h=array();
    $h[]="<div class='ibox' style='margin-top:15px'><div class='ibox-title'>";
    $h[]="<h5><i class='fas fa-download'></i>&nbsp; {bandwidth} — {details}</h5>";
    $h[]="<div class='ibox-tools'>";
    $h[]="<button class='btn btn-xs btn-default' onclick=\"\$('#ms-drill-area').html('')\"><i class='fas fa-times'></i></button>";
    $h[]="</div></div>";
    $h[]="<div class='ibox-content'>";

    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.4.4.7.js'></script>";

    // ── Bandwidth timeline ──
    if($hasTimeline){
        $fmt='H:i';
        if($period==='daily'||$period==='weekly') $fmt='M d';
        if($period==='monthly') $fmt='M Y';

        $timeBuckets=array();
        foreach($data->records as $r){
            $ts=intval($r->first ?? 0);
            $bucket=date($fmt,$ts);
            if(!isset($timeBuckets[$bucket])){
                $timeBuckets[$bucket]=array('size'=>0,'ts'=>$ts);
            }
            $timeBuckets[$bucket]['size']+=intval($r->size ?? 0);
        }
        uasort($timeBuckets,function($a,$b){ return $a['ts']-$b['ts']; });

        $labels=array_keys($timeBuckets);
        $sizeArr=array();
        foreach($timeBuckets as $b){
            $sizeArr[]=sprintf('%.2f',$b['size']/1048576);
        }

        $labelsJS=json_encode($labels);
        $sizeJS='['.implode(',',$sizeArr).']';
        $uid1='bw-line-'.uniqid();

        $h[]="<div class='row'><div class='col-md-8'>";
        $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-line'></i> {bandwidth}</h5>";
        $h[]="<div style='position:relative;height:280px'><canvas id='$uid1'></canvas></div>";
        $h[]="</div>";
    }

    // ── Top 10 users doughnut ──
    if(is_object($usersData) && is_array($usersData->entries) && !empty($usersData->entries)){
        $uLabels=array(); $uVals=array();
        $colors=_ms_colors();
        foreach($usersData->entries as $e){
            $uLabels[]=htmlspecialchars($e->key ?? '?');
            $uVals[]=sprintf('%.2f',intval($e->size ?? 0)/1048576);
        }
        $uLabelsJS=json_encode($uLabels);
        $uValsJS='['.implode(',',$uVals).']';
        $uColorsJS="['".implode("','",array_slice($colors,0,count($uLabels)))."']";
        $uid2='bw-pie-'.uniqid();

        if($hasTimeline){ $h[]="<div class='col-md-4'>"; }
        else { $h[]="<div class='row'><div class='col-md-6 col-md-offset-3'>"; }

        $h[]="<h5 style='margin-top:0'><i class='fas fa-chart-pie'></i> {top} {members}</h5>";
        $h[]="<div style='position:relative;height:200px'><canvas id='$uid2'></canvas></div>";

        // Legend table
        $h[]="<table class='table table-condensed' style='font-size:12px;margin-top:8px'>";
        $totalSize=array_sum(array_map('floatval',$uVals));
        foreach($usersData->entries as $i=>$e){
            $name=htmlspecialchars($e->key ?? '?');
            $mb=$uVals[$i];
            $pct=$totalSize>0?sprintf('%.1f',floatval($mb)/$totalSize*100):'0';
            $color=$colors[$i % count($colors)];
            $h[]="<tr>";
            $h[]="<td style='width:4px;padding:3px 0'><span style='display:inline-block;width:4px;height:14px;background:$color;border-radius:2px'></span></td>";
            $h[]="<td>$name</td>";
            $h[]="<td style='text-align:right;white-space:nowrap'><strong>$mb MB</strong></td>";
            $h[]="<td style='text-align:right;color:#999'>$pct%</td>";
            $h[]="</tr>";
        }
        $h[]="</table>";
        $h[]="</div>";

        if($hasTimeline){ $h[]="</div>"; } // close row
        else { $h[]="</div>"; }
    } elseif($hasTimeline){
        $h[]="</div>"; // close row from timeline col
    }

    $h[]="</div></div>";

    // ── Charts JS ──
    $h[]="<script>";

    if($hasTimeline){
        $h[]="new Chart(document.getElementById('$uid1'),{type:'line',";
        $h[]="data:{labels:$labelsJS,datasets:[";
        $h[]="{label:'{bandwidth} (MB)',data:$sizeJS,borderColor:'#2ecc71',backgroundColor:'rgba(46,204,113,0.1)',tension:0.3,fill:true,pointRadius:2,borderWidth:2}";
        $h[]="]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},";
        $h[]="plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:12}},";
        $h[]="tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toFixed(2)+' MB';}}}},";
        $h[]="scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:16}},";
        $h[]="y:{beginAtZero:true,title:{display:true,text:'MB'}}}}});";
    }

    if(isset($uid2)){
        $h[]="new Chart(document.getElementById('$uid2'),{type:'doughnut',";
        $h[]="data:{labels:$uLabelsJS,datasets:[{data:$uValsJS,backgroundColor:$uColorsJS,borderWidth:0,hoverOffset:8}]},";
        $h[]="options:{responsive:true,maintainAspectRatio:false,cutout:'55%',";
        $h[]="plugins:{legend:{display:false},";
        $h[]="tooltip:{callbacks:{label:function(ctx){return ctx.label+': '+ctx.parsed.toFixed(2)+' MB';}}}}}});";
    }

    $h[]="</script>";
    $h[]="<script>NoSpinner();</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}
