<?php
// stamp 01/11/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

// Contrôle de droits côté serveur : form_outside(…,"AsSquidAdministrator") ne le fait que dans le navigateur.
$users=new usersMenus();if(!$users->AsSquidAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["port-popup"])){connected_port_popup();exit;}
if(isset($_POST["SaveConnectedPort"])){post_save();exit;}
if(isset($_GET["port-delete-js"])){port_delete_js();exit;}
if(isset($_POST["port-delete"])){connected_port_delete();exit;}
if(isset($_GET["tlimit-js"])){tlimit_js();exit;}
if(isset($_GET["tlimit-popup"])){tlimit_popup();exit;}
if(isset($_GET["fix-rpfilter"])){fix_rp_filter();exit;}
table_start();

/**
 * ===========================================================================
 * Onglet "Transparent ports" (maquette stitch_transparent_proxy_ports_redesign)
 * Modele unique (tpx_model) : la table transparent_ports + le validateur du demon
 * /proxy/transparent/validate (5 verifications par port + noyau). Le validateur
 * ne couvre que les ports actifs, sans TPROXY ni WCCP (TransparentPorts.CurrentPorts) :
 * pour ceux-la seulement, la sonde locale tp_probe() prend le relais.
 * Un widget = une fonction ; CSS/JS concatenes HORS de _ENGINE_parse_body().
 * ===========================================================================
 */

/** Hote rechargeable : l'identifiant et l'URL sont rappeles par 3 autres pages. */
function table_start(){
    echo "<div id='proxy-transparent-ports' style='margin-top:10px'></div>"
        ."<script>LoadAjax('proxy-transparent-ports','fw.proxy.transparent.php?table=yes');</script>";
}

function tpx_nobr($html):string{
    return (string)preg_replace('#\s*<br\s*/?>\s*#i'," ",(string)$html);
}

/**
 * Regles d'interception presentes dans le pare-feu, indexees par port local.
 * Sert la sonde de repli (ports TPROXY / WCCP, ou validateur indisponible).
 */
function tp_firewall_ports():array{
    $TTRANSPARENTS=array();$TTPROXY=array();
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/iptablesave"));
    if(!is_object($data)){return array($TTRANSPARENTS,$TTPROXY);}
    if(!property_exists($data,"Data")){return array($TTRANSPARENTS,$TTPROXY);}
    foreach (explode("\n",strval($data->Data)) as $line){
        if(strpos("   $line","-A ")==0){continue;}
        if(preg_match("#REDIRECT.*?--to-port.*?([0-9]+)#",$line,$re)){$TTRANSPARENTS[$re[1]]=true;continue;}
        if(preg_match("#DNAT\s+--to-destination.*?:([0-9]+)#",$line,$re)){$TTRANSPARENTS[$re[1]]=true;continue;}
        if(preg_match("#TPROXY.*?--on-port.*?([0-9]+)#",$line,$re)){$TTPROXY[intval($re[1])]=true;continue;}
    }
    return array($TTRANSPARENTS,$TTPROXY);
}

/**
 * Sonde de repli : le port ecoute-t-il, et la regle d'interception est-elle posee ?
 * Retourne array(etat ok|down|fw, message systeme).
 */
function tp_probe($ligne,$TTRANSPARENTS,$TTPROXY):array{
    $localport=intval($ligne["localport"]);
    $addr="127.0.0.1";
    if(strval($ligne["nic"] ?? "")<>""){
        $net=new system_nic($ligne["nic"]);
        $addr=$net->IPADDR;
    }
    $errno=0;$errstr="";
    $fp=@fsockopen($addr,$localport,$errno,$errstr,1);
    if(!$fp){return array("down",strval($errstr));}
    @fclose($fp);
    if(intval($ligne["TProxy"])==1){
        if(!isset($TTPROXY[$localport])){return array("fw","");}
    }else{
        if(!isset($TTRANSPARENTS[$localport])){return array("fw","");}
    }
    return array("ok","");
}

/** Une verification du validateur traduite en phrase courante. */
function tpx_issue_text($check,$ligne):string{
    $tpl=new template_admin();
    $lp=intval($ligne["localport"] ?? 0);
    if(preg_match("#^iptables_prerouting_dport_([0-9]+)$#",$check,$re)){
        return str_replace("%P",$re[1],$tpl->_ENGINE_parse_body("{tpx_is_redirect}"));
    }
    if(preg_match("#^iptables_dest_exclusion_dport_([0-9]+)$#",$check,$re)){
        return str_replace("%P",$re[1],$tpl->_ENGINE_parse_body("{tpx_is_private}"));
    }
    if($check=="iptables_output_squid_bypass"){return $tpl->_ENGINE_parse_body("{tpx_is_bypass}");}
    if($check=="squid_listen_ports_conf"){return str_replace("%L",strval($lp),$tpl->_ENGINE_parse_body("{tpx_is_conf}"));}
    if($check=="squid_tcp_listen"){return str_replace("%L",strval($lp),$tpl->_ENGINE_parse_body("{tpx_is_listen}"));}
    return htmlspecialchars($check);
}

/** Libelle courant d'un parametre noyau (le nom technique reste en sous-titre). */
function tpx_kernel_label($param):string{
    $map=array("ip_forward"=>"{tpx_k_ip_forward}","rp_filter"=>"{tpx_k_rp_filter}","forwarding"=>"{tpx_k_forwarding}",
        "send_redirects"=>"{tpx_k_send_redirects}","accept_redirects"=>"{tpx_k_accept_redirects}",
        "ip_nonlocal_bind"=>"{tpx_k_nonlocal}","tcp_syncookies"=>"{tpx_k_syncookies}");
    $last=preg_replace('#^.*\.#',"",$param);
    $label=$map[$last] ?? htmlspecialchars($param);
    // net.ipv4.conf.<nic>.xxx : la carte concernee entre parentheses
    if(preg_match('#^net\.ipv4\.conf\.([^.]+)\.#',$param,$re) && $re[1]<>"all"){
        $label.=" (".htmlspecialchars($re[1]).")";
    }
    return $label;
}

/** Modele unique de l'onglet. */
function tpx_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $m["hacluster"]=intval($sock->GET_INFO("HaClusterClient"))==1;
    $m["urgency"]=intval($sock->GET_INFO("SquidSSLUrgency"))==1;
    $m["rules"]=array();

    // ---- validateur du demon -------------------------------------------------
    $v=json_decode($sock->REST_API("/proxy/transparent/validate"));
    $m["validator"]=is_object($v);
    $m["squid_running"]=true;
    $m["kernel"]=array();
    $m["masq_missing"]=false;
    $byId=array();
    if(is_object($v)){
        if(property_exists($v,"squid_running")){$m["squid_running"]=(bool)$v->squid_running;}
        if(isset($v->ports) && is_array($v->ports)){
            foreach($v->ports as $p){
                $byId[intval($p->id ?? 0)]=$p;
                if(isset($p->IsMasquerade) && !$p->IsMasquerade){$m["masq_missing"]=true;}
            }
        }
        if(isset($v->kernel) && is_array($v->kernel)){
            foreach($v->kernel as $k){
                if(!empty($k->status)){continue;}
                $m["kernel"][]=array("param"=>strval($k->param ?? ""),"current"=>strval($k->current ?? ""),"expected"=>strval($k->expected ?? ""));
            }
        }
    }

    // ---- regles + exclusions -------------------------------------------------
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM transparent_ports ORDER BY port");
    if(!is_array($results)){$results=array();}
    $wbl=array();
    $rw=$q->QUERY_SQL("SELECT portid,include,count(pattern) as tcount FROM proxy_ports_wbl GROUP BY portid,include");
    if(is_array($rw)){
        foreach($rw as $r){$wbl[intval($r["portid"])][intval($r["include"])]=intval($r["tcount"]);}
    }
    $fw=null;
    foreach($results as $ligne){
        $ID=intval($ligne["ID"]);
        $rule=tpx_rule($ligne,$wbl[$ID] ?? array());
        if(intval($ligne["enabled"])==0){
            $rule["state"]="off";
        }elseif(isset($byId[$ID])){
            $rule["checked"]=true;
            $p=$byId[$ID];
            $rule["state"]=!empty($p->status)?"ok":"fw";
            if(isset($p->issues) && is_array($p->issues)){
                foreach($p->issues as $is){
                    $check=strval($is->check ?? "");
                    if($check=="squid_tcp_listen"){$rule["state"]="down";$rule["err"]=strval($is->current ?? "");}
                    elseif($check=="squid_listen_ports_conf" && $rule["state"]!="down"){$rule["state"]="conf";}
                    $rule["issues"][]=array("text"=>tpx_issue_text($check,$ligne),"check"=>$check);
                }
            }
        }else{
            // TPROXY, WCCP ou validateur absent : sonde locale (un seul iptables-save)
            if($fw===null){$fw=tp_firewall_ports();}
            list($st,$err)=tp_probe($ligne,$fw[0],$fw[1]);
            $rule["state"]=$st;$rule["err"]=$err;
        }
        $m["rules"][]=$rule;
    }

    $m["n_total"]=count($m["rules"]);
    $m["n_enabled"]=0;$m["n_ok"]=0;$m["n_issues"]=0;
    foreach($m["rules"] as $r){
        if($r["state"]=="off"){continue;}
        $m["n_enabled"]++;
        if($r["state"]=="ok"){$m["n_ok"]++;}else{$m["n_issues"]++;}
    }
    return $m;
}

/** Une ligne de transparent_ports traduite en mots courants. */
function tpx_rule(array $ligne,array $wbl):array{
    $ID=intval($ligne["ID"]);
    $ports=array();
    $ports[]=intval($ligne["port"]);
    foreach(explode(",",strval($ligne["others_ports"] ?? "")) as $o){
        $o=intval(trim($o));
        if($o>0){$ports[]=$o;}
    }
    $iface="{all_interfaces}";
    $nic=strval($ligne["nic"] ?? "");
    if($nic<>""){
        $z=new system_nic($nic);
        $iface=htmlspecialchars($nic.(trim(strval($z->IPADDR))<>""?" · ".$z->IPADDR:""));
    }
    $out="{all_interfaces}";
    $onic=strval($ligne["outgoing_nic"] ?? "");
    if($onic<>""){
        $z=new system_nic($onic);
        $out=htmlspecialchars($onic.(trim(strval($z->IPADDR))<>""?" · ".$z->IPADDR:""));
    }
    $hidden=intval($ligne["hiddenID"] ?? 0);
    $type="std";
    if($hidden==10 || $hidden==20){$type="wccp";}
    if(intval($ligne["TProxy"] ?? 0)==1){$type="tproxy";}
    $name=trim(strval($ligne["PortName"] ?? ""));
    if($name==""){$name="#$ID";}
    return array("id"=>$ID,"name"=>htmlspecialchars($name),"ports"=>$ports,"local"=>intval($ligne["localport"]),
        "iface"=>$iface,"out"=>$out,"type"=>$type,"dnat"=>intval($ligne["dnat"] ?? 0)==1,
        "cert"=>htmlspecialchars(strval($ligne["sslcertificate"] ?? "")),
        "cache"=>intval($ligne["NoCache"] ?? 0)==0,"filter"=>intval($ligne["NoFilter"] ?? 0)==0,
        "src"=>intval($wbl[0] ?? 0),"dst"=>intval($wbl[1] ?? 0),
        "state"=>"ok","err"=>"","issues"=>array(),"checked"=>false);
}

/** Bandeau d'accroche + encadre "Good to know". */
function tpx_banner():string{
    $page=CurrentPageName();
    $h=array();
    $h[]="<section class='tpx-card tpx-ban'>";
    $h[]="<div class='tpx-ban-main'><div class='tpx-ban-ico'><i class='fas fa-route'></i></div><div>";
    $h[]="<h2>{tpx_ban_title}</h2><p>{tpx_ban_text}</p>";
    $h[]="<div class='tpx-chips'>";
    $h[]="<span class='tpx-rchip'><i class='fas fa-laptop'></i>{tpx_chip_nothing}</span>";
    $h[]="<span class='tpx-rchip'><i class='fas fa-stethoscope'></i>{tpx_chip_checked}</span>";
    $h[]="<span class='tpx-rchip'><i class='fas fa-person-walking-arrow-right'></i>{tpx_chip_leftout}</span>";
    $h[]="</div></div></div>";
    $h[]="<aside class='tpx-know'><div class='tpx-know-t'><i class='fas fa-circle-info'></i>{tpx_know_t}</div>";
    $h[]="<p>{tpx_know}</p>";
    $h[]="<a href='javascript:blur();' class='tpx-know-l' OnClick=\"Loadjs('$page?tlimit-js=yes')\">{notice}: {limits}</a></aside>";
    $h[]="</section>";
    return implode("",$h);
}

/** Carte de sante : une ligne verte si tout va bien, la liste des echecs sinon. */
function tpx_health(array $m):string{
    if($m["n_enabled"]==0){return tpx_kernel_block($m,true);}
    if(!$m["squid_running"]){
        return "<section class='tpx-card tpx-health tpx-health-bad'><div class='tpx-health-h'><i class='fas fa-circle-stop'></i>"
            ."<div><b>{tpx_h_stopped}</b><span>{tpx_h_stopped_text}</span></div>".tpx_restart_button()."</div>"
            ."<div id='tpx-progress'></div>".tpx_kernel_block($m,false)."</section>";
    }
    if($m["n_issues"]==0 && count($m["kernel"])>0){
        // Regles saines, noyau hors des valeurs attendues : ligne verte + bloc ambre, pas de rouge.
        $t=str_replace("%N","<strong>{$m["n_ok"]}</strong>",tpx_parse("{tpx_h_ok}"));
        return "<section class='tpx-card tpx-health tpx-health-ok'><div class='tpx-health-h'><i class='fas fa-circle-check'></i>"
            ."<div><b>$t</b><span>{tpx_h_ok_text}</span></div></div></section>".tpx_kernel_block($m,true);
    }
    if($m["n_issues"]==0 && count($m["kernel"])==0){
        $t=str_replace("%N","<strong>{$m["n_ok"]}</strong>",tpx_parse("{tpx_h_ok}"));
        return "<section class='tpx-card tpx-health tpx-health-ok'><div class='tpx-health-h'><i class='fas fa-circle-check'></i>"
            ."<div><b>$t</b><span>{tpx_h_ok_text}</span></div><span class='tpx-pill tpx-pill-soft'><i class='fas fa-rotate'></i>{tpx_h_refresh}</span></div></section>";
    }
    $h=array();
    $h[]="<section class='tpx-card tpx-health tpx-health-bad'>";
    if($m["n_issues"]>0){
        $t=str_replace(array("%N","%T"),array("<strong>{$m["n_issues"]}</strong>","<strong>{$m["n_enabled"]}</strong>"),tpx_parse("{tpx_h_bad_text}"));
        $h[]="<div class='tpx-health-h'><i class='fas fa-triangle-exclamation'></i><div><b>{tpx_h_bad}</b><span>$t</span></div>".tpx_restart_button()."</div>";
        $h[]="<div id='tpx-progress'></div>";
        $h[]="<ol class='tpx-checks'>";
        foreach($m["rules"] as $r){
            if($r["state"]=="off" || $r["state"]=="ok"){continue;}
            if(count($r["issues"])==0){
                $txt=$r["state"]=="down"?str_replace("%L",strval($r["local"]),tpx_parse("{tpx_is_listen}")):tpx_parse("{tpx_st_fw}");
                $h[]="<li><span class='tpx-ck-rule'>{$r["name"]}</span>$txt".($r["err"]<>""?"<span class='tpx-ck-sys'>".htmlspecialchars($r["err"])."</span>":"")."</li>";
                continue;
            }
            foreach($r["issues"] as $is){
                $sys=($is["check"]=="squid_tcp_listen" && $r["err"]<>"")?" &middot; ".htmlspecialchars($r["err"]):"";
                $h[]="<li><span class='tpx-ck-rule'>{$r["name"]}</span>{$is["text"]}<span class='tpx-ck-sys'>".htmlspecialchars($is["check"])."$sys</span></li>";
            }
        }
        $h[]="</ol><div class='tpx-health-note'>{tpx_restart_note}</div>";
    }
    $h[]=tpx_kernel_block($m,false);
    $h[]="</section>";
    return implode("",$h);
}

function tpx_parse($t):string{
    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body($t);
}

/** Bouton "Reconfigure ports and restart service" avec progression (route existante). */
function tpx_restart_button():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js=$tpl->framework_buildjs("/proxy/general/nohup/restart","squid.articarest.nohup","squid.articarest.nohup.log",
        "tpx-progress","LoadAjax('proxy-transparent-ports','$page?table=yes');");
    return "<button type='button' class='tpx-btn tpx-btn-danger' OnClick=\"$js\"><i class='fas fa-rotate'></i>{reconfigure_proxy_ports_restart}</button>";
}

/** Bloc "Gateway settings of the system" : parametres noyau hors des valeurs attendues. */
function tpx_kernel_block(array $m,$standalone):string{
    if(count($m["kernel"])==0){return "";}
    $page=CurrentPageName();
    $h=array();
    if($standalone){$h[]="<section class='tpx-card tpx-health tpx-health-warn'>";}
    $h[]="<div class='tpx-kern'><div class='tpx-kern-h'><div><b><i class='fas fa-microchip'></i> {tpx_kern_t}</b><span>{tpx_kern_text}</span></div>";
    $h[]="<button type='button' class='tpx-btn' OnClick=\"Loadjs('$page?fix-rpfilter=yes')\"><i class='fas fa-wrench'></i>{fix_kernel_values}</button></div>";
    $h[]="<div class='tpx-kern-note'>{tpx_kern_note}</div><div class='tpx-kern-list'>";
    foreach($m["kernel"] as $k){
        $h[]="<div class='tpx-kern-row'><div><b>".tpx_kernel_label($k["param"])."</b><span class='tpx-mono'>".htmlspecialchars($k["param"])."</span></div>"
            ."<div class='tpx-kern-v'><span class='tpx-kv-cur'>".htmlspecialchars($k["current"]==""?"—":$k["current"])."</span>"
            ."<i class='fas fa-arrow-right'></i><span class='tpx-kv-exp'>".htmlspecialchars($k["expected"])."</span></div></div>";
    }
    $h[]="</div></div>";
    if($standalone){$h[]="</section>";}
    return implode("",$h);
}

/** Cellule "Rule". */
function tpx_cell_rule(array $r):string{
    $page=CurrentPageName();
    $off=$r["state"]=="off"?" tpx-dim":"";
    $ico=$r["cert"]<>""?"fas fa-lock":"fas fa-plug";
    return "<div class='tpx-rule$off'><span class='tpx-rule-ico'><i class='$ico'></i></span><div>"
        ."<a href='javascript:blur();' class='tpx-rule-n' OnClick=\"Loadjs('$page?port-js={$r["id"]}')\">{$r["name"]}</a>"
        ."<div class='tpx-sub'>{tpx_iface}: <span>{$r["iface"]}</span></div>"
        ."<div class='tpx-sub'>{tpx_leaves}: <span>{$r["out"]}</span></div></div></div>";
}

/** Cellule "What is intercepted" : le flux port -> proxy, et le type d'interception. */
function tpx_cell_flow(array $r):string{
    $ports=htmlspecialchars(implode(", ",$r["ports"]));
    $types=array("std"=>"{tpx_t_std}","wccp"=>"{tpx_t_wccp}","tproxy"=>"{tpx_t_tproxy}");
    $tips=array("std"=>"{tpx_t_std_tip}","wccp"=>"{tpx_t_wccp_tip}","tproxy"=>"{tpx_t_tproxy_tip}");
    $h="<div class='tpx-flow'><span class='tpx-flow-a'><i class='fas fa-globe'></i><span>{tpx_port} <b class='tpx-mono'>$ports</b></span></span>"
        ."<i class='fas fa-arrow-right tpx-flow-arrow'></i>"
        ."<span class='tpx-flow-b'><i class='fas fa-shield-halved'></i><span>{tpx_proxy} <b class='tpx-mono'>{$r["local"]}</b></span></span></div>";
    $h.="<div class='tpx-types'><span class='tpx-type' title=\"{$tips[$r["type"]]}\">{$types[$r["type"]]}</span>";
    if($r["dnat"]){$h.="<span class='tpx-type' title=\"{tpx_t_dnat_tip}\">DNAT</span>";}
    return $h."</div>";
}

/** Cellule "What the proxy does". */
function tpx_cell_does(array $r,array $m):string{
    $h="<div class='tpx-fchips'>";
    if($r["cert"]<>""){
        $h.="<span class='tpx-fc tpx-fc-on' title=\"{tpx_c_https_tip}\"><i class='fas fa-lock'></i><span>{tpx_c_https}</span></span>";
    }else{
        $h.="<span class='tpx-fc tpx-fc-off' title=\"{tpx_c_http_tip}\"><i class='fas fa-unlock'></i><span>{tpx_c_http}</span></span>";
    }
    $h.=$r["cache"]?"<span class='tpx-fc tpx-fc-on' title=\"{tpx_c_cache_tip}\"><i class='fas fa-database'></i><span>{tpx_c_cache}</span></span>"
                   :"<span class='tpx-fc tpx-fc-off tpx-strike' title=\"{tpx_c_nocache_tip}\"><i class='fas fa-ban'></i><span>{tpx_c_nocache}</span></span>";
    $h.=$r["filter"]?"<span class='tpx-fc tpx-fc-on' title=\"{tpx_c_filter_tip}\"><i class='fas fa-filter'></i><span>{tpx_c_filter}</span></span>"
                    :"<span class='tpx-fc tpx-fc-off tpx-strike' title=\"{tpx_c_nofilter_tip}\"><i class='fas fa-ban'></i><span>{tpx_c_nofilter}</span></span>";
    $h.="</div>";
    if($r["cert"]<>""){
        $h.="<div class='tpx-cert'><i class='fas fa-certificate'></i>{tpx_cert}: <span class='tpx-mono'>{$r["cert"]}</span></div>";
        if($m["urgency"]){$h.="<div class='tpx-err'><i class='fas fa-triangle-exclamation'></i> {tpx_emergency}</div>";}
    }
    return $h;
}

/** Cellule "Left out" : les deux listes d'exclusion, ouvertes dans l'editeur existant. */
function tpx_cell_left(array $r):string{
    $h="";
    foreach(array(0=>array("src","fas fa-laptop","{tpx_l_src_one}","{tpx_l_src_many}","{tpx_l_src_none}"),
                  1=>array("dst","fas fa-globe","{tpx_l_dst_one}","{tpx_l_dst_many}","{tpx_l_dst_none}")) as $inc=>$d){
        $n=$r[$d[0]];
        $txt=$n==0?$d[4]:($n==1?$d[2]:str_replace("%N",strval($n),tpx_parse($d[3])));
        $cls=$n==0?"tpx-left tpx-left-none":"tpx-left";
        $h.="<a href='javascript:blur();' class='$cls' OnClick=\"Loadjs('fw.proxy.ports.php?transparent-wbl-js=yes&include=$inc&portid={$r["id"]}')\"><i class='{$d[1]}'></i><span>$txt</span></a>";
    }
    return "<div class='tpx-lefts'>$h</div>";
}

/** Cellule "Status". */
function tpx_cell_status(array $r):string{
    $s=$r["state"];
    if($s=="off"){return "<span class='tpx-pill tpx-pill-off'><i class='fas fa-circle-pause'></i>{tpx_st_off}</span>";}
    if($s=="ok"){return "<span class='tpx-pill tpx-pill-ok'><i class='fas fa-circle-check'></i>{tpx_st_ok}</span>";}
    if($s=="down"){
        $msg=trim($r["err"])==""?"":"<div class='tpx-err'>".htmlspecialchars($r["err"])."</div>";
        return "<span class='tpx-pill tpx-pill-bad'><i class='fas fa-circle-xmark'></i>{tpx_st_down}</span>$msg<div class='tpx-sub'>{tpx_st_hint}</div>";
    }
    if($s=="conf"){
        return "<span class='tpx-pill tpx-pill-bad'><i class='fas fa-triangle-exclamation'></i>{tpx_st_conf}</span><div class='tpx-sub'>{tpx_st_hint}</div>";
    }
    return "<span class='tpx-pill tpx-pill-bad'><i class='fas fa-triangle-exclamation'></i>{tpx_st_fw}</span><div class='tpx-sub'>{tpx_st_hint}</div>";
}

function tpx_cell_actions(array $r):string{
    $page=CurrentPageName();
    return "<button type='button' class='tpx-ibtn' aria-label='{tpx_edit} {$r["name"]}' title='{tpx_edit}' OnClick=\"Loadjs('$page?port-js={$r["id"]}')\"><i class='fas fa-pen'></i></button>"
        ."<button type='button' class='tpx-ibtn tpx-ibtn-del' aria-label='{tpx_delete} {$r["name"]}' title='{tpx_delete}' OnClick=\"Loadjs('$page?port-delete-js={$r["id"]}')\"><i class='far fa-trash-can'></i></button>";
}

/** Carte "Your interception rules" (ou son etat vide invitant). */
function tpx_table(array $m):string{
    $page=CurrentPageName();
    $h=array();
    $count="";
    if($m["n_total"]>0){
        $count="<span class='tpx-pill tpx-pill-soft'>".str_replace(array("%E","%T"),array($m["n_enabled"],$m["n_total"]),tpx_parse("{tpx_rules_count}"))."</span>";
    }
    $h[]="<section class='tpx-card tpx-tcard'><div class='tpx-th'><div><h3>{tpx_tbl_title}</h3><p>{tpx_tbl_text}</p></div>$count</div>";
    if($m["n_total"]==0){
        $h[]=tpx_empty($m);
        $h[]="</section>";
        return implode("",$h);
    }
    $h[]="<div class='tpx-scroll'><table class='tpx-tbl'><thead><tr><th>{tpx_col_rule}</th><th>{tpx_col_flow}</th><th>{tpx_col_does}</th>"
        ."<th>{tpx_col_left}</th><th>{status}<div class='tpx-th-sub'>{tpx_col_status_sub}</div></th><th class='tpx-acts'>{actions}</th></tr></thead><tbody id='tpx-rows'>";
    foreach($m["rules"] as $r){
        $h[]="<tr class='tpx-st-{$r["state"]}'><td>".tpx_cell_rule($r)."</td><td>".tpx_cell_flow($r)."</td><td>".tpx_cell_does($r,$m)."</td>"
            ."<td>".tpx_cell_left($r)."</td><td>".tpx_cell_status($r)."</td><td class='tpx-acts'>".tpx_cell_actions($r)."</td></tr>";
    }
    $h[]="</tbody></table></div>";
    $h[]="<div class='tpx-foot'><span>{showing} {$m["n_total"]} / {$m["n_total"]} {ports}</span>";
    if($m["hacluster"]){$h[]="<span><i class='fas fa-circle-nodes'></i> {tpx_hacluster}</span>";}
    $h[]="<span class='awc-pg' id='tpx-pager'></span></div></section>";
    return implode("",$h);
}

/** Etat vide : l'assistant existant d'abord, la creation manuelle ensuite. */
function tpx_empty(array $m):string{
    $page=CurrentPageName();
    $btn="";
    if(!$m["hacluster"]){
        $btn="<div class='tpx-empty-b'><button type='button' class='tpx-btn' OnClick=\"Loadjs('fw.proxy.wizard.transparent.php')\"><i class='fas fa-wand-magic-sparkles'></i>{transparent_mode}</button>"
            ."<button type='button' class='tpx-btn tpx-btn-ghost' OnClick=\"Loadjs('$page?port-js=0')\"><i class='fas fa-plus'></i>{new_port}</button></div>";
    }else{
        $btn="<p class='tpx-sub'>{tpx_hacluster}</p>";
    }
    return "<div class='tpx-empty'><div class='tpx-empty-ico'><i class='fas fa-route'></i></div><h4>{tpx_empty_t}</h4><p>{tpx_empty}</p>$btn</div>";
}

/** Note MASQUERADE : seulement quand la traduction d'adresse manque en sortie. */
function tpx_masq(array $m):string{
    if(!$m["masq_missing"]){return "";}
    return "<section class='tpx-card tpx-masq'><i class='fas fa-circle-info'></i><div><b>{tpx_masq_t}</b><p>{tpx_masq}</p>"
        ."<details><summary>{tpx_learn_more}</summary><div class='tpx-masq-more'>{masquerade_proxyt_explain}</div></details></div></section>";
}

function tpx_style():string{
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$soft=$a["accent_soft"];$dark=$a["accent_dark"];
    }
    $c=array();
    $c[]=".tpx{display:flex;flex-direction:column;gap:16px;padding:4px 2px 24px;color:#1e293b;font-size:14px}";
    $c[]=".tpx-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace}";
    $c[]=".tpx-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".tpx-ban{display:flex;gap:24px;justify-content:space-between;align-items:flex-start;padding:24px 28px;border-left:4px solid $accent}";
    $c[]=".tpx-ban-main{display:flex;gap:16px;max-width:820px}";
    $c[]=".tpx-ban-ico{flex:0 0 48px;height:48px;border-radius:10px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:22px}";
    $c[]=".tpx-ban h2{margin:0 0 6px;font-size:22px;font-weight:600;color:#0f172a}";
    $c[]=".tpx-ban p{margin:0;line-height:1.6;color:#475569}";
    $c[]=".tpx-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}";
    $c[]=".tpx-rchip{display:inline-flex;align-items:center;gap:7px;background:#f1f5f9;border-radius:999px;padding:5px 12px;font-size:13px;color:#334155}";
    $c[]=".tpx-rchip svg,.tpx-rchip i{color:$dark}";
    $c[]=".tpx-know{flex:0 0 340px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:16px 18px;color:#92400e}";
    $c[]=".tpx-know-t{display:flex;gap:8px;align-items:center;font-weight:600;margin-bottom:6px}";
    $c[]=".tpx-know p{margin:0 0 10px;font-size:13.5px;line-height:1.55}";
    $c[]=".tpx-know-l{color:#92400e!important;font-weight:600;text-decoration:underline;font-size:13px}";
    $c[]=".tpx-health{padding:16px 20px}";
    $c[]=".tpx-health-h{display:flex;align-items:center;gap:14px}";
    $c[]=".tpx-health-h>div{flex:1;display:flex;flex-direction:column;gap:2px}";
    $c[]=".tpx-health-h b{font-size:15px;font-weight:600}";
    $c[]=".tpx-health-h span{font-size:13.5px}";
    $c[]=".tpx-health-h>svg,.tpx-health-h>i{font-size:22px}";
    $c[]=".tpx-health-ok{background:#ecfdf5;border-color:#a7f3d0;color:#065f46}";
    $c[]=".tpx-health-bad{background:#fef2f2;border-color:#fecaca;color:#991b1b}";
    $c[]=".tpx-health-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}";
    $c[]=".tpx-checks{margin:12px 0 0;padding:0 0 0 22px;display:flex;flex-direction:column;gap:6px}";
    $c[]=".tpx-checks li{font-size:14px;line-height:1.45}";
    $c[]=".tpx-ck-rule{display:inline-block;background:#fff;border:1px solid #fecaca;border-radius:6px;padding:0 7px;margin-right:8px;font-size:13px;font-weight:600}";
    $c[]=".tpx-ck-sys{display:block;font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px;color:#7f1d1d;opacity:.8}";
    $c[]=".tpx-health-note{margin-top:10px;font-size:13px}";
    $c[]=".tpx-kern{margin-top:14px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;color:#1e293b}";
    $c[]=".tpx-health-warn .tpx-kern{margin-top:0}";
    $c[]=".tpx-kern-h{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}";
    $c[]=".tpx-kern-h>div{display:flex;flex-direction:column;gap:2px}";
    $c[]=".tpx-kern-h b{font-size:14.5px}";
    $c[]=".tpx-kern-h span{font-size:13px;color:#64748b}";
    $c[]=".tpx-kern-note{font-size:13px;color:#64748b;margin:4px 0 8px}";
    $c[]=".tpx-kern-list{display:flex;flex-direction:column}";
    $c[]=".tpx-kern-row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:8px 0;border-top:1px solid #f1f5f9}";
    $c[]=".tpx-kern-row>div:first-child{display:flex;flex-direction:column}";
    $c[]=".tpx-kern-row .tpx-mono{font-size:13px;color:#64748b}";
    $c[]=".tpx-kern-v{display:flex;align-items:center;gap:8px;color:#94a3b8}";
    $c[]=".tpx-kv-cur,.tpx-kv-exp{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px;border-radius:6px;padding:2px 8px}";
    $c[]=".tpx-kv-cur{background:#fef2f2;color:#b91c1c}.tpx-kv-exp{background:#ecfdf5;color:#047857}";
    $c[]=".tpx-btn{display:inline-flex;align-items:center;gap:8px;background:$dark;color:#fff;border:0;border-radius:8px;padding:9px 16px;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap}";
    $c[]=".tpx-btn:hover{filter:brightness(1.08)}";
    $c[]=".tpx-btn-danger{background:#b91c1c}";
    $c[]=".tpx-btn-ghost{background:#fff;color:$dark;border:1px solid #cbd5e1}";
    $c[]=".tpx-btn:focus-visible,.tpx-ibtn:focus-visible,.tpx-left:focus-visible,.tpx-rule-n:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".tpx-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 11px;font-size:13px;font-weight:600;border:1px solid;white-space:nowrap}";
    $c[]=".tpx-pill-ok{background:#ecfdf5;border-color:#a7f3d0;color:#047857}";
    $c[]=".tpx-pill-bad{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
    $c[]=".tpx-pill-off{background:#f1f5f9;border-color:#e2e8f0;color:#64748b}";
    $c[]=".tpx-pill-soft{background:#fff;border-color:#a7f3d0;color:#047857;font-weight:500}";
    $c[]=".tpx-th .tpx-pill-soft{border-color:#e2e8f0;color:#334155}";
    $c[]=".tpx-th{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:18px 24px;border-bottom:1px solid #e2e8f0}";
    $c[]=".tpx-th h3{margin:0 0 4px;font-size:16px;font-weight:600;color:#0f172a}";
    $c[]=".tpx-th p{margin:0;font-size:13px;color:#64748b}";
    $c[]=".tpx-scroll{overflow-x:auto}";
    $c[]=".tpx-tbl{width:100%;border-collapse:collapse;color:#1e293b}";
    $c[]=".tpx-tbl th{text-align:left;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#475569;padding:12px 14px;background:#f8fafc;border-bottom:1px solid #e2e8f0;vertical-align:bottom;white-space:nowrap}";
    $c[]=".tpx-th-sub{font-size:13px;font-weight:400;text-transform:none;letter-spacing:0;color:#64748b;white-space:normal}";
    $c[]=".tpx-tbl td{padding:16px 14px;border-bottom:1px solid #e2e8f0;vertical-align:top;font-size:14px}";
    $c[]=".tpx-tbl tr:last-child td{border-bottom:0}";
    $c[]=".tpx-tbl tbody tr:hover{background:#f8fafc}";
    $c[]=".tpx-acts{text-align:right!important;white-space:nowrap;width:90px}";
    $c[]=".tpx-rule{display:flex;gap:12px;min-width:200px}";
    $c[]=".tpx-rule-ico{flex:0 0 34px;height:34px;border-radius:8px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center}";
    $c[]=".tpx-rule-n{font-size:15px;font-weight:600;color:#0f172a!important;text-decoration:none!important}";
    $c[]=".tpx-rule-n:hover{color:$dark!important}";
    $c[]=".tpx-sub{font-size:13px;color:#64748b;margin-top:3px;line-height:1.45}";
    $c[]=".tpx-sub span{color:#334155}";
    $c[]=".tpx-dim .tpx-rule-n,.tpx-dim .tpx-rule-ico{opacity:.55}";
    $c[]=".tpx-flow{display:inline-flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:13.5px;flex-wrap:wrap;row-gap:4px}";
    $c[]=".tpx-flow-a,.tpx-flow-b{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}";
    $c[]=".tpx-flow-a svg{color:#64748b}.tpx-flow-b{color:$dark}";
    $c[]=".tpx-flow-arrow{color:#94a3b8}";
    $c[]=".tpx-types{margin-top:8px;display:flex;gap:6px;flex-wrap:wrap}";
    $c[]=".tpx-type{display:inline-block;background:#f1f5f9;color:#334155;border-radius:6px;padding:2px 8px;font-size:13px;cursor:help}";
    $c[]=".tpx-fchips{display:flex;flex-wrap:wrap;gap:6px}";
    $c[]=".tpx-fc{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 11px;font-size:13px;font-weight:500;border:1px solid transparent;white-space:nowrap;cursor:help}";
    $c[]=".tpx-fc-on{background:#ecfdf5;border-color:#a7f3d0;color:#047857}";
    $c[]=".tpx-fc-off{background:#f1f5f9;color:#64748b}";
    $c[]=".tpx-strike span{text-decoration:line-through}";
    $c[]=".tpx-cert{margin-top:8px;font-size:13px;color:#475569;display:flex;gap:6px;align-items:center}";
    $c[]=".tpx-cert svg{color:#94a3b8}";
    $c[]=".tpx-err{margin-top:6px;font-size:13px;font-weight:500;color:#b91c1c}";
    $c[]=".tpx-lefts{display:flex;flex-direction:column;gap:6px;align-items:stretch;min-width:190px}";
    $c[]=".tpx-left{display:inline-flex;align-items:center;gap:7px;border:1px solid #cbd5e1;border-radius:8px;padding:5px 10px;font-size:13px;line-height:1.35;color:#334155!important;text-decoration:none!important;background:#fff}";
    $c[]=".tpx-left:hover{border-color:$dark;color:$dark!important}";
    $c[]=".tpx-left-none{border-style:dashed;color:#64748b!important}";
    $c[]=".tpx-ibtn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:0;background:none;border-radius:6px;color:#64748b;cursor:pointer;font-size:14px}";
    $c[]=".tpx-ibtn:hover{background:#f1f5f9;color:$dark}";
    $c[]=".tpx-ibtn-del:hover{background:#fef2f2;color:#b91c1c}";
    $c[]=".tpx-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 24px;border-top:1px solid #e2e8f0;font-size:13px;color:#64748b}";
    $c[]=".tpx-empty{text-align:center;padding:48px 24px 56px}";
    $c[]=".tpx-empty-ico{width:72px;height:72px;margin:0 auto 18px;border-radius:50%;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:30px}";
    $c[]=".tpx-empty h4{margin:0 0 8px;font-size:18px;font-weight:600;color:#0f172a}";
    $c[]=".tpx-empty p{max-width:560px;margin:0 auto 20px;color:#475569;line-height:1.6}";
    $c[]=".tpx-empty-b{display:flex;gap:10px;justify-content:center}";
    $c[]=".tpx-masq{display:flex;gap:14px;padding:16px 20px;background:#eff6ff;border-color:#bfdbfe;color:#1e3a8a}";
    $c[]=".tpx-masq>svg,.tpx-masq>i{font-size:20px;color:#1d4ed8;margin-top:2px}";
    $c[]=".tpx-masq b{font-size:15px}.tpx-masq p{margin:4px 0 6px;font-size:13.5px;line-height:1.5}";
    $c[]=".tpx-masq summary{cursor:pointer;color:#1d4ed8;font-weight:600;font-size:13px}";
    $c[]=".tpx-masq-more{margin-top:8px;font-size:13.5px;line-height:1.55}";
    $c[]="@media (max-width:1200px){.tpx-ban{flex-direction:column}.tpx-know{flex:none;width:100%;box-sizing:border-box}}";
    $c[]="@media (prefers-reduced-motion:reduce){.tpx *{transition:none!important;animation:none!important}}";
    return "<style>".implode("\n",$c)."</style>";
}

/** Rafraichissement : l'hote entier toutes les 30 s (un seul appel au validateur), jamais sous une modale. */
function tpx_js():string{
    $page=CurrentPageName();
    return "<script>
(function(){
if(window.TpxTimer){clearTimeout(window.TpxTimer);}
function busy(){ return document.querySelector('.modal.in,.modal.show,.jconfirm,.sweet-alert.visible')!==null
  || (document.getElementById('tpx-progress') && document.getElementById('tpx-progress').innerHTML.trim()!==''); }
function tick(){
  if(!document.getElementById('tpx-root')){return;}
  if(busy()){ window.TpxTimer=setTimeout(tick,30000); return; }
  LoadAjaxSilent('proxy-transparent-ports','$page?table=yes');
}
window.TpxTimer=setTimeout(tick,30000);
})();
</script>";
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=tpx_model();

    $html=array();
    $html[]="<div class='tpx' id='tpx-root'>";
    $html[]=tpx_banner();
    $html[]=tpx_health($m);
    $html[]=tpx_table($m);
    $html[]=tpx_masq($m);
    $html[]="</div>";

    $jsrestart="Loadjs('fw.proxy.apply.ports.php');";
    $topbuttons=array();
    if(!$m["hacluster"]){
        $topbuttons[]=array("Loadjs('$page?port-js=0')",ico_plus,"{new_port}");
    }
    $topbuttons[]=array("LoadAjax('proxy-transparent-ports','$page?table=yes');",ico_refresh,"{refresh}");
    $topbuttons[]=array($jsrestart,ico_save,"{reconfigure_proxy_ports_restart}");
    $topbuttons[]=array("Loadjs('$page?tlimit-js=yes')","fa fa-circle-info","{notice}: {limits}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{listen_ports} ({transparent_ports})";
    $TINY_ARRAY["ICO"]="fad fa-plug";
    $TINY_ARRAY["EXPL"]="{tpx_tiny_expl}";
    $TINY_ARRAY["URL"]="proxy-ports";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>NoSpinner();$jstiny</script>";

    echo awc_table_style().tpx_style()
        .tpx_nobr($tpl->_ENGINE_parse_body(implode("\n",$html)))
        .tpx_js()
        .awc_pager_js(50,"tpx-rows","tpx-pager");
}

function fix_rp_filter():bool{
    header("content-type: application/x-javascript");
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){return false;}
    // ApplyKernelValues() : remet TOUTES les valeurs attendues (pas seulement rp_filter).
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/transparent/rp_filter");
    $page=CurrentPageName();
    echo "LoadAjax('proxy-transparent-ports','$page?table=yes');";
    return admin_tracks("Apply the expected kernel values for the transparent proxy");
}

function port_delete_js(){
    $ID=intval($_GET["port-delete-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT port,others_ports FROM transparent_ports WHERE ID=$ID");
    if(!is_array($ligne)){$ligne=array();}
    // Consequence dite avant l'action : quels ports ne sont plus filtres.
    $ports=array(intval($ligne["port"] ?? 0));
    foreach(explode(",",strval($ligne["others_ports"] ?? "")) as $o){ if(intval($o)>0){$ports[]=intval($o);} }
    $text=str_replace("%P","<strong>".implode(", ",$ports)."</strong>",$tpl->_ENGINE_parse_body("{tpx_delete_confirm}"));
    $jsafter="LoadAjax('proxy-transparent-ports','$page?table=yes');";
    $tpl->js_confirm_delete($text, "port-delete", $ID,$jsafter,true);
}
function connected_port_delete(){
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo "Permission denied";return;}
    $ID=intval($_POST["port-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT port,PortName FROM transparent_ports WHERE ID=$ID");
    if(!is_array($ligne)){$ligne=array();}
    $q->QUERY_SQL("DELETE FROM transparent_ports WHERE ID=$ID");
    if(!$q->ok){echo $q->mysql_error;return;}
    $q->QUERY_SQL("DELETE FROM proxy_ports_wbl WHERE portid=$ID");
    // Rebuild transparent nat rules so the removed port stops being intercepted.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/proxy/ports");
    admin_tracks("Delete the transparent port ".intval($ligne["port"] ?? 0)." ".strval($ligne["PortName"] ?? "")." (ID $ID)");
}

function tlimit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog("{notice}", "$page?tlimit-popup=yes");
}
function tlimit_popup():bool{
    $tpl=new template_admin();
    return $tpl->div_warning("{transparent_mode_limitations}");
    //
}



function port_js(){
    $ID=intval($_GET["port-js"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $title=$tpl->javascript_parse_text("{new_port}");

    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT PortName,port FROM transparent_ports WHERE ID=$ID");
        $title="#{$ID} {listen_port}: {$ligne["PortName"]}:{$ligne["port"]}";

    }

    $tpl->js_dialog($title, "$page?port-popup=$ID");

}
function connected_port_popup(){
    $ID=intval($_GET["port-popup"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $btname="{add}";
    $title=$tpl->javascript_parse_text("{new_port}");
    $jsafter="LoadAjax('proxy-transparent-ports','$page?table=yes');BootstrapDialog1.close();";

    if($ID>0){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM transparent_ports WHERE ID=$ID");
        $title="{$ligne["nic"]}:{$ligne["port"]}";
        if($ligne["nic"]==null){$title="{listen_port}: {$ligne["port"]}";}
        $btname="{apply}";
    }
    $ip=new networking();
    $interfaces=$ip->Local_interfaces();
    unset($interfaces["lo"]);

    $array[null]        = "{all}";
    $array2[null]       = "{all}";
    $CountOfInterfaces  = 0;
    foreach ($interfaces as $eth){
        if(preg_match("#^(gre|dummy)#", $eth)){continue;}
        if($eth=="lo"){continue;}
        $nic=new system_nic($eth);
        if($nic->enabled==0){continue;}
        $array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        $array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        $CountOfInterfaces++;
    }



    include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
    $squid_reverse=new squid_reverse();
    $sslcertificates=$squid_reverse->ssl_certificates_list();

    if($ligne["localport"]==0){$ligne["localport"]=rand(1024,63000);}
    if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
    if(intval($ligne["port"])<10){$ligne["port"]=80;}
    $others_ports=trim($ligne["others_ports"]);

    if($others_ports<>null){
        $ligne["port"]=$ligne["port"].",$others_ports";
    }

    $form[]=$tpl->field_hidden("SaveConnectedPort",$ID);
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
    $form[]=$tpl->field_text("port","{destination_port}",$ligne["port"],true,"{muliple_port_comma_explain}");
    $form[]=$tpl->field_numeric("localport","{proxy_port}",$ligne["localport"]);

    $form[]=$tpl->field_checkbox("dnat", "DNAT",  $ligne["dnat"]);
    $form[]=$tpl->field_checkbox("TProxy", "{use_tproxy_mode}",  $ligne["TProxy"]);
    $form[]=$tpl->field_text("PortName", "{service_name2}",  $ligne["PortName"]);
    $form[]=$tpl->field_checkbox("NoCache","{disable_cache}",$ligne["NoCache"]);
    $form[]=$tpl->field_checkbox("NoFilter","{disable_webfiltering}",$ligne["NoFilter"]);
    if($CountOfInterfaces>1) {
        $form[] = $tpl->field_interfaces("nic", "{listen_interface}", $ligne["nic"]);
        $form[] = $tpl->field_interfaces("outgoing_nic", "{forward_interface}", $ligne["outgoing_nic"]);
    }else{
        $tpl->field_hidden("nic",null);
        $tpl->field_hidden("outgoing_nic",null);
    }
    $form[]=$tpl->field_array_hash($sslcertificates, "sslcertificate", "{use_certificate_from_certificate_center}", $ligne["sslcertificate"]);

    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function post_save(){
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");


    if(!$q->FIELD_EXISTS("transparent_ports","others_ports")){
        $q->QUERY_SQL("ALTER TABLE transparent_ports ADD `others_ports` TEXT");
    }
    $others_ports=null;
    $tpl=new template_admin();
    $SQLAR=$tpl->CLEAN_POST("SaveConnectedPort");
    $ID=intval($_POST["SaveConnectedPort"]);

    $enabled=intval($_POST["enabled"])==1?1:0;
    $portPost=trim($_POST["port"]);
    $portPost=str_replace(" ","",$portPost);
    $portPost=str_replace("-","",$portPost);
    $portPost=str_replace("*","",$portPost);
    $portPost=str_replace(";",",",$portPost);
    $port=0;
    if(strpos($portPost,",")>0){
        $tbr=explode(",",$portPost);
        foreach ($tbr as $index=>$lport){
            if($lport==80){$port=80;unset($tbr[$index]);}
            if($lport==443){$port=443;unset($tbr[$index]);}
        }
        if($port==0){$port=$tbr[0];unset($tbr[0]);}

        $others_ports=@implode(",",$tbr);
    }else{
        $port=$portPost;
    }
    // Ports numériques uniquement : ils étaient collés dans le SQL, puis lus
    // par le daemon pour bâtir les règles iptables.
    $port=intval($port);
    $others_ports=implode(",",array_filter(array_map("intval",explode(",",(string)$others_ports))));


    $localport=intval($_POST["localport"]);

    if($localport==0){echo $tpl->_ENGINE_parse_body("{proxy_port} $localport false value!");return;}

    $PortName=(string)$_POST["PortName"];
    $NoCache=intval($_POST["NoCache"]);
    $NoFilter=intval($_POST["NoFilter"]);
    $dnat=intval($_POST["dnat"]);
    $nic=(string)$_POST["nic"];
    $outgoing_nic=(string)$_POST["outgoing_nic"];
    $sslcertificate=(string)$_POST["sslcertificate"];
    foreach (array($nic,$outgoing_nic) as $iface){
        if(!preg_match('/^[A-Za-z0-9._:-]*$/',$iface)){
            echo $tpl->post_error("{interface}: $iface");
            return;
        }
    }
    $TProxy=intval($_POST["TProxy"]);

    if($port==443){
        if($sslcertificate==null){
            echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("{error_443_without_certificate}"));
            return;
        }
    }


    $cols=array("PortName"=>$PortName,"nic"=>$nic,"outgoing_nic"=>$outgoing_nic,"sslcertificate"=>$sslcertificate,
        "enabled"=>$enabled,"NoCache"=>$NoCache,"NoFilter"=>$NoFilter,"TProxy"=>$TProxy,"localport"=>$localport,
        "port"=>$port,"others_ports"=>$others_ports,"dnat"=>$dnat);
    $params=array_values($cols);
    if($ID==0) {
        // $text (et non $Text) : c'est elle que lit admin_tracks_post() plus bas,
        // la création n'était donc jamais tracée.
        $text="Create a new proxy transparent port $PortName";
        $sql="INSERT INTO transparent_ports (".implode(",",array_keys($cols)).") VALUES (".implode(",",array_fill(0,count($cols),"?")).")";
    }else{
        $text="Update the transparent port $PortName";
        $set=array();
        foreach (array_keys($cols) as $col){$set[]="$col=?";}
        $sql="UPDATE transparent_ports SET ".implode(",",$set)." WHERE ID=?";
        $params[]=$ID;
    }
    $q->QUERY_SQL_PARAMS($sql,$params);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    // Notify the daemon to rebuild the transparent nat rules — without this a
    // saved port stays in proxy.db but never reaches iptables until an unrelated
    // reconfigure. /firewall/proxy/ports → go SquidTransparentBuild().
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/proxy/ports");
    return admin_tracks_post($text);
}
