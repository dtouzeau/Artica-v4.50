<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

// Page du serveur FTP (ProFTPD), maquette stitch_artica_ftp_server_redesign.
// Donnees : reglages Valkey (lus par articarest proftpd.BuildConf), /proftpd/status
// (bloc ini du service) et /proftpd/users (comptes de ftpusers.db : la console
// n'ouvre plus jamais cette base). Une seule ecriture : les reglages de la liste
// blanche ftpsv_settings_keys(), reserves a AsSystemAdministrator.

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$users=new usersMenus();
if(!$users->AsSystemAdministrator){exit();}

if(isset($_POST["ftpsv-save"])){ftpsv_save();exit;}
if(isset($_POST["ProFTPDInterfaces"])){ftpsv_save();exit;}
if(isset($_POST["ProFTPDUseTLS"])){ftpsv_save();exit;}
if(isset($_POST["ftpsv-uninstall"])){ftpsv_uninstall_check();exit;}
if(isset($_GET["status"])){ftpsv_status_host();exit;}
if(isset($_GET["table-start"])){ftpsv_build();exit;}
if(isset($_GET["main"])){ftpsv_main_js();exit;}
if(isset($_GET["ssl"])){ftpsv_ssl_js();exit;}
if(isset($_GET["ssl-popup"])){ftpsv_ssl_popup();exit;}
if(isset($_GET["main-popup"])){ftpsv_main_popup();exit;}
if(isset($_GET["uninstall-js"])){ftpsv_uninstall_js();exit;}

page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $version=ftpsv_version();
    $html=$tpl->page_header("{ftpsv_title} $version","fas fa-folder-open","{ftpsv_explain}",
        "$page?table-start=yes","ftp-service","progress-proftpd-restart",false,"table-loader-proftpd-service");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{ftpsv_title}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ── Helpers ─────────────────────────────────────────────────────────────────

// Traduit d'abord, substitue ensuite (%1, %2) : un str_replace sur "{jeton}"
// ne trouverait pas les marqueurs.
function ftpsv_t($token,$a="",$b=""):string{
    if(!isset($GLOBALS["FTPSV_T"])){$GLOBALS["FTPSV_T"]=array();}
    if(!isset($GLOBALS["FTPSV_T"][$token])){
        $tpl=new template_admin();
        $GLOBALS["FTPSV_T"][$token]=$tpl->_ENGINE_parse_body("{"."$token}");
    }
    return str_replace(array("%1","%2"),array($a,$b),$GLOBALS["FTPSV_T"][$token]);
}

function ftpsv_h($s):string{
    return htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
}

function ftpsv_info($key):string{
    return trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
}

function ftpsv_version():string{
    $v=ftpsv_info("ProFTPDVersion");
    if($v==""){return "";}
    return "v".ftpsv_h($v);
}

// Age relatif ; la date exacte va dans l'info-bulle.
function ftpsv_ago($epoch):string{
    $d=time()-intval($epoch);
    if($d<60){return ftpsv_t("ftpsv_just_now");}
    if($d<3600){return ftpsv_t("ftpsv_min_ago",(string)intval($d/60));}
    if($d<86400){return ftpsv_t("ftpsv_h_ago",(string)intval($d/3600));}
    return ftpsv_t("ftpsv_d_ago",(string)intval($d/86400));
}

// "3 days 4 hours 12 minutes" -> "3 days 4 hours" : les deux premieres unites.
function ftpsv_uptime($s):string{
    $s=trim((string)$s);
    if($s===""){return "";}
    if(preg_match_all('#([0-9]+\s*[a-zA-Z]+)#',$s,$re)){
        return implode(" ",array_slice($re[1],0,2));
    }
    return $s;
}

// Choix proposes : les memes cles que ComputeUmask() d'articarest.
function ftpsv_umasks():array{
    return array("022"=>"{ftpsv_um_022}","026"=>"{ftpsv_um_026}","027"=>"{ftpsv_um_027}","077"=>"{ftpsv_um_077}");
}
function ftpsv_filemodes():array{
    return array("0666"=>"{ftpsv_fm_0666}","0644"=>"{ftpsv_fm_0644}","0640"=>"{ftpsv_fm_0640}","0600"=>"{ftpsv_fm_0600}");
}

// Droits effectifs, calcules comme articarest : dossiers = 777 sans le umask,
// fichiers = mode d'ouverture sans le umask (jamais de bit d'execution).
function ftpsv_effective_perms($fileMode,$umask):array{
    $um=octdec($umask);
    $fm=octdec($fileMode);
    $dirs=0777 & ~$um;
    $files=($fm & ~$um) & 0666;
    return array(sprintf("%03o",$dirs),sprintf("%03o",$files));
}

// ── Verrou Artica Meta (politique « FTP service ») ─────────────────────────
// FTPMetaManaged / FTPMetaManagedInfo sont ecrits par articarest (metapolicies) quand
// une politique Meta tient le service. Les cles listees dans "keys" sont en lecture
// seule ici ; information illisible = tout le catalogue tenu (meme regle que
// proftpd.HeldSettingKeys). ProFTPDInterfaces et VsFTPDPassiveAddr ne le sont jamais.

function ftpsv_meta_catalog():array{
    return array("VsFTPDPassive","VsFTPDPassiveMinPort","VsFTPDPassiveMaxPort","VsFTPDLocalMaxRate",
        "VsFTPDLocalUmask","VsFTPDFileOpenMode","ProFTPDRootLogin","ProFTPDUseTLS","ProFTPDCertificateName");
}

// Fonction pure (harnais) : drapeau + JSON -> ["managed"=>bool,"held"=>[cle=>true]].
function ftpsv_meta_parse($flag,$raw):array{
    $r=array("managed"=>false,"held"=>array());
    if(trim((string)$flag)!=="1"){return $r;}
    $r["managed"]=true;
    $raw=trim((string)$raw);
    $info=$raw===""?null:json_decode($raw,true);
    if(!is_array($info)){
        foreach(ftpsv_meta_catalog() as $k){$r["held"][$k]=true;}
        return $r;
    }
    $allowed=array_flip(ftpsv_meta_catalog());
    $keys=isset($info["keys"]) && is_array($info["keys"])?$info["keys"]:array();
    foreach($keys as $k){
        $k=trim((string)$k);
        if(isset($allowed[$k])){$r["held"][$k]=true;}
    }
    // Les deux cles TLS vont ensemble : un certificat tenu tient aussi ProFTPDUseTLS
    // (jamais un ProFTPDUseTLS poste sans le certificat tenu).
    if(isset($r["held"]["ProFTPDCertificateName"])){$r["held"]["ProFTPDUseTLS"]=true;}
    return $r;
}

function ftpsv_meta():array{
    if(!isset($GLOBALS["FTPSV_META"])){
        $GLOBALS["FTPSV_META"]=ftpsv_meta_parse(ftpsv_info("FTPMetaManaged"),ftpsv_info("FTPMetaManagedInfo"));
    }
    return $GLOBALS["FTPSV_META"];
}

function ftpsv_meta_held($key):bool{
    $m=ftpsv_meta();
    return isset($m["held"][$key]);
}

function ftpsv_meta_tag():string{
    return "<span class='ftpsv-meta' title=\"{ftpsv_meta_held_tip}\"><i class='fas fa-lock'></i> Artica Meta</span>";
}

function ftpsv_meta_banner():string{
    $m=ftpsv_meta();
    if(!$m["managed"]){return "";}
    return "<div class='ftpsv-meta-banner' role='status'><i class='fas fa-diagram-project'></i><div>{ftpsv_meta_banner}</div></div>";
}

// Champ tenu : valeur affichee seulement. Le nom n'est pas la cle (field_info poste sa
// valeur) : la liste blanche ftpsv_settings_keys() l'ignore.
function ftpsv_meta_field($tpl,$key,$label,$text):string{
    return $tpl->field_info("ftpsv-ro-$key",$label,$text." (Artica Meta)","{ftpsv_meta_field_held}");
}

// ── Modele : calcule une fois, lu par tous les widgets ─────────────────────

function ftpsv_model():array{
    if(isset($GLOBALS["FTPSV_MODEL"])){return $GLOBALS["FTPSV_MODEL"];}
    $m=array();
    $ifaces=ftpsv_info("ProFTPDInterfaces");
    $m["ifaces"]=array();
    foreach(explode(",",$ifaces) as $i){
        $i=trim($i);
        if($i!==""){$m["ifaces"][]=$i;}
    }
    $m["port"]=intval(ftpsv_info("VSFTPDPort"));
    if($m["port"]==0){$m["port"]=21;}
    $host=ftpsv_info("myhostname");
    if($host===""){$host=(string)php_uname("n");}
    $m["host"]=$host;
    // Le demon lit GET_INFO_INT : une cle vide vaut 0 = passif desactive.
    $m["passive"]=intval(ftpsv_info("VsFTPDPassive"))==1;
    $m["pmin"]=intval(ftpsv_info("VsFTPDPassiveMinPort"));
    $m["pmax"]=intval(ftpsv_info("VsFTPDPassiveMaxPort"));
    if($m["pmin"]==0){$m["pmin"]=40000;}
    if($m["pmax"]==0){$m["pmax"]=40200;}
    $m["paddr"]=ftpsv_info("VsFTPDPassiveAddr");
    if(strlen($m["paddr"])<4){$m["paddr"]="";}
    $m["rate"]=intval(ftpsv_info("VsFTPDLocalMaxRate"));
    $m["root"]=intval(ftpsv_info("ProFTPDRootLogin"))==1;
    $m["umask"]=ftpsv_info("VsFTPDLocalUmask");
    if(!isset(ftpsv_umasks()[$m["umask"]])){$m["umask"]="077";}
    $m["filemode"]=ftpsv_info("VsFTPDFileOpenMode");
    if(!preg_match('#^0?[0-7]{3}$#',$m["filemode"])){$m["filemode"]="0666";}
    list($m["perm_dirs"],$m["perm_files"])=ftpsv_effective_perms($m["filemode"],$m["umask"]);
    $m["tls"]=intval(ftpsv_info("ProFTPDUseTLS"))==1;
    $m["cert"]=ftpsv_info("ProFTPDCertificateName");
    // articarest coupe le TLS quand le nom du certificat fait moins de 3 caracteres.
    $m["tls_nocert"]=$m["tls"] && strlen($m["cert"])<3;
    $m["tls_active"]=$m["tls"] && !$m["tls_nocert"];
    $m["ldap"]=intval(ftpsv_info("EnableOpenLDAP"))==1;
    $m["version"]=ftpsv_info("ProFTPDVersion");
    $m["status"]=ftpsv_status_read();
    $m["accounts"]=ftpsv_accounts_read();
    $GLOBALS["FTPSV_MODEL"]=$m;
    return $m;
}

// Etat du service : state = running|stopped|disabled|unknown.
function ftpsv_status_read():array{
    $s=array("state"=>"unknown","uptime"=>"","memory_kb"=>0);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proftpd/status"));
    if(!is_object($json) || !property_exists($json,"Info")){return $s;}
    $ini=new Bs_IniHandler();
    $ini->loadString((string)$json->Info);
    if(!isset($ini->_params["APP_PROFTPD"]) || !is_array($ini->_params["APP_PROFTPD"])){return $s;}
    $a=$ini->_params["APP_PROFTPD"];
    if(intval(isset($a["service_disabled"])?$a["service_disabled"]:1)==0){
        $s["state"]="disabled";
        return $s;
    }
    if(intval(isset($a["running"])?$a["running"]:0)==1){
        $s["state"]="running";
        $s["uptime"]=ftpsv_uptime(isset($a["uptime"])?$a["uptime"]:"");
        $s["memory_kb"]=intval(isset($a["master_memory"])?$a["master_memory"]:0);
        return $s;
    }
    $s["state"]="stopped";
    return $s;
}

// Comptes : ok=false quand articarest ne repond pas (« — », jamais « 0 »).
function ftpsv_accounts_read():array{
    $r=array("ok"=>false,"total"=>0,"signed_in"=>0,"last_userid"=>"","last_epoch"=>0,"total_logins"=>0,"error"=>"");
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/proftpd/users"));
    if(!is_object($json)){$r["error"]="no answer";return $r;}
    if(!property_exists($json,"Status") || !$json->Status){
        $r["error"]=property_exists($json,"Error")?(string)$json->Error:"";
        return $r;
    }
    if(!property_exists($json,"summary") || !is_object($json->summary)){return $r;}
    $s=$json->summary;
    $r["ok"]=true;
    foreach(array("total","signed_in","last_epoch","total_logins") as $k){
        $r[$k]=property_exists($s,$k)?intval($s->$k):0;
    }
    $r["last_userid"]=property_exists($s,"last_userid")?(string)$s->last_userid:"";
    return $r;
}

// ── Contenu ─────────────────────────────────────────────────────────────────

function ftpsv_build(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=ftpsv_model();
    $h=array();
    $h[]="<div class='ftpsv'>";
    $h[]=ftpsv_hero($m);
    $h[]=ftpsv_meta_banner();
    $h[]="<div id='ftpsv-status'>".ftpsv_status_strip($m)."</div>";
    $h[]=ftpsv_empty($m);
    $h[]="<div class='ftpsv-cols'>";
    $h[]="<div class='ftpsv-col-main'>".ftpsv_connect_card($m)."</div>";
    $h[]="<div class='ftpsv-col-side'>".ftpsv_tls_card($m).ftpsv_always_card()."</div>";
    $h[]="</div>";
    $h[]="</div>";

    $jsrestart=$tpl->framework_buildjs("/proftpd/restart","proftpd.progress","proftpd.progress.log","progress-proftpd-restart","FtpsvRefresh();");
    $topbuttons=array();
    $topbuttons[]=array($jsrestart,ico_refresh,"{ftpsv_restart}","AsSystemAdministrator");
    $topbuttons[]=array("Loadjs('$page?uninstall-js=yes')",ico_trash,"{ftpsv_uninstall}","AsSystemAdministrator");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{ftpsv_title} ".ftpsv_version();
    $TINY_ARRAY["ICO"]="fas fa-folder-open";
    $TINY_ARRAY["EXPL"]="{ftpsv_explain}";
    $TINY_ARRAY["URL"]="ftp-service";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo ftpsv_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>".ftpsv_js()."$tiny</script>";
}

// Hote rafraichi : seulement la bande d'etat.
function ftpsv_status_host(){
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(ftpsv_status_strip(ftpsv_model()));
}

// ── Widgets (une fonction chacun) ──────────────────────────────────────────

function ftpsv_hero($m):string{
    $chips=array();
    $chips[]="<span class='ftpsv-chip'><i class='fas fa-lock'></i> {ftpsv_chip_jail}</span>";
    $chips[]="<span class='ftpsv-chip'><i class='fas fa-users'></i> {ftpsv_chip_max}</span>";
    if($m["ldap"]){
        $chips[]="<span class='ftpsv-chip'><i class='fas fa-address-book'></i> {ftpsv_chip_ldap}</span>";
    }else{
        $chips[]="<span class='ftpsv-chip'><i class='fas fa-address-book'></i> {ftpsv_chip_local}</span>";
    }
    if($m["tls_active"]){
        $chips[]="<span class='ftpsv-chip'><i class='fas fa-shield-halved'></i> {ftpsv_chip_tls_on}</span>";
    }else{
        $chips[]="<span class='ftpsv-chip ftpsv-chip-warn'><i class='fas fa-triangle-exclamation'></i> {ftpsv_chip_tls_off}</span>";
    }
    return "<div class='ftpsv-hero'><div class='ftpsv-hero-ico'><i class='fas fa-folder-open'></i></div>"
        ."<div><h3>{ftpsv_hero_title}</h3><p>{ftpsv_hero_text}</p><div class='ftpsv-chips'>".implode("",$chips)."</div></div></div>";
}

function ftpsv_status_strip($m):string{
    return "<div class='ftpsv-tiles'>".ftpsv_tile_service($m).ftpsv_tile_memory($m).ftpsv_tile_accounts($m).ftpsv_tile_signin($m)."</div>";
}

function ftpsv_tile_service($m):string{
    $st=$m["status"]["state"];
    $map=array("running"=>array("ok","{ftpsv_running}"),"stopped"=>array("err","{ftpsv_stopped}"),
        "disabled"=>array("off","{ftpsv_disabled}"),"unknown"=>array("off","{ftpsv_unknown}"));
    list($cls,$label)=$map[$st];
    $sub=array();
    if($st=="running" && $m["status"]["uptime"]!==""){$sub[]=ftpsv_t("ftpsv_up_for",ftpsv_h($m["status"]["uptime"]));}
    if($m["version"]!==""){$sub[]="<span class='ftpsv-mono'>ProFTPD ".ftpsv_h($m["version"])."</span>";}
    return "<div class='ftpsv-tile'><div class='ftpsv-tile-l'>{ftpsv_service}</div>"
        ."<div class='ftpsv-tile-v'><span class='ftpsv-dot ftpsv-dot-$cls' aria-hidden='true'></span>$label</div>"
        ."<div class='ftpsv-tile-s'>".implode(" · ",$sub)."</div></div>";
}

function ftpsv_tile_memory($m):string{
    $v="—";
    if($m["status"]["state"]=="running" && $m["status"]["memory_kb"]>0){
        $v=FormatBytes($m["status"]["memory_kb"]);
    }
    return "<div class='ftpsv-tile'><div class='ftpsv-tile-l'>{ftpsv_memory}</div>"
        ."<div class='ftpsv-tile-v'>$v</div><div class='ftpsv-tile-s'>{ftpsv_memory_help}</div></div>";
}

function ftpsv_tile_accounts($m):string{
    $a=$m["accounts"];
    $link="<a href='/ftp-users' class='ftpsv-link'>{ftpsv_manage_accounts}</a>";
    if(!$a["ok"]){
        return "<div class='ftpsv-tile'><div class='ftpsv-tile-l'>{ftpsv_accounts}</div>"
            ."<div class='ftpsv-tile-v'>—</div><div class='ftpsv-tile-s'>{ftpsv_accounts_unreadable} · $link</div></div>";
    }
    $v=$a["total"]==1?ftpsv_t("ftpsv_accounts_1"):ftpsv_t("ftpsv_accounts_n",number_format($a["total"]));
    $sub=array();
    if($a["total"]>0){$sub[]=ftpsv_t("ftpsv_accounts_signed",number_format($a["signed_in"]));}
    $sub[]=$link;
    return "<div class='ftpsv-tile'><div class='ftpsv-tile-l'>{ftpsv_accounts}</div>"
        ."<div class='ftpsv-tile-v'>$v</div><div class='ftpsv-tile-s'>".implode(" · ",$sub)."</div></div>";
}

function ftpsv_tile_signin($m):string{
    $a=$m["accounts"];
    if(!$a["ok"]){
        return "<div class='ftpsv-tile'><div class='ftpsv-tile-l'>{ftpsv_last_signin}</div><div class='ftpsv-tile-v'>—</div><div class='ftpsv-tile-s'></div></div>";
    }
    if($a["last_epoch"]<=0){
        return "<div class='ftpsv-tile'><div class='ftpsv-tile-l'>{ftpsv_last_signin}</div>"
            ."<div class='ftpsv-tile-v ftpsv-muted'>{ftpsv_no_signin}</div><div class='ftpsv-tile-s'></div></div>";
    }
    $exact=date("Y-m-d H:i:s",$a["last_epoch"]);
    $v=ftpsv_t("ftpsv_ago_by",ftpsv_ago($a["last_epoch"]),ftpsv_h($a["last_userid"]));
    return "<div class='ftpsv-tile'><div class='ftpsv-tile-l'>{ftpsv_last_signin}</div>"
        ."<div class='ftpsv-tile-v' title='$exact'>$v</div>"
        ."<div class='ftpsv-tile-s'>".ftpsv_t("ftpsv_logins_total",number_format($a["total_logins"]))."</div></div>";
}

// Invitation quand aucun compte n'existe (et seulement si la liste a pu etre lue).
function ftpsv_empty($m):string{
    if(!$m["accounts"]["ok"] || $m["accounts"]["total"]>0){return "";}
    return "<div class='ftpsv-empty'><div class='ftpsv-empty-ico'><i class='fas fa-user-plus'></i></div>"
        ."<div class='ftpsv-empty-txt'><h4>{ftpsv_empty_title}</h4><p>{ftpsv_empty_text}</p></div>"
        ."<button type='button' class='ftpsv-btn ftpsv-btn-primary' OnClick=\"Loadjs('fw.proftpd.members.php?member-js=0&refresh=ftpsv');\"><i class='fas fa-user-plus'></i> {ftpsv_create_account}</button></div>";
}

function ftpsv_row($ico,$title,$sub,$value,$help):string{
    $subHtml=$sub===""?"":"<div class='ftpsv-row-sub'>$sub</div>";
    return "<div class='ftpsv-row'><div class='ftpsv-row-ico'><i class='$ico'></i></div>"
        ."<div class='ftpsv-row-body'><div class='ftpsv-row-head'><div><div class='ftpsv-row-title'>$title</div>$subHtml</div>"
        ."<div class='ftpsv-row-val'>$value</div></div><div class='ftpsv-row-help'>$help</div></div></div>";
}

function ftpsv_badge($kind,$ico,$text):string{
    return "<span class='ftpsv-badge ftpsv-badge-$kind'><i class='$ico'></i> $text</span>";
}

function ftpsv_connect_card($m):string{
    $page=CurrentPageName();
    $rows=array();
    if(count($m["ifaces"])==0){
        $ifv="<span class='ftpsv-tag'>{ftpsv_all_cards}</span>";
    }else{
        $t=array();
        foreach($m["ifaces"] as $i){$t[]="<span class='ftpsv-tag ftpsv-mono'>".ftpsv_h($i)."</span>";}
        $ifv=implode(" ",$t);
    }
    $rows[]=ftpsv_row("fas fa-network-wired","{ftpsv_ifaces}","",$ifv,"{ftpsv_ifaces_help}");
    $url="ftp://".ftpsv_h($m["host"]).":".$m["port"];
    $rows[]=ftpsv_row("fas fa-globe","{ftpsv_address}","","<span class='ftpsv-code'>$url</span>","{ftpsv_address_help}");
    if($m["passive"]){
        $pa=$m["paddr"]===""?ftpsv_t("ftpsv_public_auto"):ftpsv_t("ftpsv_public_addr",ftpsv_h($m["paddr"]));
        $nv=ftpsv_badge("ok","fas fa-check","{ftpsv_on}")." <span class='ftpsv-mono ftpsv-small'>".ftpsv_t("ftpsv_ports_range",(string)$m["pmin"],(string)$m["pmax"])." · $pa</span>";
    }else{
        $nv=ftpsv_badge("off","fas fa-minus","{ftpsv_off}");
    }
    if(ftpsv_meta_held("VsFTPDPassive") || ftpsv_meta_held("VsFTPDPassiveMinPort")){$nv=ftpsv_meta_tag()." ".$nv;}
    $rows[]=ftpsv_row("fas fa-shield","{ftpsv_nat}","{ftpsv_nat_sub}",$nv,"{ftpsv_nat_help}");
    $sv=$m["rate"]>0?ftpsv_t("ftpsv_kbs",number_format($m["rate"])):"{ftpsv_no_limit}";
    $sv="<strong>$sv</strong>";
    if(ftpsv_meta_held("VsFTPDLocalMaxRate")){$sv=ftpsv_meta_tag()." ".$sv;}
    $rows[]=ftpsv_row("fas fa-gauge","{ftpsv_speed}","",$sv,"{ftpsv_speed_help}");
    $pv="<span class='ftpsv-mono'>".ftpsv_t("ftpsv_perms_val",$m["perm_dirs"],$m["perm_files"])."</span>";
    if(ftpsv_meta_held("VsFTPDLocalUmask") || ftpsv_meta_held("VsFTPDFileOpenMode")){$pv=ftpsv_meta_tag()." ".$pv;}
    $rows[]=ftpsv_row("fas fa-file-shield","{ftpsv_perms}","",$pv,"{ftpsv_perms_help}");
    if($m["root"]){
        $rv=ftpsv_badge("warn","fas fa-triangle-exclamation","{ftpsv_on_notreco}");
    }else{
        $rv=ftpsv_badge("ok","fas fa-check","{ftpsv_off_reco}");
    }
    if(ftpsv_meta_held("ProFTPDRootLogin")){$rv=ftpsv_meta_tag()." ".$rv;}
    $rows[]=ftpsv_row("fas fa-user-shield","{ftpsv_root}","",$rv,"{ftpsv_root_help}");
    return "<div class='ftpsv-card'><div class='ftpsv-card-h'><h3><i class='fas fa-network-wired'></i> {ftpsv_connect_title}</h3>"
        ."<button type='button' class='ftpsv-btn' OnClick=\"Loadjs('$page?main=yes');\"><i class='fas fa-pen'></i> {ftpsv_edit}</button></div>"
        .implode("",$rows)."</div>";
}

function ftpsv_tls_card($m):string{
    $page=CurrentPageName();
    $js="Loadjs('$page?ssl=yes');";
    // Chiffrement entierement tenu par Meta : rien a changer ici.
    $tlsHeld=ftpsv_meta_held("ProFTPDUseTLS") && ftpsv_meta_held("ProFTPDCertificateName");
    $b=array();
    if($tlsHeld){
        $b[]="<div>".ftpsv_meta_tag()."</div>";
        $b[]="<p>{ftpsv_meta_tls_held}</p>";
        if($m["tls_active"]){
            $b[]="<div class='ftpsv-small'><i class='fas fa-certificate'></i> ".ftpsv_t("ftpsv_certificate","<span class='ftpsv-mono'>".ftpsv_h($m["cert"])."</span>")."</div>";
        }
        return "<div class='ftpsv-card'><div class='ftpsv-card-h'><h3><i class='fas fa-shield-halved'></i> {ftpsv_tls_title}</h3></div>"
            ."<div class='ftpsv-card-b'>".implode("",$b)."</div></div>";
    }
    if($m["tls_nocert"]){
        $b[]="<div class='ftpsv-alert'><i class='fas fa-circle-exclamation'></i><div>{ftpsv_tls_nocert}</div></div>";
        $b[]="<button type='button' class='ftpsv-btn ftpsv-btn-primary ftpsv-btn-block' OnClick=\"$js\"><i class='fas fa-certificate'></i> {ftpsv_tls_choose}</button>";
    }elseif($m["tls_active"]){
        $b[]=ftpsv_badge("ok","fas fa-shield-halved","{ftpsv_tls_on}");
        $b[]="<p>{ftpsv_tls_on_text}</p>";
        $b[]="<div class='ftpsv-small'><i class='fas fa-certificate'></i> ".ftpsv_t("ftpsv_certificate","<span class='ftpsv-mono'>".ftpsv_h($m["cert"])."</span>")."</div>";
    }else{
        $b[]=ftpsv_badge("warn","fas fa-triangle-exclamation","{ftpsv_tls_off}");
        $b[]="<p>{ftpsv_tls_off_text}</p>";
        $b[]="<button type='button' class='ftpsv-btn ftpsv-btn-primary ftpsv-btn-block' OnClick=\"$js\"><i class='fas fa-lock'></i> {ftpsv_tls_turn_on}</button>";
    }
    return "<div class='ftpsv-card'><div class='ftpsv-card-h'><h3><i class='fas fa-shield-halved'></i> {ftpsv_tls_title}</h3>"
        ."<button type='button' class='ftpsv-btn' OnClick=\"$js\"><i class='fas fa-sliders'></i> {ftpsv_change}</button></div>"
        ."<div class='ftpsv-card-b'>".implode("",$b)."</div></div>";
}

// Regles ecrites en dur par proftpd.BuildConf() : rien a lire, rien a regler.
function ftpsv_always_card():string{
    $li=array(
        array("fas fa-folder","{ftpsv_always_jail}"),
        array("fas fa-user-secret","{ftpsv_always_anon}"),
        array("fas fa-ban","{ftpsv_always_chmod}"),
        array("fas fa-users","{ftpsv_always_max}"),
    );
    $h=array();
    foreach($li as $l){$h[]="<li><i class='{$l[0]}'></i><span>{$l[1]}</span></li>";}
    return "<div class='ftpsv-card ftpsv-card-neutral'><div class='ftpsv-card-h'><h3><i class='fas fa-circle-info'></i> {ftpsv_always_title}</h3></div>"
        ."<ul class='ftpsv-list'>".implode("",$h)."</ul><div class='ftpsv-foot'>{ftpsv_always_foot}</div></div>";
}

// ── Modales ─────────────────────────────────────────────────────────────────

function ftpsv_main_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{ftpsv_conn_settings}","$page?main-popup=yes",620);
}
function ftpsv_ssl_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog2("{ftpsv_tls_title}","$page?ssl-popup=yes",620);
}

function ftpsv_after_save_js():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $restart=$tpl->framework_buildjs("/proftpd/restart","proftpd.progress","proftpd.progress.log","progress-proftpd-restart","FtpsvRefresh();");
    return "dialogInstance2.close();LoadAjax('table-loader-proftpd-service','$page?table-start=yes');$restart";
}

function ftpsv_main_popup(){
    $tpl=new template_admin();
    $m=ftpsv_model();
    $form=array();
    // Les cles tenues par Artica Meta sont affichees, jamais postees (refusees a l'enregistrement).
    $onoff=function($v){return ftpsv_t($v?"ftpsv_on":"ftpsv_off");};
    $form=array();
    $form[]=$tpl->field_hidden("ftpsv-save","1");
    $form[]=$tpl->field_interfaces_choose("ProFTPDInterfaces","{ftpsv_ifaces}",implode(",",$m["ifaces"]),"{ftpsv_f_ifaces_help}");
    if(ftpsv_meta_held("VsFTPDPassive")){
        $form[]=ftpsv_meta_field($tpl,"VsFTPDPassive","{ftpsv_nat}",$onoff($m["passive"]));
    }else{
        $form[]=$tpl->field_checkbox("VsFTPDPassive","{ftpsv_nat}",$m["passive"]?1:0,false,"{ftpsv_f_nat_help}");
    }
    if(ftpsv_meta_held("VsFTPDPassiveMinPort") || ftpsv_meta_held("VsFTPDPassiveMaxPort")){
        $form[]=ftpsv_meta_field($tpl,"VsFTPDPassiveMinPort","{ftpsv_f_first_port}",(string)$m["pmin"]);
        $form[]=ftpsv_meta_field($tpl,"VsFTPDPassiveMaxPort","{ftpsv_f_last_port}",(string)$m["pmax"]);
    }else{
        $form[]=$tpl->field_numeric("VsFTPDPassiveMinPort","{ftpsv_f_first_port}",$m["pmin"],"{ftpsv_f_ports_help}");
        $form[]=$tpl->field_numeric("VsFTPDPassiveMaxPort","{ftpsv_f_last_port}",$m["pmax"],"{ftpsv_f_ports_help}");
    }
    $form[]=$tpl->field_ipaddr("VsFTPDPassiveAddr","{ftpsv_f_public}",$m["paddr"],false,"{ftpsv_f_public_help}");
    if(ftpsv_meta_held("VsFTPDLocalMaxRate")){
        $form[]=ftpsv_meta_field($tpl,"VsFTPDLocalMaxRate","{ftpsv_f_speed}",(string)$m["rate"]);
    }else{
        $form[]=$tpl->field_numeric("VsFTPDLocalMaxRate","{ftpsv_f_speed}",$m["rate"],"{ftpsv_f_speed_help}");
    }
    if(ftpsv_meta_held("VsFTPDLocalUmask")){
        $form[]=ftpsv_meta_field($tpl,"VsFTPDLocalUmask","{ftpsv_f_dirs}",ftpsv_t(trim(ftpsv_umasks()[$m["umask"]],"{}")));
    }else{
        $form[]=$tpl->field_array_hash(ftpsv_umasks(),"VsFTPDLocalUmask","{ftpsv_f_dirs}",$m["umask"],true,"{ftpsv_f_dirs_help}");
    }
    if(ftpsv_meta_held("VsFTPDFileOpenMode")){
        $fm=ftpsv_filemodes();
        $form[]=ftpsv_meta_field($tpl,"VsFTPDFileOpenMode","{ftpsv_f_files}",isset($fm[$m["filemode"]])?ftpsv_t(trim($fm[$m["filemode"]],"{}")):$m["filemode"]);
    }else{
        $form[]=$tpl->field_array_hash(ftpsv_filemodes(),"VsFTPDFileOpenMode","{ftpsv_f_files}",$m["filemode"],true,"{ftpsv_f_files_help}");
    }
    if(ftpsv_meta_held("ProFTPDRootLogin")){
        $form[]=ftpsv_meta_field($tpl,"ProFTPDRootLogin","{ftpsv_root}",$onoff($m["root"]));
    }else{
        $form[]=$tpl->field_checkbox("ProFTPDRootLogin","{ftpsv_root}",$m["root"]?1:0,false,"{ftpsv_f_root_help}");
    }
    echo $tpl->form_outside("",implode("\n",$form),"{ftpsv_conn_explain}","{apply}",ftpsv_after_save_js(),"AsSystemAdministrator");
}

function ftpsv_ssl_popup(){
    $tpl=new template_admin();
    $m=ftpsv_model();
    if(ftpsv_meta_held("ProFTPDUseTLS") && ftpsv_meta_held("ProFTPDCertificateName")){
        echo $tpl->_ENGINE_parse_body($tpl->div_explain("{ftpsv_tls_title}||{ftpsv_meta_tls_held}"));
        return;
    }
    $form=array();
    $form[]=$tpl->field_hidden("ftpsv-save","1");
    if(ftpsv_meta_held("ProFTPDUseTLS")){
        $form[]=ftpsv_meta_field($tpl,"ProFTPDUseTLS","{ftpsv_f_tls}",ftpsv_t($m["tls"]?"ftpsv_on":"ftpsv_off"));
    }else{
        $form[]=$tpl->field_checkbox("ProFTPDUseTLS","{ftpsv_f_tls}",$m["tls"]?1:0,false,"{ftpsv_f_tls_help}");
    }
    if(ftpsv_meta_held("ProFTPDCertificateName")){
        $form[]=ftpsv_meta_field($tpl,"ProFTPDCertificateName","{certificate}",$m["cert"]);
    }else{
        $form[]=$tpl->field_certificate("ProFTPDCertificateName","{certificate}",$m["cert"],"{ftpsv_f_cert_help}");
    }
    echo $tpl->form_outside("",$form,"{ftpsv_tls_explain}","{apply}",ftpsv_after_save_js(),"AsSystemAdministrator");
}

// ── Ecritures ───────────────────────────────────────────────────────────────

// Seules ces cles peuvent etre ecrites (avant : tout $_POST partait en SET_INFO).
function ftpsv_settings_keys():array{
    return array("ProFTPDInterfaces","VsFTPDPassive","VsFTPDPassiveMinPort","VsFTPDPassiveMaxPort",
        "VsFTPDPassiveAddr","VsFTPDLocalMaxRate","VsFTPDLocalUmask","VsFTPDFileOpenMode","ProFTPDRootLogin",
        "ProFTPDUseTLS","ProFTPDCertificateName");
}

// Valide et normalise ; renvoie [valeurs, erreur]. Fonction pure (harnais).
function ftpsv_validate($post):array{
    $out=array();
    foreach(ftpsv_settings_keys() as $k){
        if(!array_key_exists($k,$post)){continue;}
        $out[$k]=trim((string)$post[$k]);
    }
    foreach(array("VsFTPDPassive","ProFTPDRootLogin","ProFTPDUseTLS") as $k){
        if(isset($out[$k])){$out[$k]=intval($out[$k])==1?"1":"0";}
    }
    if(isset($out["ProFTPDInterfaces"])){
        $nics=array();
        foreach(explode(",",$out["ProFTPDInterfaces"]) as $n){
            $n=trim($n);
            if($n===""){continue;}
            if(!preg_match('#^[A-Za-z0-9_.:@-]{1,32}$#',$n)){return array(array(),"{ftpsv_err_iface}");}
            $nics[]=$n;
        }
        $out["ProFTPDInterfaces"]=implode(",",$nics);
    }
    if(isset($out["VsFTPDPassiveMinPort"]) || isset($out["VsFTPDPassiveMaxPort"])){
        $a=intval(isset($out["VsFTPDPassiveMinPort"])?$out["VsFTPDPassiveMinPort"]:40000);
        $b=intval(isset($out["VsFTPDPassiveMaxPort"])?$out["VsFTPDPassiveMaxPort"]:40200);
        if($a<1024 || $b>65535 || $a>=$b){return array(array(),"{ftpsv_err_ports}");}
        if(isset($out["VsFTPDPassiveMinPort"])){$out["VsFTPDPassiveMinPort"]=(string)$a;}
        if(isset($out["VsFTPDPassiveMaxPort"])){$out["VsFTPDPassiveMaxPort"]=(string)$b;}
    }
    if(isset($out["VsFTPDPassiveAddr"]) && $out["VsFTPDPassiveAddr"]!==""){
        if(filter_var($out["VsFTPDPassiveAddr"],FILTER_VALIDATE_IP)===false){return array(array(),"{ftpsv_err_ip}");}
    }
    if(isset($out["VsFTPDLocalMaxRate"])){
        if(!preg_match('#^[0-9]{1,9}$#',$out["VsFTPDLocalMaxRate"])){return array(array(),"{ftpsv_err_rate}");}
        $out["VsFTPDLocalMaxRate"]=(string)intval($out["VsFTPDLocalMaxRate"]);
    }
    if(isset($out["VsFTPDLocalUmask"]) && !isset(ftpsv_umasks()[$out["VsFTPDLocalUmask"]])){return array(array(),"{ftpsv_err_perms}");}
    if(isset($out["VsFTPDFileOpenMode"]) && !isset(ftpsv_filemodes()[$out["VsFTPDFileOpenMode"]])){return array(array(),"{ftpsv_err_perms}");}
    if(isset($out["ProFTPDCertificateName"])){
        if(preg_match('#[\x00-\x1f<>"\']#',$out["ProFTPDCertificateName"]) || strlen($out["ProFTPDCertificateName"])>255){
            return array(array(),"{ftpsv_err_cert}");
        }
    }
    if(isset($out["ProFTPDUseTLS"]) && $out["ProFTPDUseTLS"]=="1"){
        $c=isset($out["ProFTPDCertificateName"])?$out["ProFTPDCertificateName"]:"";
        if(strlen($c)<3){return array(array(),"{ftpsv_err_cert}");}
    }
    return array($out,"");
}

// Une cle tenue par Artica Meta n'est jamais ecrite ici : la requete entiere est refusee
// (articarest refuse aussi ces cles sur /setinfo, 423).
function ftpsv_meta_refused($vals):array{
    $out=array();
    foreach(array_keys($vals) as $k){
        if(ftpsv_meta_held($k)){$out[]=$k;}
    }
    return $out;
}

function ftpsv_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $tpl->CLEAN_POST();
    list($vals,$err)=ftpsv_validate($_POST);
    if($err!==""){echo $tpl->post_error($err);return;}
    if(count(ftpsv_meta_refused($vals))>0){echo $tpl->post_error("{ftpsv_meta_key_refused}");return;}
    $changed=array();
    $failed=array();
    foreach($vals as $k=>$v){
        if(!$GLOBALS["CLASS_SOCKETS"]->SET_INFO($k,$v)){$failed[]=$k;continue;}
        $changed[]="$k=$v";
    }
    if(count($changed)>0){admin_tracks("FTP server settings saved: ".implode(", ",$changed));}
    if(count($failed)>0){echo $tpl->post_error("{ftpsv_save_refused} ".implode(", ",$failed));}
}

function ftpsv_uninstall_js(){
    $tpl=new template_admin();
    $meta=ftpsv_meta();
    if($meta["managed"]){$tpl->js_error("{ftp_meta_uninstall_refused}");return;}
    $restartjs=$tpl->framework_buildjs("/proftpd/uninstall","proftpd.progress","proftpd.progress.log","progress-proftpd-restart","window.location.href='/index'");
    $tpl->js_dialog_confirm_action("{ftpsv_uninstall_confirm}","ftpsv-uninstall","none",$restartjs);
}

// Le POST de confirmation : controle du droit, rien d'autre (vide = accepte).
function ftpsv_uninstall_check(){
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl=new template_admin();
        echo $tpl->post_error("{ERROR_NO_PRIVS2}");
        return;
    }
    $meta=ftpsv_meta();
    if($meta["managed"]){echo (new template_admin())->post_error("{ftp_meta_uninstall_refused}");return;}
    admin_tracks("FTP server: uninstall requested");
}

// ── Style et script ─────────────────────────────────────────────────────────

function ftpsv_js():string{
    $page=CurrentPageName();
    // Un seul minuteur ; rien quand une fenetre ou une SweetAlert est ouverte.
    return "
function FtpsvRefresh(){
    if(!document.getElementById('ftpsv-status')){return;}
    LoadAjax('table-loader-proftpd-service','$page?table-start=yes');
}
if(window.FtpsvTimer){clearInterval(window.FtpsvTimer);}
window.FtpsvTimer=setInterval(function(){
    var host=document.getElementById('ftpsv-status');
    if(!host){clearInterval(window.FtpsvTimer);window.FtpsvTimer=null;return;}
    if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.sweet-overlay[style*=\"block\"]')){return;}
    LoadAjaxSilent('ftpsv-status','$page?status=yes');
},15000);
";
}

function ftpsv_style():string{
    if(isset($GLOBALS["FTPSV_STYLE"])){return "";}
    $GLOBALS["FTPSV_STYLE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$line="rgba(16,185,129,.35)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.ftpsv{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;margin-top:8px}
.ftpsv>div:empty{display:none}
.ftpsv-hero{position:relative;display:flex;gap:18px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:22px 24px 20px 28px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.ftpsv-hero:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:$accent}
.ftpsv-hero-ico{flex:0 0 48px;height:48px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:20px}
.ftpsv-hero h3{margin:0 0 6px 0;font-size:18px;font-weight:600;color:#0f172a}
.ftpsv-hero p{margin:0;font-size:14px;line-height:1.6;color:#475569;max-width:900px}
.ftpsv-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.ftpsv-chip{display:inline-flex;align-items:center;gap:6px;border:1px solid $line;background:$soft;border-radius:999px;padding:3px 11px;font-size:12px;color:#334155}
.ftpsv-chip i,.ftpsv-chip svg{color:$accent}
.ftpsv-chip-warn{background:#fffbeb;border-color:#fcd34d;color:#92400e}
.ftpsv-chip-warn i,.ftpsv-chip-warn svg{color:#b45309}
.ftpsv-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.ftpsv-tile{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;box-shadow:0 1px 2px rgba(15,23,42,.06);min-width:0}
.ftpsv-tile-l{font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#64748b}
.ftpsv-tile-v{display:flex;align-items:center;gap:8px;margin-top:6px;font-size:20px;font-weight:600;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ftpsv-tile-s{margin-top:4px;font-size:13px;color:#64748b;overflow-wrap:anywhere}
.ftpsv-muted{color:#64748b;font-weight:500;font-size:16px}
.ftpsv-dot{flex:0 0 10px;width:10px;height:10px;border-radius:50%;background:#94a3b8}
.ftpsv-dot-ok{background:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.2)}
.ftpsv-dot-err{background:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.15)}
.ftpsv-link{color:$dark;text-decoration:underline;cursor:pointer}
.ftpsv-empty{display:flex;align-items:center;gap:16px;background:#fff;border:1px dashed $line;border-radius:12px;padding:18px 22px}
.ftpsv-empty-ico{flex:0 0 44px;height:44px;border-radius:50%;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:18px}
.ftpsv-empty-txt{flex:1;min-width:0}
.ftpsv-empty h4{margin:0 0 4px 0;font-size:15px;font-weight:600}
.ftpsv-empty p{margin:0;color:#475569;font-size:13px;line-height:1.55}
.ftpsv-cols{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:20px;align-items:start}
.ftpsv-col-side{display:flex;flex-direction:column;gap:20px;min-width:0}
.ftpsv-col-main{min-width:0}
.ftpsv-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 22px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.ftpsv-card-h{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:14px;border-bottom:1px solid #f1f5f9}
.ftpsv-card-h h3{margin:0;font-size:16px;font-weight:600;color:#0f172a;display:flex;align-items:center;gap:10px}
.ftpsv-card-h h3 i,.ftpsv-card-h h3 svg{color:$accent}
.ftpsv-card-neutral .ftpsv-card-h h3 i,.ftpsv-card-neutral .ftpsv-card-h h3 svg{color:#64748b}
.ftpsv-card-b{display:flex;flex-direction:column;gap:12px;padding-top:14px}
.ftpsv-card-b p{margin:0;font-size:13px;line-height:1.6;color:#475569}
.ftpsv-row{display:flex;gap:14px;padding:16px 0;border-bottom:1px solid #f1f5f9}
.ftpsv-row:last-child{border-bottom:0;padding-bottom:4px}
.ftpsv-row-ico{flex:0 0 34px;height:34px;border-radius:8px;background:#f1f5f9;color:#64748b;display:flex;align-items:center;justify-content:center}
.ftpsv-row-body{flex:1;min-width:0}
.ftpsv-row-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
.ftpsv-row-title{font-size:14px;font-weight:600;color:#0f172a}
.ftpsv-row-sub{font-size:12px;color:#94a3b8;font-family:monospace}
.ftpsv-row-val{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:6px;max-width:100%}
.ftpsv-row-help{margin-top:6px;font-size:13px;line-height:1.55;color:#64748b}
.ftpsv-tag{display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:2px 9px;font-size:12px;color:#334155}
.ftpsv-code{display:inline-block;background:#f1f5f9;border-radius:6px;padding:3px 10px;font-family:monospace;font-size:13px;color:#0f172a;overflow-wrap:anywhere}
.ftpsv-mono{font-family:monospace}
.ftpsv-small{font-size:12px;color:#475569}
.ftpsv-badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 11px;font-size:12px;font-weight:600;border:1px solid transparent;white-space:nowrap}
.ftpsv-badge-ok{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.ftpsv-badge-warn{background:#fffbeb;color:#b45309;border-color:#fcd34d}
.ftpsv-badge-off{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
.ftpsv-meta{display:inline-flex;align-items:center;gap:5px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:999px;padding:2px 9px;font-size:12px;font-weight:600;color:#1d4ed8;white-space:nowrap}
.ftpsv-meta i,.ftpsv-meta svg{color:#1d4ed8}
.ftpsv-meta-banner{display:flex;gap:12px;align-items:flex-start;background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;font-size:13px;line-height:1.6;color:#1e3a8a}
.ftpsv-meta-banner i,.ftpsv-meta-banner svg{color:#1d4ed8;margin-top:3px}
.ftpsv-alert{display:flex;gap:10px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:13px;color:#b91c1c}
.ftpsv-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:6px 14px;font-size:13px;font-weight:500;color:#334155;cursor:pointer;white-space:nowrap}
.ftpsv-btn:hover{background:#f8fafc}
.ftpsv-btn:focus-visible{outline:2px solid $accent;outline-offset:2px}
.ftpsv-btn-primary{background:$accent;border-color:$accent;color:#fff}
.ftpsv-btn-primary:hover{background:$dark;border-color:$dark}
.ftpsv-btn-block{width:100%;padding:10px 14px}
.ftpsv-list{list-style:none;margin:0;padding:14px 0 0 0;display:flex;flex-direction:column;gap:12px}
.ftpsv-list li{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:#334155;line-height:1.5}
.ftpsv-list li i,.ftpsv-list li svg{color:#94a3b8;margin-top:3px;width:16px}
.ftpsv-foot{margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;font-size:12px;color:#94a3b8}
@media (max-width:1200px){.ftpsv-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.ftpsv-cols{grid-template-columns:1fr}}
@media (prefers-reduced-motion:no-preference){.ftpsv-card,.ftpsv-tile,.ftpsv-hero{animation:ftpsvIn .15s ease-out}}
@keyframes ftpsvIn{from{opacity:0}to{opacity:1}}
</style>";
}
