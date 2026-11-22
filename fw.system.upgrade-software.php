<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["jsafter"])){jsafter();exit;}
js();

function jsafter():bool{

    header("content-type: application/x-javascript");

    $afterjs="";
    if(isset($_GET["after-js"])){
        $afterjs=$_GET["after-js"];
        if($afterjs=="reload"){
            echo "location.reload();";
            return true;
        }
    }


    // after-js used to be sent back as JavaScript as is (reflected script): every caller
    // passes "reload", handled above; anything else is ignored.
    $f[]="if(document.getElementById('table-loader-versions-service')){";
    $f[]="\tLoadAjax('table-loader-versions-service','fw.versions.php?table=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('main-dashboard-status')){";
    $f[]="\tLoadAjaxSilent('main-dashboard-status','fw.system.status.php');";
    $f[]="}";
    $f[]="if(document.getElementById('unbound-status')){";
    $f[]="LoadAjaxSilent('unbound-status','fw.dns.unbound.php?unbound-status=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('dnsdist-left')){";
    $f[]="LoadAjax('dnsdist-left','fw.dns.unbound.php?dnsdist-status-left=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('progress-suricata-wizard')){";
    $f[]="LoadAjax('progress-suricata-wizard','fw.ids.wizard.php?table=yes');";
    $f[]="}";
    

    $f[]="if(document.getElementById('artica-packages-updates') && typeof PkgUpdRefresh==='function'){";
    $f[]="\tPkgUpdRefresh();";
    $f[]="}";
    $f[]="Loadjs('fw.icon.top.php')";
    echo @implode("\n",$f)."\n";
    return true;
}

function js():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $fkey=null;
    // Ces deux valeurs d'URL repartent dans le titre de la boite et dans l'URL
    // de son contenu.
    $product=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["product"] ?? ""));
    $uuid="";

    if(isset($_GET["uuid"])){
        $uuid="&uuid=".urlencode((string)$_GET["uuid"]);
    }
    $afterjs="";
    if(isset($_GET["after-js"])){
        $afterjs="&after-js=".urlencode((string)$_GET["after-js"]);
    }

    if(isset($_GET["filter-key"])){
        $fkey="&filter-key=".intval($_GET["filter-key"]);
    }
	$title=$tpl->_ENGINE_parse_body("{{$product}}");
    if($product=="APP_SYSLOGD"){
        $title=$tpl->_ENGINE_parse_body("{APP_RSYSLOG}");
    }
    if($product=="APP_QAT"){
        $title= "Intel QuickAssist";
    }
    $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
    if($DEBIAN_VERSION>10){
        if($product=="APP_SQUID6"){
            $title="{APP_SQUID}";
        }
    }

	$tpl->js_dialog11($title, "$page?popup=$product$fkey$uuid$afterjs",760);
    return true;
}


function popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    // La cle produit vient de l'URL et repart dans un chemin REST, des attributs id
    // et le JavaScript de framework_buildjs() : liste blanche. Cles reelles : APP_SQUID6.
    $PKEY=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["popup"] ?? ""));
    $afterjs="";
    if(isset($_GET["after-js"])){$afterjs="&after-js=".urlencode((string)$_GET["after-js"]);}
    $m=upgp_model($PKEY);

    if(!$m["index_ok"]){
        // Index jamais telecharge : on le rafraichit, puis la fenetre se rouvre.
        $t=time();
        $prgress=$tpl->framework_buildjs("/system/softwares/refresh",
            "UpdateReposIndex.progress","UpdateReposIndex.log",
            "$t","Loadjs('$page?product=$PKEY');Loadjs('fw.system.upgrade-software.php?jsafter=yes$afterjs');");
        $h="<div class='ak upgp'><div class='ak-banner ak-info'><i class='fas fa-rotate ak-spin' aria-hidden='true'></i>"
            ."<span class='ak-banner-m'>{upgp_refreshing}</span></div><div id='$t'></div></div>";
        echo $tpl->_ENGINE_parse_body($h)."<script>$prgress</script>";
        return true;
    }

    $h=array();
    $h[]="<div class='ak upgp'>";
    $h[]=upgp_head($m);
    if($m["kernel"]){$h[]=upgp_kernel_notice($m);}
    if(count($m["rows"])==0){
        $h[]=upgp_empty($m,$afterjs);
    }else{
        $h[]=upgp_table($m,$afterjs);
        $h[]=upgp_foot($m);
    }
    if($PKEY=="APP_TAILSCALE"){$h[]=upgp_tailscale();}
    $h[]="</div>";
    echo upgp_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).upgp_js();
    return true;
}

// Cle de reglage qui porte la version installee de chaque produit, et la cle
// "installe" quand elle existe. Relevee dans les lignes de fw.versions.php : la
// fenetre ne peut pas l'inclure (fonctions homonymes js(), popup()).
// Produit absent de la table = version installee inconnue ("Not comparable").
function upgp_version_keys():array{
    return array(
        "APP_3PROXY"=>array("APP_3PROXY_VERSION","APP_3PROXY_INSTALLED"),
        "APP_ADAGENT_LBL"=>array("ADAGENT_VERSION",""),
        "APP_AIDE"=>array("APP_AIDE_VERSION","APP_AIDE_INSTALLED"),
        "APP_AUTOFS"=>array("APP_AUTOFS_VERSION",""),
        "APP_CLAMAV"=>array("ClamAVDaemonVersion",""),
        "APP_CLICKHOUSE"=>array("APP_CLICKHOUSE_VERSION","CLICKHOUSE_INSTALLED"),
        "APP_CLOUDFLARE_DNS"=>array("CLOUDFLARED_VERSION","CLOUDFLARED_INSTALLED"),
        "APP_CYRUS"=>array("APP_CYRUS_VERSION","APP_CYRUS_INSTALLED"),
        "APP_C_ICAP"=>array("CicapVersion","C_ICAP_INSTALLED"),
        "APP_DDNS_AGENT"=>array("APP_DDNS_AGENT_VERSION","APP_DDNS_AGENT_INSTALLED"),
        "APP_DHCP"=>array("DHCPDVersion","DHCPD_INSTALLED"),
        "APP_DNSCRYPT_PROXY"=>array("APP_DNSCRYPT_PROXY_VERSION","APP_DNSCRYPT_PROXY_INSTALLED"),
        "APP_DNSDIST"=>array("APP_DNSDIST_VERSION",""),
        "APP_DNSDIST9"=>array("APP_DNSDIST_VERSION",""),
        "APP_DOCKER"=>array("APP_DOCKER_VERSION","APP_DOCKER_INSTALLED"),
        "APP_DOH_PROXY"=>array("APP_DOH_PROXY_VERSION","DOH_PROXY_INSTALLED"),
        "APP_DSC"=>array("DSCVersion",""),
        "APP_ELASTICSEARCH"=>array("ELASTICSEARCH_VERSION","ELASTICSEARCH_INSTALLED"),
        "APP_FAIL2BAN"=>array("FAIL2BAN_VERSION","FAIL2BAN_INSTALLED"),
        "APP_FILEBEAT"=>array("FILEBEAT_VERSION","FILEBEAT_INSTALLED"),
        "APP_FIRECRACKER"=>array("APP_FIRECRACKER_VERSION","APP_FIRECRACKER_INSTALLED"),
        "APP_FIRMWARES"=>array("APP_FIRMWARES_VERSION","APP_FIRMWARES_INSTALLED"),
        "APP_GLUSTERFS"=>array("APP_GLFS_VERSION","APP_GLFS_INSTALLED"),
        "APP_GO_HOTSPOT_ENGINE"=>array("HOTSPOTWEB_VERSION",""),
        "APP_GO_PAC_ENGINE"=>array("PROXYPAC_VERSION",""),
        "APP_GO_SHIELD_CONNECTOR"=>array("GO_SHIELD_CONNECTOR_VERSION",""),
        "APP_GO_WEBFITLER_ERROR_PAGE_ENGINE"=>array("WEBERRORPAGE_VERSION",""),
        "APP_GREENSQL"=>array("APP_GREENSQL_VERSION","APP_GREENSQL_INSTALLED"),
        "APP_HAPROXY"=>array("HAPROXY_VERSION","HAPROXY_INSTALLED"),
        "APP_MANTICORE"=>array("APP_MANTICORE_VERSION","APP_MANTICORE_INSTALLED"),
        "APP_MATTERMOST"=>array("APP_MATTERMOST_VERSION","APP_MATTERMOST_INSTALLED"),
        "APP_MILTER_GREYLIST"=>array("APP_MILTER_GREYLIST_VERSION","APP_MILTER_GREYLIST_INSTALLED"),
        "APP_MILTER_REGEX"=>array("APP_MILTER_REGEX_VERSION","APP_MILTER_REGEX_INSTALLED"),
        "APP_MIMEDEFANG"=>array("MimeDefangVersion",""),
        "APP_MSKTUTIL"=>array("MSKTUTIL_VERSION","MSKTUTIL_INSTALLED"),
        "APP_MSMTP"=>array("MSMTP_VERSION","MSMTP_INSTALLED"),
        "APP_MSTPD"=>array("APP_MSTPD_VERSION","APP_MSTPD_INSTALLED"),
        "APP_MYSQL"=>array("APP_MYSQL_VERSION","APP_MYSQL_INSTALLED"),
        "APP_NETMONIX"=>array("NDPID_VERSION",""),
        "APP_NGINX"=>array("APP_NGINX_VERSION","APP_NGINX_INSTALLED"),
        "APP_NGINX_CONSOLE"=>array("APP_NGINX_CONSOLE_VERSION",""),
        "APP_NTOPNG"=>array("NTOPNG_VERSION",""),
        "APP_OPENVPN"=>array("APP_OPENVPN_VERSION","OPENVPN_INSTALLED"),
        "APP_PDNS"=>array("PDNSVersion",""),
        "APP_PHP_GEOIP2"=>array("GEOIPUPDATE_VERSION","PHP_GEOIP_INSTALLED"),
        "APP_PLAKAR"=>array("APP_PLAKAR_VERSION","APP_PLAKAR_INSTALLED"),
        "APP_POSTFIX"=>array("POSTFIX_VERSION","POSTFIX_INSTALLED"),
        "APP_POSTGRES"=>array("APP_POSTGRES_VERSION","APP_POSTGRES_INSTALLED"),
        "APP_PPTP_CLIENT"=>array("APP_PPTP_CLIENT_VERSION","APP_PPTP_CLIENT_INSTALLED"),
        "APP_PROFTPD"=>array("ProFTPDVersion","ProFTPDInstalled"),
        "APP_QAT"=>array("IntelQATVersion",""),
        "APP_RBLDNSD"=>array("APP_RBLDNSD_VERSION","APP_RBLDNSD_INSTALLED"),
        "APP_RDPPROXY"=>array("APP_RDPPROXY_VERSION","APP_RDPPROXY_INSTALLED"),
        "APP_REDIS_SERVER"=>array("APP_REDIS_SERVER_VERSION","APP_REDIS_SERVER_INSTALLED"),
        "APP_RSPAMD"=>array("APP_RSPAMD_VERSION","APP_RSPAMD_INSTALLED"),
        "APP_RUSTDESK"=>array("APP_RUSTDESK_VERSION","APP_RUSTDESK_INSTALLED"),
        "APP_SAMBA"=>array("APP_SAMBA_VERSION","SAMBA_INSTALLED"),
        "APP_SHELLINABOX"=>array("APP_SHELLINABOX_VERSION","APP_SHELLINABOX_INSTALLED"),
        "APP_SNMPD"=>array("SNMPD_VERSION","SNMPD_INSTALLED"),
        "APP_SPLUNK_FORWARDER"=>array("APP_SPLUNK_FORWARDER_VERSION","APP_SPLUNK_FORWARDER_INSTALLED"),
        "APP_SQUID"=>array("SquidRealVersion","SQUID_INSTALLED"),
        "APP_SQUID6"=>array("SquidRealVersion","SQUID_INSTALLED"),
        "APP_SQUID_REV4"=>array("SquidRealVersion","SQUID_INSTALLED"),
        "APP_SSHPORTAL"=>array("APP_SSHPORTAL_VERSION","APP_SSHPORTAL_INSTALLED"),
        "APP_STRONGSWAN"=>array("IPSEC_VERSION","IPSEC_INSTALLED"),
        "APP_SYNO_BACKUP"=>array("APP_SYNO_BACKUP_VERSION","APP_SYNO_BACKUP_INSTALLED"),
        "APP_SYSLOGD"=>array("APP_SYSLOGD_VERSION",""),
        "APP_TAILSCALE"=>array("APP_TAILSCALE_VERSION","APP_TAILSCALE_INSTALLED"),
        "APP_TRIVY"=>array("APP_TRIVY_VERSION","APP_TRIVY_INSTALLED"),
        "APP_UFDBGUARDD"=>array("UFDBDaemonVersion","APP_UFDBGUARD_INSTALLED"),
        "APP_UNBOUND"=>array("UnboundVersion",""),
        "APP_URBACKUP"=>array("APP_URBACKUP_VERSION","APP_URBACKUP_INSTALLED"),
        "APP_VMTOOLS"=>array("VMWARE_TOOLS_VERSION","VMWARE_TOOLS_INSTALLED"),
        "APP_VNSTAT"=>array("APP_VNSTAT_VERSION",""),
        "APP_WANPROXY"=>array("WANPROXY_VERSION","WANPROXY_INSTALLED"),
        "APP_ZABBIX_AGENT"=>array("APP_ZABBIX_AGENT_VERSION","APP_ZABBIX_AGENT_INSTALLED"),
        "WP_CLIENT"=>array("WP_CLIENT_VERSION","WP_CLIENT_INSTALLED"),
    );
}

function upgp_is_kernel_package(string $PKEY):bool{
    return in_array($PKEY,array("APP_XKERNEL","APP_XTABLES","PFRING","APP_PFRING_AVX"),true);
}

function upgp_vcmp(string $a,string $b):int{
    return version_compare(strtolower(trim($a)),strtolower(trim($b)));
}

// Un seul modele : l'index du depot, la version installee, le noyau.
function upgp_model(string $PKEY):array{
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array("key"=>$PKEY,"rows"=>array(),"latest"=>"","installed"=>"","state"=>"unknown",
        "kernel"=>upgp_is_kernel_package($PKEY),"kern_str"=>"","kern_bin"=>0,"kern_match"=>false,
        "repo"=>"","remote"=>false,"upload"=>true,"admin"=>false);
    $m["remote"]=strlen((string)($_GET["uuid"] ?? ""))>5;
    $m["upload"]=!$m["remote"];
    $users=new usersMenus();
    $m["admin"]=(bool)$users->AsSystemAdministrator;
    $fkey=intval($_GET["filter-key"] ?? 0);

    $UPDATES=$sock->unserializeb64($sock->GET_INFO("v4softsRepo"));
    $m["index_ok"]=is_array($UPDATES);
    if(!$m["index_ok"]){return $m;}

    if($m["kernel"]){
        $m["kern_bin"]=$tpl->kernel_binary_ver();
        $k=php_uname("r");
        if(preg_match("#^([0-9\.]+)\+deb#",$k,$re)){$k=$re[1];}
        $m["kern_str"]=preg_replace('#\+deb[0-9]+#',"",$k);
    }

    $ssl=intval($sock->GET_INFO("ArticaRepoSSL"))==1;
    $list=isset($UPDATES[$PKEY]) && is_array($UPDATES[$PKEY])?$UPDATES[$PKEY]:array();
    foreach(upgsoft_sort_by_version($list) as $a){
        $integ=$a["INTEGER"];
        if($fkey>0 && $integ<>$fkey){continue;}
        $uri=(string)($a["URI"] ?? "");
        if($ssl){$uri=str_replace("http://mirror.articatech.com","https://www.articatech.com",$uri);}
        if($m["repo"]==="" && $uri!==""){$m["repo"]=(string)parse_url($uri,PHP_URL_HOST);}
        $r=array("integ"=>$integ,"version"=>(string)($a["VERSION"] ?? ""),"size"=>intval($a["SIZE"] ?? 0),
            "md5"=>strtolower(preg_replace('/[^a-fA-F0-9]/','',(string)($a["MD5"] ?? ""))),"rel"=>"","kern_ok"=>true);
        if($m["kernel"]){
            $r["kern_ok"]=(string)$integ===(string)$m["kern_bin"];
            if($r["kern_ok"]){$m["kern_match"]=true;}
        }
        if($m["latest"]===""){$m["latest"]=$r["version"];}
        $m["rows"][]=$r;
    }

    // Version installee : seulement en local, et seulement si le produit a une cle connue.
    $keys=upgp_version_keys();
    if(!$m["remote"] && isset($keys[$PKEY])){
        list($vkey,$ikey)=$keys[$PKEY];
        $ver=trim((string)$sock->GET_INFO($vkey));
        $inst=$ikey===""?($ver!==""):(intval($sock->GET_INFO($ikey))==1);
        if(!$inst){
            $m["state"]="absent";
        }elseif($ver!==""){
            $m["installed"]=$ver;
            $m["state"]="known";
            if($m["latest"]!==""){
                $c=upgp_vcmp($m["latest"],$ver);
                if($c>0){$m["state"]="update";}elseif($c==0){$m["state"]="ok";}
            }
        }
    }
    foreach($m["rows"] as $i=>$r){
        if($m["installed"]===""){continue;}
        $c=upgp_vcmp($r["version"],$m["installed"]);
        $m["rows"][$i]["rel"]=$c>0?"newer":($c==0?"installed":"older");
    }
    return $m;
}

// Traduit d'abord, substitue ensuite : un str_replace sur "{jeton}" ne trouve pas %s.
function upgp_t(string $key,string $value):string{
    $tpl=new template_admin();
    return str_replace("%s",$value,$tpl->_ENGINE_parse_body("{".$key."}"));
}

function upgp_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function upgp_state_pill(array $m):string{
    $map=array(
        "update"=>array("ak-warn","fa-arrow-up","{upgp_update_available}"),
        "ok"=>array("ak-ok","fa-circle-check","{upgp_up_to_date}"),
        "absent"=>array("ak-off","fa-circle-minus","{upgp_not_installed}"),
        "known"=>array("ak-off","fa-circle-question","{upgp_not_comparable}"),
        "unknown"=>array("ak-off","fa-circle-question","{upgp_not_comparable}"),
    );
    $s=$map[$m["state"]];
    return "<span class='ak-pill {$s[0]}'><i class='fas {$s[1]}' aria-hidden='true'></i> {$s[2]}</span>";
}

function upgp_fact(string $label,string $value):string{
    return "<div class='upgp-fact'><span class='ak-kicker'>$label</span><span class='upgp-fact-v ak-mono'>$value</span></div>";
}

function upgp_head(array $m):string{
    if($m["state"]=="absent"){
        $inst="<span class='ak-muted upgp-word'>{upgp_not_installed}</span>";
    }else{
        $inst=$m["installed"]!==""?upgp_h($m["installed"]):"—";
    }
    $latest=$m["latest"]!==""?upgp_h($m["latest"]):"—";
    return "<section class='ak-strip upgp-head' aria-label='{version}'>"
        ."<span class='ak-ico'><i class='fas fa-box' aria-hidden='true'></i></span>"
        ."<div class='ak-strip-main'><span class='ak-kicker'>{upgp_package}</span><span class='ak-mono upgp-key'>".upgp_h($m["key"])."</span></div>"
        .upgp_fact("{upgp_installed}",$inst)
        ."<span class='upgp-vsep' aria-hidden='true'></span>"
        .upgp_fact("{upgp_latest}",$latest)
        .upgp_state_pill($m)
        ."</section>";
}

function upgp_kernel_notice(array $m):string{
    $k="<span class='ak-mono'>".upgp_h($m["kern_str"])." (".upgp_h($m["kern_bin"]).")</span>";
    $txt=upgp_t("upgp_kernel_running",$k);
    if(!$m["kern_match"]){
        return "<div class='ak-banner ak-warn upgp-kern'><i class='fas fa-microchip' aria-hidden='true'></i><span class='ak-banner-m'>$txt {upgp_kernel_missing}</span>"
            ."<a class='ak-link' href='/support'>{support}</a></div>";
    }
    return "<div class='ak-banner ak-info upgp-kern'><i class='fas fa-microchip' aria-hidden='true'></i><span class='ak-banner-m'>$txt</span></div>";
}

function upgp_table(array $m,string $afterjs):string{
    $rows=array();
    foreach($m["rows"] as $i=>$r){$rows[]=upgp_row($m,$r,$i==0,$afterjs);}
    return "<div class='ak-card upgp-card'><div class='upgp-scroll'><table class='ak-tbl upgp-tbl'>"
        ."<thead><tr><th scope='col'>{version}</th><th scope='col' class='ak-num'>{size}</th><th scope='col'><span class='sr-only'>{install_upgrade}</span></th></tr></thead>"
        ."<tbody>".implode("",$rows)."</tbody></table></div></div>";
}

function upgp_rel_tag(string $rel):string{
    if($rel=="installed"){return "<span class='ak-tag ak-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {upgp_installed}</span>";}
    if($rel=="newer"){return "<span class='ak-state ak-warn'><i class='fas fa-arrow-up' aria-hidden='true'></i> {upgp_newer}</span>";}
    if($rel=="older"){return "<span class='ak-state ak-off'><i class='fas fa-arrow-down' aria-hidden='true'></i> {upgp_older}</span>";}
    return "";
}

function upgp_md5(array $r):string{
    if($r["md5"]===""){return "";}
    $v=upgp_h($r["version"]);
    return "<div class='upgp-md5'><span class='ak-muted'>MD5</span> <span class='ak-mono upgp-md5-v' title='{$r["md5"]}'>{$r["md5"]}</span>"
        ."<button type='button' class='ak-act' aria-label=\"{upgp_copy_md5} $v\" title='{upgp_copy_md5}' OnClick=\"UpgpCopy(this,'{$r["md5"]}')\"><i class='far fa-copy' aria-hidden='true'></i></button></div>";
}

// Une version, et juste dessous la ligne de sa progression (vide = 0 px de haut).
function upgp_row(array $m,array $r,bool $latest,string $afterjs):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PKEY=$m["key"];
    $integ=$r["integ"];
    $divid="upg-$PKEY-$integ-progress-install";
    $tags=$latest?"<span class='ak-tag ak-accent ak-solid'>{upgp_latest_tag}</span>":"";
    $tags.=upgp_rel_tag($r["rel"]);

    $acts="";
    if($m["admin"]){
        if(!$r["kern_ok"]){
            $acts="<button type='button' class='ak-btn ak-btn-sm' disabled aria-disabled='true' title='{upgp_other_kernel}'><i class='fas fa-download' aria-hidden='true'></i> {install_upgrade}</button>";
        }else{
            $js=$tpl->framework_buildjs("/system/softwares/install/$PKEY/$integ",
                "system.installsoft.progress","system.installsoft.progress.txt",
                $divid,"Loadjs('$page?jsafter=yes$afterjs');");
            // Rempli pour une version plus recente ou un produit absent, contour sinon.
            $fill=($r["rel"]=="newer" || $m["state"]=="absent" || ($m["kernel"]) || ($r["rel"]=="" && $latest));
            $cls=$fill?"ak-btn ak-btn-sm ak-btn-primary":"ak-btn ak-btn-sm";
            $ico=$r["rel"]=="installed"?"fa-rotate":"fa-download";
            $acts="<button type='button' class='$cls' OnClick=\"$js\"><i class='fas $ico' aria-hidden='true'></i> {install_upgrade}</button>";
        }
        if($m["upload"] && $r["kern_ok"]){
            $acts.=$tpl->button_upload("{upload}",$page,"btn-default btn-xs","&product=$PKEY&key=$integ");
        }
    }
    $trc="";
    if($r["rel"]=="installed" || ($m["kernel"] && $r["kern_ok"])){$trc=" class='upgp-cur'";}
    if(!$r["kern_ok"]){$trc=" class='upgp-off'";}
    $h="<tr$trc><td><div class='upgp-ver'><span class='ak-mono upgp-v'>".upgp_h($r["version"])."</span>$tags</div>".upgp_md5($r)."</td>";
    // FormatBytes() attend des KILO-octets.
    $h.="<td class='ak-num ak-mono ak-nowrap'>".FormatBytes($r["size"]/1024)."</td>";
    $h.="<td class='ak-end'><div class='upgp-acts'>$acts</div></td></tr>";
    $h.="<tr class='upgp-prog'><td colspan='3'><div id='$divid'></div></td></tr>";
    return $h;
}

function upgp_foot(array $m):string{
    $n=upgp_t("upgp_versions_count",(string)count($m["rows"]));
    $repo=$m["repo"]!==""?"<span>".upgp_t("upgp_repository","<span class='ak-mono'>".upgp_h($m["repo"])."</span>")."</span>":"";
    $hint=$m["upload"] && $m["admin"]?"<p class='ak-note upgp-hint'><i class='fas fa-circle-info' aria-hidden='true'></i> {upgp_offline_hint}</p>":"";
    return "<div class='upgp-foot ak-muted'><span>$n</span>$repo</div>$hint";
}

function upgp_empty(array $m,string $afterjs):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();
    $btn="";
    if($m["admin"]){
        $js=$tpl->framework_buildjs("/system/softwares/refresh",
            "UpdateReposIndex.progress","UpdateReposIndex.log",
            "upgp-refresh-$t","Loadjs('$page?product={$m["key"]}');Loadjs('fw.system.upgrade-software.php?jsafter=yes$afterjs');");
        $btn="<button type='button' class='ak-btn' OnClick=\"$js\"><i class='fas fa-rotate' aria-hidden='true'></i> {upgp_refresh_index}</button>";
    }
    return "<div class='ak-card ak-empty-c'><i class='fas fa-box-open' aria-hidden='true'></i><span>{upgp_empty}</span>$btn<div id='upgp-refresh-$t' class='upgp-refresh'></div></div>";
}

function upgp_tailscale():string{
    $tpl=new template_admin();
    $js=$tpl->framework_buildjs("tailscale.php?pkgupgrade=yes",
        "tailscale.apt.progress","tailscale.apt.log","APP_TAILSCALE_PROGRESS");
    return "<div class='upgp-tail'><div id='APP_TAILSCALE_PROGRESS'></div>"
        ."<button type='button' class='ak-btn' OnClick=\"$js\"><i class='fas fa-rotate' aria-hidden='true'></i> {upgrade_via_system}</button></div>";
}

// Regles propres a la fenetre ; le reste vient du kit ak- (angular.css.php).
function upgp_style():string{
    if(isset($GLOBALS["UPGP_STYLE"])){return "";}
    $GLOBALS["UPGP_STYLE"]=true;
    $c=array();
    $c[]=".upgp{display:flex;flex-direction:column;gap:14px;padding:4px 2px}";
    $c[]=".upgp-head{gap:18px}.upgp-key{font-size:14px;font-weight:600;color:#0f172a}";
    $c[]=".upgp-fact{display:flex;flex-direction:column;align-items:flex-end;gap:2px;flex:0 0 auto}.upgp-fact-v{font-size:15px;font-weight:600;color:#0f172a}.upgp-word{font-family:inherit;font-weight:400}";
    $c[]=".upgp-vsep{width:1px;height:32px;background:#e2e8f0;flex:0 0 auto}";
    $c[]=".upgp-scroll{max-height:420px;overflow-y:auto}.upgp-tbl thead th{position:sticky;top:0;z-index:1}";
    $c[]=".upgp-tbl td{vertical-align:top}.upgp-tbl th,.upgp-tbl td{padding-left:14px;padding-right:14px}.upgp-tbl th:nth-child(2),.upgp-tbl td:nth-child(2){width:96px}";
    $c[]=".upgp-ver{display:flex;align-items:center;flex-wrap:wrap;gap:8px}.upgp-v{font-size:15px;font-weight:600;color:#0f172a}";
    $c[]=".upgp-md5{display:flex;align-items:center;gap:6px;margin-top:4px;font-size:13px;min-width:0}.upgp-md5-v{color:#64748b;max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $c[]=".upgp-acts{display:flex;justify-content:flex-end;align-items:center;gap:8px;flex-wrap:wrap}";
    $c[]=".upgp-acts{flex-wrap:nowrap}.upgp-tbl td:last-child{width:1%;white-space:nowrap}";
    // Bouton d'envoi : on ne depend pas de la feuille du plugin fileupload pour cacher le champ natif.
    $c[]=".upgp-acts .fileinput-button{position:relative;overflow:hidden;height:28px;display:inline-flex;align-items:center;gap:6px;border-radius:8px;font-size:13px;font-weight:600;padding:0 10px}";
    $c[]=".upgp-acts .fileinput-button input{position:absolute;top:0;right:0;margin:0;opacity:0;cursor:pointer;font-size:200px;direction:ltr}";
    $c[]=".upgp-cur td{background:rgba(16,185,129,.06)}.upgp-cur td:first-child{box-shadow:inset 3px 0 0 var(--ak-fill)}";
    $c[]=".upgp-off td{opacity:.55}";
    $c[]=".upgp-tbl .upgp-prog td{padding:0;border-bottom:0}.upgp-prog td>div:not(:empty){padding:6px 18px 12px}";
    $c[]=".upgp-foot{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:13px;padding:0 4px}";
    $c[]=".upgp-hint{margin:0 4px}.upgp-tail{display:flex;flex-direction:column;align-items:flex-end;gap:8px}";
    return "<style>".implode("\n",$c)."</style>";
}

function upgp_js():string{
    // navigator.clipboard d'abord ; repli textarea + execCommand si l'API manque OU refuse.
    return "<script>window.UpgpCopy=function(btn,v){"
        ."var ok=function(){btn.setAttribute('data-done','1');var i=btn.querySelector('svg,i');if(i){i.style.color='#0a8059';}};"
        ."var old=function(){var t=document.createElement('textarea');t.value=v;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();"
        ."try{if(document.execCommand('copy')){ok();}}catch(e){}document.body.removeChild(t);};"
        ."try{if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(v).then(ok,old);return;}}catch(e){}"
        ."old();};</script>";
}

/**
 * Trie les versions disponibles de la plus recente a la plus ancienne.
 *
 * L'ancienne normalisation completait chaque composant PAR LA DROITE, et le
 * faisait deux fois de suite: "2" devenait "20" puis "2000", "10" devenait
 * "1000". Deux consequences, mesurees:
 *   - 2.1.0 et 2.10.0 produisaient EXACTEMENT la meme cle -- l'une des deux
 *     versions ecrasait l'autre et disparaissait de la liste;
 *   - 2.10 se classait SOUS 2.9, l'inverse de l'ordre reel.
 * Chaque composant est desormais complete par la GAUCHE sur quatre chiffres, et
 * la cle reste une chaine: cinq groupes de quatre chiffres depassent
 * PHP_INT_MAX. Le prefixe "000" garantit que PHP ne convertit pas cette cle en
 * entier, et krsort(..., SORT_STRING) rend alors l'ordre numerique des versions.
 */
function upgsoft_sort_by_version(array $ISARRAY):array{
    $NEWARRAY=array();
    foreach ($ISARRAY as $integ=>$array){
        $VERSION=str_replace("-",".",(string)($array["VERSION"] ?? ""));
        $VERSIONTB=explode(".",$VERSION);
        array_unshift($VERSIONTB,"000");
        foreach ($VERSIONTB as $index=>$num){
            $VERSIONTB[$index]=str_pad(substr(preg_replace('/[^0-9]/','',$num),0,4),4,"0",STR_PAD_LEFT);
        }
        while(count($VERSIONTB)<5){$VERSIONTB[]="0000";}
        $array["INTEGER"]=$integ;
        $NEWARRAY["000".@implode("",$VERSIONTB)]=$array;
    }
    krsort($NEWARRAY,SORT_STRING);
    return $NEWARRAY;
}

function file_uploaded(){
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it

    // Memes valeurs d'URL, memes contextes: chemin REST puis identifiant de div
    // injecte en JavaScript.
    $product=preg_replace('/[^A-Za-z0-9_.-]/','',(string)($_GET["product"] ?? ""));
    $key=preg_replace('/[^A-Za-z0-9_.-]/','',(string)($_GET["key"] ?? ""));
    $file=urlencode(base64_encode("/usr/share/artica-postfix/ressources/conf/upload/"
        .basename((string)$_GET["file-uploaded"])));
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.installsoft.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.installsoft.progress.txt";
    $ARRAY["CMD"]="/system/softwares/install-local/$product/$key/$file";
    $ARRAY["TITLE"]="{installing} $product";
    $ARRAY["AFTER"]="LoadAjax('table-loader-versions-service','fw.versions.php?table=yes');";

    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=$product-$key-progress-install')";
    echo $jsrestart;

}

