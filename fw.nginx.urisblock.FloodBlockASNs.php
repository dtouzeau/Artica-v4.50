<?php
/**
 * FloodBlockASNs Configurator
 * Manage ASN-based flood blocking per reverse-proxy service.
 * Config stored in Valkey key "FloodBlockASNs" as JSON map:
 *   { "serviceID": { "Enable":1, "ASNsToblock":["45899","7552"], "TTL":28800 } }
 * Real-time ASN metrics from reverse-proxy: GET /metrics/realtime/asn/{serviceid}
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}
if(isset($_GET["popup-table"])){popup_table();exit;}
if(isset($_GET["popup-table2"])){popup_table2();exit;}
if(isset($_GET["top-buttons"])){top_buttons();exit;}
if(isset($_GET["enable-feature-js"])){enable_feature();exit;}
if(isset($_GET["asn-remove"])){asn_remove();exit;}
if(isset($_GET["asn-add-js"])){asn_add_js();exit;}
if(isset($_GET["asn-add-popup"])){asn_add_popup();exit;}
if(isset($_POST["asn-add-save"])){asn_add_save();exit;}
if(isset($_GET["asn-helper"])){asn_helper();exit;}
if(isset($_GET["ttl-js"])){ttl_js();exit;}
if(isset($_GET["ttl-popup"])){ttl_popup();exit;}
if(isset($_POST["ttl-save"])){ttl_save();exit;}

// ==================== Config Helpers ====================

function load_flood_config():array{

    $config=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FloodBlockASNs"),true);
    if(!is_array($config)){
        return array();}
    return $config;
}

function save_flood_config($config=array()):void{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FloodBlockASNs",json_encode($config));
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/flood-block/reload");
}

function get_service_config($serviceid=0):array{
    $config=load_flood_config();

    if(!isset($config[$serviceid])){
        return array("Enable"=>0,"ASNsToblock"=>array(),"TTL"=>28800);
    }
    $svc=$config[$serviceid];
    if(!isset($svc["Enable"])){$svc["Enable"]=0;}
    if(!isset($svc["ASNsToblock"]) || !is_array($svc["ASNsToblock"])){$svc["ASNsToblock"]=array();}
    if(!isset($svc["TTL"]) || intval($svc["TTL"])==0){$svc["TTL"]=28800;}
    return $svc;
}

function set_service_config($serviceid=0,$svc=array()):void{
    $config=load_flood_config();
    $config[intval($serviceid)]=$svc;
    save_flood_config($config);
}

function get_servicename(int $ID):string{
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}

function lookup_asn_info(string $asn):array{
    $LookupASNInfosCaches=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LookupASNInfosCaches"));
    if(!$LookupASNInfosCaches){
        $LookupASNInfosCaches=array();
    }
    if(isset($LookupASNInfosCaches[$asn])){
        return $LookupASNInfosCaches[$asn];
    }

    $result=array("asn"=>$asn,"name"=>"","country"=>"","description"=>"");
    if($asn==""){return $result;}
    $query="AS$asn.asn.cymru.com";
    $records=@dns_get_record($query,DNS_TXT);
    if(!is_array($records) || count($records)==0){return $result;}
    // Format: "45899 | VN | apnic | 2010-08-16 | VNPT-AS-VN VNPT Corp, VN"
    $txt=$records[0]["txt"] ?? "";
    $parts=explode("|",$txt);
    if(count($parts)>=5){
        $result["country"]=trim($parts[1]);
        $result["description"]=trim($parts[4]);
        $result["name"]=trim($parts[4]);
    }elseif(count($parts)>=2){
        $result["country"]=trim($parts[1]);
    }
    $LookupASNInfosCaches[$asn]=$result;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LookupASNInfosCaches",base64_encode(serialize($LookupASNInfosCaches)));
    return $result;
}

function compile_js_progress(int $serviceid):string{
    return "Loadjs('fw.nginx.apply.php?serviceid=$serviceid&function=NgixSitesReload&addjs=');";
}

function refresh_global_no_close(int $serviceid):string{
    return "LoadAjax('www-parameters-$serviceid','fw.nginx.sites.php?www-parameters2=$serviceid');\n";
}

// ==================== Entry Point ====================

function service_js():bool{
    $serviceid=intval($_GET["service-js"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog4("ASNs","$page?popup-main=$serviceid");
}

function popup_main():bool{
    $serviceid=intval($_GET["popup-main"]);
    $page=CurrentPageName();
    echo "<div id='flood-asn-main-$serviceid'></div>
    <script>LoadAjax('flood-asn-main-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}

// ==================== Table Container ====================

function popup_table():bool{
    $page=CurrentPageName();
    $serviceid=intval($_GET["popup-table"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    echo "<div id='flood-asn-buttons-$serviceid' style='margin-bottom:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&popup-table2=$serviceid");
    return true;
}

// ==================== Top Buttons ====================

function top_buttons():bool{
    $serviceid=intval($_GET["top-buttons"]);
    $function=$_GET["function"] ?? "";
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $svc=get_service_config($serviceid);

    if($svc["Enable"]==1){
        $topbuttons[]=array("Loadjs('$page?enable-feature-js=0&serviceid=$serviceid')","fas fa-check-circle","{active2}");
    }else{
        $topbuttons[]=array("Loadjs('$page?enable-feature-js=1&serviceid=$serviceid')","fas fa-times-circle","{disabled}");
    }

    $topbuttons[]=array("Loadjs('$page?asn-add-js=yes&serviceid=$serviceid&function=$function')","fas fa-plus","{add} ASN");
    $topbuttons[]=array("Loadjs('$page?ttl-js=yes&serviceid=$serviceid')","fas fa-clock","{ttl}");
    echo $tpl->_ENGINE_parse_body($tpl->th_buttons($topbuttons));
    return true;
}

// ==================== Enable/Disable Feature ====================

function enable_feature():bool{
    $page=CurrentPageName();
    $serviceid=intval($_GET["serviceid"]);
    $enable=intval($_GET["enable-feature-js"]);

    $svc=get_service_config($serviceid);
    $svc["Enable"]=$enable;
    set_service_config($serviceid,$svc);

    $servicename=get_servicename($serviceid);
    header("content-type: application/x-javascript");
    echo refresh_global_no_close($serviceid);
    echo "LoadAjaxSilent('flood-asn-buttons-$serviceid','$page?top-buttons=$serviceid');";
    return admin_tracks("FloodBlockASN: Turn feature to $enable for $servicename");
}

// ==================== ASN Table ====================

function popup_table2():bool{
    $serviceid=intval($_GET["popup-table2"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $function=$_GET["function"] ?? "";
    $search=trim($_GET["search"] ?? "");

    $svc=get_service_config($serviceid);

    $asns=$svc["ASNsToblock"];
    $ttl=intval($svc["TTL"]);
    $ttlHuman=format_ttl($ttl);

    $html=array();
    $html[]="<div class='alert alert-info' style='margin-bottom:10px'>";
    $html[]="<i class='fas fa-shield-alt'></i> <strong>ASN </strong> &mdash; ";
    $html[]="<strong>{ttl}:</strong> $ttlHuman &mdash; ";
    $html[]="<strong>ASNs:</strong> <span class='badge' style='background:#ed5565;color:#fff;font-size:14px'>".count($asns)."</span>";
    $html[]="</div>";

    $tableid=time();
    $html[]="<table class='table table-hover' id='$tableid'>";
    $html[]="<thead><tr>";
    $html[]="<th nowrap>ASN</th>";
    $html[]="<th nowrap>{description}</th>";
    $html[]="<th nowrap>{country}</th>";
    $html[]="<th style='width:1%' nowrap>{delete}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody>";

    $TRCLASS=null;
    foreach($asns as $asn){
        $asn=trim($asn);
        if($asn==""){continue;}

        if($search!=""){
            if(stripos($asn,$search)===false){
                $info=lookup_asn_info($asn);
                if(stripos($info["name"],$search)===false && stripos($info["country"],$search)===false){
                    continue;
                }
            }
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $info=lookup_asn_info($asn);
        $name=htmlspecialchars($info["name"]);
        $country=htmlspecialchars($info["country"]);
        $md="asn-".md5($asn);

        $delete=$tpl->icon_delete("Loadjs('$page?asn-remove=$asn&serviceid=$serviceid')","AsWebMaster");

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td nowrap><strong>AS$asn</strong></td>";
        $html[]="<td>$name</td>";
        $html[]="<td nowrap>$country</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody></table>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="LoadAjax('flood-asn-buttons-$serviceid','$page?top-buttons=$serviceid&function=$function');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function format_ttl(int $seconds):string{
    if($seconds<=0){$seconds=28800;}
    $hours=floor($seconds/3600);
    $mins=floor(($seconds%3600)/60);
    if($mins>0){return "{$hours}h {$mins}m";}
    return "{$hours}h";
}

// ==================== Remove ASN ====================

function asn_remove():bool{
    $asn=trim($_GET["asn-remove"]);
    $serviceid=intval($_GET["serviceid"]);

    $svc=get_service_config($serviceid);
    $svc["ASNsToblock"]=array_values(array_filter($svc["ASNsToblock"],function($v)use($asn){
        return trim($v)!==$asn;
    }));
    set_service_config($serviceid,$svc);

    $md="asn-".md5($asn);
    $servicename=get_servicename($serviceid);
    header("content-type: application/x-javascript");
    echo "$('#$md').remove();\n";
    echo refresh_global_no_close($serviceid);
    return admin_tracks("FloodBlockASN: Removed ASN $asn from $servicename");
}

// ==================== Add ASN ====================

function asn_add_js():bool{
    $serviceid=intval($_GET["serviceid"]);
    $function=$_GET["function"];
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog5("{add} ASN","$page?asn-add-popup=$serviceid&function=$function");
}

function asn_add_popup():bool{
    $serviceid=intval($_GET["asn-add-popup"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $function=$_GET["function"];
    $html=array();
    $html[]="<div style='margin-bottom:15px'>";
    $html[]=$tpl->div_info("{flood_block_asn_add_explain}");
    $html[]="</div>";

    // ASN helper: show real-time ASN traffic
    $html[]="<div id='asn-helper-$serviceid' style='margin-bottom:15px'></div>";
    $html[]="<script>LoadAjax('asn-helper-$serviceid','$page?asn-helper=yes&serviceid=$serviceid&function=$function');</script>";

    // Manual add form
    $jsafter="dialogInstance5.close();LoadAjax('flood-asn-main-$serviceid','$page?popup-table=$serviceid');";
    $form=array();
    $form[]=$tpl->field_hidden("serviceid",$serviceid);
    $form[]=$tpl->field_text("asn-add-save","{add} ASN","",true,"{flood_block_asn_number_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),null,"{add}",$jsafter,"AsWebMaster");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function asn_add_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid=intval($_POST["serviceid"]);
    $newAsn=trim($_POST["asn-add-save"]);
    if($serviceid==0){
        die("500: Service ID is required");
    }

    // Accept comma-separated ASNs
    if(strpos($newAsn,",")>0) {
        $newAsns = array_filter(array_map('trim', explode(",", $newAsn)));
    }else{
        $newAsns=array($newAsn);
    }

    $svc=get_service_config($serviceid);
    $existing=array_flip($svc["ASNsToblock"]);
    $added=array();
    foreach($newAsns as $a){
        $a=preg_replace('/[^0-9]/','',$a);
        if($a=="" || isset($existing[$a])){
            echo "$a already exists...\n";
            continue;
        }
        $svc["ASNsToblock"][]=$a;
        $added[]=$a;
    }
    set_service_config($serviceid,$svc);
    $servicename=get_servicename($serviceid);
    return admin_tracks("FloodBlockASN: Added ASN(s) ".implode(",",$added)." to $servicename");
}

// ==================== ASN Helper (real-time metrics) ====================

function asn_helper():bool{
    $serviceid=intval($_GET["serviceid"]);
    $function=$_GET["function"];
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/metrics/realtime/asn/$serviceid"));

    if(!isset($json->status) || !$json->status){
        return false;
    }

    $asns=$json->asns ?? [];
    if(!is_array($asns) || count($asns)==0){
        return true;
    }

    // Load current config to mark already-blocked ASNs
    $svc=get_service_config($serviceid);
    $blockedMap=array_flip($svc["ASNsToblock"]);

    $elapsed=intval($json->elapsed_sec ?? 0);
    $elapsedMin=($elapsed>0)?round($elapsed/60,1):0;

    $html=array();
    $html[]="<div class='alert alert-warning' style='padding:8px 12px;margin-bottom:8px'>";
    $html[]="<i class='fas fa-chart-bar'></i> <strong>{realtime} ASN {statistics}</strong> &mdash; $elapsedMin min";
    $html[]="</div>";

    $html[]="<table class='table table-hover table-condensed' style='font-size:12px'>";
    $html[]="<thead><tr>";
    $html[]="<th>ASN</th>";
    $html[]="<th>{description}</th>";
    $html[]="<th>{country}</th>";
    $html[]="<th style='text-align:right'>{requests}</th>";
    $html[]="<th style='text-align:right'>IPs</th>";
    $html[]="<th style='text-align:right'>4xx</th>";
    $html[]="<th style='width:1%' nowrap>&nbsp;</th>";
    $html[]="</tr></thead><tbody>";

    $count=0;
    foreach($asns as $entry){
        $asn=$entry->asn ?? "";
        if($asn==""){continue;}
        $total=$entry->total_requests ?? 0;
        $ips=$entry->unique_ips ?? 0;

        // Calculate 4xx errors
        $errors4xx=0;
        if(isset($entry->statuses) && is_object($entry->statuses)){
            foreach($entry->statuses as $code=>$detail){
                if(intval($code)>=400 && intval($code)<500){
                    $errors4xx+=intval($detail->requests ?? 0);
                }
            }
        }

        $info=lookup_asn_info($asn);
        $name=htmlspecialchars($info["name"]);
        $country=htmlspecialchars($info["country"]);

        $isBlocked=isset($blockedMap[$asn]);
        $asnDisplay="<strong>AS$asn</strong>";

        if($isBlocked){
            $btn="<span class='label' style='background:#ed5565;color:#fff'>{blocked}</span>";
        }else{
            $btn="<button class='btn btn-xs btn-primary' ";
            $btn.="onclick=\"$.post('$page',{'asn-add-save':'$asn','serviceid':'$serviceid'},function(){";
            $btn.="LoadAjax('asn-helper-$serviceid','$page?asn-helper=yes&serviceid=$serviceid&function=$function');$function();";
            $btn.="});\">";
            $btn.="<i class='fas fa-plus'></i> {add}</button>";
        }

        $errorStyle=($errors4xx>100)?"color:#ed5565;font-weight:bold":"";

        $html[]="<tr>";
        $html[]="<td nowrap>$asnDisplay</td>";
        $html[]="<td>$name</td>";
        $html[]="<td nowrap>$country</td>";
        $html[]="<td style='text-align:right'>".number_format($total)."</td>";
        $html[]="<td style='text-align:right'>".number_format($ips)."</td>";
        $html[]="<td style='text-align:right;$errorStyle'>".number_format($errors4xx)."</td>";
        $html[]="<td style='width:1%' nowrap>$btn</td>";
        $html[]="</tr>";

        $count++;
        if($count>=30){break;}
    }

    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

// ==================== TTL Configuration ====================

function ttl_js():bool{
    $serviceid=intval($_GET["serviceid"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    return $tpl->js_dialog5("{ttl}","$page?ttl-popup=$serviceid");
}

function ttl_popup():bool{
    $serviceid=intval($_GET["ttl-popup"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();

    $svc=get_service_config($serviceid);
    $ttl=intval($svc["TTL"]);
    if($ttl==0){$ttl=28800;}
    $ttlHours=intval($ttl/3600);

    $jsafter="dialogInstance5.close();LoadAjax('flood-asn-main-$serviceid','$page?popup-table=$serviceid');";
    echo $tpl->BigIntegerField("serviceid:$serviceid|ttl-save","{ttl} ({hours})",
        "{flood_block_ttl_explain}",$ttlHours,$jsafter,1,99999,"AsWebMaster");
    return true;
}

function ttl_save():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $serviceid=intval($_POST["serviceid"]);
    $hours=intval($_POST["ttl-save"]);
    if($hours<=0){$hours=8;}
    $ttl=$hours*3600;

    $svc=get_service_config($serviceid);
    $svc["TTL"]=$ttl;
    set_service_config($serviceid,$svc);

    $servicename=get_servicename($serviceid);
    return admin_tracks("FloodBlockASN: Set TTL to {$hours}h for $servicename");
}
