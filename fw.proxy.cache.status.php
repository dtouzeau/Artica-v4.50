<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
// acls_api_get(): enveloppe {success,data,error} d'articarest.
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["dynacls"])){dynacls();exit;}
if(isset($_GET['app-status'])){echo app_status();exit;}
if(isset($_GET["flot1"])){flot1();exit;}
if(isset($_GET["flot2"])){flot2();exit;}
if(isset($_GET["flot3"])){flot3();exit;}
if(isset($_GET["flot4"])){flot4();exit;}
if(isset($_GET["flot5"])){flot5();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status-caches"])){echo app_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["statistics"])){statistics();exit;}
if(isset($_GET["statistics-table"])){statistics_table();exit;}
if(isset($_GET["statistics-domain"])){statistics_domain();exit;}
if(isset($_GET["statistics-domain-table"])){statistics_domain_table();exit;}
if(isset($_GET["statistics-urls"])){statistics_urls();exit;}
if(isset($_GET["statistics-urls-table"])){statistics_urls_table();exit;}
if(isset($_GET["statistics-popup"])){statistics_settings();exit;}
if(isset($_GET["statistics-table-disabled"])){statistics_table_disabled();exit;}
if(isset($_POST["EnableSquidPurgeStoredObjects"])){statistics_save();exit;}
if(isset($_GET["hypercache-status"])){hypercache_status();exit;}
if(isset($_POST["disable-hypercache"])){hypercache_disable_perform();exit;}
if(isset($_POST["enable-hypercache"])){hypercache_enable_perform();exit;}
if(isset($_GET["disable-hypercache"])){hypercache_disable();exit;}
if(isset($_GET["enable-hypercache"])){hypercache_enable();exit;}

xgen();



function xgen(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{caching}",ico_caching,
        "{PROXY_CACHE_FEATURE_EXPLAIN}",
        "$page?tabs=yes",
        "proxy-caches",
        "progress-squidcaching-restart",false,"table-loader-caching-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{caching}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{your_caches_stored_on_disks}"]="$page?status=yes";
	echo $tpl->tabs_default($array);
    return true;
}
function hypercache_disable():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $text=$tpl->_ENGINE_parse_body("{remove_this_section}:{HYPERCACHE_STOREID}");

    $jsCompile=$tpl->framework_buildjs(
        "/proxy/caches/verify/reload",
        "squid.caches.progress",
        "squid.caches.progress.log",
        "progress-squidcaching-restart"
        );

    $tpl->js_dialog_confirm_action($text,"disable-hypercache",$text,$jsCompile);
 return true;
}
function hypercache_enable():bool{
    $tpl=new template_admin();
    $text=$tpl->_ENGINE_parse_body("{enable_feature}:{HYPERCACHE_STOREID}");

    $jsCompile=$tpl->framework_buildjs(
        "/proxy/caches/verify/reload",
        "squid.caches.progress",
        "squid.caches.progress.log",
        "progress-squidcaching-restart"
    );

    $tpl->js_dialog_confirm_action($text,"enable-hypercache",$text,$jsCompile);
    return true;
}
function hypercache_disable_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO('HyperCacheStoreID',0);
    admin_tracks($_POST["disable-hypercache"]);
    return true;
}
function hypercache_enable_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO('HyperCacheStoreID',1);
    admin_tracks($_POST["enable-hypercache"]);
    return true;
}
function status():bool{
    $page           = CurrentPageName();

    // Mise en page en flex. La cellule de gauche etait figee a 340px alors que
    // la tuile de service qu'elle recoit en mesure 335 bord a bord.
    $html[]="<div style='display:flex;flex-wrap:wrap;gap:15px;align-items:flex-start;margin-top:15px'>";
    $html[]="<div style='flex:0 0 auto' id='hypercache-status'></div>";
    $html[]="<div style='flex:1 1 420px;min-width:0' id='caches-status'></div>";
    $html[]="</div>";
    $html[]="<script>";
    $html[]="LoadAjax('hypercache-status','$page?hypercache-status=yes');";
    $html[]="LoadAjax('caches-status','$page?status-caches=yes');";
    $html[]="</script>";
    echo @implode("\n",$html);
    return true;
}
function hypercache_status():bool{
    $page           = CurrentPageName();
    $tpl            = new template_admin();
    // articarest /proxy/hypercache/service-status rend le bloc ini directement.
    $ini=new Bs_IniHandler();
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/hypercache/service-status"));
    if(is_object($json) && isset($json->Info)){$ini->loadString((string)$json->Info);}
    $html[]=$tpl->SERVICE_STATUS($ini, "HYPERCACHE_STOREID");
    $HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));

    $disbale="Loadjs('$page?disable-hypercache=yes');";
    $enable="Loadjs('$page?enable-hypercache=yes');";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    if($HyperCacheStoreID==1) {
        $html[] = "<label class=\"btn btn btn-primary\" OnClick=\"$disbale\"><i class='" . ico_play . "'></i> {disable} </label>";
    }else{
        $html[] = "<label class=\"btn btn btn-default\" OnClick=\"$enable\"><i class='" . ico_disabled . "'></i> {enable_feature} </label>";
    }
    $html[]="</div>";
    $html[]="<script>";
    $html[]="function RefreshHyperCacheStatus(){";
    $html[]="if( !document.getElementById('hypercache-status') ){return;}";
    $html[]="LoadAjaxSilent('hypercache-status','$page?hypercache-status=yes');";
    $html[]="}";
    $html[]="setTimeout('RefreshHyperCacheStatus()',3000);";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function app_status():string{
	$tpl            = new template_admin();
    $PROXY          = true;
	$users          = new usersMenus();
	$SQUIDEnable    = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    $date_info      = null;
    $capacity       = null;
    $cards          = array();
    $SubTitle       = array();
    $html           = array();
	if(!$users->SQUID_INSTALLED){$PROXY=false;}
	if($SQUIDEnable==0){$PROXY=false;}
	if($PROXY){
        $SQUID_STORE_DIR=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_STORE_DIR");
        foreach (explode("\n",$SQUID_STORE_DIR) as $storeline){
            if(preg_match("#Last-Modified.*?:\s+(.+)#",$storeline,$re)){$date_info=$re[1];}
            if(preg_match("#Current Capacity.*?:\s+(.+)#",$storeline,$re)){$capacity=$re[1];}
        }
        if($date_info<>null){$SubTitle[]="{updated}: $date_info";}

        // Cache list through articarest (squid/proxy_caches_crud.go), no direct SQL.
        $results=array();
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/caches"),true);
        if(is_array($json) && !empty($json["success"]) && isset($json["data"]) && is_array($json["data"])){
            $results=$json["data"];
        }else{
            $err=(is_array($json) && isset($json["error"]))?(string)$json["error"]:"/proxy/caches";
            $cards[]=array("state"=>"error","name"=>"{error}","dir"=>$err);
        }

        // The token holds the serialized array itself (written by articarest
        // squid.VerifyCaches / SquidStoreDir), not a file path: file_get_contents()
        // on it returned false and every cache showed {waiting}.
        $MAIN_CACHES=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("squid_get_cache_infos.db"));
        if(!is_array($MAIN_CACHES)){$MAIN_CACHES=array();}

        $SquidRockPath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRockPath"));
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRockCache"))==1){
            $dir="$SquidRockPath/rock";
            $cards[]=pcs_card_data("{squid_rock}",$dir,"rock",isset($MAIN_CACHES[$dir])?$MAIN_CACHES[$dir]:null);
        }

        $IsSNMP=IsSNMP();
        $SMPWorkers=pcs_smp_workers();
        $ALREADY_SET=array();
        foreach ($results as $ligne){
            if(empty($ligne["enabled"])){continue;}
            if(intval($ligne["remove"]??0)==1){continue;} // absent from an articarest older than 2026-10-05
            $cache_type=(string)$ligne["cache_type"];
            if($cache_type=="Cachenull"){continue;}
            $cachedir=(string)$ligne["cache_dir"];
            if($cache_type=="tmpfs"){$cachedir="/home/squid/cache/MemBooster".intval($ligne["ID"]);}
            if($cachedir==""){continue;}
            if(isset($ALREADY_SET[$cachedir])){continue;}
            $ALREADY_SET[$cachedir]=true;
            $name=(string)$ligne["cachename"];
            // SMP: an aufs/ufs/diskd cache is used by the worker of its CPU (articarest renderCacheDirs);
            // one assigned to a worker Squid does not run is not used.
            $cpu=max(1,intval($ligne["cpu"]??1));
            if(!isset($MAIN_CACHES[$cachedir]) && $IsSNMP && $cache_type!="rock" && $cpu>$SMPWorkers){
                $cards[]=array("state"=>"unsupported","name"=>$name,"dir"=>$cachedir,"type"=>$cache_type);
                continue;
            }
            $cards[]=pcs_card_data($name,$cachedir,$cache_type,isset($MAIN_CACHES[$cachedir])?$MAIN_CACHES[$cachedir]:null);
        }

        // Global capacity, over the caches Squid reports.
        $sumCur=0;$sumMax=0;
        foreach ($cards as $c){
            if($c["state"]!="ok"){continue;}
            $sumCur+=$c["current_kb"];$sumMax+=$c["max_kb"];
        }
        if($sumMax>0){
            $SubTitle[]="{total_cache_capacity}: ".FormatBytes($sumCur)." / ".FormatBytes($sumMax)." (".round($sumCur*100/$sumMax,1)."%)";
        }elseif($capacity<>null){
            $SubTitle[]="($capacity)"; // Squid's own figure, when no cache reports sizes
        }

        if(count($cards)>0){
            $html[]="<div class='pcs-grid'>";
            foreach ($cards as $c){$html[]=pcs_card_html($c);}
            $html[]="</div>";
            $html[]=pcs_partitions_html($cards);
        }
    }

    if(count($cards)==0){
        $html[]=$tpl->div_warning("{NoCache}||{proxy_no_cache_explain}");
    }

    $TINY_ARRAY["TITLE"]="{caching}";
    $TINY_ARRAY["ICO"]=ico_caching;
    $TINY_ARRAY["EXPL"]="{PROXY_CACHE_FEATURE_EXPLAIN}<br><strong>".@implode(" ",$SubTitle)."</strong>";
    $TINY_ARRAY["BUTTONS"]=null;
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$headsjs</script>";

    // The stylesheet stays OUT of _ENGINE_parse_body: every CSS {…} block would
    // be looked up as a {token}.
    return pcs_style().$tpl->_ENGINE_parse_body($html);
}

// pcs_card_data: one cache tile from the squid_get_cache_infos.db entry
// (sizes in KB, POURC in %, DIRPART_INFO = df of the cache partition, bytes).
function pcs_card_data(string $name,string $dir,string $type,$MAIN):array{
    $c=array("name"=>$name,"dir"=>$dir,"type"=>$type,"state"=>"waiting");
    if(!is_array($MAIN)){return $c;}
    $max=intval($MAIN["MAX"]??0);
    $cur=intval($MAIN["CURRENT"]??0);
    $pct=isset($MAIN["POURC"]) && is_numeric($MAIN["POURC"])?floatval($MAIN["POURC"]):0;
    if($pct==0 && $max>0){$pct=$cur*100/$max;}
    $c["state"]="ok";
    $c["current_kb"]=$cur;
    $c["max_kb"]=$max;
    $c["pct"]=round(min(100,max(0,$pct)),1);
    $c["partition"]=(string)($MAIN["PARTITION"]??"");
    // Two writers, two shapes: the Go port (SquidStoreDir) stores a JSON string
    // with lowercase keys, the PHP squid_get_cache_infos() an array with
    // uppercase keys (DEV, TOT, USED, AIV, POURC, MOUNT).
    $c["dirpart"]=array();
    $dp=$MAIN["DIRPART_INFO"]??null;
    if(is_string($dp)){$dp=json_decode($dp,true);}
    if(is_array($dp)){$c["dirpart"]=array_change_key_case($dp,CASE_LOWER);}
    return $c;
}

function pcs_level(float $pct):string{
    if($pct>94){return "full";}
    if($pct>80){return "warn";}
    return "";
}

function pcs_type_tag(string $type):string{
    if($type==""){return "";}
    $cls=$type=="tmpfs"?"ak-tag ak-warn":"ak-tag";
    $label=$type=="tmpfs"?"tmpfs / RAM":$type;
    return "<span class='$cls'>".htmlspecialchars($label)."</span>";
}

function pcs_card_html(array $c):string{
    $name=htmlspecialchars((string)$c["name"]);
    $dir=htmlspecialchars((string)($c["dir"]??""));
    $head="<div class='pcs-head'>";
    $dot=array("ok"=>"ak-dot ak-ok","waiting"=>"ak-dot ak-off","unsupported"=>"ak-dot ak-off","error"=>"ak-dot ak-err");
    $head.="<span class='".$dot[$c["state"]]."'></span><h3 class='pcs-name' title='$name'>$name</h3></div>";
    if($dir!=""){$head.="<div class='pcs-path'><i class='far fa-folder'></i><span>$dir</span></div>";}

    if($c["state"]=="error"){
        return "<article class='ak-card pcs-card pcs-card-err'>$head<div class='pcs-state pcs-state-err'>/proxy/caches</div></article>";
    }
    if($c["state"]=="unsupported"){
        return "<article class='ak-card pcs-card'>$head<div class='pcs-state'>{not_supported}</div>"
            ."<div class='pcs-foot'><span></span>".pcs_type_tag((string)$c["type"])."</div></article>";
    }
    if($c["state"]=="waiting"){
        return "<article class='ak-card pcs-card'>$head<div class='pcs-state'>{cache_not_reported}</div>"
            ."<div class='pcs-foot'><span></span>".pcs_type_tag((string)$c["type"])."</div></article>";
    }

    $pct=$c["pct"];
    $level=pcs_level($pct);
    $fill=$level==""?"pcs-fill":"pcs-fill pcs-fill-$level";
    $free=max(0,$c["max_kb"]-$c["current_kb"]);
    $width=$pct>0 && $pct<1?1:$pct; // a sliver stays visible
    $h=array();
    $h[]="<article class='ak-card pcs-card'>$head";
    $h[]="<div class='pcs-big'><div><span class='pcs-pct'>$pct%</span><span class='pcs-pct-lbl'>{used}</span></div>";
    $h[]="<span class='pcs-amount'>".FormatBytes($c["current_kb"])." / ".FormatBytes($c["max_kb"])."</span></div>";
    $h[]="<div class='pcs-bar'><div class='$fill' style='width:$width%'></div></div>";
    $h[]="<div class='pcs-scale'><span>0</span><span>".FormatBytes($free)." {free}</span><span>".FormatBytes($c["max_kb"])."</span></div>";
    $part=htmlspecialchars((string)$c["partition"]);
    $h[]="<div class='pcs-foot'><span class='pcs-part'>".($part!=""?"<i class='fas fa-hdd'></i> $part":"")."</span>".pcs_type_tag((string)$c["type"])."</div>";
    $h[]="</article>";
    return @implode("",$h);
}

// pcs_partitions_html: one row per file system holding at least one cache.
function pcs_partitions_html(array $cards):string{
    $mounts=array();
    foreach ($cards as $c){
        if($c["state"]!="ok" || empty($c["dirpart"])){continue;}
        $dp=$c["dirpart"];
        $key=(string)($dp["mount"]??"")."|".(string)($dp["dev"]??"");
        if(!isset($mounts[$key])){$mounts[$key]=array("dp"=>$dp,"names"=>array(),"types"=>array());}
        $mounts[$key]["names"][]=(string)$c["name"];
        $mounts[$key]["types"][(string)$c["type"]]=true;
    }
    if(count($mounts)==0){return "";}
    $h=array();
    $h[]="<section class='ak-card pcs-panel'>";
    $h[]="<h2>{cache_partitions}</h2><p>{cache_partitions_explain}</p>";
    $h[]="<div class='pcs-scroll'><table class='ak-tbl pcs-tbl'><thead><tr>";
    $h[]="<th>{partition}</th><th>{caches}</th><th>{cache_type}</th><th>{disk_usage}</th><th>{used}</th>";
    $h[]="</tr></thead><tbody>";
    foreach ($mounts as $m){
        $dp=$m["dp"];
        $tot=floatval($dp["tot"]??0);
        $used=floatval($dp["used"]??0);
        $pct=isset($dp["pourc"]) && is_numeric($dp["pourc"])?floatval($dp["pourc"]):($tot>0?$used*100/$tot:0);
        $level=pcs_level($pct);
        $fill=$level==""?"pcs-fill":"pcs-fill pcs-fill-$level";
        $tags="";
        foreach (array_keys($m["types"]) as $t){$tags.=pcs_type_tag($t)." ";}
        $names=array();
        foreach ($m["names"] as $n){$names[]=htmlspecialchars($n);}
        $h[]="<tr>";
        $h[]="<td><div class='pcs-mount'><span class='ak-dot ak-ok'></span><strong>".htmlspecialchars((string)($dp["mount"]??""))."</strong></div>"
            ."<div class='pcs-sub'>".htmlspecialchars((string)($dp["dev"]??""))."</div></td>";
        $h[]="<td>".@implode(", ",$names)."</td>";
        $h[]="<td>$tags</td>";
        $h[]="<td class='ak-nowrap'>".FormatBytes($used/1024)." / ".FormatBytes($tot/1024)."</td>";
        $h[]="<td class='ak-nowrap'><div class='pcs-mini'><div class='$fill' style='width:".round(min(100,$pct),1)."%'></div></div>"
            ."<span class='pcs-sub'>".round($pct,1)."%</span></td>";
        $h[]="</tr>";
    }
    $h[]="</tbody></table></div></section>";
    return @implode("",$h);
}

// pcs_style: design stitch_proxy_cache_status_tiles. Accents follow the brand
// palette (emerald on a stock appliance); class.brand.inc may be missing on an
// older appliance, hence the fallback.
function pcs_style():string{
    if(isset($GLOBALS["PCS_STYLE_DONE"])){return "";}
    $GLOBALS["PCS_STYLE_DONE"]=true;
    $accent="#10b981";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$dark=$a["accent_dark"];
    }
    return "<style>
.pcs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.pcs-card{padding:16px;transition:border-color .2s}
.pcs-card:hover{border-color:#cbd5e1}
.pcs-card-err{border-color:#fecaca}
.pcs-head{display:flex;align-items:center;gap:8px;min-width:0}
.pcs-name{font-size:14px;font-weight:700;color:#0f172a;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pcs-path{font-size:11px;color:#64748b;margin-top:4px;display:flex;align-items:center;gap:5px;word-break:break-all}
.pcs-path svg,.pcs-part svg{color:#94a3b8}
.pcs-big{display:flex;align-items:baseline;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:12px}
.pcs-pct{font-size:24px;font-weight:700;color:#0f172a;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
.pcs-pct-lbl{font-size:12px;color:#64748b;margin-left:6px}
.pcs-amount{font-size:12px;color:#475569;font-variant-numeric:tabular-nums}
.pcs-bar{width:100%;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;height:10px;padding:2px;margin-top:8px;box-sizing:border-box;overflow:hidden}
.pcs-bar .pcs-fill{height:4px}
.pcs-fill{border-radius:999px;background:linear-gradient(90deg,$accent,$dark);transition:width .5s}
.pcs-fill-warn{background:linear-gradient(90deg,$accent,#f59e0b)}
.pcs-fill-full{background:linear-gradient(90deg,#f59e0b,#dc2626)}
.pcs-scale{display:flex;justify-content:space-between;font-size:10px;color:#64748b;margin-top:4px;font-variant-numeric:tabular-nums}
.pcs-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:10px;font-size:11px;color:#475569;padding:0 2px}
.pcs-part{display:inline-flex;align-items:center;gap:5px}
.pcs-state{font-size:16px;font-weight:600;color:#64748b;margin-top:14px}
.pcs-state-err{color:#b91c1c}
.pcs-panel{padding:20px;margin-top:20px}
.pcs-panel h2{font-size:14px;font-weight:700;color:#0f172a;margin:0}
.pcs-panel p{font-size:12px;color:#64748b;margin:2px 0 14px 0}
.pcs-scroll{overflow-x:auto}
.pcs-tbl{font-size:13px}.pcs-tbl th{white-space:nowrap}
.pcs-tbl tbody tr:hover{background:#f8fafc}
.pcs-mount{display:flex;align-items:center;gap:8px;color:#0f172a}
.pcs-sub{font-size:11px;color:#64748b;margin-top:2px}
.pcs-mini{display:inline-block;vertical-align:middle;width:96px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;height:8px;overflow:hidden;margin-right:8px}
.pcs-mini .pcs-fill{height:100%}
@media(max-width:700px){.pcs-grid{grid-template-columns:1fr}}
</style>";
}
// pcs_smp_workers is the number of Squid workers as articarest computes it (squid getWorkers).
function pcs_smp_workers():int{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPDisabled"))==1){return 1;}
    $SquidSMPConfig=unserialize((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    $c=is_array($SquidSMPConfig)?count($SquidSMPConfig):0;
    if($c==0){$c=1;}
    $users=new usersMenus();
    $cpus=intval($users->CPU_NUMBER);
    if($cpus>0 && $c>$cpus){$c=$cpus;}
    return $c;
}

function IsSNMP():bool{
    $CPUZ=array();
    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    if(!$SquidSMPConfig){$SquidSMPConfig=array();}
    if(count($SquidSMPConfig)==0){$SquidSMPConfig[1]=1;}
    foreach ($SquidSMPConfig as $num=>$val){
        if($val==null){continue;}
        $val=intval($val);
        if($val==0){continue;}
        $CPUZ[$num]=true;
    }
    if(count($CPUZ)>1){return true;}
    return false;
}

function statistics(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $tpl->js_dialog1("{schedule}:{analyze_stored_objects}","$page?statistics-table-disabled=yes");
}

function statistics_table_disabled(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$security="AsSquidAdministrator";
	$EnableSquidPurgeStoredObjects=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjects"));
	$EnableSquidPurgeStoredObjectsMaxTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjectsMaxTime"));
	$EnableSquidPurgeStoredObjectsTime=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjectsTime"));
	
	if($EnableSquidPurgeStoredObjectsTime==null){$EnableSquidPurgeStoredObjectsTime="03:35";}
	if($EnableSquidPurgeStoredObjectsMaxTime==0){$EnableSquidPurgeStoredObjectsMaxTime=120;}
	$form[]=$tpl->field_checkbox("EnableSquidPurgeStoredObjects","{analyze_stored_objects}",$EnableSquidPurgeStoredObjects,true,"{analyze_stored_objects_explain}");
	$form[]=$tpl->field_clock("EnableSquidPurgeStoredObjectsTime", "{each_day}", $EnableSquidPurgeStoredObjectsTime);

	
	$jsafter="dialogInstance1.close();";
	
	$html[]=$tpl->form_outside("{parameters}", @implode("\n", $form),"{squid_purge_disabled_explain}","{apply}",$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}

function statistics_save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	// The daily scan is scheduled by articarest from these keys
	// (squid/proxy_caches_jobs.go StoredObjectsScheduled): no cron to install.
	foreach ($_POST as $key=>$val){$sock->SET_INFO($key, $val);}
}

function statistics_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$EnableSquidPurgeStoredObjects=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjects"));
	if($EnableSquidPurgeStoredObjects==0){statistics_table_disabled();return;}

	// articarest squid/proxy_caches_jobs.go StoredObjectsScan (port of exec.purge.php).
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.purge.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.purge.progress.log";
	$ARRAY["CMD"]="/proxy/caches/objects/scan";
	$ARRAY["TITLE"]="{analyze_caches}";
	$ARRAY["AFTER"]="LoadAjaxSilent('squid-purge','$page?statistics-table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=squid-purge-progress')";

	$html=array();
	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart\"><i class='fa fa-bolt'></i> {analyze_caches} </label>
			<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?statistics-popup=yes')\"><i class='fas fa-cogs'></i> {settings} </label>
			</div>");

	list($tableExists,$apiRows)=cachest_api_rows("/proxy/cache/purge/families");

	$rows=array();
	$totalSize=0;
	foreach($apiRows as $r){
		if(!is_array($r)){continue;}
		// Les constructeurs de lignes attendent les noms de colonnes SQL
		// historiques; on normalise ici plutot que de les reecrire.
		$ligne=array(
			"familysite" => (string)($r["familySite"] ?? ""),
			"hits"       => intval($r["hits"] ?? 0),
			"size"       => floatval($r["size"] ?? 0),
		);
		$totalSize=$totalSize+$ligne["size"];
		$rows[]=cachest_family_row($ligne,$page,$tpl);
	}
	$shown=count($rows);

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{websites}</th>";
	$html[]="<th class='center'>{objects}</th>";
	$html[]="<th class='center'>{size}</th>";
	$html[]="</tr></thead><tbody id='cachest-fam-rows'>";
	if($shown==0){
		$html[]=cachest_empty_row(3,$tableExists);
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown &middot; {size}: <b>".FormatBytes($totalSize/1024)."</b></span>";
	$html[]="<span class='awc-pg' id='cachest-fam-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"cachest-fam-rows","cachest-fam-pager");
}

/**
 * Lecture d'un des trois endpoints /proxy/cache/purge/* d'articarest.
 *
 * La table PostgreSQL "squidpurge" n'est creee que par le scan des caches
 * (articarest StoredObjectsScan). Tant qu'il n'a jamais tourne elle N'EXISTE PAS, et cette
 * page l'interrogeait en direct: PostgreSQL repondait
 * `relation "squidpurge" does not exist`, que FATAL_ERROR_SHOW_128() affichait
 * tel quel. Le demon rend desormais cet etat comme une DONNEE
 * ("tableExists":false), ce qui distingue "jamais analyse" de "analyse, cache
 * vide" -- deux reponses differentes dont une seule se corrige en cliquant sur
 * {analyze_caches}.
 *
 * Rend array($tableExists, $rows). $rows est toujours un tableau.
 */
function cachest_api_rows($path):array{
	$data=acls_api_get($path);
	if(!is_array($data)){
		// Appel en echec (demon injoignable, route absente d'un binaire trop
		// ancien): on ne peut PAS affirmer que le scan n'a pas tourne.
		return array(true,array());
	}
	$exists=!empty($data["tableExists"]);
	$rows=(isset($data["rows"]) && is_array($data["rows"]))?$data["rows"]:array();
	return array($exists,$rows);
}

/** Le corps de tableau affiche quand il n'y a rien a montrer. */
function cachest_empty_row($colspan,$tableExists):string{
	$text=$tableExists?"{no_data}":"{cache_not_analyzed}";
	return "<tr><td colspan='$colspan'><div class='awc-empty'>$text</div></td></tr>";
}

/** Un domaine de rattachement et le volume qu'il occupe dans le cache. */
function cachest_family_row($ligne,$page,$tpl):string{
	$familysite=(string)$ligne["familysite"];
	$zmd5=md5(serialize($ligne));
	// Pentest 2026-11-04 : noms de sites / chemins du cache (donnee externe) : htmlspecialchars UTF-8 rendait "" sur un octet Latin-1 (XSS stockee).
	$link=$tpl->td_href("<span class='awc-name'>".req_html($familysite)."</span>",
		"{stored_objects}","Loadjs('$page?statistics-domain=".urlencode($familysite)."')");

	$h ="<tr id='$zmd5'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-globe'></i></span>";
	$h.="<span>$link</span>";
	$h.="</div></td>";
	$h.="<td class='center'>".FormatNumber(intval($ligne["hits"]))."</td>";
	$h.="<td class='center'><b>".FormatBytes($ligne["size"]/1024)."</b></td>";
	$h.="</tr>";
	return $h;
}

function statistics_domain(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	// Le titre est injecte dans le JavaScript d'ouverture de la modale: il doit
	// etre echappe pour la chaine JS comme pour le HTML.
	$domain=$_GET["statistics-domain"];
	$domainenc=urlencode($domain);
	$title=addslashes(htmlspecialchars($domain));
	$tpl->js_dialog1("{stored_objects}: $title", "$page?statistics-domain-table=$domainenc");
}
function statistics_settings(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog1("{parameters}", "$page?statistics-table-disabled=yes");
	
}
function statistics_urls(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	// Meme traitement que statistics_domain().
	$domain=$_GET["statistics-urls"];
	$domainenc=urlencode($domain);
	$title=addslashes(htmlspecialchars($domain));
	$tpl->js_dialog2("{stored_objects}: $title", "$page?statistics-urls-table=$domainenc");
	
}

function statistics_urls_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	// Le nom de site partait BRUT dans le WHERE.
	$domain=(string)$_GET["statistics-urls-table"];
	list($tableExists,$apiRows)=cachest_api_rows(
		"/proxy/cache/purge/urls?sitename=".urlencode($domain));

	$rows=array();
	$totalSize=0;
	foreach($apiRows as $r){
		if(!is_array($r)){continue;}
		$ligne=array(
			"path" => (string)($r["path"] ?? ""),
			"size" => floatval($r["size"] ?? 0),
		);
		$totalSize=$totalSize+$ligne["size"];
		$rows[]=cachest_url_row($ligne,$domain);
	}
	$shown=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{urls}</th>";
	$html[]="<th class='center'>{size}</th>";
	$html[]="</tr></thead><tbody id='cachest-url-rows'>";
	if($shown==0){
		$html[]=cachest_empty_row(2,$tableExists);
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown &middot; {size}: <b>".FormatBytes($totalSize/1024)."</b></span>";
	$html[]="<span class='awc-pg' id='cachest-url-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"cachest-url-rows","cachest-url-pager");
}

/** Un objet stocke dans le cache: son chemin complet et sa taille. */
function cachest_url_row($ligne,$domain):string{
	$zmd5=md5(serialize($ligne));
	$h ="<tr id='$zmd5'>";
	// Pentest 2026-11-04 : noms de sites / chemins du cache (donnee externe) : htmlspecialchars UTF-8 rendait "" sur un octet Latin-1 (XSS stockee).
	$h.="<td class='awc-mono'>".req_html($domain.(string)$ligne["path"])."</td>";
	$h.="<td class='center'><b>".FormatBytes($ligne["size"]/1024)."</b></td>";
	$h.="</tr>";
	return $h;
}

function statistics_domain_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	// Le domaine de rattachement partait BRUT dans le WHERE.
	$domain=(string)$_GET["statistics-domain-table"];
	list($tableExists,$apiRows)=cachest_api_rows(
		"/proxy/cache/purge/sites?familysite=".urlencode($domain));

	$rows=array();
	$totalSize=0;
	foreach($apiRows as $r){
		if(!is_array($r)){continue;}
		$ligne=array(
			"sitename" => (string)($r["siteName"] ?? ""),
			"hits"     => intval($r["hits"] ?? 0),
			"size"     => floatval($r["size"] ?? 0),
		);
		$totalSize=$totalSize+$ligne["size"];
		$rows[]=cachest_site_row($ligne,$page,$tpl);
	}
	$shown=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{websites}</th>";
	$html[]="<th class='center'>{objects}</th>";
	$html[]="<th class='center'>{size}</th>";
	$html[]="</tr></thead><tbody id='cachest-site-rows'>";
	if($shown==0){
		$html[]=cachest_empty_row(3,$tableExists);
	}else{
		$html[]=@implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown &middot; {size}: <b>".FormatBytes($totalSize/1024)."</b></span>";
	$html[]="<span class='awc-pg' id='cachest-site-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n", $html))
		.awc_pager_js(50,"cachest-site-rows","cachest-site-pager");
}

/** Un site du domaine et le volume qu'il occupe dans le cache. */
function cachest_site_row($ligne,$page,$tpl):string{
	$sitename=(string)$ligne["sitename"];
	$zmd5=md5(serialize($ligne));
	// Le nom de site partait NON encode dans l'URL alors que la version encodee
	// etait calculee juste a cote et jamais utilisee.
	// Pentest 2026-11-04 : noms de sites / chemins du cache (donnee externe) : htmlspecialchars UTF-8 rendait "" sur un octet Latin-1 (XSS stockee).
	$link=$tpl->td_href("<span class='awc-name'>".req_html($sitename)."</span>",
		"{stored_objects}","Loadjs('$page?statistics-urls=".urlencode($sitename)."')");

	$h ="<tr id='$zmd5'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-globe'></i></span>";
	$h.="<span>$link</span>";
	$h.="</div></td>";
	$h.="<td class='center'>".FormatNumber(intval($ligne["hits"]))."</td>";
	$h.="<td class='center'><b>".FormatBytes($ligne["size"]/1024)."</b></td>";
	$h.="</tr>";
	return $h;
}
