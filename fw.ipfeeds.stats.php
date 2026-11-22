<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["courbe-threats"])){courbe_threats();exit;}
if(isset($_GET["period"])){stats_page();exit;}
if(isset($_GET["pie-categories"])){pie_categories();exit;}
if(isset($_GET["pie-ips"])){pie_ipaddrs();exit;}
if(isset($_GET["pie-categories-list"])){pie_categories_list();exit;}
if(isset($_GET["pie-ips-list"])){pie_ips_list();exit;}

tabs();

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{today}"]="$page?period=today";
    $array["{yesterday}"]="$page?period=yesterday";
    $array["{this_week}"]="$page?period=week";
    $array["{this_month}"]="$page?period=month";
    echo "<div style='margin-top:10px'>".$tpl->tabs_default($array)."</div>";
    return true;
}
function stats_page():bool{
    $page=CurrentPageName();
    $time=$_GET["period"];
    $tpl=new template_admin();
    // Load Chart.js synchronously here so that by the time the AJAX-
    // delivered chart payloads from Loadjs() arrive and eval, the global
    // `Chart` constructor is already defined. Same path the rest of the
    // Artica firewall pages use (fw.conntrackd.php, fw.dns.dnsdist.graphs.php).
    $html[]="";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td colspan='2' style='text-align:center;'>";
    $html[]="<div id='line-courbe'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:50%;vertical-align: top'><div id='pie-categories'></div></td>";
    $html[]="<td style='width:50%;vertical-align: top'><div id='pie-ips'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:50%;vertical-align: top'><div id='pie-categories-list'></div></td>";
    $html[]="<td style='width:50%;vertical-align: top;padding-left:10px'><div id='pie-ips-list'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?courbe-threats=$time');";
    $html[]="Loadjs('$page?pie-categories=$time');";
    $html[]="Loadjs('$page?pie-ips=$time');";
    $html[]="LoadAjax('pie-categories-list','$page?pie-categories-list=$time');";
    $html[]="LoadAjax('pie-ips-list','$page?pie-ips-list=$time');";
    $html[]="</script>";
    echo @implode("\n", $html);
    return true;
}

function courbe_threats(){
    $time=$_GET["courbe-threats"];
    $zFiles["today"]="todayBlackHits.array";
    $zFiles["yesterday"]="YesterdayBlackHits.array";
    $zFiles["week"]="WeeklyBlackHits.array";
    $zFiles["month"]="MonthlyBlackHits.array";

    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return "";
    }
    $script_tz = date_default_timezone_get();
    $data=unserialize(file_get_contents($FilePath));
    if(!is_array($data) || count($data)==0){
        // Render an empty-state placeholder rather than leaving the div
        // blank — keeps the layout from collapsing on first deploy.
        echo "document.getElementById('line-courbe').innerHTML='<div style=\"padding:30px;text-align:center;color:#999;\">{no_data}</div>';";
        return true;
    }

    // Sort chronologically so the line chart x-axis is monotonic.
    ksort($data);

    $labels=array();
    $values=array();
    foreach($data as $k=>$v){
        $date = new DateTime($k, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($script_tz));
        if($time=="today" || $time=="yesterday"){
            $labels[] = $date->format('H:i');
        } else {
            $labels[] = $date->format('d H:00');
        }
        $values[] = (int)$v;
    }

    $tpl=new template_admin();
    $titleJS=$tpl->javascript_parse_text("{threats}");
    // No need for json_encode of label strings — they're produced by
    // PHP date(), so they're guaranteed safe characters.
    $labelsJS = "['" . implode("','", $labels) . "']";
    $valuesJS = "[" . implode(",", $values) . "]";
    $uid = "ipfeeds-line-" . uniqid();

    $js = <<<JS
(function(){
    var c = document.getElementById('line-courbe');
    if (!c) return;
    c.innerHTML = "<div style='position:relative;height:280px'><canvas id='$uid'></canvas></div>";
    new Chart(document.getElementById('$uid'), {
        type: 'line',
        data: {
            labels: $labelsJS,
            datasets: [{
                label: '$titleJS',
                data: $valuesJS,
                borderColor: 'rgb(54,162,235)',
                backgroundColor: 'rgba(54,162,235,0.15)',
                tension: 0.3,
                fill: true,
                borderWidth: 2,
                pointRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {mode:'index', intersect:false},
            plugins: {
                legend: {position:'bottom', labels:{usePointStyle:true, padding:12}},
                title:  {display:true, text:'$titleJS'}
            },
            scales: {
                y: {beginAtZero:true, title:{display:true, text:'$titleJS'}},
                x: {ticks:{maxRotation:45, autoSkip:true, maxTicksLimit:24}}
            }
        }
    });
})();
JS;

    echo $tpl->_ENGINE_parse_body($js);
    return true;
}

function pie_categories():bool{
    $tpl=new template_admin();
    $time=$_GET["pie-categories"];
    $zFiles["today"]="todayCategories.array";
    $zFiles["yesterday"]="yesterdayCategories.array";
    $zFiles["week"]="weeklyCategories.array";
    $zFiles["month"]="monthlyCategories.array";

    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return false;
    }
    $PieData=unserialize(file_get_contents($FilePath));
    if(!is_array($PieData) || count($PieData)==0){
        echo "document.getElementById('pie-categories').innerHTML='<div style=\"padding:30px;text-align:center;color:#999;\">{no_data}</div>';";
        return true;
    }

    echo _ipfeeds_pie_chart($PieData, "pie-categories", $tpl->javascript_parse_text("{top_categories}/{block}"));
    return true;
}
function pie_ipaddrs():bool{
    $tpl=new template_admin();
    $time=$_GET["pie-ips"];
    $zFiles["today"]="todayIps.array";
    $zFiles["yesterday"]="yesterdayIps.array";
    $zFiles["week"]="weeklyIps.array";
    $zFiles["month"]="monthlyIps.array";

    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return false;
    }
    $PieData=unserialize(file_get_contents($FilePath));
    if(!is_array($PieData) || count($PieData)==0){
        echo "document.getElementById('pie-ips').innerHTML='<div style=\"padding:30px;text-align:center;color:#999;\">{no_data}</div>';";
        return true;
    }

    echo _ipfeeds_pie_chart($PieData, "pie-ips", $tpl->javascript_parse_text("{top_sources}/{block}"));
    return true;
}

// _ipfeeds_pie_chart returns the JS payload that replaces a container div
// with a Chart.js doughnut. Shared by pie_categories and pie_ipaddrs since
// the only difference is the data and labels — keeping them DRY.
//
// Top 10 entries are charted; everything else folded into "Other" so the
// legend stays readable when there are dozens of categories or hundreds of
// source IPs.
function _ipfeeds_pie_chart(array $data, string $containerId, string $title):string{
    arsort($data);
    $top = array_slice($data, 0, 10, true);
    $rest = array_slice($data, 10, null, true);
    if (count($rest) > 0) {
        $top['Other'] = array_sum($rest);
    }

    // Build labels JS: addslashes on each so quotes in keys (rare but
    // possible for unusual category names) don't break the literal.
    $labelLits = array();
    foreach (array_keys($top) as $lbl) {
        // Pentest 2026-11-04 : libelle externe dans un litteral JS, addslashes insuffisant (XSS stockee).
        $labelLits[] = "'" . req_js($lbl) . "'";
    }
    $labelsJS = "[" . implode(",", $labelLits) . "]";
    $valuesJS = "[" . implode(",", array_map('intval', array_values($top))) . "]";

    // Palette mirrors the one in fw.conntrackd.php so charts feel
    // visually consistent across the firewall section.
    $colors = "['#3498db','#e74c3c','#2ecc71','#f39c12','#9b59b6',"
            . "'#1abc9c','#e67e22','#34495e','#95a5a6','#16a085','#bdc3c7']";
    $uid = preg_replace('/[^a-z0-9]/i','-',$containerId) . "-" . uniqid();

    return <<<JS
(function(){
    var c = document.getElementById('$containerId');
    if (!c) return;
    c.innerHTML = "<div style='position:relative;height:300px'><canvas id='$uid'></canvas></div>";
    new Chart(document.getElementById('$uid'), {
        type: 'doughnut',
        data: {
            labels: $labelsJS,
            datasets: [{
                data: $valuesJS,
                backgroundColor: $colors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {position: 'right', labels: {usePointStyle: true, padding: 8, boxWidth: 12}},
                title:  {display: true, text: '$title'}
            }
        }
    });
})();
JS;
}
function pie_categories_list():bool{
    $t=time();
    $tpl=new template_admin();
    $html[]="<table id='table-a$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hits}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $time=$_GET["pie-categories-list"];
    $zFiles["today"]="todayCategories.array";
    $zFiles["yesterday"]="yesterdayCategories.array";
    $zFiles["week"]="weeklyCategories.array";
    $zFiles["month"]="monthlyCategories.array";
    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return false;
    }
    $TRCLASS="";
    $PieData=unserialize(file_get_contents($FilePath));
    arsort($PieData);
    foreach ($PieData as $k=>$v){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5("$k$v");
        $ico=ico_books;
        $v=$tpl->FormatNumber($v);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        // Pentest 2026-11-04 : categorie / IP source (donnee externe) affichee sans encodage (XSS stockee).
        $k=req_html($k);
        $html[]="<td style='width:99%' nowrap><i class=\"$ico\"></i>&nbsp;<strong>$k</strong></td>";
        $html[]="<td style='width:1%' nowrap>$v</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-a$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function pie_ips_list():bool{
    $t=time();
    $tpl=new template_admin();
    $html[]="<table id='table-b$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hits}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $time=$_GET["pie-ips-list"];
    $zFiles["today"]="todayIps.array";
    $zFiles["yesterday"]="yesterdayIps.array";
    $zFiles["week"]="weeklyIps.array";
    $zFiles["month"]="monthlyIps.array";

    $FilePath="/usr/share/artica-postfix/ressources/logs/nfqueue/$zFiles[$time]";
    if(!is_file($FilePath)){
        return false;
    }
    $TRCLASS="";
    $PieData=unserialize(file_get_contents($FilePath));
    arsort($PieData);
    foreach ($PieData as $k=>$v){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5("$k$v");
        $ico=ico_computer;
        $v=$tpl->FormatNumber($v);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        // Pentest 2026-11-04 : categorie / IP source (donnee externe) affichee sans encodage (XSS stockee).
        $k=req_html($k);
        $html[]="<td style='width:99%' nowrap><i class=\"$ico\"></i>&nbsp;<strong>$k</strong></td>";
        $html[]="<td style='width:1%' nowrap>$v</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-b$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
