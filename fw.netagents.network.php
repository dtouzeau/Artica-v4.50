<?php
// Display comprehensive network information for a remote agent
// Called from fw.netagents.list.php agent_info_tabs() with ?id={agent_id}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

if(isset($_GET["interface-metrics-js"])){interface_metrics_js();exit;}
if(isset($_GET["interface-detail"])){interface_detail_popup();exit;}
agent_info_net();

function agent_info_net():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    $_GET["agent-id"]=$id;

    $Main=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"),true);
    if(json_last_error()>JSON_ERROR_NONE){
        echo $tpl->div_error(json_last_error_msg());
        return true;
    }
    if(isset($Main["Error"])&&!empty($Main["Error"])){
        echo $tpl->div_error($Main["Error"]);
        return true;
    }
    if(!isset($Main["network"])){
        echo $tpl->div_error("{no_data}");
        return true;
    }

    // Fetch detailed interface info (cached, 2h TTL)
    $details_raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/interfaces/details/$id");
    $Details=json_decode($details_raw,true);
    $ifaceDetails=array();
    if(is_array($Details)&&isset($Details["interfaces"])){
        foreach($Details["interfaces"] as $iface){
            $ifaceDetails[$iface["name"]]=$iface;
        }
    }
    $f=array();

    // ── Interfaces Section ──
    $f[]=build_interfaces_section($Main["network"],$ifaceDetails,$id,$page);

    // ── Routes Section ──
    if(isset($Main["routes"]["routes"])&&count($Main["routes"]["routes"])>0){
        $f[]=build_routes_section($Main["routes"]["routes"]);
    }

    // ── Route Rules Section ──
    if(isset($Main["routes"]["rules"])&&count($Main["routes"]["rules"])>0){
        $f[]=build_rules_section($Main["routes"]["rules"]);
    }

    echo $tpl->_ENGINE_parse_body(implode("\n",$f));
    return true;
}
// ─── Interfaces ibox with per-interface cards ───
function build_interfaces_section(array $network,array $ifaceDetails,int $id,string $page):string{
    $icoIf=ico_nic;
    $w1="style='width:1%' nowrap";
    $h=array();
    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'>";
    $h[]="    <h5><i class='$icoIf'></i>&nbsp;&nbsp;{interfaces}</h5>";
    $h[]="    <div class='ibox-tools'>";
    $h[]="      <span class='label label-primary'>".count($network)."</span>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='ibox-content'>";

    foreach($network as $Interface){
        $iface=$Interface["interface"];
        if($iface=="lo"){continue;}
        $detail=isset($ifaceDetails[$iface])?$ifaceDetails[$iface]:null;
        $h[]=build_interface_card($Interface,$detail,$id,$page);
    }

    $h[]="  </div>";
    $h[]="</div>";
    return implode("\n",$h);
}

// ─── Single interface card ───
function build_interface_card(array $iface,?array $detail,int $id,string $page):string{
    $name=htmlspecialchars($iface["interface"]);
    $bytes_recv=FormatBytes($iface["bytes_recv"]/1024);
    $bytes_sent=FormatBytes($iface["bytes_sent"]/1024);
    $pkts_recv=number_format($iface["packets_recv"]??0);
    $pkts_sent=number_format($iface["packets_sent"]??0);

    // State badge
    $state="unknown";$state_class="default";
    if($detail){
        $state=$detail["oper_state"]??"unknown";
        if(strtolower($state)==="up"){$state_class="primary";}
        elseif(strtolower($state)==="down"){$state_class="danger";}
        else{$state_class="warning";}
    }
    $state_badge="<span class='label label-$state_class'>".strtoupper($state)."</span>";

    // Interface type badge
    $type_str="";
    if($detail&&!empty($detail["type"])){
        $type_str="<span class='label label-info' style='margin-left:5px'>".htmlspecialchars($detail["type"])."</span>";
    }

    $h=array();
    $h[]="<div class='panel panel-default' style='margin-bottom:15px;border-left:3px solid ".($state_class==="primary"?"#1ab394":($state_class==="danger"?"#ed5565":"#f8ac59"))."'>";
    $h[]="  <div class='panel-heading' style='padding:10px 15px;background:#f9f9f9'>";
    $h[]="    <div class='row'>";
    $h[]="      <div class='col-md-6'>";
    $h[]="        <i class='".ico_nic."' style='color:#1ab394;font-size:16px'></i>&nbsp;&nbsp;";
    $h[]="        <strong style='font-size:15px'>$name</strong>&nbsp;&nbsp;$state_badge$type_str";
    $h[]="      </div>";
    $h[]="      <div class='col-md-6 text-right'>";
    // Metrics chart link
    $h[]="        <a href='javascript:void(0)' onclick=\"Loadjs('fw.netagents.network.ifaces.metrics.php?netif-js=$id')\" class='btn btn-xs btn-white'><i class='".ico_speed."'></i> {statistics}</a>";
    $h[]="        <a href='javascript:void(0)' onclick=\"Loadjs('fw.netagents.netstats.php?js={$_GET["agent-id"]}')\" class='btn btn-xs btn-white'><i class='".ico_nic."'></i> {open_ports}</a>";



    $h[]="      </div>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='panel-body' style='padding:12px 15px'>";

    // ── Traffic stats row ──
    $h[]="    <div class='row'>";
    $h[]="      <div class='col-md-3 col-sm-6' style='margin-bottom:8px'>";
    $h[]="        <div style='color:#999;font-size:11px;text-transform:uppercase'><i class='".ico_download."' style='color:#1c84c6'></i> {incoming2}</div>";
    $h[]="        <div style='font-size:18px;font-weight:600'>$bytes_recv</div>";
    $h[]="        <div style='color:#aaa;font-size:11px'>$pkts_recv {packets}</div>";
    $h[]="      </div>";
    $h[]="      <div class='col-md-3 col-sm-6' style='margin-bottom:8px'>";
    $h[]="        <div style='color:#999;font-size:11px;text-transform:uppercase'><i class='".ico_upload."' style='color:#f8ac59'></i> {outgoing}</div>";
    $h[]="        <div style='font-size:18px;font-weight:600'>$bytes_sent</div>";
    $h[]="        <div style='color:#aaa;font-size:11px'>$pkts_sent {packets}</div>";
    $h[]="      </div>";

    // ── Detail columns (if available) ──
    if($detail){
        $mac=htmlspecialchars($detail["mac_address"]??"");
        $speed=htmlspecialchars($detail["speed"]??"");
        $mtu=intval($detail["mtu"]??0);
        $driver=htmlspecialchars($detail["driver"]??"");

        $h[]="      <div class='col-md-3 col-sm-6' style='margin-bottom:8px'>";
        if(!empty($mac)){
            $h[]="        <div style='color:#999;font-size:11px;text-transform:uppercase'><i class='".ico_server."' style='color:#888'></i> MAC</div>";
            $h[]="        <div style='font-size:13px;font-family:monospace'>$mac</div>";
        }
        if(!empty($speed)){
            $h[]="        <div style='color:#999;font-size:11px;text-transform:uppercase;margin-top:5px'><i class='".ico_speed."' style='color:#888'></i> {speed}</div>";
            $h[]="        <div style='font-size:13px'>$speed</div>";
        }
        $h[]="      </div>";

        $h[]="      <div class='col-md-3 col-sm-6' style='margin-bottom:8px'>";
        if($mtu>0){
            $h[]="        <div style='color:#999;font-size:11px;text-transform:uppercase'><i class='".ico_infoi."' style='color:#888'></i> MTU</div>";
            $h[]="        <div style='font-size:13px'>$mtu</div>";
        }
        if(!empty($driver)){
            $h[]="        <div style='color:#999;font-size:11px;text-transform:uppercase;margin-top:5px'><i class='".ico_plug."' style='color:#888'></i> {driver}</div>";
            $h[]="        <div style='font-size:13px'>$driver</div>";
        }
        $h[]="      </div>";
    }

    $h[]="    </div>"; // /row

    // ── IP Addresses ──
    if($detail){
        $addr_tags=array();
        if(!empty($detail["ipv4_addresses"])){
            foreach($detail["ipv4_addresses"] as $addr){
                $cidr=htmlspecialchars($addr["cidr"]??"");
                $scope=$addr["scope"]??"";
                $scope_color=($scope==="global")?"#1ab394":"#000888";
                $addr_tags[]="<span class='label' style='background:$scope_color;margin:2px;font-size:12px;color:white'><i class='".ico_networks."'></i> $cidr</span>";
            }
        }
        if(!empty($detail["ipv6_addresses"])){
            foreach($detail["ipv6_addresses"] as $addr){
                $cidr=htmlspecialchars($addr["cidr"]??"");
                $scope=$addr["scope"]??"";
                if($scope==="link"){continue;} // skip link-local for cleaner display
                $addr_tags[]="<span class='label label-default' style='margin:2px;font-size:11px;'><i class='".ico_networks."'></i> $cidr</span>";
            }
        }
        if(count($addr_tags)>0){
            $h[]="    <div style='margin-top:8px;padding-top:8px;border-top:1px solid #eee'>";
            $h[]="      ".implode(" ",$addr_tags);
            $h[]="    </div>";
        }

        // ── Bond/Bridge/VLAN membership ──
        $membership=array();
        if(!empty($detail["bond_master"])){
            $membership[]="<span class='label label-warning' style='margin:2px'><i class='".ico_link."'></i> Bond: ".htmlspecialchars($detail["bond_master"])."</span>";
        }
        if(!empty($detail["bridge_master"])){
            $membership[]="<span class='label label-info' style='margin:2px'><i class='".ico_link."'></i> Bridge: ".htmlspecialchars($detail["bridge_master"])."</span>";
        }
        if(!empty($detail["vlan_parent"])){
            $vids=!empty($detail["vlan_ids"])?implode(",",$detail["vlan_ids"]):"";
            $membership[]="<span class='label label-default' style='margin:2px'><i class='".ico_filter."'></i> VLAN $vids (".htmlspecialchars($detail["vlan_parent"]).")</span>";
        }
        if(!empty($detail["bond_slaves"])){
            $membership[]="<span class='label label-warning' style='margin:2px'><i class='".ico_link."'></i> Bond slaves: ".htmlspecialchars(implode(", ",$detail["bond_slaves"]))."</span>";
        }
        if(!empty($detail["bridge_slaves"])){
            $membership[]="<span class='label label-info' style='margin:2px'><i class='".ico_link."'></i> Bridge ports: ".htmlspecialchars(implode(", ",$detail["bridge_slaves"]))."</span>";
        }
        if(count($membership)>0){
            $h[]="    <div style='margin-top:5px'>";
            $h[]="      ".implode(" ",$membership);
            $h[]="    </div>";
        }
    }

    $h[]="  </div>"; // /panel-body
    $h[]="</div>"; // /panel
    return implode("\n",$h);
}

// ─── Routes ibox ───
function build_routes_section(array $routes):string{
    $h=array();
    $icoRoute=ico_routes;
    $route_count=0;
    foreach($routes as $r){if(($r["interface"]??"")!=="lo"){$route_count++;}}

    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'>";
    $h[]="    <h5><i class='$icoRoute'></i>&nbsp;&nbsp;{routes}</h5>";
    $h[]="    <div class='ibox-tools'>";
    $h[]="      <span class='label label-primary'>$route_count</span>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='ibox-content' style='padding:0'>";

    $h[]="<table class='table table-striped' style='margin-bottom:0'>";
    $h[]="<thead>";
    $h[]="<tr style='background:#f5f5f5'>";
    $h[]="  <th style='width:1%'></th>";
    $h[]="  <th>{destination}</th>";
    $h[]="  <th>{gateway}</th>";
    $h[]="  <th>{interface}</th>";
    $h[]="  <th>{protocol}</th>";
    $h[]="  <th>{scope}</th>";
    $h[]="  <th>{type}</th>";
    $h[]="  <th>{table}</th>";
    $h[]="</tr>";
    $h[]="</thead><tbody>";

    foreach($routes as $route){
        $interface=$route["interface"]??"";
        if($interface==="lo"){continue;}
        $destination=$route["destination"]??"";
        $gateway=$route["gateway"]??"";
        $protocol=$route["protocol"]??"";
        $scope=$route["scope"]??"";
        $type=$route["type"]??"";
        $table=$route["table"]??"";

        // Default route highlight
        $is_default=($destination==="0.0.0.0/0"||$destination==="default");
        $row_style=$is_default?"style='background:#f0faf6;font-weight:600'":"";

        // Route icon
        $route_ico=$is_default?ico_earth:ico_routes;
        $ico_color=$is_default?"#1ab394":"#888";

        // Gateway display
        $gw_display="<span style='color:#ccc'>-</span>";
        if(strlen($gateway)>2){
            $gw_display="<i class='".ico_arrow_right."' style='color:#1c84c6;font-size:10px'></i>&nbsp;<strong>".htmlspecialchars($gateway)."</strong>";
        }

        // Table name
        $table_name=route_table_name($table);

        // Protocol badge
        $proto_badge=protocol_badge($protocol);

        // Scope badge
        $scope_badge=scope_badge($scope);

        // Type badge
        $type_badge=type_badge($type);

        $h[]="<tr $row_style>";
        $h[]="  <td style='width:1%;text-align:center' nowrap><i class='$route_ico' style='color:$ico_color'></i></td>";
        $h[]="  <td nowrap><span style='font-size:13px;font-family:monospace'>".htmlspecialchars($destination)."</span></td>";
        $h[]="  <td nowrap>$gw_display</td>";
        $h[]="  <td nowrap><i class='".ico_nic."' style='color:#888;font-size:11px'></i>&nbsp;".htmlspecialchars($interface)."</td>";
        $h[]="  <td nowrap>$proto_badge</td>";
        $h[]="  <td nowrap>$scope_badge</td>";
        $h[]="  <td nowrap>$type_badge</td>";
        $h[]="  <td nowrap><span class='text-muted'>$table_name</span></td>";
        $h[]="</tr>";
    }

    $h[]="</tbody></table>";
    $h[]="  </div>";
    $h[]="</div>";
    return implode("\n",$h);
}

// ─── Route Rules ibox ───
function build_rules_section(array $rules):string{
    $h=array();

    // Deduplicate rules (IPv4/IPv6 can produce duplicates)
    $seen=array();
    $unique_rules=array();
    foreach($rules as $rule){
        $key=($rule["priority"]??0)."-".($rule["table"]??0);
        if(isset($rule["src"])){$key.="-".$rule["src"];}
        if(isset($rule["dst"])){$key.="-".$rule["dst"];}
        if(!isset($seen[$key])){
            $seen[$key]=true;
            $unique_rules[]=$rule;
        }
    }

    // Sort by priority
    usort($unique_rules,function($a,$b){
        return intval($a["priority"]??0)-intval($b["priority"]??0);
    });

    $h[]="<div class='ibox'>";
    $h[]="  <div class='ibox-title'>";
    $h[]="    <h5><i class='".ico_filter."'></i>&nbsp;&nbsp;{routing_rules}</h5>";
    $h[]="    <div class='ibox-tools'>";
    $h[]="      <span class='label label-primary'>".count($unique_rules)."</span>";
    $h[]="    </div>";
    $h[]="  </div>";
    $h[]="  <div class='ibox-content' style='padding:0'>";

    $h[]="<table class='table table-striped' style='margin-bottom:0'>";
    $h[]="<thead>";
    $h[]="<tr style='background:#f5f5f5'>";
    $h[]="  <th style='width:1%'></th>";
    $h[]="  <th>{priority}</th>";
    $h[]="  <th>{source}</th>";
    $h[]="  <th>{destination}</th>";
    $h[]="  <th>{table}</th>";
    $h[]="</tr>";
    $h[]="</thead><tbody>";

    foreach($unique_rules as $rule){
        $priority=intval($rule["priority"]??0);
        $table=intval($rule["table"]??0);
        $src=isset($rule["src"])&&strlen($rule["src"])>1?htmlspecialchars($rule["src"]): "<span style='color:#444343'>{all}</span>";
        $dst=isset($rule["dst"])&&strlen($rule["dst"])>1?htmlspecialchars($rule["dst"]):"<span style='color:#444343'>{all}</span>";
        $table_name=route_table_name(strval($table));

        $h[]="<tr>";
        $h[]="  <td style='width:1%;text-align:center'><i class='".ico_filter."' style='color:#444343'></i></td>";
        $h[]="  <td nowrap><span class='label label-default'>$priority</span></td>";
        $h[]="  <td nowrap><span style='font-family:monospace'>$src</span></td>";
        $h[]="  <td nowrap><span style='font-family:monospace'>$dst</span></td>";
        $h[]="  <td nowrap>$table_name</td>";
        $h[]="</tr>";
    }

    $h[]="</tbody></table>";
    $h[]="  </div>";
    $h[]="</div>";
    return implode("\n",$h);
}

// ─── Helper: human-readable route table name ───
function route_table_name(string $table):string{
    $names=array("0"=>"unspec","253"=>"default","254"=>"main","255"=>"local");
    $name=isset($names[$table])?$names[$table]:$table;
    return htmlspecialchars($name);
}

// ─── Helper: protocol badge ───
function protocol_badge(string $proto):string{
    $colors=array(
        "kernel"=>"#1c84c6","boot"=>"#1ab394","static"=>"#f8ac59",
        "dhcp"=>"#23c6c8","ra"=>"#ab7df6","bird"=>"#ed5565",
        "bgp"=>"#1c84c6","ospf"=>"#f8ac59","redirect"=>"#888"
    );
    $color=$colors[strtolower($proto)]??"#676a6c";
    if(strlen($proto)<1){return "<span style='color:#ccc'>-</span>";}
    return "<span class='badge' style='background:$color;color:#fff'>".htmlspecialchars($proto)."</span>";
}

// ─── Helper: scope badge ───
function scope_badge(string $scope):string{
    if(strlen($scope)<1){return "<span style='color:#ccc'>-</span>";}
    $colors=array("universe"=>"#1ab394","link"=>"#23c6c8","host"=>"#888","site"=>"#f8ac59");
    $color=$colors[strtolower($scope)]??"#676a6c";
    return "<span class='badge' style='background:$color;color:#fff'>".htmlspecialchars($scope)."</span>";
}

// ─── Helper: type badge ───
function type_badge(string $type):string{
    if(strlen($type)<1){return "<span style='color:#ccc'>-</span>";}
    if(strtolower($type)==="unicast"){return "<span class='text-muted'>".htmlspecialchars($type)."</span>";}
    $colors=array("broadcast"=>"#f8ac59","multicast"=>"#ab7df6","blackhole"=>"#ed5565",
        "unreachable"=>"#ed5565","prohibit"=>"#ed5565","local"=>"#23c6c8");
    $color=$colors[strtolower($type)]??"#676a6c";
    return "<span class='badge' style='background:$color;color:#fff'>".htmlspecialchars($type)."</span>";
}

// ─── JS handler: open interface metrics dialog ───
function interface_metrics_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["interface-metrics-js"]);
    return $tpl->js_dialog3("{statistics}","fw.netagents.interface.metrics.php?popup=yes&agent_id=$id",950);
}

// ─── Interface detail popup ───
function interface_detail_popup():bool{
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $ifname=trim($_GET["interface-detail"]);
    $Details=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/interfaces/details/$id"),true);
    if(!is_array($Details)||!isset($Details["interfaces"])){
        echo $tpl->div_error("{no_data}");
        return true;
    }
    $detail=null;
    foreach($Details["interfaces"] as $iface){
        if($iface["name"]===$ifname){$detail=$iface;break;}
    }
    if(!$detail){
        echo $tpl->div_error("{no_data}");
        return true;
    }

    $tpl->table_form_field_text("{interface}","<strong>".htmlspecialchars($detail["name"])."</strong>",ico_nic);
    $tpl->table_form_field_text("{status}",strtoupper($detail["oper_state"]??"unknown"),ico_infoi);
    if(!empty($detail["mac_address"])){
        $tpl->table_form_field_text("MAC",$detail["mac_address"],ico_server);
    }
    if(!empty($detail["speed"])){
        $tpl->table_form_field_text("{speed}",$detail["speed"],ico_speed);
    }
    if(intval($detail["mtu"]??0)>0){
        $tpl->table_form_field_text("MTU",$detail["mtu"],ico_infoi);
    }
    if(!empty($detail["driver"])){
        $tpl->table_form_field_text("{driver}",$detail["driver"],ico_plug);
    }
    if(!empty($detail["duplex"])){
        $tpl->table_form_field_text("Duplex",$detail["duplex"],ico_infoi);
    }
    if(!empty($detail["qdisc"])){
        $tpl->table_form_field_text("Qdisc",$detail["qdisc"],ico_infoi);
    }
    echo $tpl->table_form_compile();
    return true;
}
