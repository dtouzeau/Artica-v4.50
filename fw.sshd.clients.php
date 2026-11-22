<?php
// OpenSSH server » "Clients" tab (fw.sshd.php tabs): per-address SSH access exceptions.
// Table sshd_client (sshd.db): Ipaddr (IP or CIDR, key), PasswordAuthentication, PermitRootLogin, deny, enabled, metaid.
// What articarest writes (openssh/Configurator.go BuildMatchHost), one "Match Address <net>" block per enabled rule:
//   deny=1                 -> denied banner + every authentication method off
//   PasswordAuthentication -> "PasswordAuthentication yes" when 1, NOTHING when 0 (the global default applies)
//   PermitRootLogin        -> always written, yes or no (overrides the global default)
//   exact addresses are written before networks; every change runs /ssh/reconfigure.
// Global defaults: new openssh()->main_array (PasswordAuthentication, PermitRootLogin) and
// PasswordAuthenticationUsers (password exceptions, only used when password sign-in is off).
// Redesign 2026-11-18 (stitch_ssh_access_exceptions_console): widgets sshc_, CSS classes sshc-, tokens sshc_.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.openssh.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
// Politique Meta "SSH" (sshd) : les regles posees par Meta (sshd_client.metaid non vide) sont en lecture
// seule ici ; chaque ecriture le verifie cote serveur (sshd_meta_*). Les regles locales restent libres.
include_once(dirname(__FILE__)."/ressources/class.sshd.meta.inc");

if(isset($_POST["ipaddr"])){Save();exit;}
if(isset($_POST["sshc-enable"])){EnableRule();exit;}
if(isset($_POST["sshc-delete"])){DeleteRule();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["list"])){sshc_list();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["js"])){js();exit;}
start();

// Tab content: a host reloaded after every change (the search box lives inside it).
function start():bool{
    $page=CurrentPageName();
    echo "<div id='sshc-host'></div><script>"
        ."window.SshcRefresh=function(){LoadAjaxSilent('sshc-host','$page?list=yes');};"
        ."SshcRefresh();</script>";
    return true;
}

// Nombre de regles posees par Meta (0 sur une base sans colonne metaid).
function sshd_clients_meta_count($q):int{
    if(!sshd_meta_has_column($q,"sshd_client")){return 0;}
    $r=$q->QUERY_SQL_PARAMS("SELECT COUNT(*) AS n FROM sshd_client WHERE COALESCE(metaid,'')<>''",array());
    if(!is_array($r) || count($r)==0){return 0;}
    $first=reset($r);
    return intval($first["n"] ?? 0);
}

// Adresse IP/CIDR d'une regle (seuls ces caracteres existent dans une adresse).
function sshd_clients_ip($v):string{
    return preg_replace('#[^0-9A-Fa-f.:/]#','',strval($v));
}

// Ligne de sshd_client (tableau vide si absente). Requete parametree.
function sshd_clients_get($q,string $ipaddr):array{
    $r=$q->QUERY_SQL_PARAMS("SELECT * FROM sshd_client WHERE Ipaddr=? LIMIT 1",array($ipaddr));
    if(!is_array($r) || count($r)==0){return array();}
    $first=reset($r);
    return is_array($first)?$first:array();
}

function sshd_clients_is_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator || (bool)$users->AsDebianSystem;
}

// Fragment SQL constant (sans saisie) qui restreint une ecriture aux regles locales.
function sshd_clients_local_sql($q):string{
    return sshd_meta_has_column($q,"sshd_client")?" AND COALESCE(metaid,'')=''":"";
}

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ipaddr=sshd_clients_ip($_GET["js"]);
    $title=strlen($ipaddr)<3?"{sshc_new_title}":$ipaddr;
    return $tpl->js_dialog1($title,"$page?popup=".urlencode($ipaddr),640);
}

function popup():bool{
    $tpl=new template_admin();
    // Pentest 2026-11-04 : popup etait concatene dans la requete (adresse IP/CIDR seulement).
    $ipaddr=sshd_clients_ip($_GET["popup"]);
    $btn="{apply}";
    $ligne=array("PasswordAuthentication"=>1,"PermitRootLogin"=>0,"deny"=>0);
    $form=array();
    if(strlen($ipaddr)<3){
        $form[]=$tpl->field_hidden("new","yes");
        $form[]=$tpl->field_text("ipaddr","{sshc_f_address}","",true,"{sshc_f_address_help}");
        $btn="{add}";
    }else{
        $q=new lib_sqlite(SSHD_META_DB);
        $ligne=sshd_clients_get($q,$ipaddr);
        // Regle posee par une politique Meta "SSH" : lecture seule.
        if(sshd_meta_row($ligne)){
            echo sshd_meta_banner($tpl,true,"{sshd_meta_clients_explain}")
                .sshc_style().$tpl->_ENGINE_parse_body("<div class='sshc'>".sshc_card(sshc_rule($ligne),sshc_globals(),true)."</div>");
            return true;
        }
        $form[]=$tpl->field_hidden("ipaddr",$ipaddr);
    }
    $form[]=$tpl->field_checkbox("PasswordAuthentication","{sshc_f_password}",intval($ligne["PasswordAuthentication"] ?? 0),false,"{sshc_f_password_help}");
    $form[]=$tpl->field_checkbox("PermitRootLogin","{sshc_f_root}",intval($ligne["PermitRootLogin"] ?? 0),false,"{sshc_f_root_help}");
    $form[]=$tpl->field_checkbox("deny","{sshc_f_block}",intval($ligne["deny"] ?? 0),false,"{sshc_f_block_help}");
    $jsafter="if(window.SshcRefresh){SshcRefresh();}dialogInstance1.close();";
    $html=$tpl->form_outside($ipaddr,$form,"{sshc_dialog_intro}",$btn,$jsafter,"AsDebianSystem",false);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    if(!sshd_clients_is_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    // Pentest 2026-11-04 : ipaddr etait concatene dans la requete (adresse IP/CIDR seulement).
    $ipaddr=sshd_clients_ip($_POST["ipaddr"]);
    $Password=intval($_POST["PasswordAuthentication"] ?? 0)==1?1:0;
    $Root=intval($_POST["PermitRootLogin"] ?? 0)==1?1:0;
    $deny=intval($_POST["deny"] ?? 0)==1?1:0;
    $q=new lib_sqlite(SSHD_META_DB);
    // Regle posee par une politique Meta "SSH" (meme adresse) : ni modifiee ni remplacee ici.
    if(sshd_meta_client_is_meta($q,$ipaddr)){return sshd_meta_refuse_post("{sshd_meta_row_locked}");}
    if(isset($_POST["new"])){
        $IP=new IP();
        if(!$IP->IsACDIROrIsValid($ipaddr)){
            echo $tpl->post_error("{sshc_err_address}");
            return false;
        }
        if(count(sshd_clients_get($q,$ipaddr))>0){
            echo $tpl->post_error("{sshc_err_exists}");
            return false;
        }
        $q->QUERY_SQL_PARAMS("INSERT INTO sshd_client (Ipaddr,PasswordAuthentication,PermitRootLogin,deny) VALUES(?,?,?,?)",
            array($ipaddr,$Password,$Root,$deny));
    }else{
        $q->QUERY_SQL_PARAMS("UPDATE sshd_client SET PasswordAuthentication=?,PermitRootLogin=?,deny=? WHERE Ipaddr=?".sshd_clients_local_sql($q),
            array($Password,$Root,$deny,$ipaddr));
    }
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    return admin_tracks_post("Add/edit sshd client rule - $ipaddr");
}

// POST sshc-enable=<ip>: flips the rule. Echoes only an error.
function EnableRule():bool{
    $tpl=new template_admin();
    if(!sshd_clients_is_admin()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $ipaddr=sshd_clients_ip($_POST["sshc-enable"]);
    $q=new lib_sqlite(SSHD_META_DB);
    $ligne=sshd_clients_get($q,$ipaddr);
    if(count($ligne)==0){echo $tpl->_ENGINE_parse_body("{error}");return false;}
    // Regle posee par une politique Meta "SSH" : jamais modifiee ici.
    if(sshd_meta_row($ligne)){echo $tpl->_ENGINE_parse_body("{sshd_meta_row_locked}");return false;}
    $new=intval($ligne["enabled"] ?? 0)==1?0:1;
    $q->QUERY_SQL_PARAMS("UPDATE sshd_client SET enabled=? WHERE Ipaddr=?".sshd_clients_local_sql($q),array($new,$ipaddr));
    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    admin_tracks(($new==1?"Enable":"Disable")." sshd client rule - $ipaddr");
    return true;
}

// Confirmation that says the consequence before deleting.
function delete_js():bool{
    $tpl=new template_admin();
    $ipaddr=sshd_clients_ip($_GET["delete-js"]);
    $q=new lib_sqlite(SSHD_META_DB);
    if(sshd_meta_client_is_meta($q,$ipaddr)){return sshd_meta_refuse_js("{sshd_meta_row_locked}");}
    $t=str_replace("%S%","<strong>".htmlspecialchars($ipaddr,ENT_QUOTES)."</strong>",sshc_txt("sshc_delete_confirm"));
    return $tpl->js_confirm_delete($t,"sshc-delete",$ipaddr,"if(window.SshcRefresh){SshcRefresh();}",true);
}

// POST sshc-delete=<ip>. Echoes only an error.
function DeleteRule():bool{
    $tpl=new template_admin();
    if(!sshd_clients_is_admin()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    // Pentest 2026-11-04 : delete etait concatene dans la requete (adresse IP/CIDR seulement).
    $ipaddr=sshd_clients_ip($_POST["sshc-delete"]);
    $q=new lib_sqlite(SSHD_META_DB);
    // Regle posee par une politique Meta "SSH" : jamais supprimee ici.
    if(sshd_meta_client_is_meta($q,$ipaddr)){echo $tpl->_ENGINE_parse_body("{sshd_meta_row_locked}");return false;}
    $q->QUERY_SQL_PARAMS("DELETE FROM sshd_client WHERE Ipaddr=?".sshd_clients_local_sql($q),array($ipaddr));
    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ssh/reconfigure");
    admin_tracks("Delete sshd client rule - $ipaddr");
    return true;
}

// ---------------------------------------------------------------------------
// Widgets
// ---------------------------------------------------------------------------

// Translated plain text of a token, [br] turned into spaces, ready for str_replace of placeholders.
function sshc_txt(string $token):string{
    static $cache=array();
    if(isset($cache[$token])){return $cache[$token];}
    $tpl=new template_admin();
    $t=(string)$tpl->_ENGINE_parse_body("{".$token."}");
    $t=preg_replace('#\s*<br\s*/?>\s*#i'," ",$t);
    $cache[$token]=trim($t);
    return $cache[$token];
}

function sshc_e($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

// Global defaults that every address no rule matches follows.
function sshc_globals():array{
    $g=array("password"=>0,"root"=>0,"except"=>0,"unmanaged"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableSSHConfig"))==1);
    $s=new openssh();
    $c=is_array($s->main_array)?$s->main_array:array();
    $g["password"]=intval($c["PasswordAuthentication"] ?? 0)==1?1:0;
    $g["root"]=intval($c["PermitRootLogin"] ?? 0)==1?1:0;
    // Password exceptions of the Settings tab: only used by the daemon when password sign-in is off.
    $exc=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PasswordAuthenticationUsers"));
    if($g["password"]==0 && $exc!==""){
        foreach(preg_split('/[\s,;]+/',$exc) as $e){if(trim($e)!==""){$g["except"]++;}}
    }
    return $g;
}

// One row of sshd_client, normalised.
function sshc_rule(array $l):array{
    $ip=(string)($l["Ipaddr"] ?? "");
    return array(
        "ip"=>$ip,
        "password"=>intval($l["PasswordAuthentication"] ?? 0)==1,
        "root"=>intval($l["PermitRootLogin"] ?? 0)==1,
        "deny"=>intval($l["deny"] ?? 0)==1,
        "enabled"=>intval($l["enabled"] ?? 0)==1,
        "meta"=>sshd_meta_row($l),
    );
}

function sshc_model():array{
    $m=array("error"=>"","rules"=>array(),"meta_banner"=>false);
    $q=new lib_sqlite(SSHD_META_DB);
    $rows=$q->QUERY_SQL_PARAMS("SELECT * FROM sshd_client ORDER BY Ipaddr",array());
    if(!$q->ok){$m["error"]=(string)$q->mysql_error;$rows=array();}
    if(!is_array($rows)){$rows=array();}
    foreach($rows as $l){if(is_array($l)){$m["rules"][]=sshc_rule($l);}}
    // Exact addresses first, then networks: the order in which the daemon writes them.
    usort($m["rules"],function($a,$b){
        $na=strpos($a["ip"],"/")!==false?1:0;$nb=strpos($b["ip"],"/")!==false?1:0;
        if($na!=$nb){return $na-$nb;}
        return strnatcmp($a["ip"],$b["ip"]);
    });
    $m["globals"]=sshc_globals();
    $m["admin"]=sshd_clients_is_admin();
    $m["meta_banner"]=sshd_meta_clients_held() || sshd_clients_meta_count($q)>0;
    return $m;
}

// "One computer" / "Network of 256 addresses", from the CIDR.
function sshc_scope(string $ip):string{
    if(strpos($ip,"/")===false){return sshc_txt("sshc_one_computer");}
    list($addr,$bits)=explode("/",$ip,2);
    $bits=intval($bits);
    $v6=strpos($addr,":")!==false;
    $host=($v6?128:32)-$bits;
    if($host<=0){return sshc_txt("sshc_one_computer");}
    if($host>=63){return sshc_txt("sshc_net_huge");}
    $n=number_format(pow(2,$host),0,".",",");
    return str_replace("%N%",$n,sshc_txt("sshc_net_n"));
}

// Network open to the whole internet.
function sshc_is_any(string $ip):bool{
    return in_array($ip,array("0.0.0.0/0","::/0","0.0.0.0"),true);
}

function sshc_style():string{
    if(isset($GLOBALS["SSHC_STYLE_DONE"])){return "";}
    $GLOBALS["SSHC_STYLE_DONE"]=true;
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$soft=$a["accent_soft"];$dark=$a["accent_dark"];
    }
    $c=array();
    $c[]=".sshc{display:flex;flex-direction:column;gap:16px;padding:10px 2px 20px;color:#0f172a;font-size:14px}";
    $c[]=".sshc .sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap}";
    $c[]=".sshc-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".sshc-ban{display:flex;gap:20px;padding:24px 28px;border-left:4px solid $accent}";
    $c[]=".sshc-ban-ico{flex:0 0 48px;height:48px;border-radius:10px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:22px}";
    $c[]=".sshc-ban h2{margin:0 0 6px;font-size:20px;font-weight:600;color:#0f172a}";
    $c[]=".sshc-ban p{margin:0;line-height:1.6;color:#475569;max-width:1100px}";
    $c[]=".sshc-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}";
    $c[]=".sshc-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f1f5f9;border:1px solid #e2e8f0;color:#334155;font-size:13px}";
    $c[]=".sshc-def{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;padding:14px 20px;border:2px dashed #cbd5e1;box-shadow:none}";
    $c[]=".sshc-def-l{font-size:13px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#334155}";
    $c[]=".sshc-def-x{font-size:13px;color:#475569}";
    $c[]=".sshc-def a{margin-left:auto;color:$dark;font-weight:600;cursor:pointer;white-space:nowrap}";
    $c[]=".sshc-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;border:1px solid;font-size:13px;font-weight:500;white-space:nowrap}";
    $c[]=".sshc-pill b{font-weight:600}";
    $c[]=".sshc-pill.ok{background:#ecfdf5;border-color:#a7f3d0;color:#047857}";
    $c[]=".sshc-pill.no{background:#f1f5f9;border-color:#e2e8f0;color:#334155}";
    $c[]=".sshc-pill.dflt{background:#f8fafc;border-color:#e2e8f0;color:#64748b}";
    $c[]=".sshc-pill.warn{background:#fffbeb;border-color:#fde68a;color:#b45309}";
    $c[]=".sshc-pill.bad{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
    $c[]=".sshc-pill.meta{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}";
    $c[]=".sshc-pill.off{background:#f1f5f9;border-color:#cbd5e1;color:#475569}";
    $c[]=".sshc-bar{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:12px}";
    $c[]=".sshc-sum{display:inline-flex;align-items:center;gap:8px;padding-top:10px;font-weight:600;color:#334155}";
    $c[]=".sshc-dot{width:8px;height:8px;border-radius:50%;background:$accent}";
    $c[]=".sshc-search{position:relative;flex:0 1 420px}";
    $c[]=".sshc-search>svg,.sshc-search>i{position:absolute;left:14px;top:13px;color:#94a3b8}";
    $c[]=".sshc-search input{width:100%;height:42px;padding:0 14px 0 40px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;background:#fff;color:#0f172a}";
    $c[]=".sshc-search input:focus{outline:2px solid $accent;outline-offset:1px;border-color:$accent}";
    $c[]=".sshc-help{margin-top:6px;font-size:13px;color:#64748b}";
    $c[]=".sshc-list{display:flex;flex-direction:column;gap:12px}";
    $c[]=".sshc-rule{display:flex;align-items:flex-start;gap:16px;padding:16px 20px}";
    $c[]=".sshc-rule.off{background:#fbfcfd}.sshc-rule.off .sshc-addr,.sshc-rule.off .sshc-mid{opacity:.6}";
    $c[]=".sshc-ico{flex:0 0 44px;height:44px;border-radius:10px;background:#e6eeed;color:#334155;display:flex;align-items:center;justify-content:center;font-size:18px}";
    $c[]=".sshc-ico.bad{background:#fef2f2;color:#dc2626}";
    $c[]=".sshc-id{flex:0 0 300px;min-width:0}";
    $c[]=".sshc-addr{display:flex;flex-wrap:wrap;align-items:center;gap:8px}";
    $c[]=".sshc-addr button{padding:0;border:0;background:none;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:16px;font-weight:600;color:#0f172a;cursor:pointer;overflow-wrap:anywhere;text-align:left}";
    $c[]=".sshc-addr button:hover{text-decoration:underline}.sshc-addr button:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".sshc-scope{font-size:13px;color:#64748b;margin-top:2px}";
    $c[]=".sshc-mid{flex:1 1 auto;min-width:0}";
    $c[]=".sshc-pills{display:flex;flex-wrap:wrap;gap:8px}";
    $c[]=".sshc-risk{display:flex;align-items:flex-start;gap:8px;margin-top:8px;font-size:13px;color:#b45309}";
    $c[]=".sshc-risk.bad{color:#b91c1c;font-weight:600}";
    $c[]=".sshc-acts{flex:0 0 auto;display:flex;align-items:center;gap:6px}";
    $c[]=".sshc-sw{display:inline-flex;align-items:center;gap:10px;padding:0;border:0;background:none;cursor:pointer;font-size:13px;color:#475569}";
    $c[]=".sshc-sw .t{position:relative;width:44px;height:24px;border-radius:999px;background:#cbd5e1;transition:background .15s}";
    $c[]=".sshc-sw .t:after{content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}";
    $c[]=".sshc-sw[aria-checked=true] .t{background:$accent}.sshc-sw[aria-checked=true] .t:after{left:23px}";
    $c[]=".sshc-sw:disabled{cursor:not-allowed;opacity:.45}";
    $c[]=".sshc-sw:focus-visible{outline:2px solid $accent;outline-offset:3px;border-radius:999px}";
    $c[]=".sshc-act{width:36px;height:36px;border-radius:8px;border:1px solid transparent;background:none;color:#475569;display:inline-flex;align-items:center;justify-content:center;cursor:pointer}";
    $c[]=".sshc-act:hover{background:#f1f5f9;border-color:#e2e8f0;color:#0f172a}.sshc-act.danger:hover{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
    $c[]=".sshc-act:disabled{opacity:.35;cursor:not-allowed;background:none;border-color:transparent}";
    $c[]=".sshc-act:focus-visible{outline:2px solid $accent;outline-offset:1px}";
    $c[]=".sshc-empty{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding:40px 24px}";
    $c[]=".sshc-empty-i{width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;background:$soft;color:$dark;margin-bottom:6px}";
    $c[]=".sshc-empty h3{margin:0;font-size:18px;font-weight:600}.sshc-empty p{margin:0;max-width:640px;color:#475569;line-height:1.6}";
    $c[]=".sshc-btn{margin-top:10px;display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:8px;border:1px solid $accent;background:$accent;color:#fff;font-weight:600;font-size:14px;cursor:pointer}";
    $c[]=".sshc-btn:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".sshc-hidden{display:none !important}";
    $c[]="@media (max-width:1200px){.sshc-rule{flex-wrap:wrap}.sshc-id{flex:1 1 220px}.sshc-acts{margin-left:60px}}";
    $c[]="@media (prefers-reduced-motion:reduce){.sshc *{transition:none !important}}";
    return "<style>".implode("\n",$c)."</style>";
}

// 1. Value banner.
function sshc_banner():string{
    $chips=array(array("fa-bolt","{sshc_chip_now}"),array("fa-crosshairs","{sshc_chip_exact}"),
        array("fa-ban","{sshc_chip_denied}"),array("fa-key","{sshc_chip_keys}"));
    $h="";
    foreach($chips as $c){$h.="<span class='sshc-chip'><i class='fas {$c[0]}' aria-hidden='true'></i>{$c[1]}</span>";}
    return "<section class='sshc-card sshc-ban'><div class='sshc-ban-ico'><i class='fas fa-shield-halved' aria-hidden='true'></i></div>"
        ."<div><h2>{sshc_banner_title}</h2><p>{sshc_banner_text}</p><div class='sshc-chips'>$h</div></div></section>";
}

// 2. Default for everyone else: the global values each exception adjusts.
function sshc_defaults(array $g):string{
    $pw=$g["password"]==1
        ?"<span class='sshc-pill warn'><i class='fas fa-key' aria-hidden='true'></i>{sshc_pw_label} <b>{sshc_allowed}</b></span>"
        :"<span class='sshc-pill no'><i class='fas fa-key' aria-hidden='true'></i>{sshc_pw_label} <b>{sshc_not_allowed}</b></span>";
    $root=$g["root"]==1
        ?"<span class='sshc-pill bad'><i class='fas fa-user-shield' aria-hidden='true'></i>{sshc_root_label} <b>{sshc_allowed}</b></span>"
        :"<span class='sshc-pill no'><i class='fas fa-user-shield' aria-hidden='true'></i>{sshc_root_label} <b>{sshc_not_allowed}</b></span>";
    $exc="";
    if($g["except"]>0){
        $exc="<span class='sshc-def-x'>".str_replace("%N%",(string)$g["except"],sshc_txt($g["except"]==1?"sshc_except_1":"sshc_except_n"))."</span>";
    }
    return "<div class='sshc-card sshc-def'><span class='sshc-def-l'>{sshc_default_label}</span>$pw$root$exc"
        ."<a role='button' tabindex='0' OnClick=\"Loadjs('fw.sshd.php?settings-js=yes&section=auth')\">{sshc_change_settings} <i class='fas fa-chevron-right' aria-hidden='true'></i></a></div>";
}

// 3. Summary line + search.
function sshc_bar(array $rules):string{
    $n=count($rules);$on=0;$off=0;$deny=0;
    foreach($rules as $r){
        if($r["enabled"]){$on++;}else{$off++;}
        if($r["deny"] && $r["enabled"]){$deny++;}
    }
    $t=str_replace(array("%N%","%A%","%B%","%C%"),array((string)$n,(string)$on,(string)$off,(string)$deny),
        sshc_txt($n==1?"sshc_sum_1":"sshc_sum_n"));
    $ph=htmlspecialchars(sshc_txt("sshc_search_ph"),ENT_QUOTES,"UTF-8",false);
    return "<div class='sshc-bar'><span class='sshc-sum'><span class='sshc-dot' aria-hidden='true'></span>$t</span>"
        ."<div class='sshc-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='sshc-q' placeholder=\"$ph\" aria-label=\"$ph\" autocomplete='off'><div class='sshc-help'>{sshc_search_help}</div></div></div>";
}

// The rule as pills. A password not granted means the global default applies (the daemon writes nothing).
function sshc_pills(array $r,array $g):string{
    if($r["deny"]){
        return "<span class='sshc-pill bad'><i class='fas fa-circle-minus' aria-hidden='true'></i><b>{sshc_blocked}</b></span>";
    }
    if($r["password"]){
        $pw="<span class='sshc-pill ok'><i class='fas fa-key' aria-hidden='true'></i>{sshc_pw_label} <b>{sshc_allowed}</b></span>";
    }else{
        $d=$g["password"]==1?"sshc_default_allowed":"sshc_default_not";
        $pw="<span class='sshc-pill dflt' title=\"".htmlspecialchars(sshc_txt("sshc_default_tip"),ENT_QUOTES,"UTF-8",false)."\"><i class='fas fa-key' aria-hidden='true'></i>{sshc_pw_label} <b>{".$d."}</b></span>";
    }
    $root=$r["root"]
        ?"<span class='sshc-pill bad'><i class='fas fa-user-shield' aria-hidden='true'></i>{sshc_root_label} <b>{sshc_allowed}</b></span>"
        :"<span class='sshc-pill no'><i class='fas fa-user-shield' aria-hidden='true'></i>{sshc_root_label} <b>{sshc_not_allowed}</b></span>";
    return $pw.$root;
}

// Risks said before they bite: root allowed, or a rule that covers the whole internet.
function sshc_risks(array $r):string{
    if($r["deny"]){return "";}
    $h="";
    if(sshc_is_any($r["ip"]) && ($r["root"] || $r["password"])){
        $h.="<div class='sshc-risk bad'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>{sshc_risk_any}</span></div>";
    }elseif($r["root"]){
        $h.="<div class='sshc-risk'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>{sshc_risk_root}</span></div>";
    }
    return $h;
}

function sshc_actions(array $r,array $m):string{
    $ip=$r["ip"];
    $ipJs=htmlspecialchars(json_encode($ip),ENT_QUOTES,"UTF-8");
    $page=CurrentPageName();
    $lock=$r["meta"] || !$m["admin"];
    $tip=$r["meta"]?" title=\"".htmlspecialchars(sshc_txt("sshc_locked_tip"),ENT_QUOTES,"UTF-8",false)."\"":"";
    $aria=function($tok)use($ip){return htmlspecialchars(str_replace("%S%",$ip,sshc_txt($tok)),ENT_QUOTES,"UTF-8");};
    $on=$r["enabled"]?"true":"false";
    $h="<div class='sshc-acts'>";
    $h.="<button type='button' class='sshc-sw' role='switch' aria-checked='$on' aria-label=\"".$aria("sshc_aria_active")."\"$tip"
        .($lock?" disabled":" OnClick=\"SshcToggle($ipJs)\"")."><span>{sshc_rule_active}</span><span class='t' aria-hidden='true'></span></button>";
    $h.="<button type='button' class='sshc-act' aria-label=\"".$aria("sshc_aria_edit")."\" title=\"{sshc_edit}\" OnClick=\"Loadjs('$page?js='+encodeURIComponent($ipJs))\"><i class='fas fa-pen' aria-hidden='true'></i></button>";
    $h.="<button type='button' class='sshc-act danger' aria-label=\"".$aria("sshc_aria_delete")."\" title=\"{sshc_delete}\"$tip"
        .($lock?" disabled":" OnClick=\"Loadjs('$page?delete-js='+encodeURIComponent($ipJs))\"")."><i class='fas fa-trash' aria-hidden='true'></i></button>";
    return $h."</div>";
}

// 4. One exception card. $readonly: shown in the dialog of a Meta rule (no actions).
function sshc_card(array $r,array $g,bool $readonly=false,array $m=array()):string{
    $ip=sshc_e($r["ip"]);
    $page=CurrentPageName();
    $net=strpos($r["ip"],"/")!==false;
    $ico=$r["deny"]?"<span class='sshc-ico bad'><i class='fas fa-ban' aria-hidden='true'></i></span>"
        :"<span class='sshc-ico'><i class='fas ".($net?"fa-network-wired":"fa-desktop")."' aria-hidden='true'></i></span>";
    $name=$readonly?"<span style='font-family:ui-monospace,Menlo,Consolas,monospace;font-size:16px;font-weight:600'>$ip</span>"
        :"<button type='button' OnClick=\"Loadjs('$page?js='+encodeURIComponent(".htmlspecialchars(json_encode($r["ip"]),ENT_QUOTES,"UTF-8")."))\">$ip</button>";
    $badges="";
    if($r["meta"]){$badges.="<span class='sshc-pill meta'><i class='fas fa-lock' aria-hidden='true'></i>{sshc_meta_badge}</span>";}
    if(!$r["enabled"]){$badges.="<span class='sshc-pill off'><i class='fas fa-pause' aria-hidden='true'></i>{sshc_off}</span>";}
    $cls=$r["enabled"]?"":" off";
    $data=htmlspecialchars(strtolower($r["ip"]),ENT_QUOTES,"UTF-8");
    $acts=$readonly?"":sshc_actions($r,$m);
    return "<div class='sshc-card sshc-rule$cls' data-ip=\"$data\">$ico"
        ."<div class='sshc-id'><div class='sshc-addr'>$name$badges</div><div class='sshc-scope'>".sshc_e(sshc_scope($r["ip"]))."</div></div>"
        ."<div class='sshc-mid'><div class='sshc-pills'>".sshc_pills($r,$g)."</div>".sshc_risks($r)."</div>$acts</div>";
}

// 5. Empty state.
function sshc_empty(array $m):string{
    $btn=(!$m["globals"]["unmanaged"] && $m["admin"])
        ?"<button type='button' class='sshc-btn' OnClick=\"Loadjs('".CurrentPageName()."?js=')\"><i class='fas fa-plus' aria-hidden='true'></i>{sshc_new_rule}</button>":"";
    return "<div class='sshc-card sshc-empty'><div class='sshc-empty-i'><i class='fas fa-shield-halved' aria-hidden='true'></i></div>"
        ."<h3>{sshc_empty_title}</h3><p>{sshc_empty_text}</p>$btn</div>";
}

function sshc_js(string $page):string{
    return "<script>
(function(){
  var q=document.getElementById('sshc-q');
  function apply(){
    var s=q?q.value.trim().toLowerCase():'';var cards=document.querySelectorAll('.sshc-rule[data-ip]');var shown=0;
    for(var i=0;i<cards.length;i++){var ok=s===''||cards[i].getAttribute('data-ip').indexOf(s)>-1;
      if(ok){cards[i].classList.remove('sshc-hidden');shown++;}else{cards[i].classList.add('sshc-hidden');}}
    var nm=document.getElementById('sshc-nomatch');if(nm){if(cards.length>0&&shown===0){nm.classList.remove('sshc-hidden');}else{nm.classList.add('sshc-hidden');}}
  }
  if(q){q.oninput=apply;}
  window.SshcToggle=function(ip){
    var XHR=new XHRConnection();
    XHR.appendData('sshc-enable',ip);
    XHR.sendAndLoad('$page','POST',function(obj){
      var r=String(obj.responseText||'').trim();
      if(r.length>0){swal({title:'',text:r,type:'warning'});}
      if(window.SshcRefresh){SshcRefresh();}
    });
  };
})();
</script>";
}

// Responder of the host.
function sshc_list():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=sshc_model();
    $h=array("<div class='sshc'>");
    if($m["meta_banner"]){$h[]=sshd_meta_banner($tpl,true,"{sshd_meta_clients_explain}");}
    $h[]=sshc_banner();
    $h[]=sshc_defaults($m["globals"]);
    if($m["error"]!==""){
        $h[]="<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span class='ak-banner-m'>{sshc_err_read}<br>".sshc_e($m["error"])."</span></div>";
    }elseif(count($m["rules"])==0){
        $h[]=sshc_empty($m);
    }else{
        $h[]=sshc_bar($m["rules"]);
        $h[]="<div class='sshc-list'>";
        foreach($m["rules"] as $r){$h[]=sshc_card($r,$m["globals"],false,$m);}
        $h[]="<div class='sshc-card sshc-empty sshc-hidden' id='sshc-nomatch'><h3>{sshc_nomatch}</h3></div>";
        $h[]="</div>";
    }
    $h[]="</div>";

    $topbuttons=array();
    if(!$m["globals"]["unmanaged"] && $m["admin"]){
        $topbuttons[]=array("Loadjs('$page?js=')",ico_plus,"{sshc_new_rule}");
    }
    $OPENSSH_VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENSSH_VER");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_OPENSSH} v$OPENSSH_VER";
    $TINY_ARRAY["ICO"]=ico_terminal;
    $TINY_ARRAY["EXPL"]="{OPENSSH_EXPLAIN}";
    $TINY_ARRAY["URL"]="sshd";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo sshc_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).sshc_js($page)."<script>NoSpinner();$jstiny</script>";
    return true;
}
