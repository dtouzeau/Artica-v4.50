<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.ntpd.inc");
if(isset($_GET["ntp-server-status"])){service_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["NTPDListenInterface"])){save_config();exit;}
if(isset($_GET["flat"])){config_flat();exit;}
if(isset($_GET["config-js"])){config_js();exit;}
if(isset($_GET["clients-js"])){clients_js();exit;}
if(isset($_GET["clients-popup"])){clients_popup();exit;}
if(isset($_GET["config-popup"])){config_popup();exit;}

page();
function page(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	$ntpdv_ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydVersion");
    if(!$users->AsDebianSystem){die();}

    $html=$tpl->page_header("{APP_NTPD} $ntpdv_ver",
        "fa fa-clock","{ntp_about}","$page?tabs=yes","ntp-server",
        "progress-ntpd-restart",false,"table-loader-ntpd-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_NTPD} $ntpdv_ver",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);


}
function service_status_clients(){
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/clients"));
    if(!$json->Status){
        return $tpl->widget_rouge("{clients}",$json->Error,null,ico_computer);
    }
    if(!property_exists($json,"Info")){
        return $tpl->widget_rouge("{clients}","{error} 2",null,ico_computer);
    }
    $info = $json->Info ?? [];

    /* Normalize Info into an array of objects */
    if (is_string($info)) {
        $tmp = json_decode($info);
        if (is_array($tmp))    { $info = $tmp; }
        elseif (is_object($tmp)) { $info = array_values(get_object_vars($tmp)); }
    }
    elseif (is_object($info)) {
        $info = array_values(get_object_vars($info));
    }
    elseif (!is_array($info)) {
        $info = []; // anything else => empty
    }

    $c = 0;
    foreach ($info as $client) {
        if (!is_object($client)) { continue; }
        $hostname = $client->hostname ?? $client->name ?? '';
        if ($hostname === '') { continue; }
        $c++;
    }

    if($c==0){
        return $tpl->widget_grey("{clients}","0",null,ico_computer);
    }
    $page=CurrentPageName();
    $btn[]=array("name"=>"{clients}","js"=>"Loadjs('$page?clients-js=yes')","icon"=>ico_loupe,"color"=>null);
    return $tpl->widget_vert("{clients}",$c,$btn,ico_computer);
}

function service_status():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $tpl=new template_admin();
    $page=CurrentPageName();


    $jsrestart=$tpl->framework_buildjs("/chrony/restart",
        "ntpd.progress",
        "ntpd.progress.log",
        "progress-ntpd-restart",
        "");

    $html[]=service_status_clients();
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_NTPD",$jsrestart);

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function status(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$html[]="<table style='width:100%;margin-top:20px'>
	<tr>
		<td style='vertical-align: top;width:350px'><div id='ntp-server-status'></div></td>
		<td style='vertical-align: top;width:99%'><div id='NtpConfgFlat'></div></td>";

    $html[]="</tr></table>";
    $js=$tpl->RefreshInterval_js("ntp-server-status",$page,"ntp-server-status=yes");
    $html[]="<script>LoadAjax('NtpConfgFlat','$page?flat=yes');$js</script>";

	echo $tpl->_ENGINE_parse_body($html);
	
}

// POST de la fenetre Parameters : droit verifie cote serveur, quatre cles connues
// seulement (SAVE_POSTs enregistrait tout champ poste), valeurs validees.
function save_config(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        admin_tracks("NTP settings refused: insufficient privileges");
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
        return;
    }
    $tpl->CLEAN_POST();
    $iface=trim((string)($_POST["NTPDListenInterface"] ?? ""));
    if($iface!="" && !preg_match('/^[A-Za-z0-9_.:@-]{1,32}$/',$iface)){
        echo $tpl->_ENGINE_parse_body("{ntfl_bad_iface}");
        return;
    }
    $pool=trim((string)($_POST["NTPClientDefaultServerList"] ?? ""));
    $ntp=new ntpd();
    $pools=$ntp->ServersList();
    if($pool=="" || !isset($pools[$pool])){$pool="Generic";}
    $specified=intval($_POST["NTPDUseSpecifiedServers"] ?? 0)==1?1:0;
    $restrict=intval($_POST["NTPDRestrictToLocalNetwork"] ?? 0)==1?1:0;
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $sock->SET_INFO("NTPDListenInterface",$iface);
    $sock->SET_INFO("NTPDUseSpecifiedServers",$specified);
    $sock->SET_INFO("NTPDRestrictToLocalNetwork",$restrict);
    $sock->SET_INFO("NTPClientDefaultServerList",$pool);
    admin_tracks("NTP settings saved: listen=".($iface==""?"loopback":$iface).", specified servers=$specified, restrict to local networks=$restrict, pool=$pool");
}
// ---------------------------------------------------------------------------
// Colonne de droite de l'onglet Status (maquette stitch_ntp_status_dashboard_redesign).
// La colonne d'etat du service a gauche (ntp-server-status) n'est pas touchee.
// Ecarte de la maquette : « Declared subnets … Access enforced » (la page ne lit
// que l'interrupteur), « policy #CL-8802 », adresse du repartiteur (non exposee ici).
// ---------------------------------------------------------------------------
function ntfl_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}
function ntfl_t(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=trim(str_replace(array("[br]","<br>"),array(" "," "),$tpl->_ENGINE_parse_body("{".$token."}")));
    foreach($args as $a){$p=strpos($s,"%s");if($p===false){break;}$s=substr_replace($s,(string)$a,$p,2);}
    return $s;
}
function ntfl_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array("cluster"=>intval($sock->GET_INFO("HaClusterClient"))==1,"edit"=>false,
        "listen"=>trim((string)$sock->GET_INFO("NTPDListenInterface")),"listen_label"=>"","listen_ip"=>"",
        "restrict"=>1,"specified"=>intval($sock->GET_INFO("NTPDUseSpecifiedServers"))==1,
        "pool"=>trim((string)$sock->GET_INFO("NTPClientDefaultServerList")),"servers"=>array(),
        "src_ok"=>false,"sources"=>array(),"sel"=>null,"in_use"=>0,"backup"=>0,"unreach"=>0,"other"=>0,
        "clients"=>0,"requests"=>0,"addr"=>(string)($_SERVER["SERVER_ADDR"] ?? ""));
    $users=new usersMenus();
    $m["edit"]=(bool)$users->AsSystemAdministrator && !$m["cluster"];
    if($sock->INFO_EXISTS("NTPDRestrictToLocalNetwork")){$m["restrict"]=intval($sock->GET_INFO("NTPDRestrictToLocalNetwork"));}
    if($m["pool"]==""){$m["pool"]="Generic";}
    if($m["listen"]!=""){
        $nic=new system_nic($m["listen"]);
        $m["listen_label"]=trim((string)$nic->NICNAME);
        $m["listen_ip"]=trim((string)$nic->IPADDR);
        if(filter_var($m["listen_ip"],FILTER_VALIDATE_IP)){$m["addr"]=$m["listen_ip"];}
    }
    if($m["specified"]){
        $j=json_decode((string)$sock->REST_API("/chrony/servers"),true);
        if(is_array($j) && isset($j["Servers"]) && is_array($j["Servers"])){
            $list=$j["Servers"];
            usort($list,function($a,$b){return intval($a["Order"] ?? 0)<=>intval($b["Order"] ?? 0);});
            foreach($list as $s){$n=trim((string)($s["Server"] ?? ""));if($n!=""){$m["servers"][]=$n;}}
        }
    }else{
        $ntp=new ntpd();
        $all=$ntp->ServersList();
        if(isset($all[$m["pool"]]) && is_array($all[$m["pool"]])){$m["servers"]=array_values($all[$m["pool"]]);}
    }
    $j=json_decode((string)$sock->REST_API("/chrony/srvstatus"),true);
    if(is_array($j) && !empty($j["Status"]) && isset($j["Info"]) && is_array($j["Info"])){
        $m["src_ok"]=true;
        foreach($j["Info"] as $p){
            if(!is_array($p)){continue;}
            $st=(string)($p["status"] ?? "?");
            $m["sources"][]=$p;
            if($st=="*"){$m["in_use"]++;$m["sel"]=$p;}
            elseif($st=="+" || $st=="-"){$m["backup"]++;}
            elseif($st=="?"){$m["unreach"]++;}
            else{$m["other"]++;}
        }
    }
    $j=json_decode((string)$sock->REST_API("/chrony/clients"),true);
    if(is_array($j) && !empty($j["Status"]) && isset($j["Info"]) && is_array($j["Info"])){
        foreach($j["Info"] as $c){
            if(!is_array($c) || trim((string)($c["hostname"] ?? ""))==""){continue;}
            $m["clients"]++;
            $m["requests"]+=intval($c["requests"] ?? 0);
        }
    }
    return $m;
}
function ntfl_secs(float $s,bool $sign):string{
    $a=abs($s);
    if($a>=1){$v=sprintf("%.2f s",$a);}
    elseif($a>=0.001){$v=sprintf("%.1f ms",$a*1000);}
    else{$v=sprintf("%d µs",round($a*1000000));}
    return ($sign?($s<0?"−":"+"):"").$v;
}
function ntfl_ago(int $s):string{
    if($s<0){return "";}
    if($s<90){return ntfl_t("ntfl_ago_s",array($s));}
    if($s<5400){return ntfl_t("ntfl_ago_m",array(intval(round($s/60))));}
    if($s<172800){return ntfl_t("ntfl_ago_h",array(intval(round($s/3600))));}
    return ntfl_t("ntfl_ago_d",array(intval(round($s/86400))));
}
function ntfl_poll(int $p):string{
    $s=$p>=0 && $p<31?(1<<$p):0;
    if($s<=0){return "";}
    if($s<120){return ntfl_t("ntfl_every_s",array($s));}
    return ntfl_t("ntfl_every_m",array(intval(round($s/60))));
}
function ntfl_banner(array $m):string{
    $c=array();
    $n=count($m["servers"]);
    if($m["cluster"]){
        $c[]="<span class='ntfl-pill'><i class='fas fa-code-branch'></i>{lb_ipaddr}</span>";
    }elseif($m["specified"]){
        $c[]="<span class='ntfl-pill'><i class='fas fa-earth-europe'></i>".ntfl_h(ntfl_t($n==1?"ntfl_chip_own1":"ntfl_chip_own",array($n)))."</span>";
    }elseif($n>0){
        $c[]="<span class='ntfl-pill'><i class='fas fa-earth-europe'></i>".ntfl_h(ntfl_t("ntfl_chip_pool",array($n,$m["pool"])))."</span>";
    }
    if(!$m["cluster"]){
        $c[]=$m["restrict"]?"<span class='ntfl-pill'><i class='fas fa-shield-halved'></i>{ntfl_chip_local}</span>"
            :"<span class='ntfl-pill warn'><i class='fas fa-shield-halved'></i>{ntfl_chip_open}</span>";
    }
    if($m["clients"]>0){
        $c[]="<span class='ntfl-pill'><i class='fas fa-desktop'></i>".ntfl_h(ntfl_t($m["clients"]==1?"ntfl_chip_clients1":"ntfl_chip_clients",array($m["clients"])))."</span>";
    }
    return "<section class='ntfl-card ntfl-banner'><span class='ntfl-bico' aria-hidden='true'><i class='fas fa-clock'></i></span>"
        ."<div class='ntfl-btxt'><h2>{ntfl_banner_title}</h2><p>{ntfl_banner_text}</p><div class='ntfl-chips'>".implode("",$c)."</div></div></section>";
}
function ntfl_health(array $m):string{
    $h=array("<section class='ntfl-card ntfl-health'>");
    if(!$m["src_ok"]){
        $h[]="<div class='ntfl-hz'><span class='ntfl-state warn'><i class='fas fa-circle-question'></i>{ntfl_unknown}</span><p class='ntfl-help'>{ntfl_unknown_help}</p></div></section>";
        return implode("",$h);
    }
    $sel=$m["sel"];
    $total=count($m["sources"]);
    if($sel===null){
        $allDown=$total>0 && $m["unreach"]==$total;
        $cls=$allDown?"err":"warn";
        $ico=$allDown?"fa-circle-xmark":"fa-triangle-exclamation";
        $title=$allDown?"{ntfl_all_unreach}":"{ntfl_not_sync}";
        $h[]="<div class='ntfl-hz ntfl-hz-main'><span class='ntfl-state $cls'><i class='fas $ico'></i>$title</span><p class='ntfl-help'>{ntfl_not_sync_help}</p></div>";
    }else{
        $off=floatval($sel["offset_s"] ?? 0);
        $err=floatval($sel["error_s"] ?? ($sel["jitter_s"] ?? 0));
        $rx=intval($sel["last_rx"] ?? -1);
        $line=ntfl_t("ntfl_offset_line",array(ntfl_secs($off,true),ntfl_secs($err,false)));
        if($rx>=0){$line.=" · ".ntfl_t("ntfl_checked",array(ntfl_ago($rx)));}
        $tone=abs($off)<0.1?"ok":(abs($off)<1?"warn":"err");
        $h[]="<div class='ntfl-hz ntfl-hz-main'><span class='ntfl-state ok'><i class='fas fa-circle-check'></i>{ntfl_in_sync}</span>"
            ."<div class='ntfl-off $tone'>".ntfl_h($line)."</div><p class='ntfl-help'>{ntfl_offset_help}</p></div>";
        $name=trim((string)($sel["name"] ?? ""));
        if($name==""){$name=trim((string)($sel["address"] ?? ""));}
        $ip=trim((string)($sel["ip"] ?? ""));
        $reach=intval($sel["reach"] ?? 0)&255;
        $ok=0;for($i=0;$i<8;$i++){if($reach&(1<<$i)){$ok++;}}
        $poll=ntfl_poll(intval($sel["poll"] ?? -1));
        $h[]="<div class='ntfl-hz'><div class='ntfl-lbl'>{ntfl_current_source}</div><div class='ntfl-src'><b class='ntfl-mono'>".ntfl_h($name)."</b>"
            .($ip!="" && $ip!=$name?" <span class='ntfl-mono ntfl-mute'>(".ntfl_h($ip).")</span>":"")."</div>"
            ."<div class='ntfl-meta'><span class='ntfl-tag'>".ntfl_h(ntfl_t("ntfl_stratum",array(intval($sel["stratum"] ?? 0))))."</span>"
            ."<span class='ntfl-tag' title='{ntfl_reach_help}'>".ntfl_h(ntfl_t("ntfl_reach",array($ok)))."</span>"
            .($poll!=""?"<span class='ntfl-mute'>".ntfl_h($poll)."</span>":"")."</div><p class='ntfl-help'>{ntfl_stratum_help}</p></div>";
    }
    $parts=array(ntfl_t("ntfl_n_inuse",array($m["in_use"])),ntfl_t("ntfl_n_backup",array($m["backup"])));
    if($m["unreach"]>0){$parts[]=ntfl_t("ntfl_n_unreach",array($m["unreach"]));}
    if($m["other"]>0){$parts[]=ntfl_t("ntfl_n_other",array($m["other"]));}
    $h[]="<div class='ntfl-hz ntfl-hz-end'><div class='ntfl-lbl'>".ntfl_h(ntfl_t("ntfl_n_sources",array($total)))."</div><div class='ntfl-counts'>".ntfl_h(implode(" · ",$parts))."</div>"
        ."<a class='ntfl-link' href='javascript:blur();' OnClick='NtflTab(1)'>{ntfl_see_sources} <i class='fas fa-arrow-right'></i></a></div>";
    $h[]="</section>";
    return implode("",$h);
}
function ntfl_servers_card(array $m):string{
    $btn=$m["edit"]?"<button type='button' class='ntfl-btn sec' OnClick=\"Loadjs('fw.system.ntp.servers.php')\"><i class='fas fa-list'></i> {ntp_servers}</button>":"";
    $h=array("<section class='ntfl-card'><div class='ntfl-ch'><div><h3><i class='fas fa-earth-europe'></i> {ntfl_servers_title}</h3><p>{ntfl_servers_sub}</p></div>$btn</div><div class='ntfl-cb'>");
    $h[]="<div class='ntfl-mode'>".($m["specified"]?"{ntfl_mode_own}":ntfl_h(ntfl_t("ntfl_mode_pool",array($m["pool"]))))."</div>";
    $n=count($m["servers"]);
    if($n==0){
        $h[]="<p class='ntfl-none'>".($m["specified"]?"{ntfl_own_empty}":"—")."</p>";
    }elseif($m["specified"]){
        $h[]="<ol class='ntfl-ol'>";
        foreach($m["servers"] as $i=>$s){$h[]="<li><span class='ntfl-num'>".($i+1)."</span><span class='ntfl-chip ntfl-mono'>".ntfl_h($s)."</span></li>";}
        $h[]="</ol>";
    }else{
        $h[]="<div class='ntfl-chips2'>";
        foreach(array_slice($m["servers"],0,6) as $s){$h[]="<span class='ntfl-chip ntfl-mono'>".ntfl_h($s)."</span>";}
        if($n>6){$h[]="<span class='ntfl-chip' title=\"".ntfl_h(implode(", ",array_slice($m["servers"],6)))."\">+".($n-6)."</span>";}
        $h[]="</div>";
    }
    $h[]="<p class='ntfl-help'>{ntfl_pool_help}</p></div></section>";
    return implode("",$h);
}
function ntfl_sharing_card(array $m):string{
    $page=CurrentPageName();
    $btn=$m["edit"]?"<button type='button' class='ntfl-btn' OnClick=\"Loadjs('$page?config-js=yes')\"><i class='fas fa-pen'></i> {ntfl_edit}</button>":"";
    if($m["listen"]==""){
        $lv="<span class='ntfl-chip'>{ntfl_loopback}</span>";
    }else{
        $lbl=$m["listen_label"]!=""?$m["listen_label"]:$m["listen"];
        $lv="<span class='ntfl-chip ntfl-mono'>".ntfl_h($lbl.($m["listen_ip"]!=""?" – ".$m["listen_ip"]:""))."</span>";
    }
    $rv=$m["restrict"]?"<span class='ntfl-onoff on'><i class='fas fa-check'></i>{ntfl_on}</span>":"<span class='ntfl-onoff off'><i class='fas fa-minus'></i>{ntfl_off}</span>";
    $h=array("<section class='ntfl-card'><div class='ntfl-ch'><div><h3><i class='fas fa-network-wired'></i> {ntfl_share_title}</h3><p>{ntfl_share_sub}</p></div>$btn</div>");
    $h[]="<div class='ntfl-row'><div><div class='ntfl-rl'>{listen_interface}</div><p class='ntfl-help'>{ntfl_listen_help}</p></div><div class='ntfl-rv'>$lv</div></div>";
    $h[]="<div class='ntfl-row'><div><div class='ntfl-rl'>{NTPDRestrictToLocalNetwork}</div><p class='ntfl-help'>{ntfl_restrict_help}</p></div><div class='ntfl-rv'>$rv</div></div>";
    if($m["listen"]==""){
        $h[]="<div class='ntfl-info'><i class='fas fa-circle-info'></i><span>{ntfl_loop_info}</span>"
            .($m["edit"]?"<a class='ntfl-link' href='javascript:blur();' OnClick=\"Loadjs('$page?config-js=yes')\">{ntfl_edit}</a>":"")."</div>";
    }
    $h[]="</section>";
    return implode("",$h);
}
function ntfl_windows_card(array $m):string{
    if($m["listen"]=="" || $m["addr"]==""){
        return "<p class='ntfl-note'><i class='fas fa-brands fa-windows'></i> {ntfl_win_later}</p>";
    }
    $ip=ntfl_h($m["addr"]);
    $cmds=array("w32tm /config /manualpeerlist:\"".$m["addr"].",0x1\" /syncfromflags:manual /reliable:YES /update","net stop w32time","net start w32time","w32tm /resync");
    $txt=implode("\n",$cmds);
    $lines=array();
    foreach($cmds as $c){$lines[]="<span class='ntfl-cl'>".ntfl_h($c)."</span>";}
    return "<section class='ntfl-card'><div class='ntfl-ch'><div><h3><i class='fab fa-windows'></i> {ntfl_win_title}</h3><p>".ntfl_h(ntfl_t("ntfl_win_sub",array($m["addr"])))."</p></div>"
        ."<button type='button' class='ntfl-btn sec' aria-label='{ntfl_copy}' data-cmd=\"".htmlspecialchars($txt,ENT_QUOTES)."\" OnClick='NtflCopy(this)'><i class='fas fa-copy'></i> <span data-ok='{ntfl_copied}'>{ntfl_copy}</span></button></div>"
        ."<div class='ntfl-cb'><pre class='ntfl-code'>".implode("",$lines)."</pre><p class='ntfl-help'>{ntfl_win_note}</p></div></section>";
}
function ntfl_cluster_card():string{
    $row=function($l,$v){return "<div class='ntfl-row'><div class='ntfl-rl'>$l</div><div class='ntfl-rv'><span class='ntfl-chip'>$v</span></div></div>";};
    return "<section class='ntfl-card'><div class='ntfl-cluster'><i class='fas fa-code-branch'></i><span>{ntfl_cluster_text}</span></div>"
        .$row("{listen_interface}","{all}").$row("{use_specified_time_servers}","{lb_ipaddr}").$row("{NTPDRestrictToLocalNetwork}","{ntfl_no_restrict}")."</section>";
}
function ntfl_js():string{
    return "<script>NoSpinner();window.NtflTab=function(n){var h=document.getElementById('NtpConfgFlat');var c=h?h.closest('.tabs-container'):null;var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>n){a[n].click();}};"
        ."window.NtflCopy=function(b){var t=b.getAttribute('data-cmd');var s=b.querySelector('span');var done=function(){if(!s){return;}var o=s.textContent;s.textContent=s.getAttribute('data-ok')||o;setTimeout(function(){s.textContent=o;},2000);};"
        ."var old=function(){var x=document.createElement('textarea');x.value=t;x.style.position='fixed';x.style.opacity='0';document.body.appendChild(x);x.select();try{document.execCommand('copy');}catch(e){}document.body.removeChild(x);done();};"
        ."if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(t).then(done,old);}else{old();}};</script>";
}
function ntfl_style():string{
    return "<style>
.ntfl{display:flex;flex-direction:column;gap:16px;color:#1f2937;min-width:0}
.ntfl-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);min-width:0}
.ntfl-banner{display:flex;gap:18px;align-items:flex-start;padding:20px 22px;border-left:4px solid #005447}
.ntfl-bico{flex:0 0 48px;height:48px;border-radius:12px;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:20px}
.ntfl-btxt{min-width:0}
.ntfl-btxt h2{margin:0 0 6px 0;font-size:18px;font-weight:600;color:#111827}
.ntfl-btxt p{margin:0;font-size:14px;color:#4b5563;line-height:1.55}
.ntfl-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.ntfl-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 12px;border-radius:999px;background:#f2f6f6;border:1px solid #e6eeed;color:#005447;font-size:13px}
.ntfl-pill.warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.ntfl-health{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1.2fr) minmax(0,.9fr);gap:0}
.ntfl-hz{padding:16px 20px;border-left:1px solid #eef0f3;min-width:0;display:flex;flex-direction:column;gap:6px}
.ntfl-hz:first-child{border-left:0}
.ntfl-state{display:inline-flex;align-items:center;gap:8px;font-size:18px;font-weight:600}
.ntfl-state.ok{color:#15803d}.ntfl-state.warn{color:#b45309}.ntfl-state.err{color:#b91c1c}
.ntfl-off{font-size:14px;font-weight:600;font-variant-numeric:tabular-nums}
.ntfl-off.ok{color:#111827}.ntfl-off.warn{color:#b45309}.ntfl-off.err{color:#b91c1c}
.ntfl-help{margin:0;font-size:13px;color:#6b7280;line-height:1.5}
.ntfl-lbl{font-size:13px;font-weight:600;color:#4b5563;text-transform:uppercase;letter-spacing:.04em}
.ntfl-src{font-size:14px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ntfl-meta{display:flex;flex-wrap:wrap;gap:6px;align-items:center;font-size:13px}
.ntfl-tag{font-size:13px;padding:1px 8px;border-radius:999px;background:#f3f4f6;border:1px solid #e5e7eb;color:#374151}
.ntfl-mono{font-family:monospace;font-size:13px}
.ntfl-mute{color:#6b7280}
.ntfl-counts{font-size:14px;color:#111827}
.ntfl-link{color:#005447;font-weight:600;font-size:13px;text-decoration:underline;cursor:pointer}
.ntfl-link:hover{color:#003d33}
.ntfl-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;align-items:start}
.ntfl-ch{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:16px 20px 12px;border-bottom:1px solid #eef0f3}
.ntfl-ch h3{margin:0;font-size:15px;font-weight:600;color:#111827;display:flex;gap:8px;align-items:center}
.ntfl-ch h3 i,.ntfl-ch h3 svg{color:#005447}
.ntfl-ch p{margin:3px 0 0 0;font-size:13px;color:#6b7280}
.ntfl-cb{padding:14px 20px 16px;display:flex;flex-direction:column;gap:10px}
.ntfl-mode{font-size:14px;font-weight:600;color:#111827}
.ntfl-chips2{display:flex;flex-wrap:wrap;gap:6px}
.ntfl-chip{display:inline-block;padding:2px 9px;border-radius:6px;background:#f3f4f6;color:#111827;font-size:13px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle}
.ntfl-ol{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px}
.ntfl-ol li{display:flex;align-items:center;gap:10px}
.ntfl-num{flex:0 0 22px;height:22px;border-radius:50%;background:#f3f4f6;color:#4b5563;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center}
.ntfl-none{margin:0;color:#6b7280;font-size:13px}
.ntfl-row{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:12px 20px;border-top:1px solid #f1f3f5}
.ntfl-ch+.ntfl-row{border-top:0}
.ntfl-row>div:first-child{min-width:0}
.ntfl-rl{font-size:14px;font-weight:600;color:#111827}
.ntfl-rv{flex:0 0 auto;max-width:55%;text-align:right}
.ntfl-onoff{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:600}
.ntfl-onoff.on{background:#e6eeed;color:#005447}.ntfl-onoff.off{background:#f3f4f6;color:#4b5563}
.ntfl-info{display:flex;gap:10px;align-items:center;margin:0 20px 16px;padding:10px 14px;border-radius:8px;background:#f2f6f6;border:1px solid #e6eeed;font-size:13px;color:#374151}
.ntfl-info i,.ntfl-info svg{color:#005447;flex:0 0 auto}
.ntfl-info span{flex:1 1 auto}
.ntfl-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid #005447;background:#005447;color:#fff;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;flex:0 0 auto}
.ntfl-btn:hover{background:#003d33;border-color:#003d33}
.ntfl-btn.sec{background:#fff;color:#005447;border-color:#bfd4d1}
.ntfl-btn.sec:hover{background:#e6eeed;color:#003d33}
.ntfl-code{margin:0;padding:12px 14px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;font-family:monospace;font-size:13px;color:#111827;white-space:pre-wrap;word-break:break-all;line-height:1.6}
.ntfl-cl{display:block}
.ntfl-cl:before{content:'> ';color:#9ca3af}
.ntfl-note{margin:0;font-size:13px;color:#6b7280}
.ntfl-cluster{display:flex;gap:10px;align-items:flex-start;padding:14px 20px;background:#f8fafc;border-bottom:1px solid #eef0f3;border-radius:12px 12px 0 0;font-size:14px;color:#374151}
.ntfl-cluster i,.ntfl-cluster svg{color:#005447;margin-top:3px}
.ntfl button:focus-visible,.ntfl a:focus-visible{outline:2px solid #005447;outline-offset:2px}
@media(max-width:1200px){.ntfl-grid{grid-template-columns:minmax(0,1fr)}.ntfl-health{grid-template-columns:minmax(0,1fr)}.ntfl-hz{border-left:0;border-top:1px solid #eef0f3}.ntfl-hz:first-child{border-top:0}}
@media(prefers-reduced-motion:no-preference){.ntfl-card{animation:ntflIn .15s ease-out}}
@keyframes ntflIn{from{opacity:0}to{opacity:1}}
</style>";
}
function ntfl_tiny():string{
    $users=new usersMenus();
    $btns=null;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"))!=1){
        $tpl=new template_admin();
        $top=array();
        $top[]=array("Loadjs('fw.system.ntp.servers.php')","fa-solid fa-list","{ntp_servers}");
        if($users->AsSystemAdministrator){$top[]=array("Loadjs('".CurrentPageName()."?config-js=yes')",ico_params,"{parameters}");}
        $btns=$tpl->table_buttons($top);
    }
    $ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChronydVersion");
    $T=array("TITLE"=>"{APP_NTPD} $ver","ICO"=>"fa fa-clock","EXPL"=>"{ntfl_header_explain}","URL"=>"ntp-server","BUTTONS"=>$btns);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($T)))."');</script>";
}
function config_flat():bool{
    $tpl=new template_admin();
    $m=ntfl_model();
    $h=array("<div class='ntfl'>",ntfl_banner($m),ntfl_health($m));
    if($m["cluster"]){
        $h[]=ntfl_cluster_card();
    }else{
        $h[]="<div class='ntfl-grid'>".ntfl_servers_card($m).ntfl_sharing_card($m)."</div>";
        $h[]=ntfl_windows_card($m);
    }
    $h[]="</div>";
    echo ntfl_style().$tpl->_ENGINE_parse_body(implode("",$h)).ntfl_js().$tpl->_ENGINE_parse_body(ntfl_tiny());
    return true;
}

function clients_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{clients}","$page?clients-popup=yes",850);
}
function config_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return $tpl->js_dialog2("{parameters}","$page?config-popup=yes");
}

function config_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ntp=new ntpd();

    $sserv=$ntp->ServersList();
    foreach ($sserv as $num=>$val){
        $ServersList[$num]=$num;

    }

    if(!$GLOBALS["CLASS_SOCKETS"]->INFO_EXISTS("NTPDRestrictToLocalNetwork")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("NTPDRestrictToLocalNetwork", 1);}

    $NTPDUseSpecifiedServers=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDUseSpecifiedServers"));
    $NTPDRestrictToLocalNetwork=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDRestrictToLocalNetwork"));
    $NTPClientDefaultServerList=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPClientDefaultServerList");
    $NTPDListenInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDListenInterface");
    // Meme defaut que le daemon (chronyd/Configurator.go) et que l'affichage.
    if($NTPClientDefaultServerList==null){$NTPClientDefaultServerList="Generic";}


    $jsrestart=$tpl->framework_buildjs("/chrony/reconfigure",
        "ntpd.progress",
        "ntpd.progress.log",
        "progress-ntpd-restart",
        "LoadAjax('table-loader-ntpd-service','$page?tabs=yes');");

    $form[]=$tpl->field_interfaces("NTPDListenInterface", "{listen_interface}", $NTPDListenInterface);
    $form[]=$tpl->field_checkbox("NTPDUseSpecifiedServers","{use_specified_time_servers}",$NTPDUseSpecifiedServers);
    $form[]=$tpl->field_checkbox("NTPDRestrictToLocalNetwork","{NTPDRestrictToLocalNetwork}",$NTPDRestrictToLocalNetwork,false,"{NTPDRestrictToLocalNetwork_explain}");
    $form[]=$tpl->field_array_hash($ServersList, "NTPClientDefaultServerList", "{default_ntp_servers}", $NTPClientDefaultServerList);


    $html[]=$tpl->form_outside("", $form,null,"{apply}",
        "dialogInstance2.close();LoadAjax('table-loader-ntpd-service','$page?tabs=yes');$jsrestart","AsSystemAdministrator");



    echo $tpl->_ENGINE_parse_body($html);
}


function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	
	$array["{status}"]="$page?status=yes";
	$array["{ntp_servers} {status}"]="fw.system.ntpd.servers.status.php?list=yes";
	$array["{events}"]="fw.system.ntpd.events.php";

	
	echo $tpl->tabs_default($array);	
	
}
function clients_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/clients"));

    $html[]="<table id='table-ntpd-servers-status' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{clients}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{queries}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{since}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;


    $datas=$json->Info;
    $ipClass=new IP();
    foreach ($datas as $line){

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $hostname=$line->hostname;
        $requests=$tpl->FormatNumber($line->requests);
        $sincesec=intval($line->sincesec);
        $sincesec=time()-$sincesec;
        $last_rx_text = distanceOfTimeInWords($sincesec, time(),true);
        $md=md5(json_encode($line));
        $ico="<i class='".ico_computer."'></i>";
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td>$ico&nbsp;$hostname</td>";
        $html[]="<td style='width:1%' nowrap>$requests</td>";
        $html[]="<td style='width:1%' nowrap>$last_rx_text</td>";
        $html[]="</tr>";
    }


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}