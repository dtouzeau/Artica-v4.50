<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["www-tabs"])){www_tabs();exit;}
if(isset($_GET["www-parameters"])){www_parameters();exit;}
if(isset($_GET["www-parameters2"])){www_parameters2();exit;}
if(isset($_GET["www-parameters3"])){www_parameters3();exit;}
if(isset($_GET["www-parameters3-flat"])){www_parameters3_flat();exit;}
if(isset($_GET["www-parameters3-js"])){www_parameters3_js();exit;}
if(isset($_GET["www-browser-caching-js"])){www_browser_caching_js();exit;}
if(isset($_GET["www-browser-caching-popup"])){www_browser_caching_popup();exit;}
if(isset($_GET["optimize-for-large-files-js"])){www_optimize_for_large_files_js();exit;}
if(isset($_POST["serviceid"])){Save();exit;}
if(isset($_GET["www-whitelists"])){whitelists_start();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["down-backup"])){download_backup();exit;}
if(isset($_GET["widgets"])){widgets();exit;}
if(isset($_GET["pagespeed"])){EnablePageSpeed();exit;}
if(isset($_GET["cgicache"])){EnableCgiCache();exit;}
if(isset($_GET["proxy_buffering"])){EnableProxyBuffering();exit;}


if(isset($_GET["gzip"])){EnableGzip();exit;}
if(isset($_GET["www-proxy-buffering"])){www_proxy_buffering();exit;}
if(isset($_GET["www-proxy-buffering2"])){www_proxy_buffering2();exit;}
if(isset($_GET["www-proxy-buffering-widgets"])){www_proxy_buffering_widgets();exit;}



www_js();

function EnablePageSpeed():bool{
    $EnableModSecurity=intval($_GET["pagespeed"]);
    $serviceid=$_GET["serviceid"];
    $servicename=get_servicename($serviceid);
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("pagespeed",$EnableModSecurity);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('optimize-nginx-$serviceid','$page?www-parameters2=$serviceid');\n";
    echo "LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');\n";
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');\n";
    echo "Loadjs('fw.nginx.sites.php?td-row=$serviceid');\n";

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");

    return admin_tracks("PageSpeed plugin enable=$EnableModSecurity for reverse-proxy service $servicename");
}
function EnableCgiCache():bool{
    $EnableValue=intval($_GET["cgicache"]);
    $serviceid=$_GET["serviceid"];
    $servicename=get_servicename($serviceid);
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("cgicache",$EnableValue);

    VERBOSE("EnableCgiCache SET TO $EnableValue",__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");

    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $f[]="Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');";
    $f[] = "if ( document.getElementById('optimize-nginx-$serviceid') ){";
    $f[]= "\tLoadAjax('optimize-nginx-$serviceid','$page?www-parameters2=$serviceid');";
    $f[]= "}";
    $f[] = "if ( document.getElementById('www-parameters-$serviceid') ){";
    $f[]= "\tLoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');\n";
    $f[]= "}";
    $f[] = "if ( document.getElementById('rcolor7-$serviceid') ){";
    $f[] = "\tLoadjs('fw.nginx.sites.php?td-row=$serviceid');";
    $f[]= "}";



    echo @implode("\n",$f);
    return admin_tracks("Cache With Redis plugin enable=$EnableValue for reverse-proxy service $servicename");
}
function EnableGzip():bool{
    $EnableValue=intval($_GET["gzip"]);
    $serviceid=$_GET["serviceid"];
    $servicename=get_servicename($serviceid);
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("gzip",$EnableValue);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('optimize-nginx-$serviceid','$page?www-parameters2=$serviceid');\n";
    echo "LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');\n";
    echo "Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$serviceid');\n";
    return admin_tracks("Cache With Gzip compression enable=$EnableValue for reverse-proxy service $servicename");
}


function www_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=$_GET["www-tabs"];
    $array["{parameters}"]="$page?www-parameters=$ID";
    $array["{exclude_cache}"]="fw.nginx.cache.excludes.php?popup-table=$ID";
    $array["{memory_cache}"]="$page?www-proxy-buffering=$ID";
    echo $tpl->tabs_default($array);
}
function www_js():bool{
$page=CurrentPageName();
$tpl=new template_admin();$tpl->CLUSTER_CLI=true;
$ID=$_GET["serviceid"];
$servicename=get_servicename($ID);
return $tpl->js_dialog4("#$ID - $servicename - {optimization}", "$page?www-tabs=$ID",1200);
}
function www_parameters3_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-parameters3-js"]);
    $servicename=get_servicename($ID);
    return $tpl->js_dialog5("#$ID - $servicename - {caching}", "$page?www-parameters3=$ID");
}
function www_browser_caching_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-browser-caching-js"]);
    $servicename=get_servicename($ID);
    return $tpl->js_dialog5("#$ID - $servicename - {browser_caching}", "$page?www-browser-caching-popup=$ID");
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return $ligne["servicename"];
}
function www_parameters():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["www-parameters"]);
    echo "<div id='optimize-nginx-$ID' style='margin-top:10px'></div>";
    echo "<script>LoadAjax('optimize-nginx-$ID','$page?www-parameters2=$ID');</script>";
    return true;
}
function www_parameters_advanced_caching($tpl,$socknginx){

    $AdvancedCaching=intval($socknginx->GET_INFO("AdvancedCaching"));
    if($AdvancedCaching==0){
        $tpl->table_form_field_js("Loadjs('fw.nginx.sites.advanced-caching.php?serviceid=$socknginx->serviceid')");
        $tpl->table_form_field_bool("{advanced_caching}",0,ico_hd);
        return $tpl;
    }
    return  $tpl;

}

function www_parameters_redis_flat($tpl,$socknginx){
    $proxy_cache_revalidate=intval($socknginx->GET_INFO("proxy_cache_revalidate"));
    $proxy_cache_valid = intval($socknginx->GET_INFO("proxy_cache_valid"));
    if ($proxy_cache_valid == 0) {
        $proxy_cache_valid = 4320;
    }
    $f[]="$proxy_cache_valid {minutes}";
    if($proxy_cache_revalidate==1) {
        $f[] = "{proxy_cache_revalidate}";
    }
    $tpl->table_form_field_text("{caching_using_redis}","<small>".@implode(",",$f),ico_mem);
   return $tpl;

}
function www_browser_caching_popup():bool{

    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-browser-caching-popup"]);
    $socknginx=new socksngix($ID);


    for($i=1;$i<24;$i++){$fieldH[$i]="$i {hours}";}
    for($i=2;$i<30;$i++){$hours=$i*24;$fieldH[$hours]="$i {days}";}

    $form[] = $tpl->field_hidden("serviceid",$ID);
    $form[] = $tpl->field_checkbox("cache_images", "{cache_images}",
        intval($socknginx->GET_INFO("cache_images")), false, "");

    $Hours=intval($socknginx->GET_INFO("cache_images_hours"));
    if($Hours==0){$Hours=168;}
    $form[] = $tpl->field_array_hash($fieldH, "cache_images_hours","{max_time}",
        $Hours, false, "");


    $form[] = $tpl->field_checkbox("cache_htmlext", "{cache_htmlext}",
        intval($socknginx->GET_INFO("cache_htmlext")), false, "");

    $Hours=intval($socknginx->GET_INFO("cache_htmlext_hours"));
    if($Hours==0){$Hours=168;}
    $form[] = $tpl->field_array_hash($fieldH, "cache_htmlext_hours","{max_time}",
        $Hours, false, "");


    $form[] = $tpl->field_checkbox("cache_binaries", "{cache_binaries}",
        intval($socknginx->GET_INFO("cache_binaries")), false, "");

    $Hours=intval($socknginx->GET_INFO("cache_binaries_hours"));
    if($Hours==0){$Hours=168;}
    $form[] = $tpl->field_array_hash($fieldH, "cache_binaries_hours","{max_time}",
        $Hours, false, "");


    $page=currentPageName();
    $service_reconfigure="dialogInstance5.close();LoadAjax('optimize-nginx-$ID','$page?www-parameters2=$ID');Loadjs('fw.nginx.sites.php?td-row=$ID');Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$ID');";


    echo $tpl->form_outside(null, $form,"{browser_caching_explain}","{apply}",$service_reconfigure,"AsSystemWebMaster");
    return true;
}
function www_parameters_browser_caching_flat($tpl,$socknginx){
    $f=array();
    if ($socknginx->GET_INFO("cache_images") == 1) {

        $Hours=intval($socknginx->GET_INFO("cache_images_hours"));
        if($Hours==0){
            $Hours=168;
        }

        $f[] = "{cache_images} {during} $Hours {hours}";
    }
    if ($socknginx->GET_INFO("cache_htmlext") == 1) {
        $Hours=intval($socknginx->GET_INFO("cache_htmlext_hours"));
        if($Hours==0){
            $Hours=168;
        }

        $f[] = "{cache_htmlext} {during} $Hours {hours}";
    }
    if ($socknginx->GET_INFO("cache_binaries") == 1) {

        $Hours=intval($socknginx->GET_INFO("cache_binaries_hours"));
        if($Hours==0){
            $Hours=168;
        }

        $f[] = "{cache_binaries} {during} $Hours {hours}";
    }

    if(count($f)>0) {
        $tpl->table_form_field_text("{browser_caching}","<small>".@implode(",",$f),ico_ie);
        return $tpl;
    }
    $tpl->table_form_field_bool("{browser_caching}",0,ico_ie);
    return $tpl;

}
function www_parameters_redis($tpl,$socknginx):array{
    $tpl=www_parameters_redis($tpl,$socknginx);
    $proxy_cache_valid = intval($socknginx->GET_INFO("proxy_cache_valid"));
    if ($proxy_cache_valid == 0) {
        $proxy_cache_valid = 4320;
    }
    $proxy_cache_revalidate=intval($socknginx->GET_INFO("proxy_cache_revalidate"));

    $form[] = $tpl->field_section("{caching_using_redis}");
    $form[] = $tpl->field_numeric("proxy_cache_valid", "{proxy_cache_valid} ({minutes})",
        $proxy_cache_valid, "{proxy_cache_valid_text}");

    $form[] = $tpl->field_checkbox("proxy_cache_revalidate", "{proxy_cache_revalidate}",
        $proxy_cache_revalidate, "{proxy_cache_valid_text}");



    return array(@implode("\n",$form),$tpl);
}

function www_parameters_cachdir_flat($tpl,$socknginx){
    $proxy_cache_valid = intval($socknginx->GET_INFO("proxy_cache_valid"));
    $proxy_cache_revalidate = intval($socknginx->GET_INFO("proxy_cache_revalidate"));
    $EnforceWeebly = intval($socknginx->GET_INFO("EnforceWeebly"));
    if ($proxy_cache_valid == 0) {
        $proxy_cache_valid = 4320;
    }
    $f[]="{proxy_cache_valid} $proxy_cache_valid {minutes}";
    if($proxy_cache_revalidate==1){
        $f[] = "{proxy_cache_revalidate}";
    }

    if($EnforceWeebly==1){
        $f[]="{support} : Weebly";
    }

    $tpl->table_form_field_text("{caching}","<small>".@implode(",",$f),ico_hd);
    return $tpl;

}
function www_parameters_cachdir($tpl,$socknginx):array{
    $proxy_cache_valid = intval($socknginx->GET_INFO("proxy_cache_valid"));
    $proxy_cache_revalidate = intval($socknginx->GET_INFO("proxy_cache_revalidate"));
    $EnforceWeebly = intval($socknginx->GET_INFO("EnforceWeebly"));
    if ($proxy_cache_valid == 0) {
        $proxy_cache_valid = 4320;
    }
    $form[] = $tpl->field_numeric("proxy_cache_valid", "{proxy_cache_valid} ({minutes})",
        $proxy_cache_valid, "{proxy_cache_valid_text}");

    $form[] = $tpl->field_checkbox("proxy_cache_revalidate", "{proxy_cache_revalidate}",
        $proxy_cache_revalidate, "{proxy_cache_valid_text}");

    $form[] = $tpl->field_checkbox("EnforceWeebly", "{support} : Weebly", $EnforceWeebly);
    return array(@implode("\n",$form),$tpl);

}
function www_parameters2():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-parameters2"]);


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:335px;vertical-align:top;padding-top:9px'><div id='widget-$ID'></div></td>";
    $html[]="<td style='padding-left:15px;vertical-align:top;'>";
    $html[]="<div id='optimize-compile-$ID'></div>";
    $html[]="<div id='form-optimize-$ID'></div>";
    $html[]="</td>";

    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('widget-$ID','$page?widgets=$ID');";
    $html[]="LoadAjaxSilent('form-optimize-$ID','$page?www-parameters3-flat=$ID');";
    $html[]="</script>";
echo $tpl->_ENGINE_parse_body($html);
return true;
}

function www_optimize_for_large_files_js():bool{
    $serviceid=intval($_GET["optimize-for-large-files-js"]);
    $servicename=get_servicename($serviceid);
    $socknginx=new socksngix($serviceid);

    $OptimizeForLargeFiles=intval($socknginx->GET_INFO("OptimizeForLargeFiles"));
    if($OptimizeForLargeFiles==1){
        $EnableValue=0;
    }else{
        $EnableValue=1;
    }
    $socknginx->SET_INFO("OptimizeForLargeFiles",$EnableValue);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('form-optimize-$serviceid','$page?www-parameters3-flat=$serviceid');\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    echo "Loadjs('fw.nginx.sites.php?td-row=$serviceid');\n";
    return admin_tracks("Optimization for large files enable=$EnableValue for reverse-proxy service $servicename");
}

function www_parameters3_flat():bool{
    $ID=intval($_GET["www-parameters3-flat"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $socknginx=new socksngix($ID);
    $cgicache=intval($socknginx->GET_INFO("cgicache"));
    $gzip               = intval($socknginx->GET_INFO("gzip"));
    $pagespeed=intval($socknginx->GET_INFO("pagespeed"));
    $OptimizeForLargeFiles=intval($socknginx->GET_INFO("OptimizeForLargeFiles"));
    $tpl->table_form_field_js("Loadjs('$page?www-parameters3-js=$ID')");

    if($cgicache==1){
        $nginxsockGen=new socksngix(0);
        $nginxCachesDir=intval($nginxsockGen->GET_INFO("nginxCachesDir"));
        if($nginxCachesDir==0) {
            $tpl=www_parameters_redis_flat($tpl,$socknginx);

        }else{
            $tpl=www_parameters_cachdir_flat($tpl,$socknginx);
        }
    }else{
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{caching}",0,ico_hd);
    }
    $tpl=www_parameters_advanced_caching($tpl,$socknginx);
    $tpl->table_form_field_js("Loadjs('$page?optimize-for-large-files-js=$ID')");
    $tpl->table_form_field_bool("{optimize_for_large_files}",$OptimizeForLargeFiles,ico_hd);

    if ($gzip==1) {
        $tpl->table_form_field_js("Loadjs('$page?www-parameters3-js=$ID')");
        $gzip_types_rules = array();
        $gzip_vary          = intval($socknginx->GET_INFO("gzip_vary"));
        $gzip_comp_level    = intval($socknginx->GET_INFO("gzip_comp_level"));
        $gzip_min_length    = intval($socknginx->GET_INFO("gzip_min_length"));
        $gzip_types_data    = base64_decode($socknginx->GET_INFO("gzip_types"));
        if($gzip_types_data<>null){$gzip_types_rules=unserialize($gzip_types_data);}

        if($gzip_comp_level==0){$gzip_comp_level=6;}
        if($gzip_min_length==0){$gzip_min_length=50;}

        $f[]="{compress_level} $gzip_comp_level";
        $f[]="{minsize} $gzip_min_length bytes";
        if($gzip_vary==1){
            $f[]="Vary: Accept-Encoding";
        }
        $gzip_types_count = count($gzip_types_rules);
        $gzip_types_text = "{rules}";
        if ($gzip_types_count < 2) {
            $gzip_types_text = "{rule}";
        }
        $f[]="$gzip_types_count $gzip_types_text";
        $tpl->table_form_field_text("{gzip_compression}",
            "<small>".@implode(", ", $f)."</small>",
            ico_file_zip);

    }else{
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{gzip_compression}",0,ico_file_zip);
    }
    $f=array();
    if($pagespeed==1){
        $PageSpeedDisablePerFormance=intval($socknginx->GET_INFO("PageSpeedDisablePerFormance"));
        $PageSpeedCss=intval($socknginx->GET_INFO("PageSpeedCss"));

        if($PageSpeedDisablePerFormance==1){
            $PageSpeedPerf=0;
        }else{
            $PageSpeedPerf=1;
        }
        if($PageSpeedPerf==1) {
            $f[] = "{prefer_performance}";
        }
        if($PageSpeedCss==1){
            $f[] = "{optimize} CSS";
        }
        if($PageSpeedDisablePerFormance==1){
            if(intval($socknginx->GET_INFO("PageSpeedImages"))==1){
                $f[] = "{optimize_images}";
            }
            if(intval($socknginx->GET_INFO("PageSpeedWebp"))==1){
                $f[] = "{convert_images_to_webp}";
            }
        }

        $tpl->table_form_field_text("{APP_MOD_PAGESPEED}",
            "<small>".@implode(", ", $f)."</small>",
            ico_performance
            );
    }

    $tpl->table_form_field_js("Loadjs('$page?www-browser-caching-js=$ID')");
    $tpl=www_parameters_browser_caching_flat($tpl,$socknginx);
    echo $tpl->table_form_compile();
    return true;
}

function www_parameters3(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-parameters3"]);
    $socknginx=new socksngix($ID);
    $cgicache=intval($socknginx->GET_INFO("cgicache"));
    $gzip               = intval($socknginx->GET_INFO("gzip"));
    $pagespeed=intval($socknginx->GET_INFO("pagespeed"));
    $form[]=$tpl->field_hidden("serviceid",$ID);
    $C=0;
    $explain=null;
    if($cgicache==1){
        $C++;
        $nginxsockGen=new socksngix(0);
        $nginxCachesDir=intval($nginxsockGen->GET_INFO("nginxCachesDir"));
        VERBOSE("nginxCachesDir = $nginxCachesDir",__LINE__);

        if($nginxCachesDir==0) {
            list($ff,$tpl)=www_parameters_redis($tpl,$socknginx);
            $form[]=$ff;
        }else{
            list($ff,$tpl)=www_parameters_cachdir($tpl,$socknginx);
            $explain=$tpl->div_explain("{sites_caching}||{nginx_caching_explain}");
            $form[]=$ff;
        }
    }
    if ($gzip==1) {
        $C++;
        $gzip_types_rules = array();
        $gzip_vary          = intval($socknginx->GET_INFO("gzip_vary"));
        $gzip_comp_level    = intval($socknginx->GET_INFO("gzip_comp_level"));
        $gzip_min_length    = intval($socknginx->GET_INFO("gzip_min_length"));
        $gzip_types_data    = base64_decode($socknginx->GET_INFO("gzip_types"));
        if($gzip_types_data<>null){$gzip_types_rules=unserialize($gzip_types_data);}
        $gzip_comp_level_array=array();
        if($gzip_comp_level==0){$gzip_comp_level=6;}
        if($gzip_min_length==0){$gzip_min_length=50;}
        for($i=1;$i<10;$i++){
            $gzip_comp_level_array[$i]="{level} $i";
        }

        $form[] = $tpl->field_section("{gzip_compression}");
        $form[] = $tpl->field_checkbox("gzip_vary", "Vary: Accept-Encoding", $gzip_vary);
        $form[] = $tpl->field_array_hash($gzip_comp_level_array, "gzip_comp_level", "{compress_level}", $gzip_comp_level);
        $form[] = $tpl->field_numeric("gzip_min_length", "{minsize} (bytes)", $gzip_min_length);

        $gzip_types_count = count($gzip_types_rules);
        $gzip_types_text = "{rules}";
        if ($gzip_types_count < 2) {
            $gzip_types_text = "{rule}";
        }

        $form[] = $tpl->field_info("gzip_rules", "{gzip_rules}",
            array("VALUE" => null,
                "BUTTON" => true,
                "BUTTON_CAPTION" => "$gzip_types_count $gzip_types_text",
                "BUTTON_JS" => "Loadjs('fw.nginx.rules.gzip.php?service-js=$ID')"
            ), "{gzip_rules_fdb_explain}");

    }
    if($pagespeed==1){
        $C++;

        $PageSpeedDisablePerFormance=intval($socknginx->GET_INFO("PageSpeedDisablePerFormance"));
        $PageSpeedCss=intval($socknginx->GET_INFO("PageSpeedCss"));

        if($PageSpeedDisablePerFormance==1){
            $PageSpeedPerf=0;
        }else{
            $PageSpeedPerf=1;
        }

        $form[] = $tpl->field_section("{APP_MOD_PAGESPEED}");
        $form[] =  $tpl->field_checkbox("PageSpeedDisablePerFormance","{prefer_performance}",$PageSpeedPerf,false,"{pagespeed_prefer_performance}");
        $form[] =  $tpl->field_checkbox("PageSpeedCss","{optimize} CSS",$PageSpeedCss);


        if($PageSpeedDisablePerFormance==1) {
            $form[] = $tpl->field_checkbox("PageSpeedImages", "{optimize_images}", intval($socknginx->GET_INFO("PageSpeedImages")));
            $form[] = $tpl->field_checkbox("PageSpeedWebp", "{convert_images_to_webp}", intval($socknginx->GET_INFO("PageSpeedWebp")));
        }

    }

    $service_reconfigure="LoadAjax('optimize-nginx-$ID','$page?www-parameters2=$ID');Loadjs('fw.nginx.sites.php?td-row=$ID');Loadjs('fw.nginx.hup.php?hup=yes&serviceid=$ID');";

    if(isHarmpID()){
        $service_reconfigure="";
    }
    $t=time();
    $form_final="<div class=center><img src='img/2778963.png?t=$t' alt=''></div>";

    if($C>0){
        $form_final=$tpl->form_outside(null, $form,"","{apply}",$service_reconfigure,"AsSystemWebMaster");
    }

    echo $tpl->_ENGINE_parse_body("$explain$form_final");

}


function widgets():bool{
    $ID=intval($_GET["widgets"]);
    echo widget_pagespeed($ID);
    echo widget_Cache($ID);
    echo widget_Gzip($ID);

return true;
}
function www_proxy_buffering_widgets():bool{
    $ID=intval($_GET["www-proxy-buffering-widgets"]);
    echo widget_proxy_buffering($ID);
    return true;
}
function nginx_pagespeed_enabled():int{
    if(isHarmpID()) {return 1;}
    $nginx_pagespeed_installed = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"));
    if($nginx_pagespeed_installed==0){return 0;}
    return  intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"));
}

function  widget_Cache_disk($ID):string{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $sock=new socksngix($ID);
    $title="{sites_caching}";
    $cgicache=intval($sock->GET_INFO("cgicache"));
    if($cgicache==0){
        $btn[0]["js"] = "Loadjs('$page?cgicache=1&serviceid=$ID');";
        $btn[0]["name"] = "{activate}";
        $btn[0]["icon"] = "far fa-shield-check";
        return $tpl->widget_grey($title,"{inactive}",$btn,ico_disabled);
    }
    $btn[0]["js"] = "Loadjs('$page?cgicache=0&serviceid=$ID');";
    $btn[0]["name"] = "{disable}";
    $btn[0]["icon"] = ico_database;
    return $tpl->widget_vert($title,"{active2}",$btn,ico_database);
}
function widget_Cache($ID):string{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $nginxsock=new socksngix(0);
    $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));
    VERBOSE("nginxCachesDir = $nginxCachesDir",__LINE__);

    if($nginxCachesDir==1){
        return widget_Cache_disk($ID);
    }

    if(!isHarmpID()){
        $nginxsock=new socksngix(0);
        $nginxCachesDir=intval($nginxsock->GET_INFO("nginxCachesDir"));
        $NginxCacheRedis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"));

        if($nginxCachesDir==1){
            $NginxCacheRedis=1;
        }

        $APP_NGINX_SRCACHE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_SRCACHE"));
        if($APP_NGINX_SRCACHE==0){
            return $tpl->widget_grey("{caching_using_redis}","{missing_module}",ico_disabled);
        }
        if($NginxCacheRedis==0){
            return $tpl->widget_grey("{caching_using_redis}","{feature_disabled} ({global})",ico_disabled);
        }
    }

    $title="{caching_using_redis}";
    if($nginxCachesDir==1){
        $title="{sites_caching}";
    }


    $sock=new socksngix($ID);
    $cgicache=intval($sock->GET_INFO("cgicache"));
    if($cgicache==0){
        $btn[0]["js"] = "Loadjs('$page?cgicache=1&serviceid=$ID');";
        $btn[0]["name"] = "{activate}";
        $btn[0]["icon"] = "far fa-shield-check";
        return $tpl->widget_grey($title,"{inactive}",$btn,ico_disabled);
    }
    $btn[0]["js"] = "Loadjs('$page?cgicache=0&serviceid=$ID');";
    $btn[0]["name"] = "{disable}";
    $btn[0]["icon"] = ico_database;
    return $tpl->widget_vert($title,"{active2}",$btn,ico_database);


}
function widget_Gzip($ID):string{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $sock=new socksngix($ID);
    $gzip               = intval($sock->GET_INFO("gzip"));

    if($gzip==1){
        $btn[0]["js"] = "Loadjs('$page?gzip=0&serviceid=$ID');";
        $btn[0]["name"] = "{disable}";
        $btn[0]["icon"] = ico_disabled;
        return $tpl->widget_vert("{gzip_compression}","{active2}",$btn,ico_file_zip);

    }
    $btn[0]["js"] = "Loadjs('$page?gzip=1&serviceid=$ID');";
    $btn[0]["name"] = "{activate}";
    $btn[0]["icon"] = ico_file_zip;
    return $tpl->widget_grey("{gzip_compression}","{inactive}",$btn,ico_disabled);

}
function widget_proxy_buffering($ID):string{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $sock=new socksngix($ID);
    $proxy_buffering               = intval($sock->GET_INFO("proxy_buffering"));

    if($proxy_buffering==1){
        $btn[0]["js"] = "Loadjs('$page?proxy_buffering=0&serviceid=$ID');";
        $btn[0]["name"] = "{disable}";
        $btn[0]["icon"] = ico_disabled;
        return $tpl->widget_vert("{memory_cache}","{active2}",$btn,ico_mem);

    }
    $btn[0]["js"] = "Loadjs('$page?proxy_buffering=1&serviceid=$ID');";
    $btn[0]["name"] = "{activate}";
    $btn[0]["icon"] = ico_mem;
    return $tpl->widget_grey("{memory_cache}","{inactive}",$btn,ico_disabled);

}
function EnableProxyBuffering():bool{
    $EnableValue=intval($_GET["proxy_buffering"]);
    $serviceid=$_GET["serviceid"];
    $servicename=get_servicename($serviceid);
    $sock=new socksngix($serviceid);
    $sock->SET_INFO("proxy_buffering",$EnableValue);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('optimize-nginx-$serviceid','$page?www-parameters2=$serviceid');\n";
    echo "LoadAjax('optimize-proxy-buffering-$serviceid','$page?www-proxy-buffering2=$serviceid');\n";
    return admin_tracks("Cache With Proxy Buffering enable=$EnableValue for reverse-proxy service $servicename");
}

function widget_pagespeed($ID):string{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    if(!isHarmpID()){

        $nginx_pagespeed_installed = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"));
        if($nginx_pagespeed_installed==0){
            return $tpl->widget_grey("{enable_mod_pagespeed}","{missing_module}",ico_disabled);
        }
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"))==0){
            return $tpl->widget_grey("{enable_mod_pagespeed}","{feature_disabled} ({global})",ico_disabled);
        }
    }

    $sock=new socksngix($ID);
    $pagespeed=intval($sock->GET_INFO("pagespeed"));
    if($pagespeed==1){
        $btn[0]["js"] = "Loadjs('$page?pagespeed=0&serviceid=$ID');";
        $btn[0]["name"] = "{disable}";
        $btn[0]["icon"] = ico_disabled;
        return $tpl->widget_vert("{enable_mod_pagespeed}","{active2}",$btn,ico_speed);

    }
    $btn[0]["js"] = "Loadjs('$page?pagespeed=1&serviceid=$ID');";
    $btn[0]["name"] = "{activate}";
    $btn[0]["icon"] = ico_speed;
    return $tpl->widget_grey("{enable_mod_pagespeed}","{inactive}",$btn,ico_shield_disabled);
}

function Save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $ID=intval($_POST["serviceid"]);
    if($ID==0){echo "????\n";exit;}


    if(isset($_POST["PageSpeedDisablePerFormance"])){
        if($_POST["PageSpeedDisablePerFormance"]==1){
            $_POST["PageSpeedDisablePerFormance"]=0;
        }else{
            $_POST["PageSpeedDisablePerFormance"]=1;
        }
    }
    $trck=array();
    $servicename=get_servicename($ID);
    unset($_POST["serviceid"]);
    $sock=new socksngix($ID);
    foreach ($_POST as $key=>$val){
        $sock->SET_INFO($key,$val);
        $trck[]="$key:$val";
    }


    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return admin_tracks("Update Optimization settings for reverse-proxy $servicename ( ".@implode(", ",$trck).")");

}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}
function www_proxy_buffering():bool{
    $page = CurrentPageName();
    $ID = intval($_GET["www-proxy-buffering"]);
    echo "<div id='optimize-proxy-buffering-$ID' style='margin-top:10px'></div>";
    echo "<script>LoadAjax('optimize-proxy-buffering-$ID','$page?www-proxy-buffering2=$ID');</script>";
    return true;
}
function www_proxy_buffering2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["www-proxy-buffering2"]);
    $socknginx=new socksngix($ID);
    $t=time();
    $form[]=$tpl->field_hidden("serviceid",$ID);
    $proxy_buffering=intval($socknginx->GET_INFO("proxy_buffering"));
    $service_reconfigure="Loadjs('fw.nginx.apply.php?serviceid=$ID&function=NgixSitesReload&addjs=');";

    if(isHarmpID()){
        $service_reconfigure="";
    }


    if($proxy_buffering==1){
        $proxy_buffer_size=intval($socknginx->GET_INFO("proxy_buffer_size"));
        if($proxy_buffer_size==0){$proxy_buffer_size=16;}
        $proxy_buffers=intval($socknginx->GET_INFO("proxy_buffers"));
        if($proxy_buffer_size==0){$proxy_buffer_size=16;}
        if($proxy_buffers==0){$proxy_buffers=512;}

        $form[]=$tpl->field_numeric("proxy_buffer_size","{proxy_buffer_size} ({headers} (k))",
            $proxy_buffer_size,"{proxy_buffer_size_text}");

        $form[]=$tpl->field_numeric("proxy_buffers","{proxy_buffer_size} ({html_body} (k))",
            $proxy_buffers,"");

        $form_final=$tpl->form_outside(null, $form,"","{apply}",$service_reconfigure,"AsSystemWebMaster");
    }else{

        $form_final="<div class=center>
            <H1>{no_defined_optimization}</H1>
            <img src='img/2778963.png?t=$t'></div>";
    }





    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:335px;vertical-align:top;padding-top:9px'><div id='buffer-$ID'></div></td>";
    $html[]="<td style='padding-left:15px;vertical-align:top;'>";
    $html[]="<div id='www-proxy-buffering-widgets-$ID'></div>";
    $html[]=$form_final;
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('buffer-$ID','$page?www-proxy-buffering-widgets=$ID');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}