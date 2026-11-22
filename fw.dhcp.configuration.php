<?php
// stamp 01/11/2026
if(isset($_POST["none"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.dhcpd.meta.inc");
if(isset($_POST["none"])){die();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["dhcprelay-new-js"])){dhcp_relay_new_js();exit;}
if(isset($_GET["dhcprelay-new-popup"])){dhcp_relay_new_popup();exit;}
if(isset($_POST["DHCPRelayNew"])){dhcp_relay_new_save();exit;}

if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["dcfg-toggle"])){dcfg_toggle();exit;}
if(isset($_GET["table-config"])){table_config();exit;}
if(isset($_POST["AgentInterface"])){DHCPAgent_save();exit;}
if(isset($_GET["AsDHCPAgent"])){AsDHCPAgent();exit;}
if(isset($_GET["dcfg-mode-ask"])){dcfg_mode_ask();exit;}
if(isset($_GET["interface"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["router-interface"])){gateway_save();exit;}
if(isset($_GET["gateway-js"])){gateway_js();exit;}
if(isset($_GET["gateway-popup"])){gateway_popup();exit;}
if(isset($_POST["range1"])){dhcp_save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_GET["flush-leases"])){flush_leases_ask();exit;}
if(isset($_GET["flush-leases-confirm"])){flush_leases_confirm();exit;}
if(isset($_GET["spt-js"])){support_tool_js();exit;}
if(isset($_GET["start"])){page_start();exit;}
if(isset($_GET["spopup-interface"])){spopup_interface();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");

    $html=$tpl->page_header("{APP_DHCP} v$DHCPDVersion","fas fa-cogs","{EnableDHCPServer_text}",
        "$page?start=yes","dhcp-config","progress-dhcp-restart",false,"table-start-dhcp-service");


	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_DHCP} v$DHCPDVersion",$html);
		echo $tpl->build_firewall();
		return true;
	}
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dhcp_relay_new_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog3("{new_dhcp_relay_rule}","$page?dhcprelay-new-popup=yes");
}
function dhcp_relay_new_popup():bool{
    $security = "ASDCHPAdmin";
    $tpl=new template_admin();
    $page=CurrentPageName();
    $form[]=$tpl->field_hidden("DHCPRelayNew","yes");
    $form[]=$tpl->field_interfaces("interface","{listen_interface}");
    $form[]=$tpl->field_text("rulename","{rulename}","",true);
    $html[]=$tpl->form_outside("", $form,null,"{create}","dialogInstance3.close();LoadAjax('kea-lists','$page?interfaces=yes');",$security);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function dhcp_relay_new_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $interface=$_POST["interface"];

    if($interface=="lo") {
        echo $tpl->post_error("lo Incorrect");
        return false;
    }
    if($interface=="") {
        echo $tpl->post_error("Interface Null Incorrect");
        return false;
    }

    $rulename=$_POST["rulename"];
    // Pentest 2026-11-04 : interface/rulename etaient concatenes dans la requete.
    $q->QUERY_SQL_PARAMS("INSERT INTO dhcpd_relays (interface,rulename) VALUES (?,?)",array($interface,$rulename));
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    return admin_tracks("Create a new DHCP relay rule $rulename for $interface");
}
// The interfaces panel (?interfaces=yes): one card per DHCP-serving
// interface and per relay rule. Design: /home/claude/images/stitch_dhcp_parameters.zip.
// Each card: state badge, the "Distribution" switch (service_enabled; saved
// only, applied by the {reconfigure} header button like every other save of
// this page), the edit button (existing spopup-interface modal), the range,
// the listening mode, gateway/DNS/lease, the flags, and the pool occupancy.
function interfaces():bool{
    $net=new networking();
    $interfaces=$net->Local_interfaces();
    $tpl=new template_admin();
    include_once(dirname(__FILE__)."/ressources/class.dhcpmetrics.inc");

    $occupancy=array();
    $pools=dhcpm_api("/dhcpd/metrics/pools",array("range"=>"1h"));
    if($pools!==null && $pools->success && isset($pools->data->current->samples) && is_array($pools->data->current->samples)){
        foreach($pools->data->current->samples as $s){$occupancy[(string)$s->range]=$s;}
    }
    $reservations=dcfg_reservations_count();

    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    $cards=array(); $c=0;
    foreach ($interfaces as $interface=>$Ipaddress){
        $nic=new system_nic($interface);
        // Pentest 2026-11-04 : interface etait concatene dans la requete.
        $results=$q->QUERY_SQL_PARAMS("SELECT ID,rulename FROM dhcpd_relays WHERE interface=?",array($interface));
        if($results){
            foreach ($results as $ligne){
                $interfaceR="Relay:{$ligne["ID"]}";
                $dhcpSock=new dhcp_socks($interfaceR);
                $dhcpd=new dhcpd(0,1,$interfaceR);
                $filter=array();
                foreach(array("RelayAddress","CircuitID","AgentID") as $k){
                    $v=trim((string)$dhcpSock->GET($k));
                    if(strlen($v)>1 && !in_array($v,$filter,true)){$filter[]=$v;}
                }
                $cards[]=dcfg_card(array(
                    "id"=>$interfaceR,"kind"=>"relay","title"=>$ligne["rulename"],"enabled"=>intval($dhcpSock->GET("service_enabled")),
                    "sub"=>"{nic}: <strong>".dcfg_h($interface)."</strong>",
                    "mode_title"=>"{dcfg_relay_filter}","mode_value"=>count($filter)?"(".dcfg_h(implode(" and ",$filter)).")":"-","mode_note"=>"{receive_dhcp_requests_relay}",
                ),$dhcpd,$occupancy,$reservations);
                $c++;
            }
        }
        $dhcpd=new dhcpd(0,1,$interface);
        $dhcpSock=new dhcp_socks($interface);
        $ip=trim((string)$nic->IPADDR);
        $sub=($ip==""||$ip=="0.0.0.0")?"":"{tcp_address}: <strong>".dcfg_h($ip).(trim((string)$nic->NETMASK)!=""?"/".dcfg_h($nic->NETMASK):"")."</strong>";
        if(intval($dhcpSock->GET("AsDHCPAgent"))==1 && $dhcpd->service_enabled==1){
            $cards[]=dcfg_card(array(
                "id"=>$interface,"kind"=>"agent","title"=>"$nic->NICNAME ($interface)","enabled"=>1,"sub"=>$sub,
                "mode_title"=>"{APP_DHCP_RELAY}","mode_value"=>"Circuit: ".dcfg_h($dhcpSock->GET("CircuitID")),
                "mode_note"=>"{dcfg_forwards_to} ".dcfg_h($dhcpSock->GET("DHCPServers")),
            ),$dhcpd,$occupancy,$reservations);
            $c++;
            continue;
        }
        $cards[]=dcfg_card(array(
            "id"=>$interface,"kind"=>"direct","title"=>"$nic->NICNAME ($interface)","enabled"=>$dhcpd->service_enabled,"sub"=>$sub,
            "mode_title"=>"{dcfg_listen_mode}","mode_value"=>"{dcfg_direct_l2}","mode_note"=>"{receive_dhcp_requests_directly}",
        ),$dhcpd,$occupancy,$reservations);
        if($dhcpd->service_enabled==1){$c++;}
    }

    $html=array();
    if($c==0){$html[]=$tpl->div_error("{err_dhcp_nocard}");}
    $html[]="<div class='dcfg'>".implode("\n",$cards)."</div>";
    echo dcfg_style().$tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

function dcfg_h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8");}

// Fixed-address reservations (hostsnet.dhcpfixed=1, what fw.dhcp.reservations.php lists).
function dcfg_reservations_count():int{
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"))==1){return -1;}
    $q=new postgres_sql();
    // QUERY_SQL returns false when PostgreSQL is down: pg_fetch_assoc(false) is a
    // TypeError in PHP 8 (not silenced by @) and took the whole page down.
    $results=$q->QUERY_SQL("SELECT COUNT(*) AS n FROM hostsnet WHERE dhcpfixed=1");
    if(!$q->ok || $results===false){return -1;}
    $ligne=pg_fetch_assoc($results);
    if(!is_array($ligne)){return -1;}
    return intval($ligne["n"]);
}

function dcfg_card(array $o,$dhcpd,array $occupancy,int $reservations):string{
    $page=CurrentPageName();
    $idEnc=urlencode($o["id"]);
    $enabled=intval($o["enabled"])==1;
    $edit="Loadjs('$page?spopup-interface=$idEnc')";
    $toggle="Loadjs('$page?dcfg-toggle=$idEnc&enable=".($enabled?0:1)."')";
    // Verrou Meta (R8) : interrupteur d'une etendue inactif ; celui d'un relais reste libre (R6).
    if($o["kind"]!="relay" && dhcpd_meta_locked()){$toggle="blur()";}
    $icons=array("relay"=>"fa-solid fa-server","agent"=>"fa-solid fa-right-left","direct"=>"fa-solid fa-globe");
    $ico=$enabled?$icons[$o["kind"]]:"fa-solid fa-ban";
    $cls=$enabled?"dcfg-card dcfg-".$o["kind"]:"dcfg-card dcfg-off";
    $subnet=trim((string)$dhcpd->subnet)!=""?dcfg_h($dhcpd->subnet)."/".dcfg_h($dhcpd->netmask):"";

    if(!$enabled){$badge="<span class='dcfg-badge dcfg-badge-off'>&bull; {dcfg_inactive}</span>";}
    elseif($o["kind"]=="relay"){$badge="<span class='dcfg-badge dcfg-badge-blue'>{dcfg_active_relay}</span>";}
    elseif($o["kind"]=="agent"){$badge="<span class='dcfg-badge dcfg-badge-blue'>{dcfg_active_agent}</span>";}
    else{$badge="<span class='dcfg-badge dcfg-badge-green'>{dcfg_active_direct}</span>";}

    $h=array("<div class='$cls'>");
    $h[]="<div class='dcfg-head'><div class='dcfg-id'><span class='dcfg-ico'><i class='$ico'></i></span><div>";
    $h[]="<div class='dcfg-title'><a href=\"javascript:blur();\" OnClick=\"$edit\">".dcfg_h($o["title"])."</a> $badge <span class='dcfg-mono'>$subnet</span></div>";
    $h[]=($o["sub"]!=""?"<div class='dcfg-sub'>".$o["sub"]."</div>":"")."</div></div>";
    $h[]="<div class='dcfg-acts'>";
    if($enabled){
        $h[]="<span class='dcfg-lbl'>{dcfg_distribution}</span><a href=\"javascript:blur();\" OnClick=\"$toggle\" class='dcfg-switch dcfg-switch-on' title='{disable}'><span></span></a>";
    }else{
        $h[]="<a href=\"javascript:blur();\" OnClick=\"$toggle\" class='dcfg-btn'><i class='fa-regular fa-circle-play'></i> {dcfg_enable_here}</a>";
    }
    $h[]="<a href=\"javascript:blur();\" OnClick=\"$edit\" class='dcfg-iconbtn' title='{edit}'><i class='fa-solid fa-pen-to-square'></i></a>";
    $h[]="</div></div>";

    if(!$enabled){
        $h[]="<div class='dcfg-note'><i class='fa-solid fa-circle-info'></i> <span>{dcfg_not_listening}</span></div></div>";
        return implode("",$h);
    }

    $range=dcfg_h($dhcpd->range1)." - ".dcfg_h($dhcpd->range2);
    $dns=array();
    foreach(array($dhcpd->DNS_1,$dhcpd->DNS_2) as $d){if(trim((string)$d)!=""){$dns[]=dcfg_h($d);}}
    $lease=intval($dhcpd->max_lease_time);
    $leaseTxt=$lease>=3600&&$lease%3600==0?($lease/3600)."h":($lease>=60?intval($lease/60)."min":$lease."s");

    $h[]="<div class='dcfg-grid'>";
    $h[]="<div class='dcfg-box'><span class='dcfg-box-t'>{dcfg_range_mask}</span><span class='dcfg-box-v'>$range</span><span class='dcfg-box-n'>{dcfg_mask}: ".dcfg_h($dhcpd->netmask)."</span></div>";
    $h[]="<div class='dcfg-box'><span class='dcfg-box-t'>".$o["mode_title"]."</span><span class='dcfg-box-v'>".$o["mode_value"]."</span><span class='dcfg-box-n dcfg-accent'>".$o["mode_note"]."</span></div>";
    $h[]="<div class='dcfg-box'><span class='dcfg-box-t'>{dcfg_gateway_dns}</span><span class='dcfg-box-v'>GW: ".dcfg_h($dhcpd->gateway)."</span><span class='dcfg-box-n'>DNS: ".(count($dns)?implode(", ",$dns):"-")." ({dcfg_lease}: $leaseTxt)</span></div>";
    $h[]="</div>";

    $flags=array();
    $flags[]=dcfg_flag(intval($dhcpd->authoritative)==1,"{dcfg_authoritative}");
    $pxe=intval($dhcpd->pxe_enable)==1;
    $flags[]=dcfg_flag($pxe,"{dcfg_pxe}".($pxe?": ".dcfg_h(basename((string)$dhcpd->pxe_file)):""));
    if(trim((string)$dhcpd->ddns_domainname)!=""){$flags[]="<span class='dcfg-flag'><i class='fa-solid fa-at'></i> {domain}: ".dcfg_h($dhcpd->ddns_domainname)."</span>";}
    $h[]="<div class='dcfg-flags'>".implode("",$flags)."</div>";

    $key=trim((string)$dhcpd->range1)."-".trim((string)$dhcpd->range2);
    $h[]="<div class='dcfg-foot'><div class='dcfg-occ'><span class='dcfg-lbl'>{dcfg_occupancy}</span>";
    if(isset($occupancy[$key])){
        $s=$occupancy[$key]; $pct=floatval($s->percent);
        $col=$o["kind"]=="direct"?"#059669":"#2563eb";
        if($pct>=90){$col="#ba1a1a";}
        $h[]="<div class='dcfg-bar'><div style='width:".sprintf("%.1f",max(0,min(100,$pct)))."%;background:$col'></div></div>";
        $h[]="<span class='dcfg-occ-v'>".intval($s->used)." / ".intval($s->defined)." (".sprintf("%.1f%%",$pct).")</span>";
    }else{
        $h[]="<span class='dcfg-muted'>-</span>";
    }
    $h[]="</div>";
    if($o["kind"]=="direct"){
        $n=$reservations>=0?" ($reservations)":"";
        $h[]="<a href=\"javascript:blur();\" OnClick=\"LoadAjax('MainContent','fw.dhcp.reservations.php')\" class='dcfg-link'>{dcfg_manage_reservations}$n <i class='fa-solid fa-chevron-right'></i></a>";
    }else{
        $h[]="<a href=\"javascript:blur();\" OnClick=\"$edit\" class='dcfg-link'>{advanced_options} <i class='fa-solid fa-chevron-right'></i></a>";
    }
    $h[]="</div></div>";
    return implode("",$h);
}

function dcfg_flag(bool $on,string $label):string{
    $cls=$on?"dcfg-flag dcfg-flag-on":"dcfg-flag";
    return "<span class='$cls'><span class='dcfg-dot'></span>$label</span>";
}

// The card switch: stores service_enabled for that interface or relay rule
// only. Like every save of this page (dhcp_save() -> Save(true)), nothing is
// restarted: the {reconfigure} header button applies it.
function dcfg_toggle():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    if(!$users->ASDCHPAdmin && !$users->AsSystemAdministrator){return $tpl->js_error("{ERROR_NO_PRIVS}");}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableNetworksManagement"))==1){return $tpl->js_error("{ERROR_NO_PRIVS}");}
    $iface=(string)$_GET["dcfg-toggle"];
    if(!preg_match('/^(Relay:[0-9]+|[A-Za-z0-9_.-]+)$/',$iface)){return $tpl->js_error("{error}: ".dcfg_h($iface));}
    // Verrou Meta (R8) : service_enabled d'une etendue vient de la politique ; relais libres (R6).
    if(!dhcpd_meta_is_relay($iface) && dhcpd_meta_refuse_js()){return false;}
    $enable=intval($_GET["enable"]??0)==1?1:0;
    $sock=new dhcp_socks($iface);
    $sock->SET("service_enabled",$enable);
    return $tpl->js_ok("{dcfg_saved_apply}","LoadAjaxSilent('table-loader-dhcp-service','$page?interfaces=yes');");
}

function dcfg_style():string{
    return "<style>
.dcfg{display:flex;flex-direction:column;gap:18px}
.dcfg *{box-sizing:border-box}
.dcfg-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;min-width:0}
.dcfg-card:hover{border-color:#cbd5e1}
.dcfg-off{border-style:dashed;border-color:#cbd5e1;background:#fbfcfd}
.dcfg-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
.dcfg-id{display:flex;gap:14px;align-items:flex-start;min-width:0}
.dcfg-ico{width:42px;height:42px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;font-size:18px;flex:0 0 auto;background:#ecfdf5;border:1px solid #a7f3d0;color:#047857}
.dcfg-relay .dcfg-ico,.dcfg-agent .dcfg-ico{background:#eff6ff;border-color:#bfdbfe;color:#2563eb}
.dcfg-off .dcfg-ico{background:#e2e8f0;border-color:#cbd5e1;color:#64748b}
.dcfg-title{font-size:16px;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.dcfg-title a{color:#0f172a;text-decoration:none}
.dcfg-title a:hover{color:#047857;text-decoration:underline}
.dcfg-off .dcfg-title a{color:#475569}
.dcfg-sub{font-size:12px;color:#64748b;margin-top:4px}
.dcfg-mono{font-size:12px;color:#64748b;font-weight:400}
.dcfg-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.02em}
.dcfg-badge-green{background:#d1fae5;color:#065f46}
.dcfg-badge-blue{background:#dbeafe;color:#1e40af}
.dcfg-badge-off{background:#e2e8f0;color:#475569}
.dcfg-acts{display:flex;align-items:center;gap:10px}
.dcfg-lbl{font-size:12px;font-weight:500;color:#475569;white-space:nowrap}
.dcfg-switch{position:relative;display:inline-block;width:40px;height:22px;border-radius:999px;background:#cbd5e1;transition:background .15s}
.dcfg-switch span{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .15s}
.dcfg-switch-on{background:#059669}
.dcfg-switch-on span{left:21px}
.dcfg-iconbtn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #e2e8f0;border-radius:8px;color:#475569!important}
.dcfg-iconbtn:hover{color:#047857!important;border-color:#6ee7b7;background:#ecfdf5}
.dcfg-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;font-size:12px;font-weight:600;color:#334155!important}
.dcfg-btn:hover{color:#047857!important;background:#f1f5f9}
.dcfg-btn i,.dcfg-btn svg{color:#059669}
.dcfg-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:16px;padding-top:16px;border-top:1px solid #f1f5f9}
.dcfg-box{background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:10px 12px;display:flex;flex-direction:column;gap:3px;min-width:0}
.dcfg-box-t{font-size:12px;color:#94a3b8;font-weight:500}
.dcfg-box-v{font-size:13px;font-weight:600;color:#1e293b;overflow-wrap:anywhere}
.dcfg-box-n{font-size:11px;color:#64748b;overflow-wrap:anywhere}
.dcfg-accent{color:#047857;font-weight:500}
.dcfg-relay .dcfg-accent,.dcfg-agent .dcfg-accent{color:#2563eb}
.dcfg-flags{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.dcfg-flag{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:11px;font-weight:500}
.dcfg-dot{width:7px;height:7px;border-radius:50%;background:#94a3b8;display:inline-block}
.dcfg-flag-on{background:#ecfdf5;color:#065f46}
.dcfg-flag-on .dcfg-dot{background:#10b981}
.dcfg-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px}
.dcfg-occ{display:flex;align-items:center;gap:10px;flex:1 1 60%;min-width:260px}
.dcfg-bar{flex:1;height:8px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;overflow:hidden}
.dcfg-bar div{height:100%;border-radius:999px}
.dcfg-occ-v{font-size:12px;font-weight:600;color:#334155;white-space:nowrap}
.dcfg-muted{color:#94a3b8}
.dcfg-link{font-size:12px;font-weight:600;color:#047857!important;white-space:nowrap}
.dcfg-note{margin-top:14px;padding:12px 14px;background:#f1f5f9;border-radius:10px;font-size:12px;color:#475569;display:flex;gap:10px;align-items:flex-start}
.dcfg-note i,.dcfg-note svg{color:#94a3b8;margin-top:2px}
@media(max-width:1000px){.dcfg-grid{grid-template-columns:1fr}}
</style>";
}

function page_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsReconFigure=$tpl->framework_buildjs(
        "/dhcpd/service/reconfigure","dhcpd.progress",
        "dhcpd.progress.log","progress-dhcp-restart","","","","ASDCHPAdmin");

    $DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");
    $topbuttons[] = array($jsReconFigure, ico_refresh, "{reconfigure}");
    $topbuttons[] = array("Loadjs('$page?dhcprelay-new-js=yes')",ico_sensor,"{new_dhcp_relay_rule}");


    $TINY_ARRAY["TITLE"]="{APP_DHCP} v$DHCPDVersion";
    $TINY_ARRAY["ICO"]="fas fa-cogs";
    $TINY_ARRAY["EXPL"]="{EnableDHCPServer_text}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";

    $Interval=$tpl->RefreshInterval_js("dhcp-service",$page,"status=yes",3);
    // Verrou Meta : bandeau seulement ; reconfigure (service) et nouveau relais restent libres.
    $metaBanner=dhcpd_meta_banner($tpl);
$html="$metaBanner<table style='width:100%;table-layout:fixed'>
				<tr>
					<td style='width:350px;vertical-align:top'><div id='dhcp-service'></div></td>
					<td style='padding-left:15px;vertical-align:top'><div id='table-loader-dhcp-service'></div></td>
				</tr>
			</table>
<script>
	LoadAjax('table-loader-dhcp-service','$page?interfaces=yes');
    $jstiny
	$Interval
</script>";
echo $html;
return true;

}
function spopup_interface():bool{
    $iface=$_GET["spopup-interface"];
    $nic=new system_nic($iface);
    $tpl=new template_admin();
    $page   = CurrentPageName();
    return $tpl->js_dialog1($nic->NICNAME,"$page?interface=$iface");
}

function support_tool_js():bool{

    $tpl=new template_admin();
    $after="document.location.href='/ressources/logs/web/dhcpd.support.tar.gz'";
    $js=$tpl->framework_buildjs("/dhcpd/service/support",
        "dhcpd.tool","dhcpd.tool.log","progress-dhcp-restart",$after);
   return  $tpl->js_confirm_execute("{generate_support_tool_ask}", "none", "none",$js);
}

function flush_leases_ask(){
    $tpl    = new template_admin();
    $page   = CurrentPageName();
    $tpl->js_dialog_confirm_action("{Clear_DHCP_Leases}","none","none","Loadjs('$page?flush-leases-confirm=yes')");

}

function flush_leases_confirm(){
    $tpl=new template_admin();
    $jsafter=$tpl->framework_buildjs("/dhcpd/leases/flush",
        "dhcdp.leases.empty.progress","dhcdp.leases.empty.progress.txt","progress-dhcp-restart");

    header("content-type: application/x-javascript");
    echo "$jsafter\n";

}

function gateway_js(){

    $Interface=$_GET["gateway-js"];
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("{multiple_gateways}: $Interface","$page?gateway-popup=$Interface");

}

function gateway_popup(){
    $tpl=new template_admin();
    $security = "ASDCHPAdmin";
    $Interface=$_GET["gateway-popup"];
    $dhcp_socks=new dhcp_socks($Interface);
    $routers=$dhcp_socks->GET("routers");
    $DATA=explode(",",$routers);

    $form[]=$tpl->field_textareacode("routers","{routers}",@implode("\n",$DATA));
    $tpl->field_hidden("router-interface",$Interface);
    dhcpd_meta_lock_form($tpl);
    echo $tpl->form_outside("{multiple_gateways}",$form,null,"{apply}","dialogInstance2.close();",$security);
}

function gateway_save(){
    if(dhcpd_meta_refuse_post()){return;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $Interface=$_POST["router-interface"];
    $dhcp_socks=new dhcp_socks($Interface);
    $explode=explode("\n",$_POST["routers"]);
    $line=$dhcp_socks->CleanIpaddr1(@implode(",",$explode));
    $dhcp_socks->SET("routers",$line);
}

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{default_service}"]="$page?interface=default";
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT * FROM `nics` WHERE enabled=1 ORDER BY Interface");
    if(count($results)>1){
        foreach ($results as $index=>$ligne){
            $Interface=$ligne["Interface"];
            $NICNAME=$ligne["NICNAME"];
            $array["$NICNAME ($Interface)"]="$page?interface=$Interface";
        }

    }
    echo $tpl->tabs_default($array);
}

// Bascule serveur / relais d'une interface : confirmee, jamais au premier clic.
function dcfg_mode_ask():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    if(dhcpd_meta_refuse_js()){return false;}
    if(!$tpl->IsSecurity("AsSystemAdministrator")){return $tpl->js_error("{ERROR_NO_PRIVS}");}
    $iface=(string)$_GET["interface"];
    if(!preg_match('/^[A-Za-z0-9_.-]+$/',$iface)){return $tpl->js_error("{error}: ".dcfg_h($iface));}
    $agent=intval($_GET["AsDHCPAgent"])==1?1:0;
    $text=$agent==1?"{dcfg_mode_ask_relay}":"{dcfg_mode_ask_server}";
    $ifenc=urlencode($iface);
    return $tpl->js_dialog_confirm_action($text,"none","none","Loadjs('$page?AsDHCPAgent=$agent&interface=$ifenc')");
}
function AsDHCPAgent():bool{
    // Mode serveur / agent d'une etendue : gere par la politique Meta sous verrou.
    if(dhcpd_meta_refuse_js()){return false;}
    $page               = CurrentPageName();
    // Le bouton est cadenasse sans droit ; la bascule le verifie aussi cote serveur.
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSystemAdministrator")){return $tpl->js_error("{ERROR_NO_PRIVS}");}
    $interface=(string)$_GET["interface"];
    if(!preg_match('/^[A-Za-z0-9_.-]+$/',$interface)){return $tpl->js_error("{error}");}
    $AsDHCPAgent=intval($_GET["AsDHCPAgent"])==1?1:0;
    $dhcp       = new dhcp_socks($interface);
    $dhcp->SET("AsDHCPAgent",$AsDHCPAgent);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dhcphelper/check");
    admin_tracks("DHCP: interface $interface switched to ".($AsDHCPAgent==1?"relay agent":"server")." mode");
    $js[]="if(document.getElementById('table-loader-dhcp-service')){LoadAjaxSilent('table-loader-dhcp-service','$page?interfaces=yes');}";
    $js[]="LoadAjaxSilent('DHCP-MAIN-CONFIG-$interface','$page?table-config=$interface');";
    echo @implode("\n",$js);
    return true;
}
function table(){
    $MainInterface      = $_GET["interface"];
    $page               = CurrentPageName();
    echo "<div id='DHCP-MAIN-CONFIG-$MainInterface'></div>
        <script>
          if( !document.getElementById('DHCP-MAIN-CONFIG-$MainInterface')){
              alert('DHCP-MAIN-CONFIG-$MainInterface not found');
          }
          
          LoadAjaxSilent('DHCP-MAIN-CONFIG-$MainInterface','$page?table-config=$MainInterface');
        </script>";
}

// ---------------------------------------------------------------------------
// Fenetre d'une etendue (maquette stitch_artica_dhcp_server_params, 2026-11-11).
// Un modele lu une fois (dcfg_scope_model), une fonction par widget ; le formulaire
// lui-meme est le form_outside() commun, redessine globalement (afrm_css()).
// ---------------------------------------------------------------------------
function dcfg_scope_model(string $MainInterface,$dhcp,int $AsDHCPAgent,bool $ASRELAY):array{
    $m=array("id"=>$MainInterface,"relay"=>$ASRELAY,"agent"=>($AsDHCPAgent==1),"rulename"=>"");
    $nic=(string)$dhcp->listen_nic;
    if($ASRELAY && preg_match('#^Relay:([0-9]+)$#',$MainInterface,$re)){
        $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
        $rows=$q->QUERY_SQL_PARAMS("SELECT interface,rulename FROM dhcpd_relays WHERE ID=?",array(intval($re[1])));
        $r=(is_array($rows) && isset($rows[0]))?$rows[0]:array();
        $nic=isset($r["interface"])?(string)$r["interface"]:"";
        $m["rulename"]=isset($r["rulename"])?(string)$r["rulename"]:"";
    }
    $nicz=new system_nic($nic);
    $m["iface"]=$nic;
    $m["nicname"]=trim((string)$nicz->NICNAME);
    $m["ip"]=trim((string)$nicz->IPADDR);
    $m["mask"]=trim((string)$nicz->NETMASK);
    $m["zone"]=trim((string)$nicz->netzone);
    $m["vlan"]=0;
    if(intval($dhcp->EnableVLANs)==1 && intval($dhcp->vlan_id)>0){
        $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $vrows=$q->QUERY_SQL_PARAMS("SELECT * FROM nics_vlan WHERE ID=? and nic=?",array(intval($dhcp->vlan_id),$nic));
        $v=(is_array($vrows) && isset($vrows[0]))?$vrows[0]:array();
        if(intval(isset($v["vlanid"])?$v["vlanid"]:0)>0){
            $m["vlan"]=intval($v["vlanid"]);
            $m["ip"]=trim((string)$v["ipaddr"]);
            $m["mask"]=trim((string)$v["netmask"]);
        }
    }
    if($m["agent"]){
        $m["enabled"]=intval((new dhcp_socks($MainInterface))->GET("service_enabled"))==1;
    }else{
        $m["enabled"]=intval($dhcp->service_enabled)==1;
    }
    // Plage telle qu'enregistree : une plage absente ou invalide ne s'affiche pas.
    $m["range1"]=trim((string)$dhcp->range1);
    $m["range2"]=trim((string)$dhcp->range2);
    $m["size"]=-1;
    $a=ip2long($m["range1"]); $b=ip2long($m["range2"]);
    if($a!==false && $b!==false && $b>=$a){$m["size"]=$b-$a+1;}
    $m["lease"]=intval($dhcp->max_lease_time);
    // Occupation : meme source et meme cle que les cartes de la liste (interfaces()).
    $m["occ"]=null;
    if(!$m["agent"] && $m["size"]>0){
        include_once(dirname(__FILE__)."/ressources/class.dhcpmetrics.inc");
        $pools=dhcpm_api("/dhcpd/metrics/pools",array("range"=>"1h"));
        $key=$m["range1"]."-".$m["range2"];
        if($pools!==null && isset($pools->success) && $pools->success && isset($pools->data->current->samples) && is_array($pools->data->current->samples)){
            foreach($pools->data->current->samples as $s){
                if((string)$s->range===$key){$m["occ"]=array("used"=>intval($s->used),"defined"=>intval($s->defined),"percent"=>floatval($s->percent));}
            }
        }
    }
    return $m;
}

// Duree lisible d'un bail : 86400 -> "1 d", 43200 -> "12 h", 5400 -> "90 min".
function dcfg_lease_human(int $s):string{
    if($s<=0){return "&mdash;";}
    if($s%86400==0){return ($s/86400)." d";}
    if($s%3600==0){return ($s/3600)." h";}
    if($s%60==0){return ($s/60)." min";}
    return "$s s";
}

function dcfg_scope_header($tpl,array $m,bool $switch):string{
    $page=CurrentPageName();
    $title=$m["nicname"]<>""?$m["nicname"]." (".$m["iface"].")":$m["iface"];
    if($m["relay"] && $m["rulename"]<>""){$title=$m["rulename"];}
    $h=array();
    $h[]="<div class='ak ak-strip dcfg-hd'>";
    $h[]="<span class='ak-ico ak-ico-sq dcfg-hd-ico'><i class='fa-solid fa-ethernet' aria-hidden='true'></i></span>";
    $h[]="<div class='ak-strip-main'>";
    $h[]="<div class='dcfg-hd-t'>".dcfg_h($title);
    if($m["enabled"]){
        $h[]=" <span class='ak-pill ak-ok dcfg-hd-st'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{dcfg_scope_active}</span>";
    }else{
        $h[]=" <span class='ak-pill ak-off dcfg-hd-st'><i class='fa-regular fa-circle' aria-hidden='true'></i>{dcfg_scope_off}</span>";
    }
    $h[]="</div><div class='dcfg-pills'>";
    if($m["relay"]){$h[]="<span class='dcfg-pill'><span class='dcfg-pk'>{nic}</span><span class='dcfg-pv ak-mono'>".dcfg_h($m["iface"])."</span></span>";}
    if($m["ip"]<>"" && $m["ip"]<>"0.0.0.0"){
        $h[]="<span class='dcfg-pill'><span class='dcfg-pk'>{tcp_address}</span><span class='dcfg-pv ak-mono'>".dcfg_h($m["ip"]).($m["mask"]<>""?"/".dcfg_h($m["mask"]):"")."</span></span>";
    }
    if($m["zone"]<>""){$h[]="<span class='dcfg-pill'><span class='dcfg-pk'>{netzone}</span><span class='dcfg-pv'>".dcfg_h($m["zone"])."</span></span>";}
    if($m["vlan"]>0){$h[]="<span class='dcfg-pill'><span class='dcfg-pk'>VLAN</span><span class='dcfg-pv ak-mono'>".$m["vlan"]."</span></span>";}
    $h[]="</div></div>";
    if($switch){
        $msec=dhcpd_meta_sec("AsSystemAdministrator");
        $locked=!$tpl->IsSecurity($msec);
        $ifenc=urlencode($m["id"]);
        $opts=array(
            array(0,"fa-solid fa-server","{server_mode}"),
            array(1,"fa-solid fa-right-left","{APP_DHCP_RELAY}"),
        );
        $h[]="<div class='ak-strip-side'><div class='ak-seg dcfg-seg' role='radiogroup' aria-label=\"{dcfg_operating_mode}\">";
        foreach($opts as $o){
            $on=(($o[0]==1)==$m["agent"]);
            $attr=$on?" aria-checked='true' tabindex='0'":" aria-checked='false' tabindex='-1'";
            $js=($on||$locked)?"blur()":"Loadjs('$page?dcfg-mode-ask=yes&interface=$ifenc&AsDHCPAgent={$o[0]}')";
            $dis=($locked && !$on)?" disabled":"";
            $check=$on?"<i class='fa-solid fa-check dcfg-seg-ck' aria-hidden='true'></i>":"";
            $h[]="<button type='button' role='radio' class='ak-seg-b".($on?" on":"")."'$attr$dis OnClick=\"$js\">$check<i class='{$o[1]}' aria-hidden='true'></i><span>{$o[2]}</span></button>";
        }
        $h[]="</div></div>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

// Trois chiffres de l'etendue : plage, occupation (mesuree contre la plage), bail.
function dcfg_scope_summary($tpl,array $m):string{
    $h=array();
    $h[]="<div class='ak ak-card dcfg-sum'>";
    // Plage
    $h[]="<div class='dcfg-sum-c'><div class='dcfg-sum-l'>{dcfg_address_range}</div>";
    if($m["size"]>0){
        $h[]="<div class='dcfg-sum-v ak-mono'>".dcfg_h($m["range1"])."&nbsp;&ndash;&nbsp;".dcfg_h($m["range2"])."</div>";
        $h[]="<div class='dcfg-sum-s'>".str_replace("%s",number_format($m["size"],0,"."," "),$tpl->_ENGINE_parse_body("{dcfg_addresses}"))."</div>";
    }else{
        $h[]="<div class='dcfg-sum-v dcfg-none'>&mdash;</div>";
    }
    $h[]="</div>";
    // Occupation
    $h[]="<div class='dcfg-sum-c'><div class='dcfg-sum-l'>{dcfg_occupancy}</div>";
    if(is_array($m["occ"]) && $m["occ"]["defined"]>0){
        $pct=max(0,min(100,$m["occ"]["percent"]));
        $tone="ak-ok"; if($pct>=75){$tone="ak-warn";} if($pct>=90){$tone="ak-err";}
        $h[]="<div class='dcfg-sum-v'><span class='ak-mono'>".$m["occ"]["used"]." / ".$m["occ"]["defined"]."</span> <span class='dcfg-sum-p'>(".sprintf("%.1f",$pct)." %)</span></div>";
        $h[]="<div class='ak-bar $tone dcfg-sum-bar' role='progressbar' aria-valuemin='0' aria-valuemax='100' aria-valuenow='".sprintf("%.1f",$pct)."'><span style='width:".sprintf("%.1f",$pct)."%'></span></div>";
    }else{
        $h[]="<div class='dcfg-sum-v dcfg-none'>&mdash;</div>";
    }
    $h[]="</div>";
    // Bail
    $h[]="<div class='dcfg-sum-c'><div class='dcfg-sum-l'>{max_lease_time}</div>";
    if($m["lease"]>0){
        $h[]="<div class='dcfg-sum-v'>".dcfg_lease_human($m["lease"])." <span class='dcfg-sum-p ak-mono'>(".$m["lease"]." s)</span></div>";
    }else{
        $h[]="<div class='dcfg-sum-v dcfg-none'>&mdash;</div>";
    }
    $h[]="</div>";
    $h[]="</div>";
    return @implode("",$h);
}

// Indication lisible a cote du champ "Max Lease Time", mise a jour a la frappe.
function dcfg_lease_hint_row(int $lease):string{
    return "<tr class='afrm-row afrm-f dcfg-lease-row'><td class='afrm-l'>&nbsp;</td><td class='afrm-c'><span class='dcfg-lease-hint'><i class='fa-solid fa-hourglass-half' aria-hidden='true'></i><span class='dcfg-lease-v'>= ".dcfg_lease_human($lease)."</span></span></td></tr>";
}

function dcfg_scope_js():string{
    return "<script>
(function(){
  var rows=document.querySelectorAll('.dcfg-lease-row'); if(!rows.length){return;}
  var row=rows[rows.length-1]; var form=row.closest('form'); if(!form){return;}
  var inp=form.querySelector(\"input[name='max_lease_time']\"); var out=row.querySelector('.dcfg-lease-v');
  if(!inp||!out){return;}
  var fmt=function(s){ s=parseInt(s,10); if(!(s>0)){return '—';}
    if(s%86400==0){return (s/86400)+' d';} if(s%3600==0){return (s/3600)+' h';} if(s%60==0){return (s/60)+' min';} return s+' s'; };
  var up=function(){ out.textContent='= '+fmt(inp.value); };
  inp.addEventListener('input',up); inp.addEventListener('change',up);
})();
</script>";
}

function dcfg_scope_style():string{
    if(isset($GLOBALS["DCFG_SCOPE_STYLE"])){return "";}
    $GLOBALS["DCFG_SCOPE_STYLE"]=true;
    $c=array();
    $c[]="<style>";
    $c[]=".dcfg-hd{background:#f8fafc;border-color:#e2e8f0;padding:16px 18px;margin:6px 0 16px 0;align-items:center}";
    $c[]=".dcfg-hd-ico{flex-basis:48px;width:48px;height:48px;font-size:20px;background:#e0f2fe;color:#0369a1}";
    $c[]=".dcfg-hd-t{font-size:18px;font-weight:600;color:#0f172a;line-height:1.35;display:flex;align-items:center;flex-wrap:wrap;gap:8px}";
    $c[]=".dcfg-hd-st{font-size:13px}";
    $c[]=".dcfg-pills{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}";
    $c[]=".dcfg-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;font-size:13px}";
    $c[]=".dcfg-pk{color:#64748b}";
    $c[]=".dcfg-pv{color:#0f172a;font-weight:500}";
    $c[]=".dcfg-seg .ak-seg-b{display:inline-flex;align-items:center;gap:8px;height:36px;font-size:13px}";
    $c[]=".dcfg-seg-ck{color:#0f766e}";
    $c[]=".dcfg-seg .ak-seg-b[disabled]{cursor:not-allowed;opacity:.6}";
    // La plage (deux adresses en chasse fixe) a besoin de plus de place que les deux autres.
    $c[]=".dcfg-sum{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr) minmax(0,1fr);padding:14px 0;margin-bottom:16px}";
    $c[]=".dcfg-sum-c{padding:0 20px;border-left:1px solid #e2e8f0;min-width:0}";
    $c[]=".dcfg-sum-c:first-child{border-left:0}";
    $c[]=".dcfg-sum-l{font-size:13px;font-weight:500;color:#64748b}";
    $c[]=".dcfg-sum-v{font-size:15px;font-weight:600;color:#0f172a;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}";
    $c[]=".dcfg-sum-v.ak-mono{font-size:14px}";
    $c[]=".dcfg-sum-s,.dcfg-sum-p{font-size:13px;font-weight:500;color:#64748b}";
    $c[]=".dcfg-sum-bar{margin-top:8px}";
    $c[]=".dcfg-none{color:#94a3b8;font-weight:400}";
    $c[]=".dcfg-lease-hint{display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 12px;border:1px solid #e2e8f0;border-radius:8px;background:#f1f5f9;color:#334155;font-size:13px;font-weight:500}";
    $c[]=".dcfg-lease-hint>i,.dcfg-lease-hint>svg{color:#0f766e;font-size:12px}";
    $c[]="@media (max-width:760px){.dcfg-hd{flex-wrap:wrap}.dcfg-sum{grid-template-columns:minmax(0,1fr)}.dcfg-sum-c{border-left:0;padding:8px 20px}}";
    $c[]="</style>";
    return @implode("",$c);
}

function table_config(){
    $DEFAULT_SERVICE    = false;
    $error              = null;
    $MainInterface      = $_GET["table-config"];
    $page               = CurrentPageName();
    $ldap               = new clladp();
    $domains            = $ldap->hash_get_all_domains();
    $tpl                = new template_admin();
    $dhcpSock       = new dhcp_socks($MainInterface);
    $users              = new usersMenus();
    $buttonname         = "{apply}";
    $ASRELAY             = false;
    $EnableDHCPServer   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    $EnableDHCPUseHostnameOnFixed = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPUseHostnameOnFixed"));
    $IncludeDHCPLdapDatabase = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IncludeDHCPLdapDatabase"));
    $DHCPDAutomaticFixIPAddresses = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDAutomaticFixIPAddresses"));
    $DHCPAddNewComputers = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPAddNewComputers"));
    $DHCPDInPowerDNS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInPowerDNS"));
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $AsDHCPAgent=intval($dhcpSock->GET("AsDHCPAgent"));
    $SWITCHBUTTON=true;
    if (preg_match("#Relay:[0-9]+#", $MainInterface)) {
        $SWITCHBUTTON = false;
        $ASRELAY = true;
    }


    if ($MainInterface == "default") {$DEFAULT_SERVICE = true;}
    $q=new lib_sqlite("/home/artica/SQLITE/dhcpd.db");
    // Pentest 2026-11-04 : MainInterface etait concatene dans la requete.
    $lrows=$q->QUERY_SQL_PARAMS("SELECT `interface` FROM `dhcpd` WHERE `key`='listen_nic' AND `val`=?",array($MainInterface));
    $ligne=isset($lrows[0])?$lrows[0]:array();
    // Sous verrou Meta, l'affichage n'ecrit jamais dans la table dhcpd.
    if($ligne["interface"]=="default" && !dhcpd_meta_locked()){
        $q->QUERY_SQL("DELETE FROM  `dhcpd` WHERE `interface`='default'");
        $ligne["interface"]="";
    }



    $security = "ASDCHPAdmin";
    if ($EnableDHCPServer == 0) {$security = "disabled";}
    $dhcp       = new dhcpd(0, 1, $MainInterface);
    if ($dhcp->ddns_domainname == null) {
        $dhcp->ddns_domainname = "home.lan";
    }


    if ($MainInterface == "default") {
        if ($dhcp->listen_nic == null) {
            $dhcp->listen_nic = "eth0";
        }
    }else{
        $dhcp->listen_nic = $MainInterface;
    }

    // Modele lu AVANT les valeurs par defaut ci-dessous : la synthese montre la plage
    // enregistree, pas celle que le formulaire propose.
    $model=dcfg_scope_model((string)$MainInterface,$dhcp,$AsDHCPAgent,$ASRELAY);
    $nicz=new system_nic($dhcp->listen_nic);

    preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$nicz->IPADDR,$re);
    if(!preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$dhcp->range2)){$dhcp->range2=null;}
	
	if($dhcp->subnet==null){
		$dhcp->subnet="$re[1].$re[2].$re[3].0";
	}
	if($dhcp->netmask==null){
		$dhcp->netmask=$nicz->NETMASK;
	}
	if($dhcp->gateway==null){
		$dhcp->gateway=$nicz->GATEWAY;
	}
	if($dhcp->range1==null){
		$dhcp->range1="$re[1].$re[2].$re[3].20";
	}
	if($dhcp->range2==null){
		$dhcp->range2="$re[1].$re[2].$re[3].252";
	}
	if($dhcp->broadcast==null){
		$dhcp->broadcast="$re[1].$re[2].$re[3].255";
	}
	$form[]=$tpl->field_section("{dcfg_service_section}",$EnableKEA==1?"{dcfg_engine_kea}":"{dcfg_engine_isc}",false,"fa-solid fa-power-off");
	$tpl->field_hidden("MainInterface",$MainInterface);


    if($DEFAULT_SERVICE) {
        $form[] = $tpl->field_interfaces("listen_nic", "{listen_interface}", $dhcp->listen_nic, false);
        if($dhcp->EnableVLANs==1) {
            $form[] = $tpl->field_interfaces_vlan($dhcp->listen_nic,"vlan_id","{listen_interface} (VLAN)",$dhcp->vlan_id);
        }

        $form[] = $tpl->field_checkbox("IncludeDHCPLdapDatabase", "{dhcp_use_local_database}", $IncludeDHCPLdapDatabase, "DHCPAddNewComputers,DHCPDAutomaticFixIPAddresses,deny_unkown_clients", "{dhcp_use_local_database_explain}");
        $form[] = $tpl->field_checkbox("DHCPAddNewComputers",
            "{DHCPAddNewComputers}", $DHCPAddNewComputers, false, "{DHCPAddNewComputers_explain}");
        $form[] = $tpl->field_checkbox("DHCPDAutomaticFixIPAddresses",
            "{IncludeDHCPLdapDatabase}", $DHCPDAutomaticFixIPAddresses, false, "{DHCPDAutomaticFixIPAddresses_explain}");

        $form[] = $tpl->field_checkbox("EnableDHCPUseHostnameOnFixed",
            "{EnableDHCPUseHostnameOnFixed}", $EnableDHCPUseHostnameOnFixed, false, "{EnableDHCPUseHostnameOnFixed_explain}");

    }else{
        $tpl->field_hidden("listen_nic",$MainInterface);
        $tpl->field_hidden("vlan_id",$dhcp->vlan_id);
        $tpl->field_hidden("IncludeDHCPLdapDatabase",$IncludeDHCPLdapDatabase);
        $tpl->field_hidden("DHCPAddNewComputers",$DHCPAddNewComputers);
        $tpl->field_hidden("DHCPDAutomaticFixIPAddresses",$DHCPDAutomaticFixIPAddresses);
        $tpl->field_hidden("EnableDHCPUseHostnameOnFixed",$EnableDHCPUseHostnameOnFixed);



        $form[]=$tpl->field_checkbox("service_enabled","{service_enable}",$dhcp->service_enabled,true);
   }

    if(!$ASRELAY) {
        $form[] = $tpl->field_checkbox("DHCPauthoritative", "{authoritative}", $dhcp->authoritative, false, "{authoritativeDHCP_explain}");
    }


    if ($ASRELAY) {
        $CircuitID=$dhcpSock->GET("CircuitID");
        $AgentID=$dhcpSock->GET("AgentID");
        $RelayAddress=$dhcpSock->GET("RelayAddress");
        $form[]=$tpl->field_text("RelayAddress","{APP_DHCP_RELAY} {ipaddr}",$RelayAddress,true);
        $form[]=$tpl->field_text("CircuitID","Circuit ID",$CircuitID);
        $form[]=$tpl->field_text("AgentID","Agent ID",$AgentID);
    }


    $form[] = $tpl->field_checkbox("deny_unkown_clients", "{deny_unkown_clients}",
        $dhcp->deny_unkown_clients, false, "{deny_unkown_clients_explain}");

    if($EnableKEA==0){
        $AllwaysBrodcast=1;
        if($dhcp->DisableAllwaysBrodcast==1){
            $AllwaysBrodcast=0;
        }
        $form[] = $tpl->field_checkbox("AllwaysBrodcast", "{AllwaysBrodcast}", $AllwaysBrodcast, false, "{AllwaysBrodcast_explain}");

        $form[] = $tpl->field_checkbox("DHCPPing_check", "{DHCPPing_check}", $dhcp->ping_check, false, "{DHCPPing_check_explain}");
        $form[]=$tpl->field_checkbox("get_lease_hostnames","{get_lease_hostnames}",$dhcp->get_lease_hostnames,false,"{get_lease_hostnames_text}");
    }

	
	if($users->POWER_DNS_INSTALLED){
		$EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
		if($EnablePDNS==1){
			$form[]=$tpl->field_checkbox("DHCPDInPowerDNS","{DHCPDInPowerDNS}",$DHCPDInPowerDNS,false,"{DHCPDInPowerDNS_explain}");
		}
	}
	$form[]=$tpl->field_section("{network_attribution}","{dhcp_network_attribution_explain}",false,"fa-solid fa-sitemap");
	$form[]=$tpl->field_checkbox("do_no_verify_range","{do_no_verify_range}",$dhcp->do_no_verify_range,false,"");
	$form[]=$tpl->field_ipaddr("subnet", "{subnet}", $dhcp->subnet);
	$form[]=$tpl->field_ipaddr("netmask", "{netmask}", $dhcp->netmask);
	$form[]=$tpl->field_ipaddr("range1", "{ipfrom}", $dhcp->range1);
	$form[]=$tpl->field_ipaddr("range2", "{ipto}", $dhcp->range2);


	$form[]=$tpl->field_section("{computers_parameters}",null,false,"fa-solid fa-laptop");
	if(count($domains)==0){
		$form[]=$tpl->field_text("ddns_domainname", "{ddns_domainname}", $dhcp->ddns_domainname);
	
	}else{
		$form[]=$tpl->field_array_hash($domains, "ddns_domainname", "{ddns_domainname}", $dhcp->ddns_domainname);
	}

    $slabels=array(); $sjs=array();
    $slabels[]="ico:fa-solid fa-microchip;{hardware_attribution}";
    $sjs[]="Loadjs('fw.dhcp.configuration.hardware.php?interface=$MainInterface')";

	$slabels[]="ico:fa-solid fa-route;{multiple_gateways}";
	$sjs[]="Loadjs('$page?gateway-js=$MainInterface')";

    $slabels[]="ico:fa-solid fa-signs-post;{routing_rules}";
    $sjs[]="Loadjs('fw.dhcp.configuration.routing.php?interface=$MainInterface')";

	$form[]=$tpl->field_ipaddr("gateway", "{gateway}", $dhcp->gateway,false,null,false,"Loadjs('$page?gateway-js=$MainInterface')");

	$form[]=$tpl->field_ipaddr("broadcast", "{broadcast}", $dhcp->broadcast);
	$form[]=$tpl->field_ipaddr("DNS_1", "{DNSServer} 1", $dhcp->DNS_1);
	$form[]=$tpl->field_ipaddr("DNS_2", "{DNSServer} 2", $dhcp->DNS_2);
	$form[]=$tpl->field_ipaddr("WINSDHCPSERV", "{wins_server}", $dhcp->WINS);
	$form[]=$tpl->field_ipaddr("ntp_server", "{ntp_server} ({optional})", $dhcp->ntp_server);
	$form[]=$tpl->field_text("local-pac-server", "{wpad_label}", $dhcp->local_pac_server,false,"{wpad_label_text}");
	$form[]=$tpl->field_text("browser-portal-page", "{portal_page}",$dhcp->browser_portal_page,false,"{portal_page_explain}");
	$form[]=$tpl->field_link($slabels,null,$sjs);

	$form[]=$tpl->field_section("{dcfg_timeouts}",null,false,"fa-regular fa-clock");
	$form[]=$tpl->field_numeric("max_lease_time","{max_lease_time} ({seconds})",$dhcp->max_lease_time,"{max_lease_time_text}");
	$form[]=dcfg_lease_hint_row(intval($dhcp->max_lease_time));


    $form[]=$tpl->field_section("PXE",null,false,"fa-solid fa-network-wired");
    $form[]=$tpl->field_checkbox("pxe_enable","{enable}",$dhcp->pxe_enable,"pxe_file,pxe_server","{EnablePXEDHCP}");
    $form[]=$tpl->field_text("pxe_file","{pxe_file}",$dhcp->pxe_file);
    $form[]=$tpl->field_ipv4("pxe_server","{pxe_server}",$dhcp->pxe_server);


    $html=array();
    // Relais (R6) : hors du modele Meta, ni bandeau ni verrou.
    if(!$ASRELAY){
        $metaBanner=dhcpd_meta_banner($tpl);
        if($metaBanner!=""){$html[]=$metaBanner;}
    }
    $html[]=dcfg_scope_header($tpl,$model,$SWITCHBUTTON);
    // Mode agent : le formulaire de l'agent remplace celui du serveur (avant, il
    // s'affichait AU-DESSUS de l'en-tete, suivi d'un second formulaire vide).
    if($AsDHCPAgent==1){
        $html[]=DHCPAgent_form($MainInterface);
        echo dcfg_scope_style().$error.$tpl->_ENGINE_parse_body($html);
        return;
    }
    $html[]=dcfg_scope_summary($tpl,$model);
    if(!$ASRELAY){dhcpd_meta_lock_form($tpl);}
	$html[]=$tpl->form_outside("",$form,null,$buttonname,restart_script(),$security);
	echo dcfg_scope_style().$error.$tpl->_ENGINE_parse_body($html).dcfg_scope_js();
}
function DHCPAgent_save():bool{
    if(dhcpd_meta_refuse_post()){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $MainInterface=$_POST["AgentInterface"];
    $dhcpSock       = new dhcp_socks($MainInterface);
    foreach ($_POST as $key => $value) {
        $dhcpSock->SET($key,$value);
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dhcphelper/check");
    return admin_tracks_post("Saving DHCP relay configuration");
}

function DHCPAgent_form($MainInterface):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $security = "ASDCHPAdmin";
    $dhcpSock       = new dhcp_socks($MainInterface);
    $service_enabled=intval($dhcpSock->GET("service_enabled"));
    $CircuitID=$dhcpSock->GET("CircuitID");
    $AgentID=$dhcpSock->GET("AgentID");

    if($CircuitID==""){
        $CircuitID=$MainInterface;
    }


    if($AgentID==""){
        $hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $AgentID=$hostname;
    }
    $DHCPServers=$dhcpSock->GET("DHCPServers");
    $html=array();
    $form[]=$tpl->field_section("{APP_DHCP_RELAY}",null,false,"fa-solid fa-right-left");
    $form[]=$tpl->field_hidden("AgentInterface",$MainInterface);
    $form[]=$tpl->field_checkbox("service_enabled","{service_enable}",$service_enabled,true);

    $form[]=$tpl->field_text("CircuitID","Circuit ID",$CircuitID);
    $form[]=$tpl->field_text("AgentID","Agent ID",$AgentID);
    $form[]=$tpl->field_text("DHCPServers","{DHCP_SERVERS}",$DHCPServers);
    dhcpd_meta_lock_form($tpl);
    $html[]=$tpl->form_outside("", $form,null,"{apply}",restart_script(),$security);
    return @implode("\n",$html);
}

function dhcp_save(){
	// Verrou Meta : etendues refusees ; une entree de relais reste modifiable (R6),
	// sans toucher aux reglages globaux du catalogue (champs caches du formulaire relais).
	$metaRelayOnly=false;
	if(dhcpd_meta_locked()){
		if(!dhcpd_meta_is_relay(isset($_POST["MainInterface"])?$_POST["MainInterface"]:"")){dhcpd_meta_refuse_post();return;}
		$metaRelayOnly=true;
	}

	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return;}
	$IPClass=new IP();

	$MainInterface=$_POST["MainInterface"];
	
	$dhcp=new dhcpd(0,1,$MainInterface);

    if(!$metaRelayOnly){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO('EnableDHCPUseHostnameOnFixed',$_POST["EnableDHCPUseHostnameOnFixed"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IncludeDHCPLdapDatabase", $_POST["IncludeDHCPLdapDatabase"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPAddNewComputers", $_POST["DHCPAddNewComputers"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPDAutomaticFixIPAddresses", $_POST["DHCPDAutomaticFixIPAddresses"]);
    }

    if(!$metaRelayOnly && isset($_POST["DHCPDInPowerDNS"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DHCPDInPowerDNS", $_POST["DHCPDInPowerDNS"]);
    }

    if(isset($_POST["service_enabled"])){
        $dhcp->service_enabled=$_POST["service_enabled"];
    }

    if(isset($_POST["AllwaysBrodcast"])){
        if(intval($_POST["AllwaysBrodcast"])==1){
            $dhcp->DisableAllwaysBrodcast=0;
        }else{
            $dhcp->DisableAllwaysBrodcast=1;
        }
    }

	$dhcp->listen_nic=$_POST["listen_nic"];
	$dhcp->deny_unkown_clients=$_POST["deny_unkown_clients"];
	$dhcp->ddns_domainname=$_POST["ddns_domainname"];
	$dhcp->max_lease_time=$_POST["max_lease_time"];
	$dhcp->get_lease_hostnames=$_POST["get_lease_hostnames"];
	$dhcp->netmask=$_POST["netmask"];
	$dhcp->range1=$_POST["range1"];
	$dhcp->range2=$_POST["range2"];
	$dhcp->subnet=$_POST["subnet"];
	$dhcp->broadcast=$_POST["broadcast"];
	// Le champ s'appelle WINSDHCPSERV : lire "WINS" effacait le serveur WINS a chaque enregistrement.
	$dhcp->WINS=isset($_POST["WINSDHCPSERV"])?$_POST["WINSDHCPSERV"]:(isset($_POST["WINS"])?$_POST["WINS"]:"");
	$dhcp->ping_check=$_POST["DHCPPing_check"];
	$dhcp->authoritative=$_POST["DHCPauthoritative"];
	$dhcp->local_pac_server=$_POST["local-pac-server"];
	$dhcp->browser_portal_page=$_POST["browser-portal-page"];
	$dhcp->gateway=$_POST["gateway"];
	$dhcp->DNS_1=$_POST["DNS_1"];
	$dhcp->DNS_2=$_POST["DNS_2"];
	$dhcp->ntp_server=$_POST["ntp_server"];
	$dhcp->pxe_enable=$_POST["pxe_enable"];
    $dhcp->pxe_file=$_POST["pxe_file"];
    $dhcp->pxe_server=$_POST["pxe_server"];
    $dhcp->vlan_id=$_POST["vlan_id"];
	
	$dhcp->EnableArticaAsDNSFirst=$_POST["EnableArticaAsDNSFirst"];
	$dhcp->do_no_verify_range=$_POST["do_no_verify_range"];
	
	$MASK=$IPClass->maskTocdir($dhcp->subnet, $dhcp->netmask);
	
	if(!$IPClass->isInRange($dhcp->range1, $MASK)){
		echo (new template_admin())->post_error("$dhcp->subnet/$dhcp->netmask = $MASK $dhcp->range1 invalid for $MASK\n");
		return;
	}
	if(!$IPClass->isInRange($dhcp->range2, $MASK)){
	echo (new template_admin())->post_error("$dhcp->subnet/$dhcp->netmask = $MASK $dhcp->range2 invalid for $MASK\n");
	return;
	}
	if($dhcp->ddns_domainname==null){$dhcp->ddns_domainname="home.lan";}

    if(isset($_POST["CircuitID"])){
        $dhcpsock=new dhcp_socks($MainInterface);
        $dhcpsock->SET("CircuitID",$_POST["CircuitID"]);
        $dhcpsock->SET("AgentID",$_POST["AgentID"]);
        $dhcpsock->SET("RelayAddress",$_POST["RelayAddress"]);


    }

	$dhcp->Save(true,$MainInterface);
}


function status():bool{
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new template_admin();

    $jsafter=$tpl->framework_buildjs("/dhcpd/service/restart",
    "dhcpd.progress","dhcpd.progress.txt","progress-dhcp-restart");

    $btn_config=$tpl->button_autnonome("{apply_parameters}", $jsafter,
        "fas fa-file-check","ASDCHPAdmin",335);

    //<i class="fas fa-trash"></i>
    $btn_flush=$tpl->button_autnonome("{Clear_DHCP_Leases}", "Loadjs('$page?flush-leases=yes')",
        "fas fa-trash","ASDCHPAdmin",335,"btn-warning");


	$btn_config1=$tpl->button_autnonome("{config_file}", "Loadjs('$page?config-file-js=yes')",
        "fas fa-file-code","ASDCHPAdmin",335);


    $btn_config2=$tpl->button_autnonome("Support Tool", "Loadjs('$page?spt-js=yes')",
        "fas fa-cloud-upload","ASDCHPAdmin",335);

    $data = $sock->REST_API("/dhcpd/service/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        $status=$tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));

    }else {
        if (!$json->Status) {
            echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error", "{error}"));
            return true;
        }
    }
    if($json->Status) {
        $ini = new Bs_IniHandler();
        $ini->loadString($json->Info);
        $status = $tpl->SERVICE_STATUS($ini, "APP_DHCP", restart_script());
    }

	echo $status."<table><tr><td style='padding-top:10px'>".$tpl->_ENGINE_parse_body($btn_config)."</td></tr>
        <tr><td style='padding-top:10px'>".$tpl->_ENGINE_parse_body($btn_flush)."</td></tr>
        <tr><td style='padding-top:10px'>".$tpl->_ENGINE_parse_body($btn_config1)."</td></tr>
        <tr><td style='padding-top:10px'>".$tpl->_ENGINE_parse_body($btn_config2)."</td></tr>
        
</table>";
return true;
	
}

function config_file_popup(){
	$tpl=new template_admin();
	// Read-only: the file is generated by articarest from the DHCP settings, secrets masked.
	$json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/dhcpd/config/file"));
	if(!is_object($json) || empty($json->Status)){
		echo $tpl->div_error("{error}: articarest /dhcpd/config/file");
		return;
	}
	if(!empty($json->Error)){
		echo $tpl->div_error(htmlspecialchars((string)$json->Error));
		return;
	}
	// The content never goes through _ENGINE_parse_body(): dhcpd.conf blocks are "{ ... }".
	echo "<div class='awc-wrap'><div class='awc-card'><pre style='max-height:640px;overflow:auto;white-space:pre;font-family:monospace;font-size:12px;margin:0;padding:10px'>".htmlspecialchars((string)$json->Content)."</pre></div></div>";
}
function config_file_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->ASDCHPAdmin){$tpl->js_no_privileges();return;}
	$tpl->js_dialog6("{APP_DHCP} >> {config_file}", "$page?config-file-popup=yes",1000);

}

function restart_script(){
    $page=CurrentPageName();
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $tpl=new template_admin();

    $js[]="if(document.getElementById('table-loader-dhcp-service') ){";
    $js[]="LoadAjax('table-loader-dhcp-service','fw.dhcp.configuration.php?interfaces=yes');";
    $js[]="}";

    if($EnableKEA==1){
        $js[]=$tpl->framework_buildjs(
            "/kea/dhcp/reload","kea.service.progress",
            "kea.service.log","progress-kea-restart","LoadAjax('kea-lists','fw.kea.php?interfaces=yes');");

        return @implode(" ",$js);

    }

    $js[]=$tpl->framework_buildjs("/dhcpd/service/restart", "dhcpd.progress","dhcpd.progress.txt","progress-dhcp-restart");

    return @implode(" ",$js);
}

