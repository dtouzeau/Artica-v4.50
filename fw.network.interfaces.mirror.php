<?php
// "Mirror" tab of an interface. The daemon copies the IPv4 traffic of the
// interface to one address with two iptables TEE rules (received: mangle
// PREROUTING -i, sent: mangle POSTROUTING -o). The page shows the settings,
// what the kernel runs (GET /system/network/interface/mirror/{if}) and the
// counters of both rules, turned into a rate by polling.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(isset($_POST["eth"])){Save();exit;}
if(isset($_GET["mirror-view"])){mirror_view();exit;}
if(isset($_GET["mirror-stats"])){mirror_stats();exit;}
if(isset($_GET["mirror-apply"])){mirror_apply();exit;}

popup();

function mir_valid_eth($eth):bool{
    return preg_match('/^[A-Za-z0-9_.:@-]{1,32}$/',strval($eth))==1;
}
function mir_div($eth):string{
    return "mirror-".substr(md5($eth),0,8);
}
function mir_is_admin():bool{
    $users=new usersMenus();
    return $users->AsSystemAdministrator;
}
// mir_state asks the daemon; null when it is too old to have the route.
function mir_state($eth,$gateway=""):?array{
    $url="/system/network/interface/mirror/".urlencode($eth);
    if($gateway<>""){$url.="?gateway=".urlencode($gateway);}
    $json=json_decode(strval($GLOBALS["CLASS_SOCKETS"]->REST_API($url)),true);
    if(!is_array($json) || empty($json["Status"]) || !isset($json["rules"])){return null;}
    return $json;
}

function popup():bool{
    $eth=strval($_GET["eth"]);
    $tpl=new template_admin();
    if(!mir_valid_eth($eth)){echo $tpl->div_error("{invalid_interface}");return false;}
    $page=CurrentPageName();
    $div=mir_div($eth);
    echo "<div id='$div'></div><script>LoadAjax('$div','$page?mirror-view=".urlencode($eth)."');</script>";
    return true;
}

function mir_bytes($b):string{
    $b=floatval($b);
    $u=array("B","KB","MB","GB","TB");$i=0;
    while($b>=1024 && $i<count($u)-1){$b/=1024;$i++;}
    return ($i==0?strval(intval($b)):number_format($b,1))." ".$u[$i];
}

function mirror_view():bool{
    $eth=strval($_GET["mirror-view"]);
    $tpl=new template_admin();
    if(!mir_valid_eth($eth)){echo $tpl->div_error("{invalid_interface}");return false;}
    $page=CurrentPageName();
    $ethu=urlencode($eth);
    $ethh=htmlspecialchars($eth);
    $div=mir_div($eth);
    $admin=mir_is_admin();
    $nic=new system_nic($eth);
    $st=mir_state($eth);
    $enabled=intval($nic->mirror)==1;
    $gateway=trim(strval($nic->mirrorgateway));
    $gatewayh=htmlspecialchars($gateway);

    $html=array();
    $html[]="<div class='mir' id='$div-root'>";

    // What the feature really does.
    $html[]="<div class='mir-banner'><div class='mir-banner-ico'><i class='fa-solid fa-circle-info'></i></div>";
    $html[]="<div><strong>{mir_banner_title}</strong><div>{mir_banner} <code class='mir-code'>$ethh</code> {mir_banner2}</div></div>";
    $html[]="<span class='mir-pill'><span class='mir-dot mir-dot-on'></span>{mir_no_latency}</span></div>";

    // Header: name + state.
    if($st===null){
        $stateHtml="<span class='mir-state'><span class='mir-dot'></span>{mir_unknown}</span>";
    }elseif($enabled){
        $stateHtml="<span class='mir-state mir-state-on'><span class='mir-dot mir-dot-on'></span>{mir_enabled}</span>";
    }else{
        $stateHtml="<span class='mir-state'><span class='mir-dot'></span>{mir_disabled}</span>";
    }
    $html[]="<div class='mir-card'>";
    $html[]="<div class='mir-head'><div class='mir-ico'><i class='fa-solid fa-right-left'></i></div>";
    $html[]="<div class='mir-txt'><div class='mir-title'>{mir_title} <span class='mir-tag'>SPAN / TEE</span></div>";
    $html[]="<div class='mir-sub'>{mir_explain} <strong>$ethh</strong> {mir_explain2}</div></div>";
    $html[]="<div class='mir-head-state'><span class='mir-k'>{mir_status}</span>$stateHtml</div></div>";

    // Settings form.
    $form=array();
    $form[]=$tpl->field_hidden("eth",$eth);
    $form[]=$tpl->field_checkbox("mirror","{mir_enable}",$enabled?1:0,false);
    $form[]=$tpl->field_ipv4("mirrorgateway","{mir_destination}",$gateway,false,"{mir_destination_explain}");
    // form_outside cuts jsafter on ';': call a function, give the
    // asynchronous build time to run before reading the kernel again.
    $jsafter="MirReload('$div','$ethu')";
    $security=$admin?null:"AsSystemAdministrator";
    $html[]="<div class='mir-form'>".$tpl->form_outside(null,$form,null,"{apply}",$jsafter,$security)."</div>";

    // Facts that the old mockups promised otherwise.
    $html[]="<div class='mir-facts'>";
    $html[]="<div><span class='mir-k'>{mir_direction}</span><span class='mir-v'>{mir_direction_both}</span><div class='mir-hint'>{mir_direction_explain}</div></div>";
    $html[]="<div><span class='mir-k'>{mir_format}</span><span class='mir-v'>{mir_format_raw}</span><div class='mir-hint'>{mir_format_explain}</div></div>";
    $html[]="<div><span class='mir-k'>{mir_scope}</span><span class='mir-v'>IPv4</span><div class='mir-hint'>{mir_scope_explain}</div></div>";
    $html[]="</div>";

    // Warnings about the destination.
    if($st!==null && $enabled && $gateway<>"" && empty($st["gateway_ok"])){
        $html[]="<div class='mir-warn'><i class='fa-solid fa-triangle-exclamation'></i><div><strong>{mir_gw_unusable}</strong> {mir_gw_unusable_explain}</div></div>";
    }
    if($st!==null && $enabled && $gateway==""){
        $html[]="<div class='mir-warn'><i class='fa-solid fa-triangle-exclamation'></i><div>{mir_no_destination}</div></div>";
    }
    if($st!==null && $enabled && isset($st["interface_enabled"]) && empty($st["interface_enabled"])){
        $html[]="<div class='mir-warn'><i class='fa-solid fa-power-off'></i><div>{mir_nic_disabled}</div></div>";
    }
    if($st!==null && !empty($st["draft_open"])){
        $html[]="<div class='mir-warn'><i class='fa-solid fa-pen-ruler'></i><div>{mir_draft_open}</div></div>";
    }
    $html[]="</div>"; // card

    // Live telemetry.
    $id="$div-tm";
    $html[]="<div class='mir-card mir-tm' id='$id'>";
    $html[]="<div class='mir-tm-head'><span class='mir-beacon".($enabled?" mir-beacon-on":"")."'></span><strong>{mir_live}</strong><span class='mir-tag' id='$id-sync'>5 s</span>";
    $html[]="<span class='mir-tm-probe'>";
    if($gateway<>""){
        $html[]="{mir_probe} (<span class='mir-mono'>$gatewayh</span>) : <span id='$id-probe' class='mir-probe'>&mdash;</span>";
        if($admin && filter_var($gateway,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
            $html[]=" <button type='button' class='mir-btn' OnClick=\"MirProbe('$id','$gatewayh')\"><i class='fa-solid fa-rotate'></i> {mir_test_probe}</button>";
        }
    }
    $html[]="</span></div>";
    if($st===null){
        $html[]="<div class='mir-note'><i class='fa-solid fa-circle-info'></i> {mir_daemon_old}</div>";
    }else{
        $rx=array("packets"=>0,"bytes"=>0,"present"=>false);$tx=$rx;
        foreach ($st["rules"] as $r){
            if($r["direction"]=="rx"){$rx=$r;}
            if($r["direction"]=="tx"){$tx=$r;}
        }
        $html[]="<div class='mir-tiles'>";
        foreach (array("rx"=>$rx,"tx"=>$tx) as $dir=>$r){
            $lbl=$dir=="rx"?"{mir_rx}":"{mir_tx}";
            $ico=$dir=="rx"?"fa-arrow-down":"fa-arrow-up";
            $on=!empty($r["present"]);
            $html[]="<div class='mir-tile'><span class='mir-k'><i class='fa-solid $ico'></i> $lbl</span>";
            $html[]="<div class='mir-big' id='$id-$dir-pkts'>".number_format(floatval($r["packets"]),0,","," ")." <small>{mir_pkts}</small></div>";
            $html[]="<div class='mir-hint'><span id='$id-$dir-bytes'>".mir_bytes($r["bytes"])."</span> &middot; <span id='$id-$dir-rate'>&mdash;</span></div>";
            $html[]="<div class='mir-rule".($on?" mir-ok":" mir-off")."' id='$id-$dir-rule'>".($on?"<i class='fa-solid fa-check'></i> {mir_rule_present}":"<i class='fa-solid fa-xmark'></i> {mir_rule_absent}")."</div></div>";
        }
        $cloneDev=strval($st["clone_interface"] ?? "");
        $sameIf=$cloneDev<>"" && $cloneDev==$eth;
        $html[]="<div class='mir-tile'><span class='mir-k'><i class='fa-solid fa-route'></i> {mir_clone_path}</span>";
        $html[]="<div class='mir-big mir-mono'>".($cloneDev<>""?htmlspecialchars($cloneDev):"&mdash;")."</div>";
        $html[]="<div class='mir-hint'>".($cloneDev<>""?"{mir_clone_path_explain}":"{mir_clone_path_none}")."</div></div>";
        $html[]="</div>";
        if($sameIf){
            $html[]="<div class='mir-note'><i class='fa-solid fa-circle-info'></i> {mir_same_iface}</div>";
        }
    }
    $html[]="</div>";

    // Footer: applied or not.
    $html[]="<div class='mir-foot'>";
    if($st===null || empty($st["rules_readable"])){
        $html[]="<span class='mir-foot-txt'><i class='fa-solid fa-circle-question'></i> {mir_kernel_unknown}</span>";
    }elseif(!empty($st["applied"])){
        $html[]="<span class='mir-foot-txt mir-ok'><i class='fa-solid fa-shield-halved'></i> {mir_applied}</span>";
    }else{
        $html[]="<span class='mir-foot-txt mir-warn-txt'><i class='fa-solid fa-triangle-exclamation'></i> {mir_not_applied}</span>";
    }
    if($admin){
        $html[]="<button type='button' class='mir-apply' OnClick=\"Loadjs('$page?mirror-apply=$ethu')\"><i class='fa-solid fa-play'></i> {mir_apply}</button>";
    }
    $html[]="</div>";
    $html[]="</div>";

    $t=new template_admin();
    $js=array();
    $js[]="<script>";
    $js[]="function MirReload(div,eth){setTimeout(function(){LoadAjaxSilent(div,'$page?mirror-view='+eth);},2500);}";
    $js[]="function MirFmtRate(bps){if(bps<1000){return bps.toFixed(0)+' bit/s';}if(bps<1e6){return (bps/1e3).toFixed(1)+' kbit/s';}if(bps<1e9){return (bps/1e6).toFixed(1)+' Mbit/s';}return (bps/1e9).toFixed(2)+' Gbit/s';}";
    $js[]="function MirFmtBytes(b){var u=['B','KB','MB','GB','TB'],i=0;while(b>=1024&&i<u.length-1){b/=1024;i++;}return (i===0?b.toFixed(0):b.toFixed(1))+' '+u[i];}";
    $js[]="function MirFmtNum(n){return String(n).replace(/\\B(?=(\\d{3})+(?!\\d))/g,' ');}";
    $js[]="function MirPoll(id,eth,prev){var root=document.getElementById(id);if(!root){return;}";
    $js[]="$.ajax({url:'$page?mirror-stats='+encodeURIComponent(eth),dataType:'json',cache:false}).done(function(r){";
    $js[]="if(!document.getElementById(id)||!r||!r.ok){return;}var now=Date.now();";
    $js[]="['rx','tx'].forEach(function(d){var c=r[d];var p=document.getElementById(id+'-'+d+'-pkts');if(p){p.innerHTML=MirFmtNum(c.packets)+' <small>'+".json_encode($t->javascript_parse_text("{mir_pkts}"))."+'</small>';}";
    $js[]="var b=document.getElementById(id+'-'+d+'-bytes');if(b){b.textContent=MirFmtBytes(c.bytes);}";
    $js[]="var rt=document.getElementById(id+'-'+d+'-rate');if(rt&&prev&&prev[d]&&c.packets>=prev[d].packets){var s=(now-prev.t)/1000;if(s>0){rt.textContent=MirFmtRate((c.bytes-prev[d].bytes)*8/s)+', '+((c.packets-prev[d].packets)/s).toFixed(0)+' pkt/s';}}});";
    $js[]="r.t=now;setTimeout(function(){MirPoll(id,eth,r);},5000);";
    $js[]="}).fail(function(){setTimeout(function(){MirPoll(id,eth,prev);},10000);});}";
    $js[]="function MirProbe(id,ip){var e=document.getElementById(id+'-probe');if(!e){return;}e.className='mir-probe';e.innerHTML=".json_encode($t->javascript_parse_text("{mir_checking}")).";";
    $js[]="$.ajax({url:'fw.network.interfaces.php?multipath-probe='+encodeURIComponent(ip),dataType:'json',cache:false}).done(function(r){";
    $js[]="if(!r||!r.ok){e.innerHTML=".json_encode($t->javascript_parse_text("{mir_unknown}")).";return;}";
    $js[]="if(r.reachable){e.className='mir-probe mir-ok';e.innerHTML='&#9679; '+".json_encode($t->javascript_parse_text("{mir_online}"))."+(r.received>0?' ('+r.rtt.toFixed(2)+' ms)':' (ARP)');}";
    $js[]="else{e.className='mir-probe mir-warn-txt';e.innerHTML='&#9679; '+".json_encode($t->javascript_parse_text("{mir_unreachable}")).";}";
    $js[]="}).fail(function(){e.innerHTML=".json_encode($t->javascript_parse_text("{mir_unknown}")).";});}";
    if($st!==null){
        $js[]="MirPoll('$id',".json_encode($eth).",null);";
    }
    if($admin && $gateway<>"" && filter_var($gateway,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
        $js[]="MirProbe('$id','$gatewayh');";
    }
    $js[]="</script>";
    echo mir_style().$tpl->_ENGINE_parse_body(implode("",$html)).implode("",$js);
    return true;
}

// ?mirror-stats=<eth>: the counters of both TEE rules, as JSON.
function mirror_stats():bool{
    header("content-type: application/json");
    $eth=strval($_GET["mirror-stats"]);
    if(!mir_is_admin() || !mir_valid_eth($eth)){echo json_encode(array("ok"=>false));return false;}
    $st=mir_state($eth);
    if($st===null){echo json_encode(array("ok"=>false));return false;}
    $out=array("ok"=>true,"applied"=>!empty($st["applied"]));
    foreach ($st["rules"] as $r){
        $out[$r["direction"]]=array("packets"=>intval($r["packets"]),"bytes"=>intval($r["bytes"]),"present"=>!empty($r["present"]));
    }
    echo json_encode($out);
    return true;
}

// ?mirror-apply=<eth>: rebuild the TEE rules of every interface (asynchronous
// on the daemon side), then reload the tab.
function mirror_apply():bool{
    header("content-type: application/x-javascript");
    $eth=strval($_GET["mirror-apply"]);
    if(!mir_is_admin() || !mir_valid_eth($eth)){return false;}
    $page=CurrentPageName();
    $div=mir_div($eth);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/mirror");
    echo "setTimeout(function(){LoadAjaxSilent('$div','$page?mirror-view=".urlencode($eth)."');},2500);\n";
    return admin_tracks("Apply traffic mirroring rules ($eth)");
}

function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!mir_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $eth=strval($_POST["eth"]);
    if(!mir_valid_eth($eth)){echo $tpl->post_error("{invalid_interface}");return false;}
    $mirror=intval($_POST["mirror"])==1?1:0;
    $gateway=trim(strval($_POST["mirrorgateway"] ?? ""));
    if($gateway=="0.0.0.0"){$gateway="";}
    if($gateway<>"" && !filter_var($gateway,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)){
        echo $tpl->post_error("{mir_destination}: {mir_err_ipv4}");return false;
    }
    if($mirror==1){
        if($gateway==""){echo $tpl->post_error("{mir_no_destination}");return false;}
        // TEE hands the clone to this address as its next hop: it must be on
        // a network of the appliance, and not one of its own addresses.
        $st=mir_state($eth,$gateway);
        if($st!==null && empty($st["gateway_ok"])){
            echo $tpl->post_error("$gateway: {mir_gw_unusable_explain}");return false;
        }
    }
    $nic=new system_nic($eth);
    // Switching the mirror off disables the address field, which the browser
    // then does not send: keep the stored destination.
    if($mirror==0 && !isset($_POST["mirrorgateway"])){$gateway=trim(strval($nic->mirrorgateway));}
    $nic->mirror=$mirror;
    $nic->mirrorgateway=$gateway;
    if(!$nic->SaveNic()){
        echo $tpl->post_error($nic->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/interface/mirror");
    return admin_tracks_post("Save Interface mirror settings");
}

function mir_style():string{
    $mono="ui-monospace,SFMono-Regular,Menlo,Consolas,monospace";
    $c=array();
    $c[]=".mir{display:flex;flex-direction:column;gap:16px;margin-top:10px;font-size:13px;color:#334155}";
    $c[]=".mir-banner{display:flex;gap:14px;align-items:flex-start;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:14px;padding:16px 18px;line-height:1.55}";
    $c[]=".mir-banner strong{color:#065f46;font-size:14px}";
    $c[]=".mir-banner-ico{width:40px;height:40px;flex:0 0 40px;border-radius:10px;background:#d1fae5;color:#047857;display:flex;align-items:center;justify-content:center;font-size:18px}";
    $c[]=".mir-banner>div:nth-child(2){flex:1}";
    $c[]=".mir-pill{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;background:#d1fae5;color:#047857;border-radius:999px;padding:4px 12px;font-size:11.5px;font-weight:600}";
    $c[]=".mir-code{font-family:$mono;background:#fff;border:1px solid #d1fae5;border-radius:5px;padding:0 5px;color:#065f46}";
    $c[]=".mir-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px 22px;box-shadow:0 1px 2px rgba(15,23,42,.04)}";
    $c[]=".mir-head{display:flex;gap:16px;align-items:center;flex-wrap:wrap;padding-bottom:16px;border-bottom:1px solid #f1f5f9}";
    $c[]=".mir-ico{width:52px;height:52px;flex:0 0 52px;border-radius:14px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px}";
    $c[]=".mir-txt{flex:1;min-width:240px}";
    $c[]=".mir-title{font-size:19px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:10px;flex-wrap:wrap}";
    $c[]=".mir-tag{font-family:$mono;font-size:11px;font-weight:600;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:6px;padding:2px 8px;white-space:nowrap}";
    $c[]=".mir-sub{color:#64748b;margin-top:4px;line-height:1.5}";
    $c[]=".mir-head-state{display:flex;align-items:center;gap:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px 14px}";
    $c[]=".mir-head-state .mir-k{margin:0}";
    $c[]=".mir-state{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:11.5px;font-weight:700;text-transform:uppercase;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;white-space:nowrap}";
    $c[]=".mir-state-on{background:#ecfdf5;color:#047857;border-color:#a7f3d0}";
    $c[]=".mir-dot{width:8px;height:8px;border-radius:50%;background:#94a3b8;display:inline-block}";
    $c[]=".mir-dot-on{background:#10b981}";
    $c[]=".mir-form{margin-top:14px}";
    $c[]=".mir-form .ibox-content{border:0;padding:0}";
    $c[]=".mir-facts{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;border-top:1px solid #f1f5f9;padding-top:16px;margin-top:6px}";
    $c[]=".mir-k{display:block;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8;margin-bottom:4px}";
    $c[]=".mir-v{display:block;font-weight:600;color:#0f172a;font-size:14px}";
    $c[]=".mir-hint{color:#94a3b8;font-size:12px;margin-top:4px;line-height:1.45}";
    $c[]=".mir-warn{display:flex;gap:10px;align-items:flex-start;background:#fffbeb;border:1px solid #fde68a;color:#92400e;border-radius:10px;padding:10px 14px;margin-top:14px;line-height:1.5}";
    $c[]=".mir-note{color:#64748b;padding:10px 2px}";
    $c[]=".mir-tm{background:#f8fafc}";
    $c[]=".mir-tm-head{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding-bottom:14px;border-bottom:1px solid #e2e8f0;margin-bottom:14px}";
    $c[]=".mir-tm-head strong{text-transform:uppercase;letter-spacing:.05em;font-size:12.5px;color:#0f172a}";
    $c[]=".mir-tm-probe{margin-left:auto;color:#64748b;display:flex;align-items:center;gap:6px;flex-wrap:wrap}";
    $c[]=".mir-beacon{width:12px;height:12px;border-radius:50%;background:#cbd5e1;position:relative}";
    $c[]=".mir-beacon-on{background:#10b981}";
    $c[]=".mir-beacon-on::after{content:'';position:absolute;inset:-4px;border-radius:50%;background:#10b981;opacity:.3;animation:mirpulse 2.2s infinite}";
    $c[]="@keyframes mirpulse{0%{transform:scale(.9);opacity:.6}50%{transform:scale(1.3);opacity:.1}100%{transform:scale(.9);opacity:.6}}";
    $c[]=".mir-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px}";
    $c[]=".mir-tile{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px;min-width:0}";
    $c[]=".mir-big{font-family:$mono;font-size:20px;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".mir-big small{font-size:12px;color:#059669;font-weight:600}";
    $c[]=".mir-mono{font-family:$mono}";
    $c[]=".mir-rule{margin-top:8px;font-size:12px;font-weight:600}";
    $c[]=".mir-ok{color:#047857}";
    $c[]=".mir-off{color:#94a3b8}";
    $c[]=".mir-warn-txt{color:#b45309}";
    $c[]=".mir-probe{font-family:$mono;font-weight:600}";
    $c[]=".mir-btn{border:1px solid #e2e8f0;background:#fff;color:#334155;border-radius:8px;padding:5px 12px;font-size:12px;cursor:pointer}";
    $c[]=".mir-btn:hover{background:#f1f5f9}";
    $c[]=".mir-foot{display:flex;align-items:center;gap:12px;flex-wrap:wrap;border-top:1px solid #e2e8f0;padding-top:14px}";
    $c[]=".mir-foot-txt{flex:1;min-width:220px;color:#64748b}";
    $c[]=".mir-apply{border:0;background:#059669;color:#fff;border-radius:10px;padding:9px 20px;font-weight:700;cursor:pointer}";
    $c[]=".mir-apply:hover{background:#047857}";
    return "<style>".implode("",$c)."</style>";
}
