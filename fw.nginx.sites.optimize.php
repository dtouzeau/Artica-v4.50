<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

// --- dispatch: every AJAX endpoint here, before www_js(), each one exits ----------------------
if(isset($_GET["www-tabs"])){www_tabs();exit;}
if(isset($_GET["www-parameters"])){www_parameters();exit;}
if(isset($_GET["nopt-cards"])){nopt_cards();exit;}
if(isset($_GET["www-proxy-buffering"])){www_proxy_buffering();exit;}
if(isset($_GET["nopt-buffers"])){nopt_buffers();exit;}
if(isset($_GET["nopt-toggle"])){nopt_toggle_js();exit;}
// Legacy entry point: the sites tables (fw.nginx.sites.php, fw.nginx.services.php) toggle the
// server cache with ?cgicache=0|1&serviceid=N.
if(isset($_GET["cgicache"])){nopt_toggle_legacy_cgicache();exit;}
if(isset($_GET["nopt-cache-js"])){nopt_cache_js();exit;}
if(isset($_GET["nopt-cache-popup"])){nopt_cache_popup();exit;}
if(isset($_GET["nopt-gzip-js"])){nopt_gzip_js();exit;}
if(isset($_GET["nopt-gzip-popup"])){nopt_gzip_popup();exit;}
if(isset($_GET["nopt-ps-js"])){nopt_ps_js();exit;}
if(isset($_GET["nopt-ps-popup"])){nopt_ps_popup();exit;}
if(isset($_GET["www-browser-caching-js"])){www_browser_caching_js();exit;}
if(isset($_GET["www-browser-caching-popup"])){www_browser_caching_popup();exit;}
if(isset($_GET["nopt-buf-js"])){nopt_buf_js();exit;}
if(isset($_GET["nopt-buf-popup"])){nopt_buf_popup();exit;}
if(isset($_POST["serviceid"])){Save();exit;}

www_js();

// --- shell: modal + tabs -----------------------------------------------------------------------
function www_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["serviceid"]);
    $servicename=get_servicename($ID);
    return $tpl->js_dialog4("#$ID - $servicename - {optimization}", "$page?www-tabs=$ID",1200);
}
function www_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-tabs"]);
    $array["{parameters}"]="$page?www-parameters=$ID";
    $array["{nopt_tab_exclusions}"]="fw.nginx.cache.excludes.php?popup-table=$ID";
    $array["{nopt_tab_buffers}"]="$page?www-proxy-buffering=$ID";
    echo $tpl->tabs_default($array);
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
function nopt_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemWebMaster;
}
// Reloads every view that shows an optimization state, whichever page is open.
function nopt_refresh_js($ID):string{
    $ID=intval($ID);
    $page=CurrentPageName();
    $f=array();
    $f[]="if(document.getElementById('nopt-host-$ID')){LoadAjaxSilent('nopt-host-$ID','$page?nopt-cards=$ID');}";
    $f[]="if(document.getElementById('nopt-buf-host-$ID')){LoadAjaxSilent('nopt-buf-host-$ID','$page?nopt-buffers=$ID');}";
    $f[]="if(document.getElementById('www-parameters-$ID')){LoadAjax('www-parameters-$ID','fw.nginx.sites.php?www-parameters2=$ID');}";
    $f[]="if(document.getElementById('rcolor7-$ID')){Loadjs('fw.nginx.sites.php?td-row=$ID');}";
    return @implode("\n",$f)."\n";
}

// --- model: every setting read ONCE, every widget renders from it ------------------------------
function nopt_model($ID):array{
    $ID=intval($ID);
    $s=new socksngix($ID);
    $g=new socksngix(0);
    $m=array("id"=>$ID,"type"=>$s->GetType(),"can"=>nopt_can_write());

    // Server cache: storage chosen globally (disk = nginxCachesDir 1, Redis otherwise).
    $disk=intval($g->GET_INFO("nginxCachesDir"))==1;
    $c=array("on"=>intval($s->GET_INFO("cgicache"))==1,"storage"=>$disk?"disk":"redis","unavail"=>"");
    if(!$disk){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_SRCACHE"))==0){
            $c["unavail"]="{nopt_cache_no_module}";
        }elseif(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"))==0){
            $c["unavail"]="{nopt_cache_global_off}";
        }
    }
    $c["valid"]=intval($s->GET_INFO("proxy_cache_valid"));
    if($c["valid"]<=0){$c["valid"]=4320;}
    $c["revalidate"]=intval($s->GET_INFO("proxy_cache_revalidate"))==1;
    $c["weebly"]=intval($s->GET_INFO("EnforceWeebly"))==1;
    // Disk usage per site, measured by the reverse-proxy daemon (CacheTools.ParseCachesDisk).
    // null = never measured; a measured map without this site = nothing cached.
    $c["bytes"]=null;
    if($disk){
        $raw=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxSitesCacheSize");
        if(trim($raw)!=""){
            $sizes=@unserialize($raw);
            if(is_array($sizes)){
                $c["bytes"]=isset($sizes[$ID])?intval($sizes[$ID]):0;
            }
        }
    }
    $m["cache"]=$c;

    $m["adv"]=array("on"=>intval($s->GET_INFO("AdvancedCaching"))==1,"params"=>ac_load_params($s));
    $m["large"]=array("on"=>intval($s->GET_INFO("OptimizeForLargeFiles"))==1);

    $gz=array("on"=>intval($s->GET_INFO("gzip"))==1);
    $gz["level"]=intval($s->GET_INFO("gzip_comp_level"));
    if($gz["level"]<1 || $gz["level"]>9){$gz["level"]=6;}
    $gz["minsize"]=intval($s->GET_INFO("gzip_min_length"));
    if($gz["minsize"]<=0){$gz["minsize"]=50;}
    $gz["vary"]=intval($s->GET_INFO("gzip_vary"))==1;
    $rules=array();
    $data=base64_decode($s->GET_INFO("gzip_types"));
    if($data!=""){$u=@unserialize($data);if(is_array($u)){$rules=$u;}}
    $gz["rules"]=count($rules);
    $m["gzip"]=$gz;

    $ps=array("on"=>intval($s->GET_INFO("pagespeed"))==1,"unavail"=>"");
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"))==0){
        $ps["unavail"]="{nopt_ps_not_installed}";
    }elseif(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"))==0){
        $ps["unavail"]="{nopt_ps_global_off}";
    }
    // Stored inverted: PageSpeedDisablePerFormance=1 means "Optimize content".
    $ps["content"]=intval($s->GET_INFO("PageSpeedDisablePerFormance"))==1;
    $ps["css"]=intval($s->GET_INFO("PageSpeedCss"))==1;
    $ps["images"]=intval($s->GET_INFO("PageSpeedImages"))==1;
    $ps["webp"]=intval($s->GET_INFO("PageSpeedWebp"))==1;
    $m["ps"]=$ps;

    $br=array();
    foreach(array("cache_images","cache_htmlext","cache_binaries") as $k){
        $h=intval($s->GET_INFO("{$k}_hours"));
        if($h<=0){$h=168;}
        $br[$k]=array("on"=>intval($s->GET_INFO($k))==1,"hours"=>$h);
    }
    $m["browser"]=$br;

    // Memory buffers, and what the daemon really does with them (ProxyBuffering.Build):
    // Advanced caching replaces the block, types 13/19 force it off, the server cache forces it on.
    $b=array("on"=>intval($s->GET_INFO("proxy_buffering"))==1);
    $b["header"]=intval($s->GET_INFO("proxy_buffer_size"));
    if($b["header"]<=0){$b["header"]=16;}
    $b["body"]=intval($s->GET_INFO("proxy_buffers"));
    if($b["body"]<=0){$b["body"]=512;}
    $b["override"]="";
    if($m["adv"]["on"]){$b["override"]="adv";}
    elseif($m["type"]==13 || $m["type"]==19){$b["override"]="type";}
    elseif($c["on"]){$b["override"]="cache";}
    $m["buf"]=$b;
    return $m;
}

// --- formatting helpers --------------------------------------------------------------------------
function nopt_dur_seconds($sec):string{
    $sec=intval($sec);
    if($sec>0 && $sec%86400==0){$n=intval($sec/86400);return $n." ".($n==1?"{nopt_day}":"{nopt_days}");}
    if($sec>0 && $sec%3600==0){$n=intval($sec/3600);return $n." ".($n==1?"{nopt_hour}":"{nopt_hours}");}
    if($sec>0 && $sec%60==0){$n=intval($sec/60);return $n." ".($n==1?"{nopt_minute}":"{nopt_minutes}");}
    return $sec." ".($sec==1?"{nopt_second}":"{nopt_seconds}");
}
function nopt_yesno($b):string{
    return $b?"{nopt_yes}":"{nopt_no}";
}
function nopt_hours_hash():array{
    $h=array();
    $h[1]="1 {nopt_hour}";
    for($i=2;$i<24;$i++){$h[$i]="$i {nopt_hours}";}
    $h[24]="1 {nopt_day}";
    for($i=2;$i<30;$i++){$h[$i*24]="$i {nopt_days}";}
    return $h;
}

// --- Parameters tab: host + cards ----------------------------------------------------------------
function www_parameters():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["www-parameters"]);
    echo "<div id='nopt-host-$ID' style='margin-top:10px'></div>";
    echo "<script>LoadAjaxSilent('nopt-host-$ID','$page?nopt-cards=$ID');</script>";
    return true;
}
function nopt_cards():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["nopt-cards"]);
    $m=nopt_model($ID);
    $html=array();
    $html[]="<div class='ak nopt'>";
    $html[]="<div class='ak-card ak-card-pad nopt-intro'>{nopt_intro}</div>";
    $html[]=nopt_strip($m);
    $html[]="<div class='nopt-grid'>";
    $html[]=nopt_card_cache($m);
    $html[]=nopt_card_adv($m);
    $html[]=nopt_card_large($m);
    $html[]=nopt_card_gzip($m);
    $html[]=nopt_card_ps($m);
    $html[]=nopt_card_browser($m);
    $html[]="</div></div>";
    echo nopt_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}
// One chip per optimization: on / off / unavailable, always written, never colour only.
// Unavailable wins over On: a site-level switch cannot make a missing or globally disabled module work.
function nopt_strip($m):string{
    $ID=$m["id"];
    $anyBr=false;
    foreach($m["browser"] as $r){if($r["on"]){$anyBr=true;}}
    $items=array(
        array("cache","{nopt_server_cache}","fas fa-database",$m["cache"]["on"],$m["cache"]["unavail"]!=""),
        array("adv","{nopt_adv}","fas fa-hard-drive",$m["adv"]["on"],false),
        array("large","{nopt_large}","fas fa-file-arrow-down",$m["large"]["on"],false),
        array("gzip","{nopt_gzip_short}","fas fa-file-zipper",$m["gzip"]["on"],false),
        array("ps","{nopt_ps_short}","fas fa-gauge-high",$m["ps"]["on"],$m["ps"]["unavail"]!=""),
        array("browser","{nopt_browser}","fas fa-globe",$anyBr,false),
    );
    $h=array();
    $h[]="<div class='ak-card nopt-strip'>";
    foreach($items as $it){
        list($key,$label,$ico,$on,$unav)=$it;
        if($unav){
            $state="<span class='ak-state ak-warn'><i class='fas fa-triangle-exclamation'></i> {nopt_unavailable}</span>";
        }elseif($on){
            $state="<span class='ak-state ak-ok'><span class='ak-dot'></span> {nopt_on}</span>";
        }else{
            $state="<span class='ak-state ak-off'><span class='nopt-hollow'></span> {nopt_off}</span>";
        }
        $h[]="<button type='button' class='nopt-chip' OnClick=\"var e=document.getElementById('nopt-c-$key-$ID');if(e){e.scrollIntoView({behavior:'smooth',block:'start'});}\">";
        $h[]="<i class='$ico nopt-chip-ico'></i><span class='nopt-chip-t'><span class='nopt-chip-l'>$label</span>$state</span></button>";
    }
    $h[]="</div>";
    return @implode("",$h);
}
// Shared card frame. $sw = switch markup, $body = values, $foot = links.
function nopt_card($m,$key,$ico,$title,$explain,$on,$sw,$body,$foot,$pill=""):string{
    $ID=$m["id"];
    $tone=$on?"ak-ok":"ak-off";
    $h=array();
    $h[]="<div class='ak-card nopt-card' id='nopt-c-$key-$ID'>";
    $h[]="<div class='nopt-ch'><span class='ak-ico ak-ico-sq $tone'><i class='$ico'></i></span>";
    $h[]="<div class='nopt-ct'><div class='nopt-title'>$title$pill</div><div class='nopt-expl'>$explain</div></div>";
    $h[]="<div class='nopt-swbox'>$sw</div></div>";
    $h[]="<div class='nopt-body'>$body</div>";
    if($foot!=""){$h[]="<div class='nopt-foot'>$foot</div>";}
    $h[]="</div>";
    return @implode("",$h);
}
// role=switch button. $disabledReason non-empty = locked with its reason as tooltip.
function nopt_switch($m,$key,$on,$label,$disabledReason="",$withState=true):string{
    $page=CurrentPageName();
    $ID=$m["id"];
    $checked=$on?"true":"false";
    $value=$on?0:1;
    $state=$on?"{nopt_on}":"{nopt_off}";
    $stateHtml=$withState?"<span class='nopt-swl'>$state</span>":"";
    if(!$m["can"]){$disabledReason="{nopt_no_priv}";}
    if($disabledReason!=""){
        return "<button type='button' role='switch' class='ak-sw' aria-checked='$checked' aria-label=\"$label\" title=\"$disabledReason\" disabled></button>$stateHtml";
    }
    return "<button type='button' role='switch' class='ak-sw' aria-checked='$checked' aria-label=\"$label\" title='{nopt_applies_now}' OnClick=\"this.disabled=true;Loadjs('$page?nopt-toggle=$key&value=$value&serviceid=$ID');\"></button>$stateHtml";
}
function nopt_rows($rows):string{
    $h="<dl class='nopt-dl'>";
    foreach($rows as $r){
        $h.="<div class='nopt-row'><dt>{$r[0]}</dt><dd>{$r[1]}</dd></div>";
        if(isset($r[2]) && $r[2]!=""){$h.="<div class='nopt-rownote ak-warn'><i class='fas fa-triangle-exclamation'></i> {$r[2]}</div>";}
    }
    return $h."</dl>";
}
function nopt_link($js,$ico,$label):string{
    return "<button type='button' class='ak-link' OnClick=\"$js\"><i class='$ico'></i> $label</button>";
}
function nopt_off_body():string{
    return "<div class='nopt-offline'>{nopt_off_sentence}</div>";
}
function nopt_unavail_body($reason):string{
    return "<div class='ak-banner ak-warn'><i class='fas fa-triangle-exclamation'></i><span class='ak-banner-m'>$reason</span></div>";
}

function nopt_card_cache($m):string{
    $page=CurrentPageName();
    $ID=$m["id"];
    $c=$m["cache"];
    $pill=" <span class='ak-pill ak-off nopt-pill' title='{nopt_storage_help}'>".($c["storage"]=="disk"?"{nopt_storage_disk}":"{nopt_storage_redis}")."</span>";
    // Unavailable: the switch stays usable only to turn an already enabled cache off.
    $reason=($c["unavail"]!="" && !$c["on"])?$c["unavail"]:"";
    $sw=nopt_switch($m,"cgicache",$c["on"],"{nopt_server_cache}",$reason);
    $foot="";
    if($c["unavail"]!=""){
        $body=nopt_unavail_body($c["unavail"]);
    }elseif(!$c["on"]){
        $body=nopt_off_body();
    }else{
        $rows=array();
        $rows[]=array("{nopt_cache_time}",nopt_dur_seconds($c["valid"]*60).($c["valid"]%1440==0||$c["valid"]%60==0?" <span class='ak-muted'>({$c["valid"]} {nopt_minutes_short})</span>":""));
        $rows[]=array("{nopt_revalidate}",nopt_yesno($c["revalidate"]));
        if($c["storage"]=="disk"){
            $rows[]=array("{nopt_weebly}",nopt_yesno($c["weebly"]));
            if($c["bytes"]===null){
                $used="<span class='ak-muted'>{nopt_not_measured}</span>";
            }else{
                $used=FormatBytes($c["bytes"]/1024,true);
            }
            $rows[]=array("{nopt_disk_used}",$used);
        }
        $body=nopt_rows($rows);
        if($m["can"]){$foot.=nopt_link("Loadjs('$page?nopt-cache-js=$ID')","fas fa-sliders","{nopt_settings}");}
    }
    if($c["storage"]=="disk" && $c["bytes"]!==null && $c["bytes"]>0){
        $foot=nopt_link("Loadjs('fw.nginx.cachesobject.php?serviceid=$ID')","fas fa-list","{nopt_cached_objects}").$foot;
    }
    return nopt_card($m,"cache","fas fa-database","{nopt_server_cache}","{nopt_server_cache_explain}",$c["on"],$sw,$body,$foot,$pill);
}
function nopt_card_adv($m):string{
    $ID=$m["id"];
    $a=$m["adv"];
    $p=$a["params"];
    $sw=nopt_switch($m,"AdvancedCaching",$a["on"],"{nopt_adv}");
    if(!$a["on"]){
        $body=nopt_off_body();
    }else{
        $rows=array();
        $rows[]=array("{nopt_adv_lifetime}",nopt_dur_seconds($p["TTLCache"]));
        $rows[]=array("{nopt_adv_maxsize}",$p["MaxSizeGigabytes"]." {nopt_gb}");
        $rows[]=array("{nopt_adv_inactive}",$p["InactiveTime"]." ".($p["InactiveTime"]==1?"{nopt_hour}":"{nopt_hours}"));
        $rows[]=array("{nopt_adv_stale}",nopt_yesno(intval($p["UseStale"])==1));
        $rows[]=array("{nopt_adv_forcettl}",nopt_yesno(intval($p["ForceServerTTL"])==1),intval($p["ForceServerTTL"])==1?"{nopt_adv_forcettl_warn}":"");
        $body=nopt_rows($rows);
    }
    $foot=nopt_link("Loadjs('fw.nginx.sites.advanced-caching.php?serviceid=$ID')","fas fa-arrow-up-right-from-square","{nopt_adv_open}");
    return nopt_card($m,"adv","fas fa-hard-drive","{nopt_adv}","{nopt_adv_explain}",$a["on"],$sw,$body,$foot);
}
function nopt_card_large($m):string{
    $on=$m["large"]["on"];
    $sw=nopt_switch($m,"OptimizeForLargeFiles",$on,"{nopt_large}");
    $body="<div class='nopt-note'><i class='fas fa-circle-info'></i> {nopt_large_help}</div>";
    return nopt_card($m,"large","fas fa-file-arrow-down","{nopt_large}","{nopt_large_explain}",$on,$sw,$body,"");
}
function nopt_card_gzip($m):string{
    $page=CurrentPageName();
    $ID=$m["id"];
    $g=$m["gzip"];
    $sw=nopt_switch($m,"gzip",$g["on"],"{nopt_gzip}");
    $foot="";
    if(!$g["on"]){
        $body=nopt_off_body();
    }else{
        $rules=$g["rules"]." ".($g["rules"]==1?"{nopt_rule}":"{nopt_rules}");
        if($m["can"]){
            $rules=nopt_link("Loadjs('fw.nginx.rules.gzip.php?service-js=$ID')","fas fa-list-check",$rules);
        }
        $rows=array();
        $rows[]=array("{nopt_gzip_level}",$g["level"]." / 9");
        $rows[]=array("{nopt_gzip_minsize}",$g["minsize"]." {nopt_bytes}");
        $rows[]=array("{nopt_gzip_vary}",nopt_yesno($g["vary"]));
        $rows[]=array("{nopt_gzip_types}",$rules);
        $body=nopt_rows($rows)."<div class='nopt-note'>{nopt_gzip_note}</div>";
        if($m["can"]){$foot=nopt_link("Loadjs('$page?nopt-gzip-js=$ID')","fas fa-sliders","{nopt_settings}");}
    }
    return nopt_card($m,"gzip","fas fa-file-zipper","{nopt_gzip}","{nopt_gzip_explain}",$g["on"],$sw,$body,$foot);
}
function nopt_card_ps($m):string{
    $page=CurrentPageName();
    $ID=$m["id"];
    $p=$m["ps"];
    $reason=($p["unavail"]!="" && !$p["on"])?$p["unavail"]:"";
    $sw=nopt_switch($m,"pagespeed",$p["on"],"{nopt_ps}",$reason);
    $foot="";
    if($p["unavail"]!=""){
        $body=nopt_unavail_body($p["unavail"]);
    }elseif(!$p["on"]){
        $body=nopt_off_body();
    }else{
        $rows=array();
        $rows[]=array("{nopt_ps_mode}",$p["content"]?"{nopt_ps_content}":"{nopt_ps_perf}");
        $rows[]=array("{nopt_ps_css}",nopt_yesno($p["css"]));
        // Images and WebP are only generated in "Optimize content" mode (ConfiguratorPageSpeed).
        if($p["content"]){
            $rows[]=array("{nopt_ps_images}",nopt_yesno($p["images"]));
            $rows[]=array("{nopt_ps_webp}",nopt_yesno($p["webp"]));
        }
        $body=nopt_rows($rows);
        if($m["can"]){$foot=nopt_link("Loadjs('$page?nopt-ps-js=$ID')","fas fa-sliders","{nopt_settings}");}
    }
    return nopt_card($m,"ps","fas fa-gauge-high","{nopt_ps}","{nopt_ps_explain}",$p["on"],"$sw",$body,$foot);
}
function nopt_card_browser($m):string{
    $page=CurrentPageName();
    $ID=$m["id"];
    $labels=array("cache_images"=>"{nopt_br_images}","cache_htmlext"=>"{nopt_br_htmlext}","cache_binaries"=>"{nopt_br_binaries}");
    $any=false;
    $h="<div class='nopt-brlist'>";
    foreach($m["browser"] as $k=>$r){
        if($r["on"]){$any=true;}
        $dur=$r["on"]?nopt_dur_seconds($r["hours"]*3600):"<span class='ak-muted'>{nopt_off}</span>";
        $h.="<div class='nopt-brrow'>".nopt_switch($m,$k,$r["on"],$labels[$k],"",false)."<span class='nopt-brl'>{$labels[$k]}</span><span class='nopt-brd'>$dur</span></div>";
    }
    $h.="</div><div class='nopt-note'>{nopt_br_help}</div>";
    $state=$any?"<span class='ak-state ak-ok'><span class='ak-dot'></span> {nopt_on}</span>":"<span class='ak-state ak-off'>{nopt_off}</span>";
    $foot=$m["can"]?nopt_link("Loadjs('$page?www-browser-caching-js=$ID')","fas fa-sliders","{nopt_settings}"):"";
    return nopt_card($m,"browser","fas fa-globe","{nopt_browser}","{nopt_browser_explain}",$any,$state,$h,$foot);
}

// --- Memory buffers tab --------------------------------------------------------------------------
function www_proxy_buffering():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["www-proxy-buffering"]);
    echo "<div id='nopt-buf-host-$ID' style='margin-top:10px'></div>";
    echo "<script>LoadAjaxSilent('nopt-buf-host-$ID','$page?nopt-buffers=$ID');</script>";
    return true;
}
function nopt_buffers():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["nopt-buffers"]);
    $m=nopt_model($ID);
    $b=$m["buf"];
    $notice="";
    $effective=$b["on"];
    if($b["override"]=="adv"){$notice="{nopt_buf_replaced_adv}";}
    if($b["override"]=="type"){$notice="{nopt_buf_forced_type}";$effective=false;}
    if($b["override"]=="cache"){$notice="{nopt_buf_forced_cache}";$effective=true;}
    $sw=nopt_switch($m,"proxy_buffering",$b["on"],"{nopt_buf}");
    $body="";
    if($notice!=""){
        $body.="<div class='ak-banner ak-info'><i class='fas fa-circle-info'></i><span class='ak-banner-m'>$notice</span></div>";
    }
    $foot="";
    if($b["override"]=="adv" || $b["override"]=="type" || !$effective){
        if($notice==""){$body.=nopt_off_body();}
    }else{
        $rows=array();
        $rows[]=array("{nopt_buf_header}",$b["header"]." {nopt_kb}");
        $rows[]=array("{nopt_buf_body}",$b["body"]." {nopt_kb}");
        $body.=nopt_rows($rows);
        if($m["can"]){$foot=nopt_link("Loadjs('$page?nopt-buf-js=$ID')","fas fa-sliders","{nopt_settings}");}
    }
    $html="<div class='ak nopt'><div class='nopt-grid nopt-grid-1'>".
        nopt_card($m,"buf","fas fa-memory","{nopt_buf}","{nopt_buf_explain}",$effective,$sw,$body,$foot).
        "</div></div>";
    echo nopt_style().$tpl->_ENGINE_parse_body($html);
    return true;
}

// --- switches ------------------------------------------------------------------------------------
function nopt_toggle_keys():array{
    return array(
        "cgicache"=>"Server cache","AdvancedCaching"=>"Advanced caching",
        "OptimizeForLargeFiles"=>"Large files","gzip"=>"Gzip compression","pagespeed"=>"PageSpeed",
        "cache_images"=>"Browser cache (images)","cache_htmlext"=>"Browser cache (JavaScript/CSS)",
        "cache_binaries"=>"Browser cache (downloads)","proxy_buffering"=>"Memory buffers");
}
function nopt_toggle_legacy_cgicache():bool{
    $_GET["nopt-toggle"]="cgicache";
    $_GET["value"]=$_GET["cgicache"];
    return nopt_toggle_js();
}
function nopt_toggle_js():bool{
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $key=strval($_GET["nopt-toggle"]);
    $ID=intval($_GET["serviceid"]);
    $keys=nopt_toggle_keys();
    if(!isset($keys[$key]) || $ID==0){
        $tpl->js_error("{nopt_invalid}");
        echo nopt_refresh_js($ID);
        return false;
    }
    if(!nopt_can_write()){
        $tpl->js_error("{nopt_no_priv}");
        echo nopt_refresh_js($ID);
        return false;
    }
    $value=intval($_GET["value"])==1?1:0;
    $sock=new socksngix($ID);
    $sock->SET_INFO($key,$value);
    if($key=="gzip" && $value==1){gzipTypesMimeDef($ID);}
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$ID');\n";
    echo nopt_refresh_js($ID);
    return admin_tracks("{$keys[$key]} enable=$value for reverse-proxy service ".get_servicename($ID));
}

// --- settings modals (js_dialog5, closed by dialogInstance5) -------------------------------------
function nopt_after($ID):string{
    return "dialogInstance5.close();Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$ID');".str_replace("\n","",nopt_refresh_js($ID));
}
function nopt_cache_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["nopt-cache-js"]);
    return $tpl->js_dialog5("#$ID - {nopt_server_cache}","$page?nopt-cache-popup=$ID",620);
}
function nopt_cache_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["nopt-cache-popup"]);
    $m=nopt_model($ID);
    $c=$m["cache"];
    $form[]=$tpl->field_hidden("serviceid",$ID);
    $form[]=$tpl->field_numeric("proxy_cache_valid","{nopt_cache_time_min}",$c["valid"],"{nopt_cache_time_help}");
    $form[]=$tpl->field_checkbox("proxy_cache_revalidate","{nopt_revalidate}",$c["revalidate"]?1:0,false,"{nopt_revalidate_help}");
    if($c["storage"]=="disk"){
        $form[]=$tpl->field_checkbox("EnforceWeebly","{nopt_weebly}",$c["weebly"]?1:0,false,"{nopt_weebly_help}");
    }
    $html[]=$tpl->div_explain("{nopt_server_cache}||{nopt_cache_modal_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",nopt_after($ID),"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nopt_gzip_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["nopt-gzip-js"]);
    return $tpl->js_dialog5("#$ID - {nopt_gzip}","$page?nopt-gzip-popup=$ID",620);
}
function nopt_gzip_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["nopt-gzip-popup"]);
    $m=nopt_model($ID);
    $g=$m["gzip"];
    $levels=array();
    for($i=1;$i<10;$i++){$levels[$i]="{nopt_level} $i";}
    $form[]=$tpl->field_hidden("serviceid",$ID);
    $form[]=$tpl->field_array_hash($levels,"gzip_comp_level","{nopt_gzip_level}",$g["level"],false,"{nopt_gzip_level_help}");
    $form[]=$tpl->field_numeric("gzip_min_length","{nopt_gzip_minsize_b}",$g["minsize"],"{nopt_gzip_minsize_help}");
    $form[]=$tpl->field_checkbox("gzip_vary","{nopt_gzip_vary}",$g["vary"]?1:0,false,"{nopt_gzip_vary_help}");
    $caption=$g["rules"]." ".($g["rules"]==1?"{nopt_rule}":"{nopt_rules}");
    $form[]=$tpl->field_info("gzip_rules","{nopt_gzip_types}",
        array("VALUE"=>null,"BUTTON"=>true,"BUTTON_CAPTION"=>$caption,
            "BUTTON_JS"=>"Loadjs('fw.nginx.rules.gzip.php?service-js=$ID')"),"{nopt_gzip_note}");
    $html[]=$tpl->div_explain("{nopt_gzip}||{nopt_gzip_modal_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",nopt_after($ID),"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nopt_ps_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["nopt-ps-js"]);
    return $tpl->js_dialog5("#$ID - {nopt_ps}","$page?nopt-ps-popup=$ID",620);
}
function nopt_ps_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["nopt-ps-popup"]);
    $m=nopt_model($ID);
    $p=$m["ps"];
    $modes=array("perf"=>"{nopt_ps_perf_reco}","content"=>"{nopt_ps_content}");
    $form[]=$tpl->field_hidden("serviceid",$ID);
    $form[]=$tpl->field_array_hash($modes,"PageSpeedMode","{nopt_ps_mode}",$p["content"]?"content":"perf",false,"{nopt_ps_mode_help}");
    $form[]=$tpl->field_checkbox("PageSpeedCss","{nopt_ps_css}",$p["css"]?1:0,false,"{nopt_ps_css_help}");
    $form[]=$tpl->field_checkbox("PageSpeedImages","{nopt_ps_images}",$p["images"]?1:0,false,"{nopt_ps_images_help}");
    $form[]=$tpl->field_checkbox("PageSpeedWebp","{nopt_ps_webp}",$p["webp"]?1:0,false,"{nopt_ps_webp_help}");
    $html[]=$tpl->div_explain("{nopt_ps}||{nopt_ps_modal_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",nopt_after($ID),"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function www_browser_caching_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-browser-caching-js"]);
    return $tpl->js_dialog5("#$ID - {nopt_browser}","$page?www-browser-caching-popup=$ID",620);
}
function www_browser_caching_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-browser-caching-popup"]);
    $m=nopt_model($ID);
    $hash=nopt_hours_hash();
    $labels=array("cache_images"=>"{nopt_br_images}","cache_htmlext"=>"{nopt_br_htmlext}","cache_binaries"=>"{nopt_br_binaries}");
    $form[]=$tpl->field_hidden("serviceid",$ID);
    foreach($m["browser"] as $k=>$r){
        $hours=$r["hours"];
        if(!isset($hash[$hours])){$hash[$hours]=nopt_dur_seconds($hours*3600);}
        $form[]=$tpl->field_section($labels[$k]);
        $form[]=$tpl->field_checkbox($k,"{nopt_br_enable}",$r["on"]?1:0,false,"{nopt_br_enable_help}");
        $form[]=$tpl->field_array_hash($hash,"{$k}_hours","{nopt_br_keep}",$hours,false,"{nopt_br_help}");
    }
    $html[]=$tpl->div_explain("{nopt_browser}||{nopt_br_modal_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",nopt_after($ID),"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nopt_buf_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["nopt-buf-js"]);
    return $tpl->js_dialog5("#$ID - {nopt_buf}","$page?nopt-buf-popup=$ID",620);
}
function nopt_buf_popup():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["nopt-buf-popup"]);
    $m=nopt_model($ID);
    $b=$m["buf"];
    $form[]=$tpl->field_hidden("serviceid",$ID);
    $form[]=$tpl->field_numeric("proxy_buffer_size","{nopt_buf_header_kb}",$b["header"],"{nopt_buf_header_help}");
    $form[]=$tpl->field_numeric("proxy_buffers","{nopt_buf_body_kb}",$b["body"],"{nopt_buf_body_help}");
    $html[]=$tpl->div_explain("{nopt_buf}||{nopt_buf_modal_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",nopt_after($ID),"AsSystemWebMaster");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// --- save: whitelisted keys only, right checked server-side, echo nothing on success -----------
function Save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $ID=intval($_POST["serviceid"]);
    if($ID==0){echo $tpl->post_error("{nopt_invalid}");return false;}
    if(!nopt_can_write()){echo $tpl->post_error("{nopt_no_priv}");return false;}

    // key => array(min, max); max 0 = no upper bound. Booleans are 0..1.
    $allowed=array(
        "proxy_cache_valid"=>array(1,0),"proxy_cache_revalidate"=>array(0,1),"EnforceWeebly"=>array(0,1),
        "gzip_vary"=>array(0,1),"gzip_comp_level"=>array(1,9),"gzip_min_length"=>array(1,0),
        "PageSpeedCss"=>array(0,1),"PageSpeedImages"=>array(0,1),"PageSpeedWebp"=>array(0,1),
        "cache_images"=>array(0,1),"cache_htmlext"=>array(0,1),"cache_binaries"=>array(0,1),
        "cache_images_hours"=>array(1,8760),"cache_htmlext_hours"=>array(1,8760),"cache_binaries_hours"=>array(1,8760),
        "proxy_buffer_size"=>array(1,1048576),"proxy_buffers"=>array(1,1048576),
    );
    $vals=array();
    if(isset($_POST["PageSpeedMode"])){
        $vals["PageSpeedDisablePerFormance"]=($_POST["PageSpeedMode"]=="content")?1:0;
    }
    foreach($allowed as $key=>$range){
        if(!isset($_POST[$key])){continue;}
        $raw=trim(strval($_POST[$key]));
        if($raw===""){continue;}
        if(!preg_match('/^[0-9]+$/',$raw)){echo $tpl->post_error("{nopt_invalid}: $key");return false;}
        $v=intval($raw);
        if($v<$range[0] || ($range[1]>0 && $v>$range[1])){echo $tpl->post_error("{nopt_invalid}: $key");return false;}
        $vals[$key]=$v;
    }
    if(count($vals)==0){return true;}
    $sock=new socksngix($ID);
    $trck=array();
    foreach($vals as $key=>$val){
        $sock->SET_INFO($key,$val);
        $trck[]="$key:$val";
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return admin_tracks("Update Optimization settings for reverse-proxy ".get_servicename($ID)." ( ".@implode(", ",$trck).")");
}

// Seeds the compressed MIME types the first time Gzip is enabled on a site.
function gzipTypesMimeDef($serviceid):bool{
    $sock       = new socksngix($serviceid);
    $data       = @unserialize(base64_decode($sock->GET_INFO("gzip_types")));
    if(!$data OR !is_array($data)){
        $data=array();
    }
    if(count($data)>1){
        return true;
    }
    $f=array("text/plain","text/css","application/json","application/x-javascript","text/xml",
        "application/xml","application/xml+rss","text/javascript","application/x-font-ttf",
        "application/javascript","font/eot","font/opentype","image/svg+xml","image/x-icon",
        "application/xhtml+xml","application/x-font-opentype","application/vnd.ms-fontobject",
        "application/rss+xml","application/atom_xml");
    $seen=array();
    foreach ($f as $item) {
        if(isset($seen[$item])){continue;}
        $seen[$item]=true;
        $data[]=array("mime"=>$item,"enable"=>1);
    }
    $sock->SET_INFO("gzip_types",base64_encode(serialize($data)));
    return true;
}

// --- page stylesheet: only what the ak- kit does not provide --------------------------------------
function nopt_style():string{
    if(isset($GLOBALS["NOPT_STYLE"])){return "";}
    $GLOBALS["NOPT_STYLE"]=true;
    $c=array();
    $c[]=".nopt{display:flex;flex-direction:column;gap:16px;padding-bottom:8px}";
    $c[]=".nopt-intro{font-size:13px;line-height:1.6;color:#3d4947}";
    $c[]=".nopt-strip{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:8px;padding:12px 16px}";
    $c[]=".nopt-chip{display:flex;align-items:center;gap:10px;background:none;border:0;padding:6px 8px;border-radius:8px;text-align:left;cursor:pointer;min-width:0}";
    $c[]=".nopt-chip:hover{background:#f8fafc}";
    $c[]=".nopt-chip:focus-visible{outline:2px solid #2b6cb0;outline-offset:2px}";
    $c[]=".nopt-chip-ico{font-size:16px;color:#3d4947;flex:0 0 auto}";
    $c[]=".nopt-chip-t{display:flex;flex-direction:column;gap:2px;min-width:0}";
    $c[]=".nopt-chip-l{font-size:13px;color:#3d4947;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".nopt .ak-state{font-size:13px;font-weight:600}";
    $c[]=".nopt-hollow{display:inline-block;width:8px;height:8px;border-radius:50%;border:1.5px solid currentColor;box-sizing:border-box}";
    $c[]=".nopt-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;align-items:start}";
    $c[]=".nopt-grid-1{grid-template-columns:minmax(0,760px)}";
    $c[]="@media (max-width:1200px){.nopt-grid{grid-template-columns:minmax(0,1fr)}.nopt-strip{grid-template-columns:repeat(3,minmax(0,1fr))}}";
    $c[]=".nopt-card{padding:20px;display:flex;flex-direction:column;gap:14px;scroll-margin-top:12px}";
    $c[]=".nopt-ch{display:flex;align-items:flex-start;gap:14px}";
    $c[]=".nopt-ct{flex:1 1 auto;min-width:0}";
    $c[]=".nopt-title{font-size:15px;font-weight:600;color:#111d23;display:flex;align-items:center;gap:8px;flex-wrap:wrap}";
    $c[]=".nopt-pill{font-size:13px;font-weight:500}";
    $c[]=".nopt-expl{font-size:13px;line-height:1.55;color:#3d4947;margin-top:4px}";
    $c[]=".nopt-swbox{display:flex;align-items:center;gap:8px;flex:0 0 auto;padding-top:2px}";
    $c[]=".nopt-swl{font-size:13px;font-weight:600;color:#3d4947;min-width:24px}";
    $c[]=".nopt-body{border-top:1px solid #f1f5f9;padding-top:12px}";
    $c[]=".nopt-dl{margin:0}";
    $c[]=".nopt-row{display:flex;justify-content:space-between;gap:16px;padding:6px 0;font-size:13px}";
    $c[]=".nopt-row dt{font-weight:400;color:#3d4947}";
    $c[]=".nopt-row dd{margin:0;font-weight:500;color:#111d23;text-align:right}";
    $c[]=".nopt-row dd .ak-link{font-weight:500}";
    $c[]=".nopt-rownote{font-size:13px;color:var(--ak-tfg);background:var(--ak-tbg);border-radius:6px;padding:6px 10px;margin:2px 0 6px}";
    $c[]=".nopt-offline{font-size:13px;font-style:italic;color:#6d7a77}";
    $c[]=".nopt-note{font-size:13px;color:#6d7a77;margin-top:8px;line-height:1.5}";
    $c[]=".nopt-foot{display:flex;justify-content:flex-end;gap:16px;border-top:1px solid #f1f5f9;padding-top:12px}";
    $c[]=".nopt-brlist{display:flex;flex-direction:column;gap:10px}";
    $c[]=".nopt-brrow{display:flex;align-items:center;gap:12px;font-size:13px}";
    $c[]=".nopt-brl{flex:1 1 auto;color:#111d23}";
    $c[]=".nopt-brd{font-weight:500;color:#111d23}";
    $c[]="@media (prefers-reduced-motion:reduce){.nopt-card{scroll-behavior:auto}}";
    return "<style>".@implode("\n",$c)."</style>";
}
