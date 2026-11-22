<?php
//SP119
// DNS-over-HTTPS (DoH) du service dnsdist : etat reel, adresses a donner aux clients, compteurs
// cumules depuis le dernier demarrage, et une modale de reglages.
// Donnees : reglages DNSDistDOH*, sonde /dnsfw/doh/status (https://127.0.0.1:5599/status),
// /dnsfw/struct (dohFrontends + statistics.uptime + version), centre de certificats (DateTo).
// La conf est generee par articarest dnsdist/acls/Acls.go dohConfig() : TLS toujours actif
// (DNSDistDOHSSL=1), une interface en 127.x est ignoree, sans certificat le bloc DoH est abandonne.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
// Verrou Artica Meta (politique dnsfirewall) : section DoH tenue en lecture seule
include_once(dirname(__FILE__)."/ressources/class.dnsfirewall.meta.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
dnsfw_meta_dispatch_shim();
if(isset($_POST["DNSDistDOH"])){dohp_save();exit;}
if(isset($_GET["overview"])){dohp_overview();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_GET["status"])){dohp_page();exit;}

start();

// Cles de la section DoH tenables par la politique (DNSFirewallMeta.DOHCatalog). Le certificat et
// l'indicateur de capacite DnsDistProxyProtocol restent locaux.
function dnsfw_doh_keys():array{
    return array("DNSDistDOH","DNSDistDOHSSL","DNSDistDOHPort","DNSDistDOHPath","DNSDistDOHProxyProto","DNSDistDOHInterfaces");
}

function dohp_can_write():bool{
    $tpl=new template_admin();
    return (bool)$tpl->IsSecurity("AsDnsAdministrator");
}

function start():bool{
    $page=CurrentPageName();
    echo "<div id='dnsdist-doh-form'></div>
    <script>LoadAjaxSilent('dnsdist-doh-form','$page?status=yes')</script>
";
    return true;
}

// --- Modele : calcule une seule fois, lu par tous les widgets ------------------

function dohp_settings():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $m["enabled"]=intval($s->GET_INFO("DNSDistDOH"))==1;
    $m["port"]=intval($s->GET_INFO("DNSDistDOHPort"));
    if($m["port"]==0){ $m["port"]=80; }
    $m["path"]=trim($s->GET_INFO("DNSDistDOHPath"));
    if(strlen($m["path"])<2){ $m["path"]="/dns-query"; }
    $m["cert"]=trim($s->GET_INFO("DNSDistSSLCertificate"));
    $m["proxy_supported"]=intval($s->GET_INFO("DnsDistProxyProtocol"))==1;
    $m["proxy"]=intval($s->GET_INFO("DNSDistDOHProxyProto"))==1;
    $m["ifaces_raw"]=trim($s->GET_INFO("DNSDistDOHInterfaces"));
    $m["ifaces"]=array();
    foreach(explode(",",$m["ifaces_raw"]) as $i){
        $i=trim($i);
        if($i!==""){ $m["ifaces"][]=$i; }
    }
    return $m;
}

// IPv4 de chaque interface, comme ipclass.InterfaceToIPv4() cote Go.
function dohp_iface_ips():array{
    $out=array();
    if(!function_exists("net_get_interfaces")){ return $out; }
    $all=@net_get_interfaces();
    if(!is_array($all)){ return $out; }
    foreach($all as $name=>$info){
        if(!isset($info["unicast"]) || !is_array($info["unicast"])){ continue; }
        foreach($info["unicast"] as $u){
            if(isset($u["family"]) && intval($u["family"])==2 && !empty($u["address"])){
                $out[$name]=$u["address"];
                break;
            }
        }
    }
    return $out;
}

function dohp_host_of(string $address):string{
    $h=preg_replace('/:[0-9]+$/','',$address);
    return trim($h,"[]");
}

function dohp_num($o,string $k){
    if(is_object($o) && property_exists($o,$k) && is_numeric($o->{$k})){ return floatval($o->{$k}); }
    return null;
}

function dohp_model():array{
    if(isset($GLOBALS["DOHP_MODEL"])){ return $GLOBALS["DOHP_MODEL"]; }
    $m=dohp_settings();

    // Interfaces clientes : celles que le Go publie vraiment (une IP en 127.x est ignoree).
    $ips=dohp_iface_ips();
    $m["listen"]=array();
    foreach($m["ifaces"] as $i){
        $ip=isset($ips[$i])?$ips[$i]:"";
        if($ip!=="" && strpos($ip,"127.")===0){ continue; }
        if($i=="lo"){ continue; }
        $m["listen"][]=array("iface"=>$i,"ip"=>$ip);
    }

    // Certificat : le Go abandonne tout le bloc DoH s'il ne le trouve pas.
    $m["cert_row"]=null;
    if($m["cert"]!==""){
        $db=new CertificatesDB();
        $row=$db->getByCommonName($m["cert"]);
        if(is_object($row)){ $m["cert_row"]=$row; }
    }
    $m["cert_days"]=null;
    if(is_object($m["cert_row"]) && !empty($m["cert_row"]->DateTo)){
        $t=strtotime($m["cert_row"]->DateTo);
        if($t>0){ $m["cert_days"]=intval(floor(($t-time())/86400)); }
    }

    $m["probe_ok"]=false;
    $m["probe_err"]="";
    $m["fe"]=array();
    $m["uptime"]=null;
    $m["version"]="";

    if($m["enabled"]){
        $p=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/doh/status"));
        if(is_object($p) && !empty($p->Status) && isset($p->Info) && trim($p->Info)=="OK"){
            $m["probe_ok"]=true;
        }elseif(is_object($p)){
            $m["probe_err"]=!empty($p->Status)?trim((string)$p->Info):trim((string)(isset($p->Error)?$p->Error:""));
        }else{
            $m["probe_err"]="no answer from the Artica daemon";
        }
    }

    $st=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/struct"));
    if(is_object($st) && !empty($st->Status) && isset($st->Info) && is_object($st->Info)){
        if(isset($st->Info->version)){ $m["version"]=trim((string)$st->Info->version); }
        if(isset($st->Info->statistics) && is_object($st->Info->statistics)){
            $u=dohp_num($st->Info->statistics,"uptime");
            if($u!==null){ $m["uptime"]=intval($u); }
        }
        if(isset($st->Info->dohFrontends) && is_array($st->Info->dohFrontends)){
            foreach($st->Info->dohFrontends as $fe){
                if(!is_object($fe) || !isset($fe->address)){ continue; }
                // L'ecoute locale 127.0.0.1:5599 ne sert qu'a la sonde.
                if(strpos(dohp_host_of((string)$fe->address),"127.")===0){ continue; }
                $m["fe"][]=$fe;
            }
        }
    }

    // Compteurs : somme de toutes les ecoutes clientes ; null = champ inconnu (affiche « — »).
    $keys=array("http-connects","get-queries","post-queries","valid-responses","bad-requests","error-responses","http1-queries","http2-queries");
    $m["c"]=array();
    foreach($keys as $k){
        $sum=null;
        foreach($m["fe"] as $fe){
            $v=dohp_num($fe,$k);
            if($v===null){ continue; }
            $sum=($sum===null?0:$sum)+$v;
        }
        $m["c"][$k]=$sum;
    }

    // Etat de synthese, dans l'ordre de gravite.
    if(!$m["enabled"]){ $m["state"]="off"; }
    elseif($m["cert"]==="" || !is_object($m["cert_row"])){ $m["state"]="nocert"; }
    elseif(count($m["listen"])==0){ $m["state"]="noiface"; }
    elseif($m["probe_ok"]){ $m["state"]="ok"; }
    else{ $m["state"]="err"; }

    $GLOBALS["DOHP_MODEL"]=$m;
    return $m;
}

// --- Petits rendus -------------------------------------------------------------

// Traduit d'abord, remplace ensuite : un str_replace sur "{jeton}" non traduit ne trouve pas %s.
function dohp_t(string $token,$value):string{
    $tpl=new template_admin();
    return str_replace("%s",(string)$value,$tpl->_ENGINE_parse_body($token));
}

function dohp_fmt($v):string{
    if($v===null){ return "&mdash;"; }
    return number_format($v,0,".",",");
}

function dohp_duration(int $sec):string{
    $d=intval($sec/86400);
    $h=intval(($sec%86400)/3600);
    $mn=intval(($sec%3600)/60);
    if($d>0){ return "$d {dohp_days}, $h {dohp_hours}"; }
    if($h>0){ return "$h {dohp_hours}, $mn {dohp_minutes}"; }
    return "$mn {dohp_minutes}";
}

function dohp_row(string $label,string $value,string $help=""):string{
    $h=$help===""?"":"<div class='dohp-help'>$help</div>";
    return "<div class='dohp-row'><div class='dohp-row-l'><div class='dohp-lbl'>$label</div>$h</div><div class='dohp-val'>$value</div></div>";
}

function dohp_card_head(string $ico,string $tone,string $title,string $explain,string $right=""):string{
    return "<div class='dohp-head'><span class='ak-ico ak-ico-sm ak-ico-sq $tone'><i class='$ico'></i></span>"
        ."<div class='dohp-head-t'><div class='dohp-title'>$title</div><div class='dohp-sub'>$explain</div></div>$right</div>";
}

function dohp_edit_js():string{
    $page=CurrentPageName();
    return "Loadjs('$page?form-js=yes');";
}

function dohp_edit_link(string $label):string{
    if(!dohp_can_write()){
        $tpl=new template_admin();
        $t=htmlspecialchars(strip_tags($tpl->_ENGINE_parse_body("{dohp_no_privs}")),ENT_QUOTES);
        return "<span title='$t'><button type='button' class='ak-link' disabled><i class='fa-solid fa-lock'></i> $label</button></span>";
    }
    return "<button type='button' class='ak-link' OnClick=\"".dohp_edit_js()."\">$label <i class='fa-solid fa-chevron-right'></i></button>";
}

function dohp_url(string $ip,int $port,string $path):string{
    $p=($port==443)?"":":$port";
    return "https://$ip$p$path";
}

// --- Widgets : un par fonction ---------------------------------------------------

function dohp_banner(array $m):string{
    $state=$m["state"];
    $tech="";
    $btn="";
    if($state=="off"){
        $tone="ak-off"; $ico="fa-solid fa-circle-pause";
        $title="{dohp_st_off}"; $detail="{dohp_st_off_text}"; $pill="{dohp_pill_off}";
        if(dohp_can_write() && !dnsfw_meta_key_held("DNSDistDOH")){
            $btn="<button type='button' class='ak-btn ak-btn-sm' OnClick=\"".dohp_edit_js()."\"><i class='fa-solid fa-power-off'></i> {dohp_turn_on}</button>";
        }
    }elseif($state=="nocert"){
        $tone="ak-err"; $ico="fa-solid fa-circle-xmark";
        $title="{dohp_st_nocert}";
        $detail=$m["cert"]===""?"{dohp_st_nocert_text}":"{dohp_st_certmissing_text}";
        $pill="{dohp_pill_stopped}";
    }elseif($state=="noiface"){
        $tone="ak-warn"; $ico="fa-solid fa-triangle-exclamation";
        $title="{dohp_st_noiface}"; $detail="{dohp_st_noiface_text}"; $pill="{dohp_pill_local}";
    }elseif($state=="ok"){
        $tone="ak-ok"; $ico="fa-solid fa-circle-check";
        $title="{dohp_st_ok}"; $detail="{dohp_st_ok_text}"; $pill="{dohp_pill_running}";
    }else{
        $tone="ak-err"; $ico="fa-solid fa-circle-xmark";
        $title="{dohp_st_err}"; $pill="{dohp_pill_error}";
        $e=strtolower($m["probe_err"]);
        if(strpos($e,"refused")!==false){ $detail="{dohp_err_refused}"; }
        elseif(strpos($e,"timeout")!==false || strpos($e,"deadline")!==false){ $detail="{dohp_err_timeout}"; }
        elseif(strpos($e,"certificate")!==false || strpos($e,"tls")!==false || strpos($e,"x509")!==false){ $detail="{dohp_err_tls}"; }
        else{ $detail="{dohp_err_generic}"; }
        if($m["probe_err"]!==""){
            $raw=str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),htmlspecialchars($m["probe_err"]));
            $tech="<details class='dohp-tech'><summary>{dohp_technical_detail}</summary><div class='dohp-mono'>$raw</div></details>";
        }
    }
    return "<div class='dohp-banner $tone' role='status'>"
        ."<span class='dohp-bico'><i class='$ico'></i></span>"
        ."<div class='dohp-bt'><div class='dohp-btitle'>$title</div><div class='dohp-bdetail'>$detail</div>$tech</div>"
        ."<div class='dohp-bright'>$btn<span class='ak-pill $tone'><i class='$ico'></i> $pill</span></div></div>";
}

function dohp_endpoints(array $m):string{
    $urls=array();
    // En marche : les adresses que dnsdist ecoute vraiment ; sinon celles des interfaces choisies.
    foreach($m["fe"] as $fe){
        $addr=(string)$fe->address;
        $host=dohp_host_of($addr);
        $port=0;
        if(preg_match('/:([0-9]+)$/',$addr,$re)){ $port=intval($re[1]); }
        if($port==0){ $port=$m["port"]; }
        $iface="";
        foreach($m["listen"] as $l){ if($l["ip"]==$host){ $iface=$l["iface"]; } }
        if(strpos($host,":")!==false){ $host="[$host]"; }
        $urls[]=array("iface"=>$iface,"url"=>dohp_url($host,$port,$m["path"]));
    }
    // Desactive : aucune adresse n'est publiee, meme si des interfaces restent configurees.
    if(count($urls)==0 && $m["enabled"]){
        foreach($m["listen"] as $l){
            if($l["ip"]===""){ continue; }
            $urls[]=array("iface"=>$l["iface"],"url"=>dohp_url($l["ip"],$m["port"],$m["path"]));
        }
    }
    if(count($urls)==0){
        return "<div class='dohp-ep dohp-ep-empty'><i class='fa-solid fa-link-slash'></i> {dohp_no_endpoint}</div>";
    }
    $h=array();
    foreach($urls as $u){
        $url=htmlspecialchars($u["url"],ENT_QUOTES);
        $chip=$u["iface"]===""?"":"<span class='ak-chip ak-info'>".htmlspecialchars($u["iface"])."</span>";
        $dim=$m["state"]=="ok"?"":" dohp-ep-dim";
        $h[]="<div class='dohp-ep$dim'>$chip<span class='dohp-url' title='$url'>$url</span>"
            ."<button type='button' class='ak-act' aria-label='{dohp_copy_url}' title='{dohp_copy_url}' data-url='$url' OnClick=\"DohpCopy(this)\"><i class='fa-regular fa-copy'></i></button></div>";
    }
    return implode("",$h);
}

function dohp_cert_value(array $m):string{
    if($m["cert"]===""){
        return "<span class='ak-tag ak-warn'><i class='fa-solid fa-triangle-exclamation'></i> {dohp_no_cert}</span>";
    }
    $name=htmlspecialchars($m["cert"]);
    if(!is_object($m["cert_row"])){
        return "<span class='ak-tag ak-err'><i class='fa-solid fa-circle-xmark'></i> $name</span><div class='dohp-meta'>{dohp_cert_notfound}</div>";
    }
    $meta=array();
    if(!empty($m["cert_row"]->UseLetsEncrypt)){ $meta[]="Let's Encrypt"; }
    $tone="ak-ok";
    if($m["cert_days"]!==null){
        $date=date("Y-m-d",strtotime($m["cert_row"]->DateTo));
        if($m["cert_days"]<0){ $tone="ak-err"; $meta[]="<span class='dohp-bad'>{dohp_cert_expired} $date</span>"; }
        elseif($m["cert_days"]<30){ $tone="ak-warn"; $meta[]="<span class='dohp-warn'>".dohp_t("{dohp_cert_expires_in}",$m["cert_days"])." ($date)</span>"; }
        else{ $meta[]="{dohp_cert_expires} $date"; }
    }
    $ico=$tone=="ak-ok"?"fa-solid fa-certificate":"fa-solid fa-triangle-exclamation";
    return "<span class='ak-state $tone'><i class='$ico'></i> $name</span><div class='dohp-meta'>".implode(" &middot; ",$meta)."</div>";
}

function dohp_card_access(array $m):string{
    $rows=array();
    $chips=array();
    foreach($m["ifaces"] as $i){
        $lo=($i=="lo");
        $tone=$lo?"ak-off":"ak-info";
        $chips[]="<span class='ak-chip $tone'>".htmlspecialchars($i).($lo?" ({dohp_ignored})":"")."</span>";
    }
    $ifv=count($chips)?"<div class='dohp-chips'>".implode("",$chips)."</div>":"<span class='ak-tag ak-warn'><i class='fa-solid fa-triangle-exclamation'></i> {dohp_none}</span>";
    $rows[]=dohp_row("{listen_interfaces}",$ifv,"{dohp_ifaces_help}");
    $portHelp=$m["port"]==443?"{dohp_port_help}":"{dohp_port_help_not443}";
    $rows[]=dohp_row("{dohp_port}","<span class='dohp-box'>".$m["port"]."</span>",$portHelp);
    $rows[]=dohp_row("{doh_subfolder}","<span class='dohp-box'>".htmlspecialchars($m["path"])."</span>","{dohp_path_help}");
    $rows[]=dohp_row("{certificate}",dohp_cert_value($m),"{dohp_cert_help}");
    if(!$m["proxy_supported"]){
        $pv="<span class='ak-pill ak-off'><i class='fa-solid fa-ban'></i> {not_installed}</span>";
    }elseif($m["proxy"]){
        $pv="<span class='ak-pill ak-warn'><i class='fa-solid fa-circle-check'></i> {dohp_on}</span>";
    }else{
        $pv="<span class='ak-pill ak-off'><i class='fa-regular fa-circle'></i> {dohp_off}</span>";
    }
    $rows[]=dohp_row("{proxy_protocol}",$pv,"{dohp_proxy_help}");

    $engine=$m["version"]!==""?"<span>{dohp_engine} dnsdist ".htmlspecialchars($m["version"])."</span>":"<span></span>";
    return "<div class='ak-card dohp-card'><div>"
        .dohp_card_head("fa-solid fa-link","ak-ok","{dohp_access_title}","{dohp_access_explain}")
        .dohp_endpoints($m)
        ."<div class='dohp-rows'>".implode("",$rows)."</div></div>"
        ."<div class='dohp-foot'>$engine".dohp_edit_link("{dohp_edit_settings}")."</div></div>";
}

function dohp_kpi(string $ico,string $tone,string $label,$value,string $foot):string{
    $f=$foot===""?"":"<div class='dohp-kfoot'>$foot</div>";
    return "<div class='dohp-kpi $tone'><div class='dohp-khead'><span>$label</span><i class='$ico'></i></div>"
        ."<div class='dohp-kval'>".dohp_fmt($value)."</div>$f</div>";
}

function dohp_proto_bar(array $m):string{
    $h1=$m["c"]["http1-queries"];
    $h2=$m["c"]["http2-queries"];
    if($h1===null && $h2===null){ return ""; }
    $t=floatval($h1)+floatval($h2);
    if($t<=0){ return ""; }
    $p1=round(floatval($h1)*100/$t);
    $p2=100-$p1;
    return "<div class='dohp-proto'><div class='dohp-proto-h'><span>{dohp_proto_title}</span><span class='dohp-mono'>".dohp_fmt($t)."</span></div>"
        ."<div class='dohp-pbar' role='img' aria-label='HTTP/1 $p1%, HTTP/2 $p2%'><span class='dohp-p1' style='width:$p1%'></span><span class='dohp-p2' style='width:$p2%'></span></div>"
        ."<div class='dohp-legend'><span><i class='dohp-sw dohp-p1'></i>HTTP/1 <em>".dohp_fmt($h1)." &middot; $p1%</em></span>"
        ."<span><i class='dohp-sw dohp-p2'></i>HTTP/2 <em>".dohp_fmt($h2)." &middot; $p2%</em></span></div></div>";
}

function dohp_card_queries(array $m):string{
    $c=$m["c"];
    $known=count($m["fe"])>0;
    $q=null;
    if($c["get-queries"]!==null || $c["post-queries"]!==null){ $q=floatval($c["get-queries"])+floatval($c["post-queries"]); }
    $bad=null;
    if($c["bad-requests"]!==null || $c["error-responses"]!==null){ $bad=floatval($c["bad-requests"])+floatval($c["error-responses"]); }

    if(!$known){
        $c=array_fill_keys(array_keys($c),null);
        $q=null; $bad=null;
    }
    $qfoot=$q===null?"":"GET ".dohp_fmt($c["get-queries"])." &middot; POST ".dohp_fmt($c["post-queries"]);
    $vfoot="";
    if($q!==null && $q>0 && $c["valid-responses"]!==null){
        $vfoot=dohp_t("{dohp_pct_of_queries}",round($c["valid-responses"]*100/$q,1));
    }
    $bfoot=$bad===null?"":"{dohp_bad_requests} ".dohp_fmt($c["bad-requests"])." &middot; {dohp_errors} ".dohp_fmt($c["error-responses"]);
    $badTone=($bad!==null && $bad>0)?"dohp-kpi-err":"";

    $k=array();
    $k[]=dohp_kpi("fa-solid fa-plug","","{dohp_kpi_connects}",$c["http-connects"],"");
    $k[]=dohp_kpi("fa-solid fa-right-to-bracket","","{dohp_kpi_queries}",$q,$qfoot);
    $k[]=dohp_kpi("fa-solid fa-check-double",$c["valid-responses"]===null?"":"dohp-kpi-ok","{dohp_kpi_valid}",$c["valid-responses"],$vfoot);
    $k[]=dohp_kpi("fa-solid fa-triangle-exclamation",$badTone,"{dohp_kpi_failed}",$bad,$bfoot);

    $empty="";
    if(!$known || $q===null || $q==0){
        $msg="{dohp_no_query}";
        if(!$m["enabled"]){ $msg="{dohp_no_query_off}"; }
        elseif(!$known && $m["state"]!="ok"){ $msg="{dohp_no_counters}"; }
        $empty="<div class='dohp-empty'><i class='fa-solid fa-circle-info'></i> $msg</div>";
    }
    $up=$m["uptime"]!==null?"<span>{dohp_uptime} ".dohp_duration($m["uptime"])."</span>":"<span></span>";
    return "<div class='ak-card dohp-card'><div>"
        .dohp_card_head("fa-solid fa-chart-simple","ak-info","{dohp_queries_title}","{dohp_queries_explain}")
        ."<div class='dohp-kpis'>".implode("",$k)."</div>$empty".($known?dohp_proto_bar($m):"")."</div>"
        ."<div class='dohp-foot'>$up</div></div>";
}

function dohp_style():string{
    if(isset($GLOBALS["DOHP_STYLE"])){ return ""; }
    $GLOBALS["DOHP_STYLE"]=true;
    return "<style>
#dohp-root{display:flex;flex-direction:column;gap:20px;margin-top:15px}
#dohp-root #dnsfw-meta-banner{margin:0!important}
#dohp-root .dohp-banner{display:flex;align-items:center;gap:14px;min-height:72px;padding:14px 18px;background:#fff;border:1px solid #e5e7eb;border-left:4px solid var(--ak-tsolid,#94a3b8);border-radius:10px;box-sizing:border-box;animation:akIn .15s ease-out}
#dohp-root .dohp-bico{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;flex:0 0 40px;border-radius:50%;background:var(--ak-tbg,#f1f5f9);color:var(--ak-tfg,#475569);font-size:18px}
#dohp-root .dohp-bt{flex:1;min-width:0}
#dohp-root .dohp-btitle{font-size:15px;font-weight:600;color:#0f172a}
#dohp-root .dohp-bdetail{font-size:13px;color:#475569;margin-top:2px;line-height:1.45}
#dohp-root .dohp-bright{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}
#dohp-root .dohp-tech{margin-top:6px;font-size:13px;color:#475569}
#dohp-root .dohp-tech summary{cursor:pointer;color:#334155}
#dohp-root .dohp-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:12px;color:#334155;overflow-wrap:anywhere}
#dohp-root .dohp-tech .dohp-mono{margin-top:4px;padding:8px 10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px}
#dohp-root .dohp-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;align-items:stretch}
#dohp-root .dohp-card{display:flex;flex-direction:column;justify-content:space-between;padding:20px;box-sizing:border-box}
#dohp-root .dohp-head{display:flex;align-items:flex-start;gap:12px;padding-bottom:14px;border-bottom:1px solid #f1f5f9;margin-bottom:14px}
#dohp-root .dohp-head-t{flex:1;min-width:0}
#dohp-root .dohp-title{font-size:15px;font-weight:600;color:#0f172a}
#dohp-root .dohp-sub{font-size:13px;color:#64748b;margin-top:2px;line-height:1.45}
#dohp-root .dohp-ep{display:flex;align-items:center;gap:10px;padding:12px 14px;margin-bottom:8px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px}
#dohp-root .dohp-ep-dim .dohp-url{color:#64748b}
#dohp-root .dohp-ep-empty{color:#64748b;font-size:13px}
#dohp-root .dohp-url{flex:1;min-width:0;font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:14px;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#dohp-root .dohp-rows{margin-top:6px}
#dohp-root .dohp-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid #f1f5f9}
#dohp-root .dohp-rows .dohp-row:last-child{border-bottom:0}
#dohp-root .dohp-row-l{flex:1;min-width:0}
#dohp-root .dohp-lbl{font-size:13px;color:#334155;font-weight:500;line-height:20px}
#dohp-root .dohp-help{font-size:13px;color:#64748b;line-height:1.45;margin-top:2px}
#dohp-root .dohp-val{font-size:13px;color:#0f172a;text-align:right;max-width:55%;line-height:20px}
#dohp-root .dohp-val .ak-tag{white-space:normal;text-align:left}
#dohp-root .dohp-box{display:inline-block;padding:1px 8px;border:1px solid #e5e7eb;border-radius:6px;background:#f8fafc;font-family:'JetBrains Mono',Menlo,Consolas,monospace;white-space:nowrap}
#dohp-root .dohp-chips{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end}
#dohp-root .dohp-meta{font-size:12px;color:#64748b;margin-top:2px}
#dohp-root .dohp-bad{color:#ba1a1a;font-weight:600}
#dohp-root .dohp-warn{color:#865300;font-weight:600}
#dohp-root .dohp-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:13px;color:#64748b}
#dohp-root .dohp-kpis{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
#dohp-root .dohp-kpi{padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;min-width:0}
#dohp-root .dohp-khead{display:flex;justify-content:space-between;gap:8px;font-size:13px;color:#475569}
#dohp-root .dohp-khead i{color:#94a3b8}
#dohp-root .dohp-kval{font-size:24px;font-weight:600;color:#0f172a;margin-top:6px;font-variant-numeric:tabular-nums}
#dohp-root .dohp-kfoot{font-size:12px;color:#64748b;margin-top:4px}
#dohp-root .dohp-kpi-ok{background:#f0fdf4;border-color:#bbf7d0}
#dohp-root .dohp-kpi-ok .dohp-kval,#dohp-root .dohp-kpi-ok .dohp-khead i{color:#15803d}
#dohp-root .dohp-kpi-err{background:#fef2f2;border-color:#fecaca}
#dohp-root .dohp-kpi-err .dohp-kval,#dohp-root .dohp-kpi-err .dohp-khead i{color:#ba1a1a}
#dohp-root .dohp-empty{display:flex;align-items:center;gap:8px;margin-top:12px;padding:10px 12px;border-radius:8px;background:#f1f5f9;color:#475569;font-size:13px}
#dohp-root .dohp-proto{margin-top:16px}
#dohp-root .dohp-proto-h{display:flex;justify-content:space-between;font-size:13px;color:#334155;margin-bottom:6px}
#dohp-root .dohp-pbar{display:flex;height:8px;border-radius:8px;overflow:hidden;background:#e5e7eb}
#dohp-root .dohp-pbar span{display:block;height:100%}
#dohp-root .dohp-p1{background:#0ea5e9}
#dohp-root .dohp-p2{background:#005447}
#dohp-root .dohp-legend{display:flex;flex-wrap:wrap;gap:8px 24px;margin-top:8px;font-size:13px;color:#334155}
#dohp-root .dohp-legend em{font-style:normal;color:#64748b;font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:12px;margin-left:4px}
#dohp-root .dohp-sw{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:6px;vertical-align:-1px}
@media (max-width:1200px){#dohp-root .dohp-grid{grid-template-columns:minmax(0,1fr)}}
@media (prefers-reduced-motion:reduce){#dohp-root .dohp-banner{animation:none}}
</style>";
}

function dohp_js():string{
    $page=CurrentPageName();
    return "<script>
NoSpinner();
function DohpCopy(b){
    var u=b.getAttribute('data-url');
    var ok=function(){ b.setAttribute('title','OK'); setTimeout(function(){b.setAttribute('title','');},1500); };
    if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(u).then(ok); return; }
    var t=document.createElement('textarea'); t.value=u; t.style.position='fixed'; t.style.opacity='0';
    document.body.appendChild(t); t.select(); try{ document.execCommand('copy'); ok(); }catch(e){} document.body.removeChild(t);
}
if(window.DohpTimer){ clearInterval(window.DohpTimer); }
window.DohpTimer=setInterval(function(){
    if(!document.getElementById('dohp-host')){ clearInterval(window.DohpTimer); window.DohpTimer=null; return; }
    if(typeof ModalIsOpenOrOpening==='function' && ModalIsOpenOrOpening()){ return; }
    if(document.querySelector('.sweet-alert.visible,.swal2-shown')){ return; }
    LoadAjaxSilent('dohp-host','$page?overview=yes');
},10000);
</script>";
}

// --- Page ---------------------------------------------------------------------

function dohp_overview():bool{
    $tpl=new template_admin();
    $m=dohp_model();
    $h=array();
    $h[]="<div id='dohp-root' class='ak'>";
    // Bandeau Meta deja traduit et echappe par dnsfw_meta_banner() : insere apres le parse.
    $meta=dnsfw_meta_any_held(dnsfw_doh_keys())?dnsfw_meta_banner($tpl,false,"{dnsfw_meta_settings_explain}"):"";
    $h[]=dohp_banner($m);
    $h[]="<div class='dohp-grid'>";
    $h[]=dohp_card_access($m);
    $h[]=dohp_card_queries($m);
    $h[]="</div></div>";
    $body=$tpl->_ENGINE_parse_body(implode("",$h));
    if($meta!==""){ $body=str_replace("<div id='dohp-root' class='ak'>","<div id='dohp-root' class='ak'>$meta",$body); }
    echo dohp_style().$body;
    return true;
}

function dohp_page():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $topbuttons=array();
    if(dohp_can_write()){
        $topbuttons[]=array(dohp_edit_js(),ico_params,"{dohp_edit_settings}");
    }
    $topbuttons[]=array("LoadAjaxSilent('dohp-host','$page?overview=yes');",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_DNSDIST}: DNS-over-HTTPS (DoH)";
    $TINY_ARRAY["ICO"]="fa-solid fa-lock";
    $TINY_ARRAY["EXPL"]="{dohp_page_explain}";
    $TINY_ARRAY["URL"]="";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo $tpl->_ENGINE_parse_body("<div id='dohp-host'></div><script>LoadAjaxSilent('dohp-host','$page?overview=yes');$jstiny</script>").dohp_js();
    return true;
}

// --- Modale -------------------------------------------------------------------

function form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{dohp_modal_title}","$page?form-popup=yes",720);
    return true;
}

function form_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=dohp_settings();

    $form=array();
    // Certificat : toujours local (jamais diffuse par la politique), le formulaire n'est donc jamais entierement verrouille.
    dnsfw_meta_form_guard($tpl,dnsfw_doh_keys(),$form,false);
    // DoH est toujours servi en TLS par le Go : le champ reste force a 1.
    if(!dnsfw_meta_key_held("DNSDistDOHSSL")){ $form[]=$tpl->field_hidden("DNSDistDOHSSL",1); }
    $form[]=dnsfw_meta_ro($tpl,"DNSDistDOH","{enable_feature}",$m["enabled"]?1:0,true)
        ?? $tpl->field_checkbox("DNSDistDOH","{enable_feature}",$m["enabled"]?1:0,false,"{dohp_f_enable_help}");
    $form[]=dnsfw_meta_ro($tpl,"DNSDistDOHInterfaces","{listen_interfaces}",$m["ifaces_raw"])
        ?? $tpl->field_interfaces_choose("DNSDistDOHInterfaces","{listen_interfaces}",$m["ifaces_raw"],"{dohp_f_ifaces_help}");
    $form[]=dnsfw_meta_ro($tpl,"DNSDistDOHPort","{dohp_port}",$m["port"])
        ?? $tpl->field_numeric("DNSDistDOHPort","{dohp_port}",$m["port"],"{dohp_f_port_help}");
    $form[]=dnsfw_meta_ro($tpl,"DNSDistDOHPath","{doh_subfolder}",$m["path"])
        ?? $tpl->field_text("DNSDistDOHPath","{doh_subfolder}",$m["path"],false,"{dohp_f_path_help}");
    $form[]=$tpl->field_certificate("DNSDistSSLCertificate","{certificate}",$m["cert"],"{dohp_f_cert_help}");
    if($m["proxy_supported"]){
        $form[]=dnsfw_meta_ro($tpl,"DNSDistDOHProxyProto","{proxy_protocol}",$m["proxy"]?1:0,true)
            ?? $tpl->field_checkbox("DNSDistDOHProxyProto","{proxy_protocol}",$m["proxy"]?1:0,false,"{dohp_f_proxy_help}");
    }

    $html=array();
    $html[]=$tpl->div_explain("{dohp_modal_title}||{dohp_modal_explain}");
    if($m["proxy_supported"] && !dnsfw_meta_key_held("DNSDistDOHProxyProto")){
        $html[]=$tpl->div_warning("{dohp_proxy_warning}");
    }
    $jsafter="dialogInstance1.close();LoadAjaxSilent('dohp-host','$page?overview=yes');";
    $html[]=$tpl->form_outside("",$form,"","{dohp_apply_restart}",$jsafter,"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// Valeur effective apres l'enregistrement : postee si libre, sinon celle en place (tenue par Meta).
function dohp_effective(string $key):string{
    if(isset($_POST[$key])){ return trim((string)$_POST[$key]); }
    return trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
}

function dohp_save():bool{
    $tpl=new template_admin();
    if(!dohp_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}"); return false; }
    $tpl->CLEAN_POST();
    // Cles tenues par la politique Meta : jamais ecrites (le demon repond 423).
    $_POST=dnsfw_meta_strip_post($_POST);
    if(isset($_POST["DNSDistDOHSSL"])){ $_POST["DNSDistDOHSSL"]=1; }

    if(isset($_POST["DNSDistDOHPort"])){
        $port=intval($_POST["DNSDistDOHPort"]);
        if($port<1 || $port>65535){ echo $tpl->post_error("{dohp_err_port}"); return false; }
    }
    if(isset($_POST["DNSDistDOHPath"])){
        $path=trim((string)$_POST["DNSDistDOHPath"]);
        if($path===""){ $path="/dns-query"; }
        if(!preg_match('#^/[A-Za-z0-9/_.\-]*$#',$path)){ echo $tpl->post_error("{dohp_err_path}"); return false; }
        $_POST["DNSDistDOHPath"]=$path;
    }
    if(intval(dohp_effective("DNSDistDOH"))==1){
        // Sans certificat valide, articarest abandonne tout le bloc DoH : on refuse avant.
        $cert=dohp_effective("DNSDistSSLCertificate");
        if(strlen($cert)<2){ echo $tpl->post_error("{dohp_err_cert}"); return false; }
        $db=new CertificatesDB();
        if(!is_object($db->getByCommonName($cert))){ echo $tpl->post_error("{dohp_err_cert}"); return false; }
        if(isset($_POST["DNSDistDOHInterfaces"])){
            $has=false;
            foreach(explode(",",(string)$_POST["DNSDistDOHInterfaces"]) as $i){
                $i=trim($i);
                if($i!=="" && $i!=="lo"){ $has=true; }
            }
            if(!$has){ echo $tpl->post_error("{dohp_err_iface}"); return false; }
        }
    }
    admin_tracks_post("Save DNS load-balancing DNS-over-HTTPS settings");
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/php/restart");
    return true;
}
