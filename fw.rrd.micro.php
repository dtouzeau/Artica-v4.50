<?php
/**
 * Per-process CPU/Memory metrics using Chart.js.
 * Entry: Loadjs('fw.rrd.micro.php?key=APP_SQUID')
 * API:   GET /process/metrics/{name}?range=1h|2h|6h|24h|48h|7d|30d|60d|90d
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}

if(isset($_GET["tabs"]))          {tabs();          exit;}
if(isset($_GET["chart-render"]))  {chart_render();  exit;}
js();

// ── Helpers ──────────────────────────────────────────────────────────────

function safe_key():string{
    $key=trim($_GET["key"] ?? $_GET["tabs"] ?? $_GET["chart-render"] ?? "");
    if($key==="SQUID_AD_RESTFULL"){$key="APP_ACTIVE_DIRECTORY_REST";}
    return preg_replace('/[^A-Za-z0-9_\-]/','',$key);
}

function date_fmt_for_range(string $range):string{
    $map=[
        '1h'=>'H:i','2h'=>'H:i',
        '6h'=>'H:00','24h'=>'H:00','48h'=>'D H:00',
        '7d'=>'D d','30d'=>'M d',
        '60d'=>'M d','90d'=>'M Y',
    ];
    return $map[$range] ?? 'H:i';
}

// ── Dialog opener ────────────────────────────────────────────────────────

function js():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $key=safe_key();
    $title="{{$key}}";
    $tpl->js_dialog6($title,"$page?tabs=$key",1200);
}

// ── Tabs shell (range buttons + chart container) ─────────────────────────

function tabs():void{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $key=safe_key();
    $keyEnc=urlencode($key);

    $h=[];

    // Range buttons
    $h[]="<div style='margin:10px 0'>";
    $ranges=['1h','2h','6h','24h','48h','7d','30d','60d','90d'];
    foreach($ranges as $r){
        $active=($r==='1h')?'btn-primary':'btn-white';
        $h[]="<button type='button' class='btn btn-sm $active' id='proc-rbtn-$r-$keyEnc'";
        $h[]="  onclick=\"ProcRange_$keyEnc('$r')\">$r</button>";
    }
    $h[]="</div>";

    // Chart container
    $h[]="<div id='proc-charts-$keyEnc'></div>";

    // Navigation JS
    $h[]="<script>";
    $h[]="function ProcRange_$keyEnc(r){";
    $h[]="  var btns=document.querySelectorAll('[id^=\"proc-rbtn-\"]');";
    $h[]="  for(var i=0;i<btns.length;i++){btns[i].className=btns[i].className.replace('btn-primary','btn-white');}";
    $h[]="  var b=document.getElementById('proc-rbtn-'+r+'-$keyEnc');";
    $h[]="  if(b) b.className=b.className.replace('btn-white','btn-primary');";
    $h[]="  LoadAjax('proc-charts-$keyEnc','$page?chart-render=$keyEnc&range='+r);";
    $h[]="}";
    $h[]="ProcRange_$keyEnc('1h');";
    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}

// ── Chart rendering (CPU + Memory, loaded via LoadAjax) ──────────────────

function chart_render():void{
    $tpl=new template_admin();
    $key=safe_key();
    $range=$_GET["range"] ?? "1h";
    $ok_ranges=['1h','2h','6h','24h','48h','7d','30d','60d','90d'];
    if(!in_array($range,$ok_ranges)){$range='1h';}

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API(
        "/process/metrics/".urlencode($key)."?range=".urlencode($range)
    ));

    if(!is_object($json)||!($json->success ?? false)||!is_array($json->data ?? null)||count($json->data)===0){
        $err='';
        if(is_object($json)&&isset($json->error)){$err=htmlspecialchars($json->error);}
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{no_data}".($err?" — $err":"")));
        return;
    }

    $points=$json->data;
    $isAgg=is_object($points[0])&&isset($points[0]->avg_cpu);
    $dateFmt=date_fmt_for_range($range);

    // Build data arrays
    $labels=[];
    $cpuAvg=[];$cpuMax=[];
    $memAvg=[];$memMax=[];
    $hasMemlock=false;
    $mlockAvg=[];$mlockMax=[];

    foreach($points as $p){
        $labels[]="'".date($dateFmt,$p->timestamp)."'";
        if($isAgg){
            $cpuAvg[]=sprintf('%.1f',$p->avg_cpu);
            $cpuMax[]=sprintf('%.1f',$p->max_cpu);
            $memAvg[]=sprintf('%.1f',$p->avg_memory_mb);
            $memMax[]=sprintf('%.1f',$p->max_memory_mb);
            $mlk=floatval($p->avg_memlock_kb ?? 0);
            $mlkMax=intval($p->max_memlock_kb ?? 0);
            if($mlk>0||$mlkMax>0){$hasMemlock=true;}
            $mlockAvg[]=sprintf('%.1f',$mlk);
            $mlockMax[]=sprintf('%d',$mlkMax);
        }else{
            $cpuAvg[]=sprintf('%.1f',$p->cpu);
            $memAvg[]=sprintf('%.1f',$p->memory_mb);
            $mlk=intval($p->memlock_kb ?? 0);
            if($mlk>0){$hasMemlock=true;}
            $mlockAvg[]=sprintf('%d',$mlk);
        }
    }

    $labelsJs='['.implode(',',$labels).']';
    $cpuAvgJs='['.implode(',',$cpuAvg).']';
    $memAvgJs='['.implode(',',$memAvg).']';
    $cpuMaxJs='['.implode(',',$cpuMax).']';
    $memMaxJs='['.implode(',',$memMax).']';
    $mlockAvgJs='['.implode(',',$mlockAvg).']';
    $mlockMaxJs='['.implode(',',$mlockMax).']';

    // Resolution badge text
    $res=$isAgg?($points[0]->period ?? $range):$range;

    // Unique canvas IDs
    $uid_cpu='proc-cpu-'.uniqid();
    $uid_mem='proc-mem-'.uniqid();
    $uid_mlk='proc-mlk-'.uniqid();

    $h=[];

    // Chart.js must be in same HTML response
    $h[]="<script src='angular/js/plugins/chartJs/Chart.min.js'></script>";

    // ── CPU Chart ──
    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'><h5><i class='fas fa-microchip' style='color:#3498db'></i>&nbsp; CPU (%)</h5>";
    $h[]="    <div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$res</span></div>";
    $h[]="  </div>";
    $h[]="  <div class='ibox-content'><div style='height:300px'><canvas id='$uid_cpu'></canvas></div></div>";
    $h[]="</div>";

    // ── Memory Chart ──
    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'><h5><i class='fas fa-memory' style='color:#2ecc71'></i>&nbsp; {memory} (MB)</h5>";
    $h[]="    <div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$res</span></div>";
    $h[]="  </div>";
    $h[]="  <div class='ibox-content'><div style='height:300px'><canvas id='$uid_mem'></canvas></div></div>";
    $h[]="</div>";

    // ── Memlock Chart (only if data) ──
    if($hasMemlock){
        $h[]="<div class='ibox'>";
        $h[]="  <div class='ibox-title'><h5><i class='fas fa-lock' style='color:#e67e22'></i>&nbsp; VmLck (kB)</h5>";
        $h[]="    <div class='ibox-tools'><span class='badge' style='background:#676a6c;color:#fff'>$res</span></div>";
        $h[]="  </div>";
        $h[]="  <div class='ibox-content'><div style='height:250px'><canvas id='$uid_mlk'></canvas></div></div>";
        $h[]="</div>";
    }

    // ── Chart.js init ──
    $h[]="<script>";

    // Shared chart options factory
    $h[]="var _procOpts=function(yLabel,unit){return{";
    $h[]="  responsive:true,maintainAspectRatio:false,";
    $h[]="  interaction:{mode:'index',intersect:false},";
    $h[]="  plugins:{legend:{position:'bottom',labels:{usePointStyle:true,padding:15}},";
    $h[]="    tooltip:{callbacks:{label:function(c){return c.dataset.label+': '+c.parsed.y.toFixed(1)+' '+unit;}}}},";
    $h[]="  scales:{x:{ticks:{maxRotation:45,autoSkip:true,maxTicksLimit:24,font:{size:10}}},";
    $h[]="    y:{beginAtZero:true,title:{display:true,text:yLabel}}}";
    $h[]="};};";

    // CPU chart
    $h[]="(function(){";
    $h[]="var labels=$labelsJs;";
    if($isAgg){
        $h[]="var ds=[";
        $h[]="  {label:'CPU avg %',data:$cpuAvgJs,borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.08)',pointRadius:2,tension:0.3,fill:true,borderWidth:2},";
        $h[]="  {label:'CPU peak %',data:$cpuMaxJs,borderColor:'rgba(231,76,60,0.4)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
        $h[]="];";
    }else{
        $h[]="var ds=[";
        $h[]="  {label:'CPU %',data:$cpuAvgJs,borderColor:'rgb(54,162,235)',backgroundColor:'rgba(54,162,235,0.08)',pointRadius:1,tension:0.3,fill:true,borderWidth:2}";
        $h[]="];";
    }
    $h[]="new Chart(document.getElementById('$uid_cpu'),{type:'line',data:{labels:labels,datasets:ds},options:_procOpts('%','%')});";
    $h[]="})();";

    // Memory chart
    $h[]="(function(){";
    $h[]="var labels=$labelsJs;";
    if($isAgg){
        $h[]="var ds=[";
        $h[]="  {label:'Memory avg (MB)',data:$memAvgJs,borderColor:'rgb(46,204,113)',backgroundColor:'rgba(46,204,113,0.08)',pointRadius:2,tension:0.3,fill:true,borderWidth:2},";
        $h[]="  {label:'Memory peak (MB)',data:$memMaxJs,borderColor:'rgba(231,76,60,0.4)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
        $h[]="];";
    }else{
        $h[]="var ds=[";
        $h[]="  {label:'Memory (MB)',data:$memAvgJs,borderColor:'rgb(46,204,113)',backgroundColor:'rgba(46,204,113,0.08)',pointRadius:1,tension:0.3,fill:true,borderWidth:2}";
        $h[]="];";
    }
    $h[]="new Chart(document.getElementById('$uid_mem'),{type:'line',data:{labels:labels,datasets:ds},options:_procOpts('MB','MB')});";
    $h[]="})();";

    // Memlock chart (only if data)
    if($hasMemlock){
        $h[]="(function(){";
        $h[]="var labels=$labelsJs;";
        if($isAgg){
            $h[]="var ds=[";
            $h[]="  {label:'VmLck avg (kB)',data:$mlockAvgJs,borderColor:'rgb(230,126,34)',backgroundColor:'rgba(230,126,34,0.08)',pointRadius:2,tension:0.3,fill:true,borderWidth:2},";
            $h[]="  {label:'VmLck peak (kB)',data:$mlockMaxJs,borderColor:'rgba(231,76,60,0.4)',borderDash:[5,3],pointRadius:0,tension:0.3,fill:false,borderWidth:1.5}";
            $h[]="];";
        }else{
            $h[]="var ds=[";
            $h[]="  {label:'VmLck (kB)',data:$mlockAvgJs,borderColor:'rgb(230,126,34)',backgroundColor:'rgba(230,126,34,0.08)',pointRadius:1,tension:0.3,fill:true,borderWidth:2}";
            $h[]="];";
        }
        $h[]="new Chart(document.getElementById('$uid_mlk'),{type:'line',data:{labels:labels,datasets:ds},options:_procOpts('kB','kB')});";
        $h[]="})();";
    }

    $h[]="</script>";

    echo $tpl->_ENGINE_parse_body(implode("\n",$h));
}
