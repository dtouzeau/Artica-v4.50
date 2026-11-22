<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// Onglet "Network" d'un agent distant, dans la modale de fw.netagents.list.php
// (agent_info_tabs(), ?id={agent_id}). Sous-onglets : Interfaces | Routing | VLAN.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

// Meme droit que la liste des agents qui ouvre cette modale.
if(!nwif_is_admin()){
    $tpl=new template_admin();
    echo $tpl->div_error("{ERROR_NO_PRIVS}");
    exit();
}

if(isset($_GET["interfaces"])){interfaces();exit;}

tabs();

function nwif_is_admin():bool{
    $users=new usersMenus();
    return meta_can_view($users);
}

function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $array["{network_interfaces}"]="$page?interfaces=yes&id=$id";
    $array["{routing}"]="fw.netagents.network.routing.php?agent-id=$id";
    $array["VLAN"]="fw.netagents.network.vlan.php?agent-id=$id";
    echo "<div style='margin-top:10px'>".$tpl->tabs_default($array)."</div>";
    return true;
}

// ─── Onglet Interfaces ───
function interfaces():bool{
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $m=nwif_model($id);
    if($m["error"]!==""){
        echo $tpl->div_error($m["error"]);
        return true;
    }
    $f=array();
    $f[]="<div class='nwif' id='nwif-root'>";
    $f[]=nwif_notice_stale($m);
    $f[]=nwif_notice_collecting($m);
    if(count($m["ifaces"])==0){
        $f[]=nwif_empty($m);
    }else{
        $f[]=nwif_summary($m);
        $f[]=nwif_cards($m);
    }
    $f[]=nwif_routes($m);
    $f[]=nwif_rules($m);
    $f[]="</div>";
    echo nwif_style().$tpl->_ENGINE_parse_body(implode("\n",$f)).nwif_js();
    return true;
}

// Lecture d'une route du daemon ; isolee pour le harnais de test.
function nwif_rest(string $path){
    return json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path),true);
}

// Horodatage Go (RFC3339) -> epoch ; 0 pour le temps nul de Go ou une valeur vide.
function nwif_epoch($iso):int{
    if(is_numeric($iso)){ return intval($iso); }
    $s=trim((string)$iso);
    if($s=="" || strpos($s,"0001-01-01")===0){ return 0; }
    $t=@strtotime($s);
    return ($t===false || $t<86400)?0:intval($t);
}

// Modele de l'onglet, calcule une fois pour tous les widgets.
function nwif_model(int $id):array{
    $m=array("id"=>$id,"error"=>"","pending"=>false,"details_pending"=>false,
        "status_at"=>0,"details_at"=>0,"ifaces"=>array(),"details"=>array(),
        "routes"=>array(),"rules"=>array(),
        "rx"=>0,"tx"=>0,"up"=>0,"down"=>0,"errors"=>0,"dropped"=>0);

    $Main=nwif_rest("/netagents/status/$id");
    if(!is_array($Main)){
        $m["error"]="{no_data}";
        return $m;
    }
    if(!empty($Main["Error"])){
        $m["error"]=htmlspecialchars((string)$Main["Error"]);
        return $m;
    }
    if(($Main["status"]??"")==="pending" || !isset($Main["network"])){
        $m["pending"]=true;
        return $m;
    }
    $m["status_at"]=nwif_epoch($Main["cached_at"]??"");

    // Details : cache de 2 h cote daemon, "pending" tant qu'il n'est pas rempli.
    $Details=nwif_rest("/netagents/interfaces/details/$id");
    if(is_array($Details) && isset($Details["interfaces"]) && is_array($Details["interfaces"])){
        foreach($Details["interfaces"] as $d){
            if(isset($d["name"])){ $m["details"][$d["name"]]=$d; }
        }
        $m["details_at"]=nwif_epoch($Details["cached_at"]??"");
    }else{
        $m["details_pending"]=true;
    }

    if(is_array($Main["network"])){
        foreach($Main["network"] as $n){
            $name=(string)($n["interface"]??"");
            if($name=="" || $name=="lo"){ continue; }
            $d=isset($m["details"][$name])?$m["details"][$name]:null;
            $state=nwif_state($d);
            if($state=="up"){ $m["up"]++; }
            if($state=="down"){ $m["down"]++; }
            $m["rx"]+=floatval($n["bytes_recv"]??0);
            $m["tx"]+=floatval($n["bytes_sent"]??0);
            if($d && isset($d["stats"]) && is_array($d["stats"])){
                $s=$d["stats"];
                $m["errors"]+=intval($s["rx_errors"]??0)+intval($s["tx_errors"]??0);
                $m["dropped"]+=intval($s["rx_dropped"]??0)+intval($s["tx_dropped"]??0);
            }
            $m["ifaces"][]=array("name"=>$name,"net"=>$n,"detail"=>$d,"state"=>$state);
        }
    }

    if(isset($Main["routes"]["routes"]) && is_array($Main["routes"]["routes"])){
        $m["routes"]=nwif_routes_rows($Main["routes"]["routes"]);
    }
    if(isset($Main["routes"]["rules"]) && is_array($Main["routes"]["rules"])){
        $m["rules"]=nwif_rules_rows($Main["routes"]["rules"]);
    }
    return $m;
}

// up / down / disabled / unknown. Le noyau rapporte "unknown" pour les interfaces
// virtuelles qui fonctionnent (tun, veth, dummy) : on se fie alors a IFF_RUNNING.
function nwif_state(?array $d):string{
    if(!$d){ return "unknown"; }
    $oper=strtolower((string)($d["oper_state"]??""));
    $admin=strtolower((string)($d["admin_state"]??""));
    $link=strtolower((string)($d["link_state"]??""));
    if($admin=="down"){ return "disabled"; }
    if($oper=="up"){ return "up"; }
    if($oper=="unknown" && $link=="up"){ return "up"; }
    if(in_array($oper,array("down","lower_layer_down","not_present","dormant"))){ return "down"; }
    return "unknown";
}

// Octets -> [valeur, unite], base 1024 comme FormatBytes().
function nwif_bytes($b):array{
    $b=floatval($b);
    $u=array("B","KB","MB","GB","TB","PB");
    $i=0;
    while($b>=1024 && $i<count($u)-1){ $b=$b/1024; $i++; }
    $v=($i==0)?strval(intval($b)):number_format($b,2,".","");
    return array($v,$u[$i]);
}

function nwif_bytes_html($b,string $cls=""):string{
    list($v,$u)=nwif_bytes($b);
    return "<span class='nwif-num $cls'>$v<span class='nwif-unit'>$u</span></span>";
}

function nwif_time(int $ts):string{
    if($ts<=0){ return "&mdash;"; }
    if(date("Y-m-d",$ts)==date("Y-m-d")){ return date("H:i",$ts); }
    return date("Y-m-d H:i",$ts);
}

function nwif_ico(string $fa,string $cls=""):string{
    return "<span class='nwif-i $cls' aria-hidden='true'><i class='$fa'></i></span>";
}

function nwif_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES);
}

// ─── Bandeaux ───
function nwif_notice_stale(array $m):string{
    $ts=$m["status_at"];
    if($ts<=0 || (time()-$ts)<180){ return ""; }
    $when=date("Y-m-d H:i",$ts);
    return "<div class='nwif-notice nwif-notice-stale' role='status'>".nwif_ico("fas fa-plug-circle-xmark")
        ."<span><strong>{netagent_stale_title}</strong> {netagent_stale_text} <strong>$when</strong></span></div>";
}

function nwif_notice_collecting(array $m):string{
    if(!$m["pending"] && !$m["details_pending"]){ return ""; }
    $txt=$m["pending"]?"{nwif_status_pending}":"{nwif_collecting}";
    return "<div class='nwif-notice nwif-notice-info' role='status'>".nwif_ico("fas fa-hourglass-half")."<span>$txt</span></div>";
}

function nwif_empty(array $m):string{
    if($m["pending"]){ return ""; }
    return "<div class='nwif-card ak-empty-c nwif-empty'>".nwif_ico("fas fa-network-wired","nwif-empty-i")
        ."<div class='nwif-empty-t'>{nwif_empty_title}</div><div class='ak-muted'>{nwif_empty_text}</div></div>";
}

// ─── Bandeau de synthese : 4 tuiles ───
function nwif_summary(array $m):string{
    $f=array();
    $f[]="<div class='nwif-sum'>";
    $f[]=nwif_tile_interfaces($m);
    $f[]=nwif_tile_received($m);
    $f[]=nwif_tile_sent($m);
    $f[]=nwif_tile_errors($m);
    $f[]="</div>";
    $stamp=array();
    if($m["details_at"]>0){ $stamp[]="{nwif_collected} ".nwif_time($m["details_at"]); }
    if($m["status_at"]>0){ $stamp[]="{nwif_counters} ".nwif_time($m["status_at"]); }
    if(count($stamp)>0){ $f[]="<div class='nwif-stamp ak-muted'>".implode(" &middot; ",$stamp)."</div>"; }
    return implode("",$f);
}

function nwif_tile(string $label,string $value,string $sub,string $ico,string $tone):string{
    return "<div class='nwif-card nwif-tile'><div class='nwif-tile-b'><div class='nwif-lbl'>$label</div>"
        ."<div class='nwif-tile-v'>$value</div><div class='nwif-tile-s ak-muted'>$sub</div></div>"
        ."<span class='nwif-tile-ico nwif-tone-$tone' aria-hidden='true'><i class='$ico'></i></span></div>";
}

function nwif_tile_interfaces(array $m):string{
    $n=count($m["ifaces"]);
    $sub="{$m["up"]} {nwif_word_up} &middot; {$m["down"]} {nwif_word_down}";
    return nwif_tile("{interfaces}","<span class='nwif-num'>$n</span>",$sub,"fas fa-ethernet","neutral");
}

function nwif_tile_received(array $m):string{
    return nwif_tile("{nwif_received}",nwif_bytes_html($m["rx"],"nwif-in"),"{nwif_since_boot}","fas fa-arrow-down","in");
}

function nwif_tile_sent(array $m):string{
    return nwif_tile("{nwif_sent}",nwif_bytes_html($m["tx"],"nwif-out"),"{nwif_since_boot}","fas fa-arrow-up","out");
}

// Les erreurs materielles sont en rouge ; les paquets ecartes (souvent des
// protocoles inconnus du noyau) seulement en orange.
function nwif_tile_errors(array $m):string{
    if($m["details_pending"]){
        $v="<span class='nwif-state ak-muted'>".nwif_ico("fas fa-circle-question")."{nwif_not_collected}</span>";
        return nwif_tile("{nwif_errors_title}",$v,"{nwif_errors_sub}","fas fa-shield-halved","neutral");
    }
    if($m["errors"]>0){
        $v="<span class='nwif-state nwif-t-err'>".nwif_ico("fas fa-triangle-exclamation").number_format($m["errors"])." {nwif_errors_word}</span>";
        $sub=$m["dropped"]>0?number_format($m["dropped"])." {nwif_dropped_word}":"{nwif_errors_sub}";
        return nwif_tile("{nwif_errors_title}",$v,$sub,"fas fa-shield-halved","err");
    }
    if($m["dropped"]>0){
        $v="<span class='nwif-state nwif-t-warn'>".nwif_ico("fas fa-circle-exclamation").number_format($m["dropped"])." {nwif_dropped_word}</span>";
        return nwif_tile("{nwif_errors_title}",$v,"{nwif_no_hw_errors}","fas fa-shield-halved","warn");
    }
    $v="<span class='nwif-state nwif-t-ok'>".nwif_ico("fas fa-circle-check")."{nwif_none_recorded}</span>";
    return nwif_tile("{nwif_errors_title}",$v,"{nwif_errors_sub}","fas fa-shield-halved","ok");
}

// ─── Cartes d'interface ───
function nwif_cards(array $m):string{
    $f=array();
    $f[]="<div class='nwif-grid'>";
    foreach($m["ifaces"] as $if){
        $f[]=nwif_card($if,$m);
    }
    $f[]="</div>";
    return implode("\n",$f);
}

function nwif_card(array $if,array $m):string{
    $f=array();
    $f[]="<article class='nwif-card nwif-if nwif-if-{$if["state"]}' aria-label='".nwif_h($if["name"])."'>";
    $f[]=nwif_card_head($if,$m["id"]);
    $f[]="<div class='nwif-if-b'>";
    $f[]=nwif_card_addresses($if);
    $f[]=nwif_card_membership($if,$m["details"]);
    $f[]=nwif_card_traffic($if);
    $f[]=nwif_card_history($if,$m["details_at"]);
    $f[]=nwif_card_facts($if);
    $f[]="</div>";
    $f[]=nwif_card_footer($if);
    $f[]="</article>";
    return implode("\n",$f);
}

function nwif_type_meta(string $type):array{
    $t=strtolower($type);
    $map=array(
        "ethernet"=>array("fas fa-ethernet","Ethernet"),
        "bridge"=>array("fas fa-network-wired","Bridge"),
        "bond"=>array("fas fa-link","Bond"),
        "vlan"=>array("fas fa-tags","802.1Q VLAN"),
        "veth"=>array("fas fa-shuffle","veth"),
        "tunnel"=>array("fas fa-shuffle","Tunnel"),
        "gre"=>array("fas fa-shuffle","GRE"),
        "gretap"=>array("fas fa-shuffle","GRETAP"),
        "vxlan"=>array("fas fa-shuffle","VXLAN"),
        "tun/tap"=>array("fas fa-shuffle","TUN/TAP"),
        "macvlan"=>array("fas fa-shuffle","macvlan"),
        "macvtap"=>array("fas fa-shuffle","macvtap"),
        "ipvlan"=>array("fas fa-shuffle","ipvlan"),
    );
    if(isset($map[$t])){ return $map[$t]; }
    return array("fas fa-ethernet",$type);
}

function nwif_state_pill(string $state):string{
    $map=array(
        "up"=>array("fas fa-circle-check","ok","{nwif_state_up}"),
        "down"=>array("fas fa-circle-xmark","err","{nwif_state_down}"),
        "disabled"=>array("fas fa-circle-minus","neutral","{nwif_state_disabled}"),
        "unknown"=>array("fas fa-circle-question","neutral","{nwif_state_unknown}"),
    );
    $s=isset($map[$state])?$map[$state]:$map["unknown"];
    $tones=array("ok"=>"ak-ok","err"=>"ak-err","neutral"=>"ak-off");
    $tone=isset($tones[$s[1]])?$tones[$s[1]]:"ak-off";
    return "<span class='ak-pill $tone nwif-pill'>".nwif_ico($s[0])."{$s[2]}</span>";
}

function nwif_card_head(array $if,int $id):string{
    $d=$if["detail"];
    $type=$d?(string)($d["type"]??""):"";
    list($ico,$tlabel)=nwif_type_meta($type==""?"ethernet":$type);
    $chip=$type!=""?"<span class='nwif-chip'>".nwif_h($tlabel)."</span>":"";
    $name=nwif_h($if["name"]);
    $f=array();
    $f[]="<div class='nwif-if-h'>";
    $f[]="<div class='nwif-if-t'>".nwif_ico($ico,"ak-muted")."<span class='nwif-mono nwif-if-n nwif-ell' title='$name'>$name</span>".nwif_state_pill($if["state"]).$chip."</div>";
    $f[]="<div class='nwif-if-a'>";
    $f[]="<button type='button' class='nwif-btn' OnClick=\"Loadjs('fw.netagents.network.ifaces.metrics.php?netif-js=$id')\">".nwif_ico("fas fa-chart-line")."{statistics}</button>";
    $f[]="<button type='button' class='nwif-btn' OnClick=\"Loadjs('fw.netagents.netstats.php?js=$id')\">".nwif_ico("fas fa-plug")."{open_ports}</button>";
    $f[]="</div></div>";
    return implode("",$f);
}

function nwif_card_addresses(array $if):string{
    $d=$if["detail"];
    if(!$d){ return ""; }
    $chips=array();
    $name=$if["name"];
    foreach((is_array($d["ipv4_addresses"]??null)?$d["ipv4_addresses"]:array()) as $a){
        $chips[]=nwif_addr_chip($a,$name);
    }
    foreach((is_array($d["ipv6_addresses"]??null)?$d["ipv6_addresses"]:array()) as $a){
        if(($a["scope"]??"")=="link"){ continue; }
        $chips[]=nwif_addr_chip($a,$name);
    }
    $lbl=count($chips)>1?"{nwif_ip_addresses}":"{nwif_ip_address}";
    $body=count($chips)>0?"<div class='ak-chips'>".implode("",$chips)."</div>":"<div class='ak-muted nwif-it'>{nwif_no_address}</div>";
    return "<div class='nwif-sec'><div class='nwif-lbl'>$lbl</div>$body</div>";
}

function nwif_addr_chip(array $a,string $ifname):string{
    $cidr=nwif_h($a["cidr"]??($a["address"]??""));
    $label=(string)($a["label"]??"");
    $secondary=!empty($a["secondary"]);
    $tag="";
    if($secondary){
        $t=($label!="" && $label!=$ifname)?nwif_h($label):"{nwif_secondary}";
        $tag="<span class='nwif-tag'>$t</span>";
    }elseif($label!="" && $label!=$ifname){
        $tag="<span class='nwif-tag'>".nwif_h($label)."</span>";
    }
    $cls=$secondary?"nwif-addr nwif-addr-2":"nwif-addr";
    return "<span class='$cls'>".nwif_ico("fas fa-network-wired","ak-muted")."<span class='nwif-mono'>$cidr</span>$tag</span>";
}

// Bond, bridge, VLAN : le mode du bond est porte par le maitre.
function nwif_card_membership(array $if,array $details):string{
    $d=$if["detail"];
    if(!$d){ return ""; }
    $items=array();
    $bm=(string)($d["bond_master"]??"");
    if($bm!=""){
        $mode=isset($details[$bm]["bond_mode"])?(string)$details[$bm]["bond_mode"]:"";
        $items[]=nwif_ico("fas fa-diagram-project")."{nwif_member_of} <strong class='nwif-mono'>".nwif_h($bm)."</strong>".($mode!=""?" (".nwif_h($mode).")":"");
    }
    if(!empty($d["bond_slaves"]) && is_array($d["bond_slaves"])){
        $mode=(string)($d["bond_mode"]??"");
        $items[]=nwif_ico("fas fa-link")."{nwif_bond_slaves} <strong class='nwif-mono'>".nwif_h(implode(", ",$d["bond_slaves"]))."</strong>".($mode!=""?" (".nwif_h($mode).")":"");
    }
    $br=(string)($d["bridge_master"]??"");
    if($br!=""){
        $items[]=nwif_ico("fas fa-network-wired")."{nwif_bridge_port_of} <strong class='nwif-mono'>".nwif_h($br)."</strong>";
    }
    if(!empty($d["bridge_slaves"]) && is_array($d["bridge_slaves"])){
        $items[]=nwif_ico("fas fa-network-wired")."{nwif_bridge_ports} <strong class='nwif-mono'>".nwif_h(implode(", ",$d["bridge_slaves"]))."</strong>";
    }
    $vp=(string)($d["vlan_parent"]??"");
    if($vp!=""){
        $vids=(!empty($d["vlan_ids"]) && is_array($d["vlan_ids"]))?implode(",",array_map("intval",$d["vlan_ids"])):"";
        $items[]=nwif_ico("fas fa-tags")."VLAN $vids {nwif_on} <strong class='nwif-mono'>".nwif_h($vp)."</strong>";
    }
    if(count($items)==0){ return ""; }
    $out=array();
    foreach($items as $it){ $out[]="<span class='nwif-mem'>$it</span>"; }
    return "<div class='nwif-mems'>".implode("",$out)."</div>";
}

function nwif_card_traffic(array $if):string{
    $n=$if["net"];
    $f=array();
    $f[]="<div class='nwif-traffic'>";
    $f[]="<div><div class='nwif-lbl nwif-in'>".nwif_ico("fas fa-arrow-down")."{incoming2}</div>".nwif_bytes_html($n["bytes_recv"]??0)
        ."<div class='ak-muted nwif-xs'>".number_format(floatval($n["packets_recv"]??0))." {nwif_packets_since_boot}</div></div>";
    $f[]="<div><div class='nwif-lbl nwif-out'>".nwif_ico("fas fa-arrow-up")."{nwif_outgoing}</div>".nwif_bytes_html($n["bytes_sent"]??0)
        ."<div class='ak-muted nwif-xs'>".number_format(floatval($n["packets_sent"]??0))." {nwif_packets_since_boot}</div></div>";
    $f[]="</div>";
    return implode("",$f);
}

// 24 barres horaires (entrant, sortant) a partir des echantillons de 5 min.
// Le dernier echantillon date de la collecte des details (jusqu'a 2 h) : la borne
// droite est cette heure-la, pas "maintenant".
function nwif_history_buckets(array $points,int $end):array{
    $in=array_fill(0,24,0.0);$out=array_fill(0,24,0.0);
    if($end<=0){
        foreach($points as $p){ $t=nwif_epoch($p["timestamp"]??""); if($t>$end){$end=$t;} }
    }
    foreach($points as $p){
        $t=nwif_epoch($p["timestamp"]??"");
        if($t<=0 || $t>$end){ continue; }
        $age=intval(floor(($end-$t)/3600));
        if($age>23){ continue; }
        $i=23-$age;
        $in[$i]+=floatval($p["bytes_recv"]??0);
        $out[$i]+=floatval($p["bytes_sent"]??0);
    }
    return array($in,$out,$end);
}

function nwif_card_history(array $if,int $detailsAt):string{
    $d=$if["detail"];
    if(!$d || empty($d["traffic_history"]) || !is_array($d["traffic_history"])){ return ""; }
    list($in,$out,$end)=nwif_history_buckets($d["traffic_history"],$detailsAt);
    $total=array_sum($in)+array_sum($out);
    $vals=array();
    for($i=0;$i<24;$i++){
        $vals[]=sprintf("%.0f",$in[$i]);
        $vals[]=sprintf("%.0f",$out[$i]);
    }
    $f=array();
    $f[]="<div class='nwif-hist'>";
    $f[]="<div class='nwif-hist-h'><span>{nwif_traffic_24h}</span>";
    if($total>0){
        $f[]="<span class='nwif-leg'><span class='nwif-sw nwif-sw-in'></span>{nwif_in}<span class='nwif-sw nwif-sw-out'></span>{nwif_out}</span>";
    }else{
        $f[]="<span class='nwif-it'>{nwif_no_traffic}</span>";
    }
    $f[]="</div>";
    if($total>0){
        $f[]="<div class='nwif-spark-w'><span class='nwif-spark' data-h='32'>".implode(",",$vals)."</span></div>";
    }else{
        $f[]="<div class='nwif-spark-empty'></div>";
    }
    $f[]="<div class='nwif-hist-f nwif-mono'><span>{nwif_24h_ago}</span><span>".nwif_time($end)."</span></div>";
    $f[]="</div>";
    return implode("",$f);
}

function nwif_fact(string $label,string $value):string{
    if($value===""){ $value="<span class='ak-muted'>&mdash;</span>"; }
    return "<div class='nwif-fact'><div class='nwif-flbl'>$label</div><div class='nwif-fval'>$value</div></div>";
}

function nwif_card_facts(array $if):string{
    $d=$if["detail"];
    if(!$d){ return ""; }
    $speed=nwif_h($d["speed"]??"");
    $duplex=strtolower((string)($d["duplex"]??""));
    $dup="";
    if($duplex=="full"){ $dup="{nwif_full}"; }
    if($duplex=="half"){ $dup="{nwif_half}"; }
    $sd="";
    if($speed!=""){ $sd="<span class='nwif-mono'>$speed</span>"; }
    if($dup!=""){ $sd.="<span class='ak-muted".($sd!=""?" nwif-dup":"")."'>$dup</span>"; }
    $mtu=intval($d["mtu"]??0);
    $mac=nwif_h($d["mac_address"]??"");
    $drv=(string)($d["driver"]??"");
    if($drv!=""){ $drv=trim($drv." ".(string)($d["driver_version"]??"")); }
    $carrier=strtolower((string)($d["carrier_state"]??""));
    $car="";
    if($carrier=="plugged"){ $car="<span class='nwif-t-ok'>".nwif_ico("fas fa-link")."{nwif_connected}</span>"; }
    if($carrier=="unplugged"){ $car="<span class='nwif-t-err'>".nwif_ico("fas fa-link-slash")."{nwif_no_carrier}</span>"; }

    $f=array();
    $f[]="<div class='nwif-facts'>";
    $f[]=nwif_fact("{nwif_speed_duplex}",$sd);
    $f[]=nwif_fact("MTU",$mtu>0?"<span class='nwif-mono'>$mtu</span>":"");
    if(strtolower((string)($d["type"]??""))=="vlan"){
        $vp=nwif_h($d["vlan_parent"]??"");
        $vids=(!empty($d["vlan_ids"]) && is_array($d["vlan_ids"]))?implode(",",array_map("intval",$d["vlan_ids"])):"";
        $f[]=nwif_fact("{nwif_parent_link}",$vp!=""?nwif_ico("fas fa-link","nwif-t-ok")."<span class='nwif-mono'>$vp</span>":"");
        $f[]=nwif_fact("{nwif_mac}",$mac!=""?"<span class='nwif-mono'>$mac</span>":"");
        $f[]=nwif_fact("{nwif_vlan_id}",$vids!=""?"<span class='nwif-mono'>$vids</span>":"");
        $f[]=nwif_fact("{nwif_driver}",$drv!=""?"<span class='nwif-mono'>".nwif_h($drv)."</span>":"");
    }else{
        $f[]=nwif_fact("{nwif_carrier}",$car);
        $f[]=nwif_fact("{nwif_mac}",$mac!=""?"<span class='nwif-mono'>$mac</span>":"");
        $f[]=nwif_fact("{nwif_driver}",$drv!=""?"<span class='nwif-mono nwif-ell' title='".nwif_h($drv)."'>".nwif_h($drv)."</span>":"");
        $pci=nwif_h($d["pci_bus_location"]??"");
        $f[]=nwif_fact("{nwif_pci_slot}",$pci!=""?"<span class='nwif-mono'>$pci</span>":"");
    }
    $f[]="</div>";
    return implode("",$f);
}

function nwif_card_footer(array $if):string{
    $d=$if["detail"];
    if(!$d){ return ""; }
    $s=(isset($d["stats"]) && is_array($d["stats"]))?$d["stats"]:array();
    $err=array(
        "{nwif_rx_errors}"=>intval($s["rx_errors"]??0),
        "{nwif_rx_dropped}"=>intval($s["rx_dropped"]??0),
        "{nwif_tx_errors}"=>intval($s["tx_errors"]??0),
        "{nwif_tx_dropped}"=>intval($s["tx_dropped"]??0),
        "{nwif_collisions}"=>intval($s["tx_collisions"]??0),
    );
    $hw=intval($s["rx_errors"]??0)+intval($s["tx_errors"]??0)+intval($s["tx_collisions"]??0);
    $drop=intval($s["rx_dropped"]??0)+intval($s["tx_dropped"]??0);
    $flags=array();
    if(!empty($d["promiscuous"])){ $flags[]="<span class='nwif-flag'>{nwif_promiscuous}</span>"; }
    if(!empty($d["xdp_attached"])){ $flags[]="<span class='nwif-flag'>{nwif_xdp}</span>"; }
    if(!empty($d["nfqueue_attached"])){ $flags[]="<span class='nwif-flag'>{nwif_nfqueue}</span>"; }

    $cls="nwif-foot";
    if($hw>0 || $drop>0){
        $parts=array();
        foreach($err as $k=>$v){ if($v>0){ $parts[]="$k ".number_format($v); } }
        $tone=$hw>0?"err":"warn";
        $cls.=" nwif-foot-$tone";
        $ico=$hw>0?"fas fa-triangle-exclamation":"fas fa-circle-exclamation";
        $left="<span class='nwif-t-$tone'>".nwif_ico($ico).implode(" &middot; ",$parts)."</span>";
    }elseif($if["state"]=="up"){
        $left="<span>".nwif_ico("fas fa-circle-check","nwif-t-ok")."{nwif_link_ok} &middot; {nwif_no_errors}</span>";
    }elseif($if["state"]=="disabled"){
        $left="<span>".nwif_ico("fas fa-circle-minus","ak-muted")."{nwif_admin_down}</span>";
    }else{
        $left="<span>".nwif_ico("fas fa-circle-xmark","nwif-t-err")."{nwif_link_down} &middot; {nwif_no_errors}</span>";
    }
    return "<div class='$cls'>$left<span class='nwif-flags'>".implode("",$flags)."</span></div>";
}

// ─── Routes ───
function nwif_routes_rows(array $routes):array{
    $rows=array();
    foreach($routes as $r){
        if(($r["interface"]??"")==="lo"){ continue; }
        $rows[]=$r;
    }
    return $rows;
}

function nwif_section_head(string $key,string $ico,string $title,int $count,string $extra):string{
    return "<div class='nwif-sh'><button type='button' class='nwif-sh-t' aria-expanded='true' aria-controls='nwif-$key' OnClick=\"NwifToggle(this)\">"
        .nwif_ico($ico,"ak-muted")."<span>$title</span><span class='nwif-count'>$count</span></button>"
        ."<span class='nwif-sh-x'>$extra<button type='button' class='nwif-chev' aria-label='{nwif_toggle}' aria-expanded='true' aria-controls='nwif-$key' OnClick=\"NwifToggle(this)\">".nwif_ico("fas fa-chevron-up")."</button></span></div>";
}

function nwif_routes(array $m):string{
    if(count($m["routes"])==0){ return ""; }
    $link="<button type='button' class='ak-link' OnClick=\"NwifRoutingTab()\">{nwif_manage_routing}".nwif_ico("fas fa-arrow-up-right-from-square")."</button>";
    $f=array();
    $f[]="<section class='nwif-card nwif-sec-t'>";
    $f[]=nwif_section_head("routes","fas fa-route","{nwif_routes}",count($m["routes"]),$link);
    $f[]="<div id='nwif-routes' class='nwif-tw'><table class='nwif-tbl'><thead><tr>";
    $f[]="<th>{destination}</th><th>{gateway}</th><th>{nwif_interface}</th><th>{nwif_protocol}</th><th>{scope}</th><th>{nwif_type}</th><th class='nwif-r'>{nwif_metric}</th><th class='nwif-r'>{nwif_table}</th>";
    $f[]="</tr></thead><tbody>";
    foreach($m["routes"] as $r){
        $f[]=nwif_route_row($r);
    }
    $f[]="</tbody></table></div></section>";
    return implode("\n",$f);
}

function nwif_route_row(array $r):string{
    $dst=(string)($r["destination"]??"");
    $default=($dst==="0.0.0.0/0" || $dst==="default" || $dst==="::/0");
    $gw=(string)($r["gateway"]??"");
    $iface=(string)($r["interface"]??"");
    $metric=intval($r["priority"]??0);
    $dcell="<span class='nwif-mono'>".nwif_h($dst)."</span>";
    if($default){
        $dcell=nwif_ico("fas fa-globe","nwif-t-ok")."<span class='nwif-mono nwif-b'>".nwif_h($dst)."</span><span class='nwif-tag nwif-tag-ok'>{nwif_default_route}</span>";
    }
    $gcell=strlen($gw)>2?"<span class='nwif-mono'>".nwif_h($gw)."</span>":"<span class='ak-muted'>&mdash;</span>";
    $icell=$iface!=""?nwif_ico("fas fa-ethernet","ak-muted")."<span class='nwif-mono nwif-ell nwif-cut' title='".nwif_h($iface)."'>".nwif_h($iface)."</span>":"<span class='ak-muted'>&mdash;</span>";
    $cls=$default?" class='nwif-row-def'":"";
    return "<tr$cls><td class='nwif-nw'>$dcell</td><td class='nwif-nw'>$gcell</td><td class='nwif-nw'>$icell</td>"
        ."<td>".nwif_proto_pill((string)($r["protocol"]??""))."</td><td>".nwif_scope_pill((string)($r["scope"]??""))."</td>"
        ."<td>".nwif_h($r["type"]??"")."</td><td class='nwif-r nwif-mono'>".($metric>0?$metric:"<span class='ak-muted'>&mdash;</span>")."</td>"
        ."<td class='nwif-r'>".nwif_table_name(strval($r["table"]??""))."</td></tr>";
}

function nwif_proto_pill(string $p):string{
    if($p===""){ return "<span class='ak-muted'>&mdash;</span>"; }
    $tone=array("boot"=>"ok","static"=>"ok","kernel"=>"in","dhcp"=>"in","ra"=>"in");
    $t=isset($tone[strtolower($p)])?$tone[strtolower($p)]:"neutral";
    return "<span class='nwif-tag nwif-tag-$t'>".nwif_h($p)."</span>";
}

function nwif_scope_pill(string $s):string{
    if($s===""){ return "<span class='ak-muted'>&mdash;</span>"; }
    $t=strtolower($s)=="universe"?"ok":"neutral";
    return "<span class='nwif-tag nwif-tag-$t'>".nwif_h($s)."</span>";
}

function nwif_table_name(string $table):string{
    $names=array("0"=>"unspec","253"=>"default","254"=>"main","255"=>"local");
    return nwif_h(isset($names[$table])?$names[$table]:$table);
}

// ─── Regles de routage ───
// Les regles IPv4 et IPv6 identiques sont fusionnees.
function nwif_rules_rows(array $rules):array{
    $seen=array();
    $rows=array();
    foreach($rules as $r){
        $key=implode("|",array(intval($r["priority"]??0),intval($r["table"]??0),$r["src"]??"",$r["dst"]??"",
            $r["iifname"]??"",$r["oifname"]??"",intval($r["mark"]??0),intval($r["goto"]??0)));
        if(isset($seen[$key])){ continue; }
        $seen[$key]=true;
        $rows[]=$r;
    }
    usort($rows,function($a,$b){ return intval($a["priority"]??0)-intval($b["priority"]??0); });
    return $rows;
}

function nwif_rules(array $m):string{
    if(count($m["rules"])==0){ return ""; }
    $f=array();
    $f[]="<section class='nwif-card nwif-sec-t'>";
    $f[]=nwif_section_head("rules","fas fa-filter","{routing_rules}",count($m["rules"]),"");
    $f[]="<div id='nwif-rules' class='nwif-tw'><table class='nwif-tbl'><thead><tr>";
    $f[]="<th>{priority}</th><th>{source}</th><th>{destination}</th><th>{nwif_iif}</th><th>{nwif_oif}</th><th>{nwif_fwmark}</th><th class='nwif-r'>{nwif_table}</th>";
    $f[]="</tr></thead><tbody>";
    foreach($m["rules"] as $r){
        $f[]=nwif_rule_row($r);
    }
    $f[]="</tbody></table></div></section>";
    return implode("\n",$f);
}

function nwif_rule_cell($v,string $empty):string{
    $v=(string)$v;
    if(strlen($v)<2){ return $empty; }
    return "<span class='nwif-mono'>".nwif_h($v)."</span>";
}

function nwif_rule_row(array $r):string{
    $all="<span class='ak-muted'>{all}</span>";
    $dash="<span class='ak-muted'>&mdash;</span>";
    $mark=intval($r["mark"]??0);
    $table=nwif_table_name(strval(intval($r["table"]??0)));
    $goto=intval($r["goto"]??0);
    if($goto>0){ $table="goto $goto"; }
    $iif=(string)($r["iifname"]??"");
    $oif=(string)($r["oifname"]??"");
    return "<tr><td><span class='nwif-tag nwif-mono'>".intval($r["priority"]??0)."</span></td>"
        ."<td>".nwif_rule_cell($r["src"]??"",$all)."</td><td>".nwif_rule_cell($r["dst"]??"",$all)."</td>"
        ."<td>".($iif!=""?"<span class='nwif-mono'>".nwif_h($iif)."</span>":$dash)."</td>"
        ."<td>".($oif!=""?"<span class='nwif-mono'>".nwif_h($oif)."</span>":$dash)."</td>"
        ."<td class='nwif-mono'>".($mark>0?sprintf("0x%x",$mark):$dash)."</td>"
        ."<td class='nwif-r nwif-b'>$table</td></tr>";
}

// ─── Feuille et script : concatenes HORS de _ENGINE_parse_body() ───
function nwif_accents():array{
    $a=array("accent"=>"#1C6FD1");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        $a["accent"]=$b["accent_dark"];
    }
    return $a;
}

function nwif_style():string{
    $a=nwif_accents();$ac=$a["accent"];
    $f=array();
    $f[]="<style>";
    $f[]=".nwif{--nb:#E3E8EE;--nt:#1F2933;--nm:#5F6B7A;--nbg:#F6F8FA;--nok:#1F9D55;--nwarn:#B7791F;--nerr:#D64545;--nneu:#8A94A6;--nin:#1C6FD1;--nout:#7A4FD1;--nac:$ac;";
    $f[]="color:var(--nt);font-size:13px;background:var(--nbg);padding:16px;border-radius:8px;margin-top:10px;display:flex;flex-direction:column;gap:16px}";
    $f[]=".nwif *,.nwif *:before,.nwif *:after{box-sizing:border-box}";
    $f[]=".nwif-card{background:#fff;border:1px solid var(--nb);border-radius:8px;box-shadow:0 1px 2px rgba(16,24,40,.05)}";
    $f[]=".nwif-mono{font-family:JetBrains Mono,ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}";
    $f[]=".nwif-muted{color:var(--nm)}.nwif-xs{font-size:12px}.nwif-it{font-style:italic}.nwif-b{font-weight:600}.nwif-r{text-align:right}.nwif-nw{white-space:nowrap}";
    $f[]=".nwif-ell{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}";
    $f[]=".nwif-i{display:inline-flex;align-items:center;justify-content:center;width:13px;height:13px;margin-right:6px;font-size:12px;flex:0 0 13px}";
    $f[]=".nwif-t-ok{color:var(--nok)}.nwif-t-warn{color:var(--nwarn)}.nwif-t-err{color:var(--nerr)}.nwif-in{color:var(--nin)}.nwif-out{color:var(--nout)}";
    $f[]=".nwif-lbl{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--nm);display:flex;align-items:center;margin-bottom:4px}";
    $f[]=".nwif-lbl.nwif-in{color:var(--nin)}.nwif-lbl.nwif-out{color:var(--nout)}";
    $f[]=".nwif-num{font-family:JetBrains Mono,ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:20px;font-weight:600;line-height:1.2}";
    $f[]=".nwif-unit{font-size:12px;font-weight:500;margin-left:4px;color:var(--nm)}";
    // Bandeaux
    $f[]=".nwif-notice{display:flex;align-items:center;gap:4px;padding:12px 14px;border-radius:8px;font-size:13px;line-height:1.5}";
    $f[]=".nwif-notice-stale{background:#FFF7E6;border:1px solid #F5C26B;color:#7A4B05}.nwif-notice-stale .nwif-i{color:var(--nwarn)}";
    $f[]=".nwif-notice-info{background:#EEF4FB;border:1px solid #BFDBFE;color:#1E429F}.nwif-notice-info .nwif-i{color:var(--nin)}";
    // Synthese
    $f[]=".nwif-sum{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}";
    $f[]=".nwif-tile{min-height:72px;padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:8px}";
    $f[]=".nwif-tile-b{min-width:0}.nwif-tile-b .nwif-lbl{margin-bottom:2px}.nwif-tile-v{min-height:24px;display:flex;align-items:center}";
    $f[]=".nwif-tile-s{font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $f[]=".nwif-tile-ico{width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:15px;flex:0 0 36px}";
    $f[]=".nwif-tone-neutral{background:#F1F4F8;color:var(--nm)}.nwif-tone-in{background:#EAF2FC;color:var(--nin)}.nwif-tone-out{background:#F2EDFB;color:var(--nout)}";
    $f[]=".nwif-tone-ok{background:#E8F6EE;color:var(--nok)}.nwif-tone-warn{background:#FDF4E3;color:var(--nwarn)}.nwif-tone-err{background:#FCEBEB;color:var(--nerr)}";
    $f[]=".nwif-state{display:inline-flex;align-items:center;font-size:13px;font-weight:600}";
    $f[]=".nwif-stamp{text-align:right;font-size:12px;margin-top:-8px}";
    // Cartes
    $f[]=".nwif-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}";
    $f[]="@media (max-width:1000px){.nwif-grid{grid-template-columns:minmax(0,1fr)}}";
    $f[]=".nwif-if{border-left:4px solid var(--nneu);display:flex;flex-direction:column;overflow:hidden;min-width:0}";
    $f[]=".nwif-if-up{border-left-color:var(--nok)}.nwif-if-down{border-left-color:var(--nerr)}";
    $f[]=".nwif-if-h{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 14px;border-bottom:1px solid var(--nb);background:#FAFBFC}";
    $f[]=".nwif-if-t{display:flex;align-items:center;gap:8px;min-width:0}.nwif-if-t .nwif-i{margin-right:0}";
    $f[]=".nwif-if-n{font-size:15px;font-weight:700;max-width:170px}";
    $f[]=".nwif-if-a{display:flex;gap:4px;flex:0 0 auto}";
    $f[]=".nwif-btn{display:inline-flex;align-items:center;font-size:13px;color:var(--nm);background:transparent;border:1px solid transparent;border-radius:6px;padding:4px 8px;cursor:pointer;white-space:nowrap}";
    $f[]=".nwif-btn:hover{background:#F1F4F8;color:var(--nt)}";
    $f[]=".nwif button:focus-visible{outline:2px solid var(--nac);outline-offset:2px}";
        $f[]=".nwif-pill .nwif-i{margin-right:4px;width:11px;height:11px;flex-basis:11px;font-size:11px}";
    $f[]=".nwif-chip{font-size:12px;color:var(--nm);background:#F1F4F8;border:1px solid var(--nb);border-radius:4px;padding:1px 6px;white-space:nowrap}";
    $f[]=".nwif-if-b{padding:14px;display:flex;flex-direction:column;gap:12px;flex:1}";
    $f[]=".nwif-addr{display:inline-flex;align-items:center;max-width:100%;padding:3px 8px;border-radius:4px;background:#EEF2F7;border:1px solid #DCE3EB;font-size:13px}";
    $f[]=".nwif-addr .nwif-mono{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $f[]=".nwif-addr-2{background:#F8FAFC;color:var(--nm)}";
    $f[]=".nwif-tag{display:inline-block;margin-left:6px;font-size:11px;font-weight:600;padding:1px 5px;border-radius:4px;background:#E6EBF1;color:var(--nm);white-space:nowrap}";
    $f[]=".nwif-tag-ok{background:#E8F6EE;color:#17804A}.nwif-tag-in{background:#EAF2FC;color:#1858A8}.nwif-tag-neutral{background:#F1F4F8;color:var(--nm)}";
    $f[]=".nwif-tbl .nwif-tag{margin-left:0;font-size:12px}.nwif-row-def .nwif-tag{margin-left:8px}";
    $f[]=".nwif-mems{display:flex;flex-wrap:wrap;gap:6px 14px;font-size:13px;color:var(--nm)}.nwif-mem{display:inline-flex;align-items:center}.nwif-mem strong{color:var(--nt);margin:0 3px}";
    $f[]=".nwif-traffic{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding-top:12px;border-top:1px solid #EEF1F5}";
    $f[]=".nwif-if-b>.nwif-traffic:first-child{border-top:0;padding-top:0}";
    $f[]=".nwif-hist{background:#F8FAFC;border:1px solid #E6EBF1;border-radius:6px;padding:8px 10px}";
    $f[]=".nwif-hist-h{display:flex;justify-content:space-between;align-items:center;font-size:12px;color:var(--nm);margin-bottom:4px}";
    $f[]=".nwif-leg{display:inline-flex;align-items:center;gap:4px}.nwif-sw{display:inline-block;width:9px;height:9px;border-radius:2px;margin-left:6px}.nwif-sw-in{background:var(--nin)}.nwif-sw-out{background:var(--nout)}";
    $f[]=".nwif-spark-w{height:32px;line-height:0}.nwif-spark-empty{height:32px;border-bottom:1px dashed #D5DCE4}";
    $f[]=".nwif-hist-f{display:flex;justify-content:space-between;font-size:11px;color:var(--nm);margin-top:2px}";
    $f[]=".nwif-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 12px;padding-top:10px;border-top:1px solid #EEF1F5}.nwif-dup{margin-left:6px}";
    $f[]=".nwif-fact{min-width:0}.nwif-flbl{font-size:12px;text-transform:uppercase;color:var(--nm);margin-bottom:2px}.nwif-fval{font-size:13px;display:flex;align-items:center;min-width:0}";
    $f[]=".nwif-foot{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;color:var(--nm);padding:10px 14px;border-top:1px solid #EEF1F5}";
    $f[]=".nwif-foot>span:first-child{display:inline-flex;align-items:center;min-width:0}";
    $f[]=".nwif-foot-err{background:#FDF1F1;border-top-color:#F2C4C4}.nwif-foot-warn{background:#FFF9EE;border-top-color:#F5DDB0}";
    $f[]=".nwif-flags{display:flex;gap:6px;flex:0 0 auto}.nwif-flag{font-family:JetBrains Mono,ui-monospace,Menlo,Consolas,monospace;font-size:11px;padding:2px 6px;border-radius:4px;background:#F1F4F8;color:var(--nm);white-space:nowrap}";
    // Sections repliables
    $f[]=".nwif-sec-t{overflow:hidden}";
    $f[]=".nwif-sh{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--nb)}";
    $f[]=".nwif-sh-t{display:inline-flex;align-items:center;gap:2px;background:none;border:0;padding:0;font-size:15px;font-weight:600;color:var(--nt);cursor:pointer}";
    $f[]=".nwif-count{margin-left:8px;font-size:12px;font-weight:600;background:#F1F4F8;border:1px solid var(--nb);border-radius:4px;padding:0 6px;color:var(--nm)}";
    $f[]=".nwif-sh-x{display:inline-flex;align-items:center;gap:10px}";
    $f[]=".nwif .ak-link .nwif-i{margin:0}";
    $f[]=".nwif-chev{background:none;border:0;padding:4px;color:var(--nm);cursor:pointer;line-height:0;border-radius:4px}.nwif-chev .nwif-i{margin:0;transition:transform .15s}";
    $f[]=".nwif-sec-t.nwif-closed .nwif-chev .nwif-i{transform:rotate(180deg)}.nwif-sec-t.nwif-closed .nwif-sh{border-bottom:0}.nwif-sec-t.nwif-closed .nwif-tw{display:none}";
    $f[]=".nwif-tw{overflow-x:auto}";
    $f[]=".nwif-tbl{width:100%;border-collapse:collapse;font-size:13px;color:var(--nt)}";
    $f[]=".nwif-tbl th{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--nm);text-align:left;padding:10px 14px;background:#FAFBFC;border-bottom:1px solid var(--nb);white-space:nowrap}";
    $f[]=".nwif-tbl th.nwif-r{text-align:right}";
    $f[]=".nwif-tbl td{padding:9px 14px;border-bottom:1px solid #EEF1F5;vertical-align:middle;color:var(--nt)}";
    $f[]=".nwif-tbl tr:last-child td{border-bottom:0}.nwif-row-def td{background:#F2FBF6}";
    $f[]=".nwif-tbl td .nwif-i{margin-right:6px}.nwif-cut{display:inline-block;max-width:170px;vertical-align:bottom}";
    // Etat vide
    $f[]=".nwif-empty{border-style:dashed}";
    $f[]=".nwif-empty-i{width:44px;height:44px;flex-basis:44px;margin:0 0 4px;border-radius:50%;background:#F1F4F8;font-size:18px;color:var(--nm)}.nwif-empty-t{font-size:14px;font-weight:600}";
    $f[]="@media (prefers-reduced-motion:reduce){.nwif-chev .nwif-i{transition:none}}";
    $f[]="</style>";
    return implode("\n",$f);
}

function nwif_js():string{
    return "<script src='js/jquery.peity.min.js'></script>
<script>
(function(){
window.NwifToggle=function(btn){
    var s=btn.closest ? btn.closest('.nwif-sec-t') : null; if(!s){ return; }
    var closed=s.classList.toggle('nwif-closed');
    var b=s.querySelectorAll('[aria-expanded]');
    for(var i=0;i<b.length;i++){ b[i].setAttribute('aria-expanded',closed?'false':'true'); }
};
window.NwifRoutingTab=function(){
    var a=document.querySelectorAll('a[data-toggle=tab]');
    for(var i=a.length-1;i>=0;i--){
        var u=a[i].getAttribute('data-url')||'';
        if(u.indexOf('fw.netagents.network.routing.php')>-1){ a[i].click(); return; }
    }
};
window.NwifPeity=function(){
    if(!window.jQuery || !jQuery.fn.peity){ return; }
    jQuery('.nwif-spark').each(function(){
        var e=jQuery(this); if(e.data('nwifDone')){ return; } e.data('nwifDone',1);
        var h=parseInt(e.attr('data-h'),10)||32, w=Math.max(160,Math.floor(e.parent().width()||420));
        e.peity('bar',{width:w,height:h,padding:0.15,fill:function(v,i){ return (i%2)?'#7A4FD1':'#1C6FD1'; }});
    });
};
if(typeof NoSpinner==='function'){ NoSpinner(); }
NwifPeity();
})();
</script>";
}
