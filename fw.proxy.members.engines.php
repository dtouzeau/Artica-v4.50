<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["table"])){table();exit;}
page();

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{your_proxy} {authentication}",
        ico_groups_finders,"{pauth_tiny_expl}","$page?start=yes",
        "proxy-auth-types","progress-squidauth-restart",false,"table-loader-squid-auth");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

/**
 * Hote rechargeable. L'identifiant auth-table et l'URL ?table=yes sont rappeles par
 * les trois fenetres de fw.proxy.members.php apres un enregistrement : ne pas les changer.
 */
function start():bool{
    $page=CurrentPageName();
    echo "<div id='auth-table' style='margin-top:10px'></div><script>LoadAjax('auth-table','$page?table=yes');</script>";
    return true;
}

/**
 * ===========================================================================
 * Onglet "Authentication" (maquette stitch_artica_proxy_authentication_redesign)
 * Modele unique (pauth_model) ; un widget = une fonction ; CSS/JS hors du parse.
 * Regle metier lue dans les handlers d'enregistrement de fw.proxy.members.php :
 * base locale, annuaire distant et RADIUS s'excluent ; Kerberos est independant.
 * ===========================================================================
 */

function pauth_nobr($html):string{
    return (string)preg_replace('#\s*<br\s*/?>\s*#i'," ",(string)$html);
}
function pauth_parse($t):string{
    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body($t);
}

/** Processus d'aide d'une famille (ntlm = Kerberos, basic = connexion par mot de passe). */
function pauth_processes(array $params,$kind):array{
    if($kind=="ntlm"){
        $max=intval($params["auth_param_ntlm_children"] ?? 20);
        $start=intval($params["auth_param_ntlm_startup"] ?? 0);
        $idle=intval($params["auth_param_ntlm_idle"] ?? 1);
        // Meme plancher que fw.proxy.kerberos.php : 5 / 5 / 1
        if($max<5){$max=5;}
        if($start<5){$start=5;}
        if($idle<1){$idle=1;}
        return array($max,$start,$idle);
    }
    $max=intval($params["auth_param_basic_children"] ?? 0);
    $start=intval($params["auth_param_basic_startup"] ?? 0);
    $idle=intval($params["auth_param_basic_idle"] ?? 0);
    if($max==0){$max=3;}
    if($start==0){$start=2;}
    if($idle==0){$idle=1;}
    return array($max,$start,$idle);
}

/**
 * Etat d'un annuaire LDAP utilise par le proxy (memes controles que l'ancien widget
 * de fw.proxy.members.php) : joignable ? declare dans authenticate.conf ? exige par
 * http_access_final.conf ? puis processus actifs et requetes depuis le demarrage.
 */
function pauth_ldap_state($host,$port):array{
    $st=array("state"=>"ok","err"=>"","active"=>null,"total"=>null,"requests"=>null);
    $errno=0;$errstr="";
    $fp=@fsockopen($host,intval($port),$errno,$errstr,2);
    if(!$fp){$st["state"]="down";$st["err"]=trim("$errno $errstr");return $st;}
    @fclose($fp);
    $re_host=str_replace(".","\\.",$host);
    $found=false;
    foreach(explode("\n",strval(@file_get_contents("/etc/squid3/authenticate.conf"))) as $line){
        if(preg_match("#basic_ldap_auth.*?-h $re_host -p ".intval($port)."#",$line)){$found=true;break;}
    }
    if(!$found){$st["state"]="notlinked";return $st;}
    $deny=false;
    foreach(explode("\n",strval(@file_get_contents("/etc/squid3/http_access_final.conf"))) as $line){
        if(preg_match("#http_access deny.*?ldapauth#",$line)){$deny=true;break;}
    }
    if(!$deny){$st["state"]="notrequired";return $st;}
    if(class_exists("cache_manager")){
        $manager=new cache_manager();
        foreach(explode("\n",strval($manager->makeQuery("basicauthenticator"))) as $line){
            $line=trim($line);
            if(preg_match("#number active:\s+([0-9]+) of ([0-9]+)#",$line,$re)){$st["active"]=intval($re[1]);$st["total"]=intval($re[2]);}
            if(preg_match("#requests sent:\s+([0-9]+)#",$line,$re)){$st["requests"]=intval($re[1]);}
        }
    }
    return $st;
}

/** Modele unique de l'onglet. */
function pauth_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $users=new usersMenus();
    $m["admin"]=(bool)$users->AsSquidAdministrator;
    $m["corp"]=(bool)$sock->CORP_LICENSE();
    $params=$sock->unserializeb64($sock->GET_INFO("SquidClientParams"));
    if(!is_array($params)){$params=array();}

    // ---- Kerberos (Windows domain) ------------------------------------------
    $Lock=intval($sock->GET_INFO("LockActiveDirectoryToKerberos"));
    if(intval($sock->GET_INFO("HaClusterClient"))==1){$Lock=1;}
    $kerb=(intval($sock->GET_INFO("EnableKerbAuth"))==1 || intval($sock->GET_INFO("UseNativeKerberosAuth"))==1 || $Lock==1);
    if(intval($sock->GET_INFO("EnableActiveDirectoryFeature"))==0){$kerb=false;}
    $m["kerb"]=array("on"=>($kerb && $m["corp"]),"proc"=>pauth_processes($params,"ntlm"));

    // ---- methodes a mot de passe (exclusives) --------------------------------
    $basic=pauth_processes($params,"basic");
    $local=intval($sock->GET_INFO("SquidStandardLDAPAuth"))==1;
    $m["local"]=array("on"=>$local,"openldap"=>intval($sock->GET_INFO("EnableOpenLDAP"))==1,"proc"=>$basic,"st"=>null);
    if($local){$m["local"]["st"]=pauth_ldap_state("127.0.0.1",389);}

    $remote=intval($sock->GET_INFO("SquidExternLDAPAUTH"))==1;
    $ext=$sock->unserializeb64($sock->GET_INFO("SquidExternalAuth"));
    if(!is_array($ext)){$ext=array();}
    $srv=trim(strval($ext["ldap_server"] ?? ""));
    $prt=intval($ext["ldap_port"] ?? 0);
    if($prt==0){$prt=389;}
    $m["remote"]=array("on"=>$remote,"server"=>$srv,"port"=>$prt,"user"=>trim(strval($ext["ldap_user"] ?? "")),"proc"=>$basic,"st"=>null);
    if($remote && $srv<>""){$m["remote"]["st"]=pauth_ldap_state($srv,$prt);}

    $radius=intval($sock->GET_INFO("SquidRadiusAuth"))==1;
    $rport=intval($sock->GET_INFO("radiusport"));
    if($rport==0){$rport=1812;}
    $m["radius"]=array("on"=>$radius,"server"=>trim(strval($sock->GET_INFO("radiusserver"))),"port"=>$rport,
        "id"=>trim(strval($sock->GET_INFO("radiusidentifier"))));

    $m["active"]=array();
    if($m["kerb"]["on"]){$m["active"][]=array("fab fa-windows","{pauth_kerb_name}");}
    if($local){$m["active"][]=array("fas fa-users","{pauth_local_name}");}
    if($remote){$m["active"][]=array("fas fa-sitemap","{pauth_remote_name}");}
    if($radius){$m["active"][]=array("fas fa-tower-broadcast","{pauth_radius_name}");}
    return $m;
}

/** Bandeau d'accroche + encadre "Currently active". */
function pauth_banner(array $m):string{
    $h=array();
    $h[]="<section class='pauth-card pauth-ban'>";
    $h[]="<div class='pauth-ban-main'><div class='pauth-ban-ico'><i class='fas fa-id-badge'></i></div><div>";
    $h[]="<h2>{pauth_ban_title}</h2><p>{pauth_ban_text}</p><div class='pauth-chips'>";
    $h[]="<span class='pauth-rchip'><i class='fas fa-user-shield'></i>{pauth_chip_passwords}</span>";
    $h[]="<span class='pauth-rchip'><i class='fab fa-windows'></i>{pauth_chip_windows}</span>";
    $h[]="<span class='pauth-rchip'><i class='fas fa-arrows-rotate'></i>{pauth_chip_change}</span>";
    $h[]="</div></div></div>";
    $h[]=pauth_active_box($m);
    $h[]="</section>";
    return implode("",$h);
}

/** Encadre "Currently active" (ou invitation quand personne n'est identifie). */
function pauth_active_box(array $m):string{
    if(count($m["active"])==0){
        $btn="";
        if($m["admin"]){
            $btn=$m["corp"]
                ?"<button type='button' class='pauth-btn' OnClick=\"Loadjs('fw.proxy.kerberos.php?js=yes')\"><i class='fab fa-windows'></i>{pauth_empty_kerb}</button>"
                :"<button type='button' class='pauth-btn' OnClick=\"Loadjs('fw.proxy.members.php?local-ldap-js=yes')\"><i class='fas fa-users'></i>{pauth_empty_local}</button>";
        }
        return "<aside class='pauth-act pauth-act-none'><div class='pauth-act-t'><i class='fas fa-triangle-exclamation'></i>{pauth_none_t}</div>"
            ."<p>{pauth_none}</p>$btn</aside>";
    }
    $h="<aside class='pauth-act'><div class='pauth-act-t'>{pauth_active_t}</div><div class='pauth-act-list'>";
    foreach($m["active"] as $a){
        $h.="<div class='pauth-act-row'><i class='{$a[0]}'></i><span>{$a[1]}</span><span class='pauth-pill pauth-pill-ok'><i class='fas fa-circle-check'></i>{pauth_st_on}</span></div>";
    }
    return $h."</div></aside>";
}

/** Rappel de la regle d'exclusivite, toujours visible. */
function pauth_rule():string{
    return "<div class='pauth-rule'><i class='fas fa-circle-info'></i><span><strong>{pauth_rule_t}</strong> {pauth_rule}</span></div>";
}

/** Pastille d'etat d'une carte. */
function pauth_pill($state):string{
    $map=array(
        "on"=>array("ok","fas fa-circle-check","{pauth_st_on}"),
        "off"=>array("off","fas fa-circle-pause","{pauth_st_off}"),
        "down"=>array("bad","fas fa-circle-xmark","{pauth_st_down}"),
        "notlinked"=>array("warn","fas fa-triangle-exclamation","{pauth_st_notlinked}"),
        "notrequired"=>array("warn","fas fa-triangle-exclamation","{pauth_st_notrequired}"),
        "license"=>array("warn","fas fa-lock","{pauth_st_license}"),
        "service"=>array("warn","fas fa-power-off","{pauth_st_service}"));
    $d=$map[$state] ?? $map["off"];
    return "<span class='pauth-pill pauth-pill-{$d[0]}'><i class='{$d[1]}'></i>{$d[2]}</span>";
}

/** Ossature commune d'une carte de methode. */
function pauth_card($state,$ico,$name,$sub,$how,$body,$foot):string{
    $cls=($state=="on")?" pauth-mcard-on":"";
    return "<section class='pauth-card pauth-mcard$cls'><div class='pauth-mh'><span class='pauth-mico'><i class='$ico'></i></span>"
        ."<div class='pauth-mt'><b>$name</b><span>$sub</span></div>".pauth_pill($state)."</div>"
        ."<p class='pauth-how'>$how</p><div class='pauth-mbody'>$body</div><div class='pauth-mfoot'>$foot</div></section>";
}

function pauth_kv($k,$v,$mono=false):string{
    $cls=$mono?" pauth-mono":"";
    return "<div class='pauth-kv'><span>$k</span><b class='$cls'>$v</b></div>";
}

function pauth_proc_text(array $p):string{
    return str_replace(array("%M","%S","%I"),array($p[0],$p[1],$p[2]),pauth_parse("{pauth_proc}"));
}

/** Detail d'etat d'un annuaire LDAP actif (base locale ou distante). */
function pauth_ldap_detail($st,$addr):string{
    if(!is_array($st)){return "";}
    $h="<div class='pauth-stat'>";
    if($st["state"]=="down"){
        $msg=str_replace("%A",htmlspecialchars($addr),pauth_parse("{pauth_ldap_down}"));
        $h.="<div class='pauth-err'><i class='fas fa-circle-xmark'></i> $msg".($st["err"]<>""?" &mdash; ".htmlspecialchars($st["err"]):"")."</div>";
    }elseif($st["state"]=="notlinked"){
        $h.="<div class='pauth-warn'><i class='fas fa-triangle-exclamation'></i> {pauth_ldap_notlinked}</div>";
    }elseif($st["state"]=="notrequired"){
        $h.="<div class='pauth-warn'><i class='fas fa-triangle-exclamation'></i> {pauth_ldap_notrequired}</div>";
    }else{
        $h.="<div class='pauth-ok'><i class='fas fa-circle-check'></i> ".str_replace("%A",htmlspecialchars($addr),pauth_parse("{pauth_ldap_ok}"))."</div>";
        if($st["total"]!==null){
            $pct=$st["total"]>0?round($st["active"]*100/$st["total"]):0;
            $h.="<div class='pauth-live'><svg class='pauth-donut' viewBox='0 0 36 36' role='img' aria-label='{$st["active"]} / {$st["total"]}'>"
                ."<circle cx='18' cy='18' r='15.9155' class='pauth-donut-bg'/><circle cx='18' cy='18' r='15.9155' class='pauth-donut-fg' stroke-dasharray='$pct 100'/></svg>"
                ."<span>".str_replace(array("%A","%T"),array("<strong>{$st["active"]}</strong>","<strong>{$st["total"]}</strong>"),pauth_parse("{pauth_ldap_active}"))."</span>";
            if($st["requests"]!==null){
                $tpl=new template_admin();
                $h.="<span class='pauth-live-r'>{pauth_ldap_requests}: <strong>".$tpl->FormatNumber($st["requests"])."</strong></span>";
            }
            $h.="</div>";
        }
    }
    return $h."</div>";
}

function pauth_btn($label,$js,$primary=true,$disabled=false):string{
    $cls=$primary?"pauth-btn":"pauth-btn pauth-btn-ghost";
    if($disabled){return "<button type='button' class='$cls' disabled aria-disabled='true'>$label</button>";}
    return "<button type='button' class='$cls' OnClick=\"$js\">$label</button>";
}

/** Carte "Windows domain" (Kerberos). */
function pauth_card_kerb(array $m):string{
    $k=$m["kerb"];
    if(!$m["corp"]){
        $body="<div class='pauth-lic'><i class='fas fa-lock'></i><span>{pauth_kerb_license}</span></div>";
        $foot=pauth_btn("{pauth_configure}","",true,true)."<span class='pauth-foot-note'>{pauth_kerb_license_why}</span>";
        return pauth_card("license","fab fa-windows","{pauth_kerb_name}","{kerberos_authentication}","{pauth_kerb_how}",$body,$foot);
    }
    $body="";
    if($k["on"]){$body=pauth_kv("{pauth_helpers}",pauth_proc_text($k["proc"]));}
    $foot=pauth_btn("{pauth_configure}","Loadjs('fw.proxy.kerberos.php?js=yes')");
    return pauth_card($k["on"]?"on":"off","fab fa-windows","{pauth_kerb_name}","{kerberos_authentication}","{pauth_kerb_how}",$body,$foot);
}

/** Carte "Local user database". */
function pauth_card_local(array $m):string{
    $l=$m["local"];
    $state=$l["on"]?"on":"off";
    if($l["on"] && is_array($l["st"]) && $l["st"]["state"]!="ok"){$state=$l["st"]["state"];}
    if(!$l["openldap"]){$state="service";}
    $body="";
    if(!$l["openldap"]){$body.="<div class='pauth-warn'><i class='fas fa-power-off'></i> {pauth_local_service}</div>";}
    if($l["on"]){
        $body.=pauth_ldap_detail($l["st"],"127.0.0.1:389");
        $body.=pauth_kv("{pauth_helpers}",pauth_proc_text($l["proc"]));
    }
    $foot=pauth_btn("{pauth_configure}","Loadjs('fw.proxy.members.php?local-ldap-js=yes')");
    if($l["on"]){$foot.=pauth_btn("{pauth_helpers}","Loadjs('fw.proxy.general.php?basic-authentication-js=yes&restart-id=progress-squidauth-restart')",false);}
    return pauth_card($state,"fas fa-users","{pauth_local_name}","{pauth_local_sub}","{pauth_local_how}",$body,$foot);
}

/** Carte "Company directory (LDAP)". */
function pauth_card_remote(array $m):string{
    $r=$m["remote"];
    $state=$r["on"]?"on":"off";
    if($r["on"] && is_array($r["st"]) && $r["st"]["state"]!="ok"){$state=$r["st"]["state"];}
    $body="";
    if($r["server"]<>""){
        $body.=pauth_kv("{pauth_server}",htmlspecialchars($r["server"].":".$r["port"]),true);
        if($r["user"]<>""){$body.=pauth_kv("{pauth_account}",htmlspecialchars($r["user"]),true);}
    }else{
        $body.="<div class='pauth-muted'>{pauth_not_set}</div>";
    }
    if($r["on"]){
        $body.=pauth_ldap_detail($r["st"],$r["server"].":".$r["port"]);
        $body.=pauth_kv("{pauth_helpers}",pauth_proc_text($r["proc"]));
    }
    $foot=pauth_btn("{pauth_configure}","Loadjs('fw.proxy.members.php?remote-ldap-js=yes')");
    if($r["on"]){$foot.=pauth_btn("{pauth_helpers}","Loadjs('fw.proxy.general.php?basic-authentication-js=yes&restart-id=progress-squidauth-restart')",false);}
    return pauth_card($state,"fas fa-sitemap","{pauth_remote_name}","{pauth_remote_sub}","{pauth_remote_how}",$body,$foot);
}

/** Carte "RADIUS server". */
function pauth_card_radius(array $m):string{
    $r=$m["radius"];
    $body="";
    if($r["server"]<>""){
        $body.=pauth_kv("{pauth_server}",htmlspecialchars($r["server"].":".$r["port"]),true);
        if($r["id"]<>""){$body.=pauth_kv("{pauth_identifier}",htmlspecialchars($r["id"]),true);}
    }else{
        $body.="<div class='pauth-muted'>{pauth_not_set}</div>";
    }
    if($r["on"]){$body.="<div class='pauth-muted'><i class='fas fa-circle-info'></i> {pauth_radius_nostatus}</div>";}
    $foot=pauth_btn("{pauth_configure}","Loadjs('fw.proxy.members.php?radius-config-js=yes')");
    return pauth_card($r["on"]?"on":"off","fas fa-tower-broadcast","{pauth_radius_name}","{authenticate_users_radius}","{pauth_radius_how}",$body,$foot);
}

function pauth_footnote():string{
    return "<div class='pauth-foot'><i class='fas fa-microchip'></i><span><strong>{pauth_helpers}</strong> &mdash; {pauth_helpers_explain}</span></div>";
}

function pauth_style():string{
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$soft=$a["accent_soft"];$dark=$a["accent_dark"];
    }
    $c=array();
    $c[]=".pauth{display:flex;flex-direction:column;gap:16px;padding:4px 2px 24px;color:#1e293b;font-size:14px}";
    $c[]=".pauth-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px}";
    $c[]=".pauth-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".pauth-ban{display:flex;gap:24px;justify-content:space-between;align-items:flex-start;padding:24px 28px;border-left:4px solid $accent}";
    $c[]=".pauth-ban-main{display:flex;gap:16px;max-width:820px}";
    $c[]=".pauth-ban-ico{flex:0 0 48px;height:48px;border-radius:10px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:22px}";
    $c[]=".pauth-ban h2{margin:0 0 6px;font-size:22px;font-weight:600;color:#0f172a}";
    $c[]=".pauth-ban p{margin:0;line-height:1.6;color:#475569}";
    $c[]=".pauth-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}";
    $c[]=".pauth-rchip{display:inline-flex;align-items:center;gap:7px;background:#f1f5f9;border-radius:999px;padding:5px 12px;font-size:13px;color:#334155}";
    $c[]=".pauth-rchip svg{color:$dark}";
    $c[]=".pauth-act{flex:0 0 340px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px}";
    $c[]=".pauth-act-t{display:flex;gap:8px;align-items:center;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#334155;margin-bottom:10px}";
    $c[]=".pauth-act-list{display:flex;flex-direction:column;gap:8px}";
    $c[]=".pauth-act-row{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:14px}";
    $c[]=".pauth-act-row>span:first-of-type{flex:1}";
    $c[]=".pauth-act-row>svg:first-child{color:$dark}";
    $c[]=".pauth-act-none{background:#fffbeb;border-color:#fde68a;color:#92400e}";
    $c[]=".pauth-act-none .pauth-act-t{color:#92400e;text-transform:none;letter-spacing:0;font-size:14.5px}";
    $c[]=".pauth-act-none p{margin:0 0 12px;font-size:13.5px;line-height:1.5}";
    $c[]=".pauth-rule{display:flex;gap:10px;align-items:flex-start;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;color:#1e3a8a;font-size:14px;line-height:1.5}";
    $c[]=".pauth-rule>svg{color:#1d4ed8;margin-top:3px}";
    $c[]=".pauth-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}";
    $c[]=".pauth-mcard{display:flex;flex-direction:column;padding:20px 22px;min-height:220px}";
    $c[]=".pauth-mcard-on{border-top:3px solid $dark}";
    $c[]=".pauth-mh{display:flex;align-items:flex-start;gap:12px}";
    $c[]=".pauth-mico{flex:0 0 40px;height:40px;border-radius:10px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center;font-size:18px}";
    $c[]=".pauth-mcard-on .pauth-mico{background:$soft;color:$dark}";
    $c[]=".pauth-mt{flex:1;display:flex;flex-direction:column;min-width:0}";
    $c[]=".pauth-mt b{font-size:16px;font-weight:600;color:#0f172a}";
    $c[]=".pauth-mt span{font-size:13px;color:#64748b}";
    $c[]=".pauth-how{margin:12px 0 10px;color:#334155;line-height:1.55}";
    $c[]=".pauth-mbody{flex:1;display:flex;flex-direction:column;gap:8px}";
    $c[]=".pauth-mfoot{display:flex;align-items:center;gap:10px;margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;flex-wrap:wrap}";
    $c[]=".pauth-foot-note{font-size:13px;color:#92400e}";
    $c[]=".pauth-kv{display:flex;justify-content:space-between;gap:12px;font-size:13.5px;padding:6px 0;border-bottom:1px dashed #e2e8f0}";
    $c[]=".pauth-kv span{color:#64748b}.pauth-kv b{font-weight:500;color:#0f172a;text-align:right;word-break:break-all}";
    $c[]=".pauth-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 11px;font-size:13px;font-weight:600;border:1px solid;white-space:nowrap}";
    $c[]=".pauth-pill-ok{background:#ecfdf5;border-color:#a7f3d0;color:#047857}";
    $c[]=".pauth-pill-off{background:#f1f5f9;border-color:#e2e8f0;color:#64748b}";
    $c[]=".pauth-pill-bad{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
    $c[]=".pauth-pill-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}";
    $c[]=".pauth-stat{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;display:flex;flex-direction:column;gap:8px}";
    $c[]=".pauth-ok{color:#047857;font-size:13.5px}.pauth-err{color:#b91c1c;font-size:13.5px}.pauth-warn{color:#92400e;font-size:13.5px}";
    $c[]=".pauth-live{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:13.5px;color:#334155}";
    $c[]=".pauth-live-r{margin-left:auto;color:#64748b}";
    $c[]=".pauth-donut{width:30px;height:30px;transform:rotate(-90deg)}";
    $c[]=".pauth-donut circle{fill:none;stroke-width:5}.pauth-donut-bg{stroke:#e2e8f0}.pauth-donut-fg{stroke:#047857;stroke-linecap:round}";
    $c[]=".pauth-lic{display:flex;gap:10px;align-items:flex-start;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;color:#92400e;font-size:13.5px;line-height:1.5}";
    $c[]=".pauth-muted{font-size:13px;color:#64748b}";
    $c[]=".pauth-btn{display:inline-flex;align-items:center;gap:8px;background:$dark;color:#fff;border:1px solid $dark;border-radius:8px;padding:8px 16px;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap}";
    $c[]=".pauth-btn:hover{filter:brightness(1.08)}";
    $c[]=".pauth-btn-ghost{background:#fff;color:$dark;border-color:#cbd5e1}";
    $c[]=".pauth-btn[disabled]{background:#f1f5f9;color:#94a3b8;border-color:#e2e8f0;cursor:not-allowed;filter:none}";
    $c[]=".pauth-btn:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".pauth-foot{display:flex;gap:10px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;font-size:13.5px;color:#475569;line-height:1.5}";
    $c[]=".pauth-foot>svg{color:$dark;margin-top:3px}";
    $c[]="@media (max-width:1200px){.pauth-ban{flex-direction:column}.pauth-act{flex:none;width:100%;box-sizing:border-box}.pauth-grid{grid-template-columns:1fr}}";
    $c[]="@media (prefers-reduced-motion:reduce){.pauth *{transition:none!important;animation:none!important}}";
    return "<style>".implode("\n",$c)."</style>";
}

/** Rafraichissement de l'hote toutes les 30 s (etat des annuaires), jamais sous une modale ou une progression. */
function pauth_js():string{
    $page=CurrentPageName();
    return "<script>
(function(){
if(window.PauthTimer){clearTimeout(window.PauthTimer);}
function busy(){ var p=document.getElementById('progress-squidauth-restart');
  return document.querySelector('.modal.in,.modal.show,.jconfirm,.sweet-alert.visible')!==null || (p && p.innerHTML.trim()!==''); }
function tick(){
  if(!document.getElementById('pauth-root')){return;}
  if(!busy()){ LoadAjaxSilent('auth-table','$page?table=yes'); return; }
  window.PauthTimer=setTimeout(tick,30000);
}
window.PauthTimer=setTimeout(tick,30000);
})();
</script>";
}

function table():bool{
    $tpl=new template_admin();
    $m=pauth_model();
    $html=array();
    $html[]="<div class='pauth' id='pauth-root'>";
    $html[]=pauth_banner($m);
    $html[]=pauth_rule();
    $html[]="<div class='pauth-grid'>".pauth_card_kerb($m).pauth_card_local($m).pauth_card_remote($m).pauth_card_radius($m)."</div>";
    $html[]=pauth_footnote();
    $html[]="</div>";

    $jsrestart=$tpl->framework_buildjs("/proxy/ntlm/reconfigure","onlyntlm.progress","onlyntlm.progress.log","progress-squidauth-restart");
    $topbuttons=array();
    if($m["admin"]){$topbuttons[]=array($jsrestart,ico_retweet,"{pauth_reconfigure}");}
    $TINY_ARRAY=array();
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{your_proxy} {authentication}";
    $TINY_ARRAY["ICO"]=ico_groups_finders;
    $TINY_ARRAY["URL"]="proxy-auth-types";
    $TINY_ARRAY["EXPL"]="{pauth_tiny_expl}";
    $html[]="<script>NoSpinner();Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";

    echo pauth_style().pauth_nobr($tpl->_ENGINE_parse_body(implode("\n",$html))).pauth_js();
    return true;
}
