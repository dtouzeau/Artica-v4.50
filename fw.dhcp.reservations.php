<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpmetrics.inc");

// Les repartiteurs ?delete-rule-js= / delete-remove (delete_js(), delete_remove())
// et ?enable-signature= / ?enable-firewall= appelaient des fonctions definies
// NULLE PART: fatal « Call to undefined function ». Retires.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){table();exit;}
if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_GET["dhcpfixed"])){dhcpfixed();exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{reservations}",ico_computer_down,"{dhcp_reservations_explain}","$page?search-form=yes","dhcp-reservations","progress-reservations-restart");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_DHCP} {reservations}",$html);
        echo $tpl->build_firewall();
        return;
    }
	echo $tpl->_ENGINE_parse_body($html);
}

// A MAC address, nothing else: the value reaches class.hosts.inc, which
// interpolates it into SQL.
function dres_mac($v):string{
    $v=strtolower(trim((string)$v));
    if(!preg_match('/^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/',$v)){return "";}
    return $v;
}

function dhcpfixed(){
    $mac=dres_mac($_GET["dhcpfixed"]);
    if($mac==""){return false;}
    $comp=new hosts($mac);
    if($comp->dhcpfixed==1){
        $comp->dhcpfixed=0;
        $comp->Save();
        return admin_tracks("Remove host $mac from DHCP reservation");
    }
    $comp->dhcpfixed=1;
    $comp->Save();
    return admin_tracks("Add host $mac to reservation");
}

function search_form(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>".$tpl->search_block($page)."</div>";
}

// Search term -> SQL LIKE pattern. postgres_sql has no bound parameters, so the
// term is reduced to the characters a MAC, an IP or a hostname can carry: no
// quote, no backslash, no % can reach the SQL text.
function dres_like($raw):string{
    $s=strtolower(trim((string)$raw));
    $s=preg_replace('/[^a-z0-9.:_\-* ]/','',$s);
    $s=trim(substr($s,0,64));
    if(strlen($s)<2){return "";}
    $s=str_replace("*","%","%$s%");
    return preg_replace('/%+/','%',$s);
}

// The dynamic ranges dhcpd actually serves, from the DHCP metrics API.
function dres_pools():array{
    $json=dhcpm_api("/dhcpd/metrics/pools");
    $out=array();
    if(!is_object($json) || !isset($json->data->current->samples)){return $out;}
    foreach($json->data->current->samples as $s){
        $a=ip2long((string)$s->first); $b=ip2long((string)$s->last);
        if($a===false || $b===false){continue;}
        $out[]=array($a,$b,(string)$s->range);
    }
    return $out;
}

// MAC -> fixed-address of what dhcpd really loads (/etc/dhcpd-reservations.conf,
// written by articarest SetFixedAddresses()). null = file unreadable.
function dres_served():?array{
    $f="/etc/dhcpd-reservations.conf";
    if(!is_readable($f)){return null;}
    $out=array();
    $mac="";
    foreach(explode("\n",(string)@file_get_contents($f)) as $line){
        if(preg_match('/hardware ethernet\s+([0-9a-fA-F:]+)\s*;/',$line,$re)){$mac=strtolower($re[1]);$out[$mac]="";continue;}
        if($mac!="" && preg_match('/fixed-address\s+([0-9.]+)\s*;/',$line,$re)){$out[$mac]=$re[1];continue;}
        if(strpos($line,"}")!==false){$mac="";}
    }
    return $out;
}

function dres_ts($v):int{
    // hostsnet.updated is "timestamp without time zone" written with NOW()
    // under a GMT session: read it as UTC.
    $v=trim((string)$v);
    if($v==""){return 0;}
    $t=strtotime($v." UTC");
    return $t===false?0:$t;
}

function dres_style():string{
    return "<style>
.dres-flag{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;padding:3px 7px;border-radius:4px;margin-left:6px;background:#ffddb8;color:#653e00}
.dres-flag-err{background:#ffdad6;color:#93000a}
.dbc-flag{background:#d7e4ec;color:#1d4e6b}
.dres-badge-off{background:#e8ecec;color:#3d4947}
.dres-ip{font-size:14px;font-weight:600;color:#111d23;white-space:nowrap}
</style>";
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log",
            "progress-reservations-restart",
            "$function()"
        );
        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";
        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    $where="WHERE dhcpfixed=1";
    $like=dres_like($_GET["search"] ?? "");
    if($like!=""){
        $where.=" AND (TEXT(mac) LIKE '$like' OR host(ipaddr) LIKE '$like' OR LOWER(hostname) LIKE '$like' OR LOWER(proxyalias) LIKE '$like' OR LOWER(domainname) LIKE '$like' OR LOWER(fullhostname) LIKE '$like')";
    }
	$q=new postgres_sql();
    // The column is created by articarest (hostnets.PatchTables): an older
    // daemon has not created it yet, so never SELECT it blindly.
    $bcCol=$q->FIELD_EXISTS("hostsnet","dhcpbroadcast")?",dhcpbroadcast":"";
    $total=0;
    // QUERY_SQL returns false when PostgreSQL is down: pg_fetch_assoc(false) is a
    // TypeError in PHP 8, not silenced by @.
    $resT=$q->QUERY_SQL("SELECT COUNT(*) AS tcount FROM hostsnet WHERE dhcpfixed=1");
    $ligneT=($resT===false)?false:pg_fetch_assoc($resT);
    if(is_array($ligneT)){$total=intval($ligneT["tcount"]);}
	$results=$q->QUERY_SQL("SELECT mac,ipaddr,hostname,domainname,proxyalias,dhcpiface,gateway,updated$bcCol FROM hostsnet $where ORDER BY ipaddr LIMIT 1000");
    if(!$q->ok){
        echo $tpl->div_error(dhcpm_h($q->mysql_error));
        return false;
    }

    $rowsData=array();
    $ipCount=array();
    while ($ligne = pg_fetch_assoc($results)) {
        if(strlen((string)$ligne["mac"])<3){continue;}
        $rowsData[]=$ligne;
        $ip=trim((string)$ligne["ipaddr"]);
        if($ip!=""){$ipCount[$ip]=isset($ipCount[$ip])?$ipCount[$ip]+1:1;}
    }

    // Coherence against what the DHCP server really serves (ISC dhcpd only;
    // Kea has its own reservation path).
    $served=null;
    $include=1;
    if($EnableKEA==0){
        $include=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IncludeDHCPLdapDatabase"));
        $served=dres_served();
    }
    $pools=dres_pools();

    $CallBackMyComps=base64_encode($function);
    $icocomp=ico_computer;
    $rows=array();
    foreach($rowsData as $ligne){
        $mac=strtolower((string)$ligne["mac"]);
        $macenc=urlencode($mac);
        $ip=trim((string)$ligne["ipaddr"]);
        $name=trim((string)$ligne["hostname"]);
        $domain=trim((string)$ligne["domainname"]);
        if(strlen($domain)>3){$name="$name.$domain";}
        $alias=trim((string)$ligne["proxyalias"]);

        $jshost="Loadjs('fw.edit.computer.php?mac=$macenc&CallBackFunction=$CallBackMyComps')";
        $nameHtml=$name==""?"<span class='awc-muted'>{unknown}</span>":dhcpm_h($name);
        $meta=dhcpm_h($mac);
        if($alias!=""){$meta.=" &middot; ".dhcpm_h($alias);}

        $flags="";
        $ipl=ip2long($ip);
        if($ip=="" || $ip=="0.0.0.0" || $ipl===false){
            $flags.="<span class='dres-flag dres-flag-err'>{dres_invalid_ip}</span>";
        }else{
            foreach($pools as $p){
                if($ipl>=$p[0] && $ipl<=$p[1]){
                    $flags.="<span class='dres-flag' title='".dhcpm_h($p[2])."'>{dres_in_dynamic_range}</span>";
                    break;
                }
            }
            if($ipCount[$ip]>1){$flags.="<span class='dres-flag dres-flag-err'>{dres_duplicate_ip}</span>";}
        }
        if(intval($ligne["dhcpbroadcast"] ?? 0)==1){
            $flags.="<span class='dres-flag dbc-flag' title='{dbc_always_broadcast}'>{dbc_broadcast}</span>";
        }

        $gw=trim((string)$ligne["gateway"]);
        $gwHtml=($gw=="" || $gw=="0.0.0.0")?"<span class='awc-muted'>{default}</span>":dhcpm_h($gw);

        $upd=dres_ts($ligne["updated"]);
        $updHtml="<span class='awc-muted'>-</span>";
        if($upd>0){
            $updHtml="<div class='awc-seen'>".dhcpm_h(distanceOfTimeInWords($upd,time()))."</div>"
                ."<div class='awc-seen-sub'>".dhcpm_h($tpl->time_to_date($upd,true))."</div>";
        }

        $h="<tr>";
        $h.="<td><div class='awc-ident'><span class='awc-ico'><i class='$icocomp'></i></span><span>"
            ."<span class='awc-name'><a href=\"javascript:blur();\" OnClick=\"$jshost\">$nameHtml</a></span>"
            ."<div class='awc-meta'>$meta</div></span></div></td>";
        $h.="<td><span class='dres-ip'>".dhcpm_h($ip)."</span>$flags</td>";
        $h.="<td class='awc-mono'>$gwHtml</td>";
        if($EnableKEA==1){
            $h.="<td class='awc-mono'>".dhcpm_h(trim((string)$ligne["dhcpiface"]))."</td>";
        }
        $h.="<td>$updHtml</td>";
        if($EnableKEA==0){
            $isServed=is_array($served) && isset($served[$mac]) && $served[$mac]==$ip;
            if(!is_array($served)){
                $h.="<td class='center'><span class='awc-muted'>-</span></td>";
            }elseif($isServed){
                $h.="<td class='center'><span class='awc-badge awc-badge-on'>{dres_served}</span></td>";
            }else{
                $h.="<td class='center'><span class='awc-badge dres-badge-off'>{dres_not_served}</span></td>";
            }
        }
        $enable=$tpl->icon_check(1,"Loadjs('$page?dhcpfixed=$macenc')",null,"ASDCHPAdmin");
        $h.="<td><div class='awc-acts' style='align-items:center'>$enable"
            ."<a class='awc-act' title='{edit}' href=\"javascript:blur();\" OnClick=\"$jshost\"><i class='fas fa-pen'></i></a>"
            ."</div></td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $cols=6;
    $html=array();
    if($EnableKEA==0 && $include==0 && $total>0){
        $html[]=$tpl->div_warning("{dres_not_applied}||{dres_not_applied_explain}");
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{hostname}</th><th>{addr}</th><th>{gateway}</th>";
    if($EnableKEA==1){$html[]="<th>{nic}</th>";}
    $html[]="<th>{updated}</th>";
    if($EnableKEA==0){$html[]="<th class='center'>{status}</th>";}
    $html[]="<th style='text-align:right'>{actions}</th>";
    $html[]="</tr></thead><tbody id='dres-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='$cols'><div class='awc-empty'>{dres_no_reservation}</div></td></tr>";
    }else{
        $html[]=implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)." / $total {items}</span><span class='awc-pg' id='dres-pager'></span></div>";
    $html[]="</div></div>";

    $topbuttons=array();
    $topbuttons[] = array("Loadjs('fw.add.computer.php?CallBackFunction=$CallBackMyComps&dhcpfixed=1')", ico_plus, "{new_computer}");
    $jsreload=$tpl->framework_buildjs(
        "/kea/dhcp/reload","kea.service.progress",
        "kea.service.log","progress-reservations-restart","","","","ASDCHPAdmin");
    $topbuttons[] = array("$jsreload", ico_refresh, "{reload}");

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{reservations} $total {records}";
    $TINY_ARRAY["ICO"]=ico_computer_down;
    $TINY_ARRAY["EXPL"]="{dhcp_reservations_explain}";
    $TINY_ARRAY["URL"]="dhcp-reservations";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // CSS and JS are concatenated around the parse: _ENGINE_parse_body would
    // read every {…} block as a token.
    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$headsjs</script>";
    echo awc_table_style().dres_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script.awc_pager_js(25,"dres-rows","dres-pager");
    return true;
}
