<?php
// stamp 01/11/2026
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.rdpproxy.inc");
include_once(dirname(__FILE__)."/ressources/class.rdpproxy.meta.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_POST["AuthorizeTSElogin"])){SaveSettings();exit;}
if(isset($_POST["RDPProxyVideoRententionDays"])){video_params_save();exit;}
if(isset($_POST["RDPProxyVideoPreset"])){video_params_save();exit;}
// Onglet de l'authentificateur : seule la cle lue par authhook (IsRDPProxyAuthDebug). Le formulaire
// d'ecoute envoie aussi RDPProxyPort et reste traite par SaveSettings() ci-dessous.
// Sous verrou Meta, le formulaire d'ecoute n'envoie plus RDPProxyPort (lecture seule) : RDPProxyListen le route.
if(isset($_POST["IsRDPProxyAuthDebug"]) && !isset($_POST["RDPProxyPort"]) && !isset($_POST["RDPProxyListen"])){authhook_debug_save();exit;}
if(isset($_POST["RDPProxyPort"])){SaveSettings();exit;}
// Personnalisation du skin (couleurs, fond, logo, texte d'accueil) : licence Artica requise ;
// le daemon l'ignore aussi sans licence (RDPProxy/theme.go skinLicensed).
if(isset($_POST["RDPProxyTheme"])){skin_license_guard();SaveSettings();exit;}
if(isset($_POST["RDPProxyBackGroundColor"])){skin_license_guard();SaveSettings();exit;}
if(isset($_POST["RDPProxyEditBgcolor"])){skin_license_guard();SaveSettings();exit;}
if(isset($_POST["RDPProxySelectorLine1Bgcolor"])){skin_license_guard();SaveSettings();exit;}
if(isset($_GET["authook"])){authook();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["settings"])){settings_flat();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["about"])){about();exit;}
if(isset($_GET["webview"])){webview_tabs();exit;}
if(isset($_GET["webview-status"])){webview_status();exit;}
if(isset($_GET["webview-params"])){webview_params();exit;}
if(isset($_GET["webview-params-host"])){webview_params_host();exit;}
// Sessions Web gardees apres une deconnexion (onglet WebView, routes /rdpproxy/webview/parked et /keep)
if(isset($_GET["webview-keep"])){webview_keep_start();exit;}
if(isset($_GET["webview-keep-table"])){webview_keep_table();exit;}
if(isset($_GET["webview-keep-js"])){webview_keep_js();exit;}
if(isset($_GET["webview-keep-popup"])){webview_keep_popup();exit;}
if(isset($_POST["RDPWebKeepSave"])){webview_keep_save();exit;}
if(isset($_POST["RDPWebNoticeSave"])){theme_notice_save();exit;}
if(isset($_GET["webview-parked-close-js"])){webview_parked_close_js();exit;}
if(isset($_POST["RDPWebParkedClose"])){webview_parked_close_save();exit;}
if(isset($_GET["webview-trust"])){webview_trust_start();exit;}
if(isset($_GET["webview-trust-table"])){webview_trust_table();exit;}
if(isset($_GET["webview-trust-add-js"])){webview_trust_add_js();exit;}
if(isset($_GET["webview-trust-add-popup"])){webview_trust_add_popup();exit;}
if(isset($_POST["RDPWebTrustAdd"])){webview_trust_add_save();exit;}
if(isset($_GET["webview-trust-remove-js"])){webview_trust_remove_js();exit;}
if(isset($_POST["RDPWebTrustRemove"])){webview_trust_remove_save();exit;}
if(isset($_GET["webview-trust-edit-js"])){webview_trust_edit_js();exit;}
if(isset($_GET["webview-trust-edit-popup"])){webview_trust_edit_popup();exit;}
if(isset($_POST["RDPWebTrustEdit"])){webview_trust_edit_save();exit;}
if(isset($_GET["video"])){video_params();exit;}
if(isset($_GET["video-flat"])){video_flat();exit;}
if(isset($_GET["video-storage-js"])){video_storage_js();exit;}
if(isset($_GET["video-storage-popup"])){video_storage_popup();exit;}
if(isset($_GET["video-quality-js"])){video_quality_js();exit;}
if(isset($_GET["video-quality-popup"])){video_quality_popup();exit;}
if(isset($_GET["theme"])){theme_start();exit;}
if(isset($_GET["rdpproxy-theme"])){theme();exit;}
if(isset($_GET["theme-apply-js"])){theme_apply_js();exit;}
if(isset($_GET["theme-general-js"])){theme_general_js();exit;}
if(isset($_GET["theme-general-popup"])){theme_general_popup();exit;}
if(isset($_GET["theme-screen-js"])){theme_screen_js();exit;}
if(isset($_GET["theme-screen-popup"])){theme_screen_popup();exit;}
if(isset($_GET["theme-fields-js"])){theme_fields_js();exit;}
if(isset($_GET["theme-fields-popup"])){theme_fields_popup();exit;}
if(isset($_GET["theme-selector-js"])){theme_selector_js();exit;}
if(isset($_GET["theme-notice-js"])){theme_notice_js();exit;}
if(isset($_GET["theme-notice-popup"])){theme_notice_popup();exit;}
if(isset($_GET["theme-selector-popup"])){theme_selector_popup();exit;}
if(isset($_GET["charter-js"])){charter_js();exit;}
if(isset($_GET["welcome-js"])){welcome_js();exit;}
if(isset($_GET["welcome-popup"])){welcome_popup();exit;}
if(isset($_POST["RDPProxyWelcomeSave"])){welcome_save();exit;}
if(isset($_GET["charter-popup"])){charter_popup();exit;}
if(isset($_POST["RDPProxyCharterSave"])){charter_save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["rdpproxy-top"])){status_top();exit;}

if(isset($_GET["errmsg-js"])){errmsg_js();exit;}
if(isset($_GET["errmsg-popup"])){errmsg_popup();exit;}
if(isset($_POST["RDP_ERROR_MSG1"])){SaveSettings();exit;}
if(isset($_GET["listen-js"])){listen_js();exit;}
if(isset($_GET["listen-popup"])){listen_popup();exit;}
if(isset($_POST["RDPProxyListen"])){SaveSettings();exit;}

if(isset($_GET["dh-js"])){dh_js();exit;}
if(isset($_GET["dh-popup"])){dh_popup();exit;}

if(isset($_GET["timeouts-js"])){timeouts_js();exit;}
if(isset($_GET["timeouts-popup"])){timeouts_popup();exit;}

if(isset($_GET["rdp-js"])){rdp_js();exit;}
if(isset($_GET["rdp-popup"])){rdp_popup();exit;}


if(isset($_GET["tls-js"])){tls_js();exit;}
if(isset($_GET["tls-popup"])){tls_popup();exit;}
if(isset($_GET["target-ciphers-js"])){target_ciphers_js();exit;}
if(isset($_GET["tls-policies-js"])){tls_policies_js();exit;}
if(isset($_GET["tls-policies-popup"])){tls_policies_popup();exit;}
if(isset($_GET["tls-policies-list"])){tls_policies_list();exit;}
if(isset($_GET["tls-policy-js"])){tls_policy_js();exit;}
if(isset($_GET["tls-policy-popup"])){tls_policy_popup();exit;}
if(isset($_GET["tls-policy-delete-js"])){tls_policy_delete_js();exit;}
if(isset($_POST["RDPProxyTLSPolicySave"])){tls_policy_save();exit;}
if(isset($_POST["tls-policy-delete"])){tls_policy_delete();exit;}
// Carte "NLA authentication (Kerberos)" (routes /rdpproxy/nla*).
if(isset($_GET["nla-join-js"])){nla_join_js();exit;}
if(isset($_GET["nla-join-popup"])){nla_join_popup();exit;}
if(isset($_POST["nla-join-save"])){nla_join_save();exit;}
if(isset($_GET["nla-leave-js"])){nla_leave_js();exit;}
if(isset($_GET["nla-leave-popup"])){nla_leave_popup();exit;}
if(isset($_POST["nla-leave"])){nla_leave_save();exit;}
if(isset($_GET["nla-settings-js"])){nla_settings_js();exit;}
if(isset($_GET["nla-settings-popup"])){nla_settings_popup();exit;}
if(isset($_POST["nla-settings-save"])){nla_settings_save();exit;}
if(isset($_GET["nla-renew-js"])){nla_renew_js();exit;}
if(isset($_POST["nla-renew"])){nla_renew_save();exit;}
if(isset($_GET["nla-checks"])){nla_checks();exit;}
if(isset($_GET["target-ciphers-popup"])){target_ciphers_popup();exit;}
if(isset($_GET["target-ciphers-default-js"])){target_ciphers_default_js();exit;}
if(isset($_POST["RDPProxyTargetCiphersSave"])){target_ciphers_save();exit;}
if(isset($_GET["ssh-key-js"])){ssh_key_js();exit;}
if(isset($_GET["ssh-key-popup"])){ssh_key_popup();exit;}
if(isset($_GET["ssh-key-card"])){ssh_key_card();exit;}
if(isset($_GET["ssh-key-regen-js"])){ssh_key_regen_js();exit;}
if(isset($_GET["ssh-key-regen-popup"])){ssh_key_regen_popup();exit;}
if(isset($_POST["ssh-bastion-regenerate"])){ssh_key_regen_save();exit;}
if(isset($_POST["ssh-bastion-create"])){ssh_key_create_save();exit;}
if(isset($_POST["RDPProxySSLCertificate"])){SaveSettings();exit;}
if(isset($_POST["RDPIgnoreLogonPassword"])){SaveSettings();exit;}
if(isset($_POST["RDPHandshakeTimeout"])){SaveSettings();exit;}




page();

function tabs(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table-start=yes";
    $array["{video_recording}"]="$page?video=yes";
    $array["{template}"]="$page?theme=yes";
    $array["{APP_RDPPROXY_AUTHHOOK}"]="$page?authook=yes";
    $array["{rdpproxy_webview}"]="$page?webview=yes";
    $array["{about2}"]="$page?about=yes";

    echo $tpl->tabs_default($array);

}
function theme_start(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-theme' style='margin-top: 20px'></div><script>LoadAjax('rdpproxy-theme','$page?rdpproxy-theme=yes')</script>";
}
// Valeurs effectives du theme de la page de connexion RDP, defauts compris. Les
// defauts sont ceux de l'ancien formulaire : ce sont ceux qu'applique le daemon
// quand la cle est vide, les changer changerait le rendu sans le dire.
function theme_defaults():array{
    return array(
        // #005447 depuis le 2026-10-29 (RDPProxy/theme.go DefaultBackgroundColor), ecrit meme sans theme.
        "RDPProxyBackGroundColor"=>"#005447",
        "RDPProxyFgcolor"=>"#FFFFFF",
        "RDPProxySeparatorColor"=>"#cfd5eb",
        "RDPProxyFocusColor"=>"#004D9C",
        "RDPProxyErrorColor"=>"#ffff00",
        "RDPProxySelectorLine1Bgcolor"=>"#000000",
        "RDPProxySelectorLine1Fgcolor"=>"#cfd5eb",
        "RDPProxySelectorLine2Bgcolor"=>"#cfd5eb",
        "RDPProxySelectorLine2Fgcolor"=>"#000000",
        "RDPProxySelectorSelectedBgcolor"=>"#4472C4",
        "RDPProxySelectorSelectedFgcolor"=>"#FFFFFF",
        "RDPProxySelectorFocusBgcolor"=>"#004D9C",
        "RDPProxySelectorFocusFgcolor"=>"#FFFFFF",
        "RDPProxySelectorLabelBgcolor"=>"#4472C4",
        "RDPProxySelectorLabelFgcolor"=>"#FFFFFF",
        "RDPProxyEditBgcolor"=>"#FFFFFF",
        "RDPProxyEditFgcolor"=>"#000000",
        "RDPProxyEditFocusColor"=>"#004D9C",
        "RDPProxyLogo"=>"img/rdpproxy/artica-logo.png",
    );
}

function theme_values():array{
    $defaults=theme_defaults();
    $v=array();
    foreach($defaults as $key=>$def){
        $v[$key]=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
        if($v[$key]==""){$v[$key]=$def;}
    }
    $v["RDPProxyTheme"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyTheme"));
    return $v;
}

// Une couleur n'entre dans un attribut style que si c'est bien une couleur.
function theme_css_color($c):string{
    $c=trim((string)$c);
    if(preg_match('/^#[0-9A-Fa-f]{3,8}$/',$c)){return $c;}
    return "transparent";
}

// Pastille d'une couleur seule, suivie de son code.
function theme_swatch($c):string{
    $css=theme_css_color($c);
    $txt=htmlspecialchars((string)$c,ENT_QUOTES);
    return "<span style='display:inline-block;width:16px;height:16px;border-radius:4px;"
        ."border:1px solid #c8ccd2;background:$css;vertical-align:middle;margin-right:6px'></span>"
        ."<span style='font-family:monospace;vertical-align:middle'>$txt</span>";
}

// Apercu d'un couple fond/texte : un « Aa » rendu dans les deux couleurs.
function theme_pair($bg,$fg):string{
    $cbg=theme_css_color($bg);
    $cfg=theme_css_color($fg);
    $tbg=htmlspecialchars((string)$bg,ENT_QUOTES);
    $tfg=htmlspecialchars((string)$fg,ENT_QUOTES);
    return "<span style='display:inline-block;padding:1px 10px;border-radius:4px;border:1px solid #c8ccd2;"
        ."background:$cbg;color:$cfg;font-weight:700;vertical-align:middle;margin-right:6px'>Aa</span>"
        ."<span style='font-family:monospace;vertical-align:middle'>$tbg / $tfg</span>";
}

// Apres un enregistrement : fermer la fenetre, redemarrer le service, puis
// recharger les tuiles.
// theme_jsafter : apres un enregistrement du theme, rdpproxy.ini est reecrit SANS redemarrage
// (theme_apply_js) : ReDemPtion relit l'ini a chaque nouvelle session (verifie sur .225 le
// 2026-10-29 : nouvelle couleur de fond, meme PID). Avant, /rdpproxy/restart coupait toutes les
// sessions ouvertes a chaque changement de couleur.
function theme_jsafter():string{
    $page=CurrentPageName();
    return "dialogInstance2.close();Loadjs('$page?theme-apply-js=yes');";
}

function theme_apply_js(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $err=rdpproxy_config_apply();
    if($err!=""){$tpl->js_error($err);return;}
    echo "LoadAjaxSilent('rdpproxy-theme','$page?rdpproxy-theme=yes');\n";
}

// ---------------------------------------------------------------------------
// Onglet « Modele » (?rdpproxy-theme=yes) -- maquette
// /home/claude/images/stitch_redemption_rdp_theme.zip.
//
// Bandeau (activation + logo), apercu des deux ecrans que voient les
// utilisateurs, trois cartes de couleurs, puis les textes (accueil, charte,
// messages d'erreur). Chaque carte ouvre SA fenetre existante.
//
// L'apercu suit ce que le daemon ecrit vraiment (RDPProxy/theme.go themeSection) :
// sans licence, skin Artica par defaut ; licence mais fonctionnalite coupee, seul
// le fond configure s'applique (logo Artica, palette native pour le reste) ; les
// couleurs « natives » sont dessinees avec les defauts de la console, d'ou la
// legende qui le dit. Les reperes numerotes relient une zone de l'apercu a sa
// ligne de reglage ; le survol d'une ligne entoure la zone.
// Textes de l'administrateur : charter_safe() (accolades echappees -> le moteur
// de traduction n'y voit aucun {jeton}).
// ---------------------------------------------------------------------------

function rth_css():string{
    $ac=rst_accents();
    $a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];$al=$ac["accent_line"];
    return "<style>
.rth{max-width:1500px;margin-top:14px;display:flex;flex-direction:column;gap:24px;color:#1e293b}
.rth-hero-r{flex:0 0 auto;display:flex;flex-direction:column;align-items:flex-end;gap:12px}
.rth-hero-line{display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end}
.rth-logo{display:inline-flex;align-items:center;padding:4px 10px;border-radius:6px;border:1px solid #cbd5e1}
.rth-logo img{max-height:28px;max-width:160px;vertical-align:middle}
.rth-lic{display:flex;gap:12px;margin-top:18px;padding:14px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;color:#92400e;font-size:13px;line-height:1.55}
.rth-sec{padding:20px 24px}
.rth-sec-h{display:flex;justify-content:space-between;align-items:center;gap:16px;padding-bottom:14px;border-bottom:1px solid #eef2f6}
.rth-sec-h h3{font-size:16px;font-weight:600;margin:0;display:flex;align-items:center;gap:10px;color:#1e293b}
.rth-sec-h h3 span{color:$ad}
.rth-legend{font-size:12px;color:#64748b;text-align:right;max-width:560px;line-height:1.45}
.rth-pv{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;margin-top:18px}
@media(max-width:1100px){.rth-pv{grid-template-columns:1fr}}
.rth-pv-t{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#475569;margin-bottom:8px}
.rth-canvas{position:relative;aspect-ratio:16/10;border-radius:8px;overflow:hidden;border:1px solid #cbd5e1;box-shadow:0 4px 10px rgba(15,23,42,.10);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:22px;font-family:Arial,Helvetica,sans-serif}
.rth-canvas-sel{justify-content:flex-start;align-items:stretch}
.rth-z{position:relative;border-radius:4px}
.rth-hl{outline:2px dashed $a;outline-offset:3px}
.rth-m{position:absolute;top:-9px;right:-9px;display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#dc2626;color:#fff;font-size:10px;font-weight:700;line-height:1;box-shadow:0 0 0 2px #fff;font-family:Arial,sans-serif;z-index:2}
.rth-m-in{position:static;box-shadow:none;flex:0 0 18px}
.rth-welcome{font-size:12px;line-height:1.4;text-align:center;max-width:80%;margin:8px 0 12px 0;padding:2px 4px}
.rth-form{width:62%;max-width:300px;display:flex;flex-direction:column;gap:6px}
.rth-lbl{font-size:10px}
.rth-in{height:24px;border-radius:3px;font-size:11px;display:flex;align-items:center;padding:0 7px;border:2px solid transparent}
.rth-sep{height:0;border-top:1px solid;margin:8px 0 4px 0}
.rth-sep .rth-m{right:auto;left:-28px}
.rth-err{font-size:11px;font-weight:700;text-align:center;padding:2px}
.rth-ok{align-self:center;font-size:11px;padding:3px 18px;border:2px solid;border-radius:3px;margin-top:4px}
.rth-go{margin-left:auto;font-size:11px;padding:0 5px;align-self:stretch;display:flex;align-items:center}
.rth-box{width:62%;max-width:300px;display:flex;flex-direction:column;align-items:center;gap:4px;margin-top:14px;padding-top:10px;border-top:1px dashed rgba(255,255,255,.35)}
.rth-box-c{font-size:10px;opacity:.7;align-self:flex-start}
.rth-box-t{font-size:11px}
.rth-box-s{width:70%;border-top:1px solid}
.rth-box-m{font-size:11px}
.rth-tbl{width:100%;font-size:11px;border-collapse:collapse;margin-top:6px}
.rth-tbl th,.rth-tbl td{padding:6px 10px;text-align:left;position:relative}
.rth-tbl td:first-child{font-family:'JetBrains Mono',Consolas,monospace}
.rth-sel-t{font-size:12px;font-weight:600;margin:4px 0 6px 2px}
.rth-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px}
@media(max-width:1300px){.rth-cards{grid-template-columns:1fr}}
.rth-crow{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 8px;margin:0 -8px;border-bottom:1px solid #eef2f6;border-radius:6px}
.rth-crow:last-child{border-bottom:0}
.rth-crow:hover{background:#f8fafc}
.rth-crow-l{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:#334155;min-width:0}
.rth-crow-r{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.rth-sw{display:inline-block;width:20px;height:20px;border-radius:4px;border:1px solid #cbd5e1;flex:0 0 20px}
.rth-aa{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:22px;padding:0 6px;border-radius:4px;border:1px solid #cbd5e1;font-weight:700;font-size:12px}
.rth-tag{display:inline-flex;align-items:center;gap:5px}
.rth-group{font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#475569;margin:4px 0 -8px 2px}
.rth-quote{margin-top:14px;padding:12px 14px;background:#f8fafc;border-left:3px solid #cbd5e1;border-radius:0 6px 6px 0;font-size:14px;line-height:1.55;color:#334155;max-height:11em;overflow:auto;word-break:break-word}
.rth-empty{font-style:italic}
.rth-meta{margin-top:8px;font-size:12px;color:#64748b;display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.rth-fp{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace;background:#f1f5f9;border-radius:4px;padding:1px 6px;word-break:break-all}
.rth-state{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #eef2f6;font-size:14px;font-weight:500;color:#334155;margin-top:4px}
.rth-errs{margin-top:14px;border:1px solid #e2e8f0}
.rth-errs td{vertical-align:top;word-break:break-word}
.rth-errs td.n{font-family:'JetBrains Mono',Consolas,monospace;color:#64748b;width:60px}
.rth-errs td.o{width:140px;text-align:right}
.rth .rst-head h3 .rth-tag{margin-left:8px;vertical-align:middle}
.rth-hero-p br:last-child,.rth-legend br:last-child{display:none}
</style>";
}

// Luminance relative WCAG d'un #rgb/#rrggbb ; -1 si illisible.
function rth_luminance($hex):float{
    $h=ltrim(trim((string)$hex),"#");
    if(strlen($h)==3){$h=$h[0].$h[0].$h[1].$h[1].$h[2].$h[2];}
    if(!preg_match('/^[0-9A-Fa-f]{6}/',$h)){return -1;}
    $l=array();
    foreach(array(0,2,4) as $i){
        $c=hexdec(substr($h,$i,2))/255;
        $l[]=($c<=0.03928)?$c/12.92:pow(($c+0.055)/1.055,2.4);
    }
    return 0.2126*$l[0]+0.7152*$l[1]+0.0722*$l[2];
}

// Contraste < 4.5:1 (WCAG AA texte courant) entre deux couleurs lisibles.
function rth_low_contrast($bg,$fg):bool{
    $a=rth_luminance($bg);$b=rth_luminance($fg);
    if($a<0||$b<0){return false;}
    return ((max($a,$b)+0.05)/(min($a,$b)+0.05))<4.5;
}

function rth_mark($n):string{
    return "<span class='rth-m'>$n</span>";
}

function rth_button($js,$label,$ico="fa-solid fa-pen"):string{
    if($js==""){return "";}
    return "<button type='button' class='ak-btn' OnClick=\"$js\"><i class='$ico'></i>$label</button>";
}

// Couleurs dessinees dans l'apercu, et si la configuration y est vraiment appliquee.
function rth_effective($v,$licensed):array{
    $d=theme_defaults();
    $themeOn=intval($v["RDPProxyTheme"])==1;
    $e=$d;
    if($licensed){
        $e["RDPProxyBackGroundColor"]=$v["RDPProxyBackGroundColor"];
        if($themeOn){$e=$v;}
    }
    foreach($e as $k=>$c){if($k!="RDPProxyLogo"&&$k!="RDPProxyTheme"){$e[$k]=theme_css_color($c);}}
    $e["logo"]=($licensed&&$themeOn)?$v["RDPProxyLogo"]:$d["RDPProxyLogo"];
    $e["applied"]=($licensed&&$themeOn);
    return $e;
}

function rth_hero($tpl,$v,$licensed):string{
    $page=CurrentPageName();
    $on=intval($v["RDPProxyTheme"])==1;
    $logo=htmlspecialchars($licensed?$v["RDPProxyLogo"]:theme_defaults()["RDPProxyLogo"],ENT_QUOTES);
    $logobg=theme_css_color($licensed?$v["RDPProxyBackGroundColor"]:theme_defaults()["RDPProxyBackGroundColor"]);
    $button="";
    if($licensed && $tpl->IsSecurity("AsSquidAdministrator") && !rdpproxy_meta_locked()){
        $button="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?theme-general-js=yes')\"><i class='fa-solid fa-pen'></i>{rdpproxy_theme_edit_general}</button>";
    }
    $h=array();
    $h[]="<section class='rvd-hero'><div class='rvd-hero-top'>";
    $h[]="<div><h2>{rdpproxy_theme_hero_title}</h2><p class='rth-hero-p'>{rdpproxy_theme_hero_text}</p></div>";
    $h[]="<div class='rth-hero-r'><div class='rth-hero-line'>";
    $h[]="<span class='rth-logo' style='background:$logobg' title=\"{logo}\"><img src='$logo' alt=\"{logo}\"></span>";
    $h[]="<span style='font-size:13px;font-weight:500;color:#334155'>{enable_feature}</span>".rst_state_pill($on && $licensed);
    $h[]="</div>$button</div></div>";
    if(!$licensed){
        $h[]="<div class='rth-lic' role='status'><span style='font-size:16px;color:#d97706'><i class='fa-solid fa-lock'></i></span><div>{rdpproxy_skin_license_required}</div></div>";
    }
    $h[]="</section>";
    return @implode("",$h);
}

function rth_preview_login($e,$welcome):string{
    $bg=$e["RDPProxyBackGroundColor"];$fg=$e["RDPProxyFgcolor"];
    $logo=htmlspecialchars($e["logo"],ENT_QUOTES);
    $err=rdpproxy_error_message(8);
    $h=array();
    $h[]="<div class='rth-canvas' style='background:$bg;color:$fg'>";
    $h[]="<span><img src='$logo' alt='' style='max-height:44px;max-width:220px'></span>";
    if($welcome!=""){
        $h[]="<div class='rth-z rth-welcome' data-rth='1'>$welcome".rth_mark(1)."</div>";
    }
    $h[]="<div class='rth-form'>";
    $in="background:{$e["RDPProxyEditBgcolor"]};color:{$e["RDPProxyEditFgcolor"]}";
    $h[]="<span class='rth-lbl'>{username}</span><div class='rth-z rth-in' data-rth='5' style='$in'>jdupont".rth_mark(5)."</div>";
    // Comme ReDemPtion : pas de bouton OK sur cet ecran, la fleche du champ qui a le focus
    // (ou Entree) valide ; elle prend la couleur de bordure du champ (edit_focus_border_color).
    $go="<span class='rth-go' style='background:{$e["RDPProxyEditFocusColor"]};color:{$e["RDPProxyEditBgcolor"]}'>&#10140;</span>";
    $h[]="<span class='rth-lbl'>{password}</span><div class='rth-z rth-in' data-rth='6' style='$in;border-color:{$e["RDPProxyEditFocusColor"]}'>&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;$go".rth_mark(6)."</div>";
    $h[]="<div class='rth-z rth-sep' data-rth='4' style='border-color:{$e["RDPProxySeparatorColor"]}'>".rth_mark(4)."</div>";
    $h[]="<div class='rth-z rth-err' data-rth='3' style='color:{$e["RDPProxyErrorColor"]}'>".charter_safe($err["text"]).rth_mark(3)."</div>";
    $h[]="</div>";
    // Boites de message (refus, fermeture de connexion) : c'est la que sert la couleur de focus.
    $h[]="<div class='rth-box'><div class='rth-box-c'>{rdpproxy_preview_message_box}</div>";
    $h[]="<div class='rth-box-t'>Information</div><div class='rth-box-s' style='border-color:{$e["RDPProxySeparatorColor"]}'></div>";
    $h[]="<div class='rth-box-m'>Error, ".charter_safe(rdpproxy_error_message(7)["text"])."</div>";
    $h[]="<span class='rth-z rth-ok' data-rth='2' style='border-color:#fff;background:{$e["RDPProxyFocusColor"]}'>OK".rth_mark(2)."</span>";
    $h[]="</div></div>";
    return @implode("",$h);
}

function rth_preview_selector($e):string{
    $bg=$e["RDPProxyBackGroundColor"];$fg=$e["RDPProxyFgcolor"];
    // Lignes d'exemple : noms fictifs, seules les couleurs viennent de la configuration.
    $rows=array(
        array("srv-01","RDP","RDPProxySelectorLine1",7),
        array("srv-02","RDP","RDPProxySelectorLine2",8),
        array("srv-03","VNC","RDPProxySelectorSelected",9),
        array("srv-04","RDP","RDPProxySelectorFocus",10),
    );
    $h=array();
    $h[]="<div class='rth-canvas rth-canvas-sel' style='background:$bg;color:$fg'>";
    $h[]="<div class='rth-sel-t'>{targets}</div>";
    $h[]="<table class='rth-tbl'>";
    $h[]="<tr class='rth-z' data-rth='11' style='background:{$e["RDPProxySelectorLabelBgcolor"]};color:{$e["RDPProxySelectorLabelFgcolor"]}'><th>{target}</th><th>{protocol}".rth_mark(11)."</th></tr>";
    foreach($rows as $r){
        $style="background:".$e[$r[2]."Bgcolor"].";color:".$e[$r[2]."Fgcolor"];
        $h[]="<tr class='rth-z' data-rth='{$r[3]}' style='$style'><td>{$r[0]}</td><td>{$r[1]}".rth_mark($r[3])."</td></tr>";
    }
    $h[]="</table></div>";
    return @implode("",$h);
}

function rth_preview($v,$licensed,$welcomeText):string{
    $e=rth_effective($v,$licensed);
    $legend="{rdpproxy_theme_preview_legend}";
    if(!$licensed){$legend.=" {rdpproxy_theme_preview_nolicense}";}
    elseif(!$e["applied"]){$legend.=" {rdpproxy_theme_preview_off}";}
    $welcome="";
    if($licensed && $welcomeText!=""){
        $t=function_exists("mb_substr")?mb_substr($welcomeText,0,180,"UTF-8"):substr($welcomeText,0,180);
        if(strlen($t)<strlen($welcomeText)){$t.="...";}
        $welcome=nl2br(charter_safe($t));
    }
    $h=array();
    $h[]="<section class='ak-card rth-sec'>";
    $h[]="<div class='rth-sec-h'><h3><span><i class='fa-regular fa-eye'></i></span>{rdpproxy_theme_preview}</h3><div class='rth-legend'>$legend</div></div>";
    $h[]="<div class='rth-pv'>";
    $h[]="<div><div class='rth-pv-t'>{rdpproxy_login_screen}</div>".rth_preview_login($e,$welcome)."</div>";
    $h[]="<div><div class='rth-pv-t'>{rdpproxy_theme_preview_selector}</div>".rth_preview_selector($e)."</div>";
    $h[]="</div></section>";
    return @implode("",$h);
}

// Ligne de couleur : repere, libelle, pastille (ou « Aa » fond/texte), codes, contraste.
function rth_color_row($mark,$label,$c1,$c2=null):string{
    $right="";
    if($c2===null){
        $right.="<span class='rth-sw' style='background:".theme_css_color($c1)."'></span>";
        $right.="<span class='ak-chip'>".htmlspecialchars($c1,ENT_QUOTES)."</span>";
    }else{
        if(rth_low_contrast($c1,$c2)){
            $right.="<span class='ak-tag ak-warn rth-tag'><i class='fa-solid fa-triangle-exclamation'></i>{rdpproxy_theme_low_contrast}</span>";
        }
        $right.="<span class='rth-aa' style='background:".theme_css_color($c1).";color:".theme_css_color($c2)."'>Aa</span>";
        $right.="<span class='ak-chip'>".htmlspecialchars("$c1 / $c2",ENT_QUOTES)."</span>";
    }
    return "<div class='rth-crow' data-rth='$mark'><div class='rth-crow-l'><span class='rth-m rth-m-in'>$mark</span>$label</div><div class='rth-crow-r'>$right</div></div>";
}

// En-tete d'une carte de couleurs : etiquette d'etat quand la palette n'est pas appliquee.
function rth_color_card($tpl,$licensed,$v,$ico,$title,$explain,$rows,$js):string{
    $tag="";
    if(!$licensed){$tag="<span class='ak-tag ak-warn rth-tag'><i class='fa-solid fa-lock'></i>{rdpproxy_theme_license_required}</span>";}
    elseif(intval($v["RDPProxyTheme"])!=1){$tag="<span class='ak-tag ak-off rth-tag'>{rdpproxy_theme_not_applied}</span>";}
    $button=($licensed && !rdpproxy_meta_locked())?rth_button(rst_js($tpl,$js),"{modify}"):"";
    return rst_card($ico,$title.$tag,$explain,$rows,$button);
}

function rth_card_screen($tpl,$v,$licensed):string{
    $page=CurrentPageName();
    $rows=array();
    $rows[]=rth_color_row(1,"{background_color} / {text_color}",$v["RDPProxyBackGroundColor"],$v["RDPProxyFgcolor"]);
    $rows[]=rth_color_row(2,"{focus_color}",$v["RDPProxyFocusColor"]);
    $err=rth_color_row(3,"{error_color}",$v["RDPProxyErrorColor"]);
    if(rth_low_contrast($v["RDPProxyBackGroundColor"],$v["RDPProxyErrorColor"])){
        // L'erreur s'ecrit sur le fond : c'est ce couple qui doit rester lisible.
        $err=str_replace("<div class='rth-crow-r'>","<div class='rth-crow-r'><span class='ak-tag ak-warn rth-tag'><i class='fa-solid fa-triangle-exclamation'></i>{rdpproxy_theme_low_contrast}</span>",$err);
    }
    $rows[]=$err;
    $rows[]=rth_color_row(4,"{separator_color}",$v["RDPProxySeparatorColor"]);
    return rth_color_card($tpl,$licensed,$v,"fa-solid fa-display","{screen}","{rdpproxy_theme_screen_explain}",$rows,"Loadjs('$page?theme-screen-js=yes')");
}

function rth_card_fields($tpl,$v,$licensed):string{
    $page=CurrentPageName();
    $rows=array();
    $rows[]=rth_color_row(5,"{rdpproxy_theme_input}",$v["RDPProxyEditBgcolor"],$v["RDPProxyEditFgcolor"]);
    $rows[]=rth_color_row(6,"{edit_focus_color}",$v["RDPProxyEditFocusColor"]);
    return rth_color_card($tpl,$licensed,$v,"fa-solid fa-i-cursor","{fields}","{rdpproxy_theme_fields_explain}",$rows,"Loadjs('$page?theme-fields-js=yes')");
}

function rth_card_selector($tpl,$v,$licensed):string{
    $page=CurrentPageName();
    $rows=array();
    $rows[]=rth_color_row(7,"{row} 1",$v["RDPProxySelectorLine1Bgcolor"],$v["RDPProxySelectorLine1Fgcolor"]);
    $rows[]=rth_color_row(8,"{row} 2",$v["RDPProxySelectorLine2Bgcolor"],$v["RDPProxySelectorLine2Fgcolor"]);
    $rows[]=rth_color_row(9,"{rdpproxy_theme_selected}",$v["RDPProxySelectorSelectedBgcolor"],$v["RDPProxySelectorSelectedFgcolor"]);
    $rows[]=rth_color_row(10,"{rdpproxy_theme_focus}",$v["RDPProxySelectorFocusBgcolor"],$v["RDPProxySelectorFocusFgcolor"]);
    $rows[]=rth_color_row(11,"{rdpproxy_theme_header}",$v["RDPProxySelectorLabelBgcolor"],$v["RDPProxySelectorLabelFgcolor"]);
    return rth_color_card($tpl,$licensed,$v,"fa-solid fa-list","{selector}","{rdpproxy_theme_selector_explain}",$rows,"Loadjs('$page?theme-selector-js=yes')");
}

// Bandeau des cibles Web (telechargements) : couleurs, hauteur et duree (patch 0020).
function rth_card_notice($tpl,$licensed):string{
    $page=CurrentPageName();
    $err="";
    $n=rdpproxy_web_notice($err);
    if($err!=""){
        return rst_card("fa-solid fa-window-minimize","{rdpproxy_webnotice}","{rdpproxy_webnotice_explain}",array($tpl->div_error($err)));
    }
    $rows=array();
    $colors=rth_color_row(12,"{background_color} / {text_color}",$n["effective_bgcolor"],$n["effective_fgcolor"]);
    if($n["bgcolor"]=="" && $n["fgcolor"]==""){
        $colors=str_replace("<div class='rth-crow-r'>","<div class='rth-crow-r'><span class='ak-tag ak-off rth-tag'>{rdpproxy_webnotice_follow_screen}</span>",$colors);
    }
    $rows[]=$colors;
    $rows[]="<div class='rth-crow'><div class='rth-crow-l'>{rdpproxy_webnotice_lines}</div><div class='rth-crow-r'><span class='ak-chip'>".intval($n["lines"])."</span></div></div>";
    $rows[]="<div class='rth-crow'><div class='rth-crow-l'>{rdpproxy_webnotice_seconds}</div><div class='rth-crow-r'><span class='ak-chip'>".intval($n["seconds"])." s</span></div></div>";
    $tag="";
    if(!$n["supported"]){$tag="<span class='ak-tag ak-warn rth-tag'>{rdpproxy_webnotice_engine_required}</span>";}
    $button=!rdpproxy_meta_locked()?rth_button(rst_js($tpl,"Loadjs('$page?theme-notice-js=yes')"),"{modify}"):"";
    return rst_card("fa-solid fa-window-minimize","{rdpproxy_webnotice}".$tag,"{rdpproxy_webnotice_explain}",$rows,$button);
}

function rth_quote($text):string{
    if($text===""){return "<div class='rth-quote ak-muted rth-empty'>{rdpproxy_theme_no_text}</div>";}
    return "<div class='rth-quote'>".nl2br(charter_safe($text))."</div>";
}

function rth_warn($text):string{
    return "<div class='rst-note rst-note-warn' style='margin-top:12px'><i class='fa-solid fa-triangle-exclamation' style='margin-top:2px'></i><span>$text</span></div>";
}

// Texte d'accueil : partie du skin, donc soumis a la licence comme les couleurs.
function rth_card_welcome($tpl,$licensed,$w,$err):string{
    $page=CurrentPageName();
    $rows=array();
    if($err!=""){
        $rows[]=rth_warn(charter_safe($err));
    }else{
        $text=isset($w["text"])?(string)$w["text"]:"";
        $rows[]=rth_quote($text);
        if($text!==""){$rows[]="<div class='rth-meta'>".intval($w["chars"])." {characters}</div>";}
        if($text!=="" && empty($w["supported"])){
            $rows[]=rth_warn("{rdpproxy_welcome_authhook_too_old} (".charter_safe($w["min_authhook"]).")");
        }
    }
    $button=($licensed && !rdpproxy_meta_locked())?rth_button(rst_js($tpl,"Loadjs('$page?welcome-js=yes')"),"{rdpproxy_theme_edit_welcome}"):"";
    $title="{rdpproxy_login_screen}";
    if(!$licensed){$title.="<span class='ak-tag ak-warn rth-tag'><i class='fa-solid fa-lock'></i>{rdpproxy_theme_license_required}</span>";}
    return rst_card("fa-solid fa-door-open",$title,"{rdpproxy_login_screen_explain}",$rows,$button);
}

function rth_card_charter($tpl){
    $page=CurrentPageName();
    $err="";
    $c=rdpproxy_charter($err);
    $rows=array();
    if($err!=""){
        $rows[]=rth_warn(charter_safe($err));
    }else{
        $enabled=!empty($c["enabled"]);
        $text=isset($c["text"])?(string)$c["text"]:"";
        $rows[]="<div class='rth-state'><span>{rdpproxy_charter_enabled}</span>".rst_state_pill($enabled)."</div>";
        $rows[]=rth_quote($text);
        if($text!==""){
            $rows[]="<div class='rth-meta'><span>".intval($c["chars"])." {characters}</span>&middot;<span class='rth-fp'>".charter_safe($c["fingerprint"])."</span></div>";
        }
        if($enabled && empty($c["supported"])){
            $rows[]=rth_warn("{rdpproxy_charter_authhook_too_old} (".charter_safe($c["min_authhook"]).")");
        }
    }
    return rst_card("fa-solid fa-file-signature","{rdpproxy_charter}","{rdpproxy_charter_section_explain}",$rows,
        rdpproxy_meta_locked()?"":rth_button(rst_js($tpl,"Loadjs('$page?charter-js=yes')"),"{rdpproxy_theme_edit_charter}"));
}

// Messages d'erreur : pas soumis a la licence, le daemon les applique sans elle.
function rth_card_errmsg($tpl):string{
    $page=CurrentPageName();
    $t=array();
    $t[]="<table class='ak-tbl rth-errs'><thead><tr><th>N&deg;</th><th>{rdpproxy_theme_displayed_message}</th><th style='text-align:right'>{origin}</th></tr></thead><tbody>";
    for($i=1;$i<=count(rdpproxy_error_defaults());$i++){
        $m=rdpproxy_error_message($i);
        $o=$m["custom"]?"<span class='ak-tag ak-info rth-tag'>{customized}</span>":"<span class='ak-tag ak-off rth-tag'>{default}</span>";
        $t[]="<tr><td class='n'>".sprintf("%02d",$i)."</td><td>".charter_safe($m["text"])."</td><td class='o'>$o</td></tr>";
    }
    $t[]="</tbody></table>";
    return rst_card("fa-solid fa-triangle-exclamation","{error_messages_customization}","{rdpproxy_errmsg_section_explain}",
        array(@implode("",$t)),rth_button(rst_js($tpl,"Loadjs('$page?errmsg-js=yes')"),"{rdpproxy_theme_edit_messages}"));
}

// Survol d'une ligne de couleur -> la zone correspondante de l'apercu s'entoure.
function rth_js():string{
    return "<script>(function(){var r=document.getElementById('rth-root');if(!r||!r.closest){return;}
function hl(n,on){var e=r.querySelectorAll('.rth-pv [data-rth=\"'+n+'\"]');for(var i=0;i<e.length;i++){e[i].classList.toggle('rth-hl',on);}}
r.addEventListener('mouseover',function(ev){var t=ev.target.closest('.rth-cards [data-rth]');if(t){hl(t.getAttribute('data-rth'),true);}});
r.addEventListener('mouseout',function(ev){var t=ev.target.closest('.rth-cards [data-rth]');if(t){hl(t.getAttribute('data-rth'),false);}});
})();</script>";
}

function theme(){
    $tpl=new template_admin();
    $v=theme_values();
    $licensed=skin_licensed();
    $werr="";
    $w=rdpproxy_welcome($werr);
    $welcomeText=($werr=="" && isset($w["text"]))?(string)$w["text"]:"";

    $html=array();
    $html[]=rdpproxy_meta_banner();
    $html[]="<div class='rth ak' id='rth-root'>";
    $html[]=rth_hero($tpl,$v,$licensed);
    $html[]=rth_preview($v,$licensed,$welcomeText);
    $html[]="<div class='rth-cards'>".rth_card_screen($tpl,$v,$licensed).rth_card_fields($tpl,$v,$licensed).rth_card_selector($tpl,$v,$licensed).rth_card_notice($tpl,$licensed)."</div>";
    $html[]="<div class='rth-group'>{rdpproxy_theme_texts}</div>";
    $html[]=rth_card_welcome($tpl,$licensed,$w,$werr);
    $html[]=rth_card_charter($tpl);
    $html[]=rth_card_errmsg($tpl);
    $html[]="</div>";
    // Le bandeau dit deja la licence : l'en-tete reste neutre.
    echo rst_css().rvd_css().rth_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rth_js();
    echo "<script>".main_tiny_default("{skin}")."</script>";
}

// ------------------------------------------------------- licence du skin
// skin_licensed : licence Artica (Corporate ou Gold). Sans elle, le skin Artica par defaut :
// le daemon ignore couleurs, fond, logo et texte d'accueil (RDPProxy/theme.go skinLicensed,
// authhook RDPEngine/login.go welcomeText).
function skin_licensed():bool{
    return $GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
}
// skin_license_guard : refuse un enregistrement du skin sans licence (reponse jserror:, exit).
function skin_license_guard(){
    if(skin_licensed()){return;}
    $tpl=new template_admin();
    echo $tpl->post_error("{rdpproxy_skin_license_required}");
    exit;
}

// ------------------------------------------------------- texte d'accueil
// Ecran de connexion natif de ReDemPtion (authhook >= 1.6.3, RDPEngine/login.go) : le texte
// commun passe par login_message, au-dessus des champs. Relu par authhook a chaque session.
function welcome_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_welcome_text}","$page?welcome-popup=yes",850);
}

function welcome_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return false;}
    $err="";
    $w=rdpproxy_welcome($err);
    if($err!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error(charter_safe($err)));return false;}
    $form[]=$tpl->field_hidden("RDPProxyWelcomeSave","1");
    // Texte hors _ENGINE_parse_body (un {jeton} saisi serait remplace) : marqueur remplace apres.
    $form[]=$tpl->field_textarea_normal("RDPProxyWelcomeText","{rdpproxy_welcome_text}","__RDPWELCOMETEXT__","100%","220px","{rdpproxy_welcome_text_explain}");
    rdpproxy_meta_lock_form($tpl);
    $html[]=$tpl->div_explain("{rdpproxy_welcome_text}||{rdpproxy_welcome_screen_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),null,"{apply}",
        "dialogInstance2.close();LoadAjaxSilent('rdpproxy-theme','$page?rdpproxy-theme=yes');","AsSquidAdministrator");
    $text=isset($w["text"])?htmlspecialchars((string)$w["text"],ENT_QUOTES,"UTF-8"):"";
    echo strtr($tpl->_ENGINE_parse_body(@implode("\n",$html)),array("__RDPWELCOMETEXT__"=>$text));
    return true;
}

// welcome_save : valide par articarest (UTF-8, controles, invisibles, 2000 caracteres) ;
// vide = pas de message. Une couche encodeURIComponent : rawurldecode (voir charter_save).
function welcome_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    if(rdpproxy_meta_refuse_post(true)){return;}
    $text=rawurldecode((string)($_POST["RDPProxyWelcomeText"] ?? ""));
    if(!skin_licensed()){echo $tpl->post_error("{rdpproxy_skin_license_required}");return;}
    $err=rdpproxy_welcome_save($text);
    if($err=="license_required"){$err="{rdpproxy_skin_license_required}";}
    if($err!=""){echo $tpl->post_error($err);return;}
    $w=rdpproxy_welcome($e2);
    admin_tracks("Save RDS Proxy login screen welcome text (".intval($w["chars"] ?? 0)." characters)");
}

// ------------------------------------------------------- charte d'utilisation
// Commune au bastion, montree a chaque connexion apres l'authentification et le second
// facteur (authhook >= 1.6.2, RDPEngine/charter.go). Pas de redemarrage : authhook relit
// les deux cles a chaque session, d'ou un enregistrement sans theme_jsafter().

// charter_safe : texte de l'administrateur affiche dans la page. Echappe, accolades
// neutralisees (jamais lu comme un {jeton}), caracteres non ASCII en entites.
function charter_safe($s):string{
    $h=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    if(function_exists("mb_encode_numericentity")){$h=mb_encode_numericentity($h,array(0x80,0x10FFFF,0,0x1FFFFF),"UTF-8");}
    return str_replace(array("{","}"),array("&#123;","&#125;"),$h);
}

function charter_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_charter}","$page?charter-popup=yes",850);
}

function charter_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return false;}
    $err="";
    $c=rdpproxy_charter($err);
    if($err!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error(charter_safe($err)));return false;}
    $form[]=$tpl->field_hidden("RDPProxyCharterSave","1");
    $form[]=$tpl->field_checkbox("RDPProxyCharterEnabled","{rdpproxy_charter_enabled}",empty($c["enabled"])?0:1);
    // Le texte ne passe pas par _ENGINE_parse_body (un {jeton} saisi serait remplace puis
    // reenregistre traduit) : marqueur remplace APRES le rendu.
    $form[]=$tpl->field_textarea_normal("RDPProxyCharterText","{rdpproxy_charter_text}","__RDPCHARTERTEXT__","100%","320px","{rdpproxy_charter_text_explain}");
    rdpproxy_meta_lock_form($tpl);
    $html[]=$tpl->div_explain("{rdpproxy_charter}||{rdpproxy_charter_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),null,"{apply}",
        "dialogInstance2.close();LoadAjaxSilent('rdpproxy-theme','$page?rdpproxy-theme=yes');","AsSquidAdministrator");
    $text=isset($c["text"])?htmlspecialchars((string)$c["text"],ENT_QUOTES,"UTF-8"):"";
    echo strtr($tpl->_ENGINE_parse_body(@implode("\n",$html)),array("__RDPCHARTERTEXT__"=>$text));
    return true;
}

// charter_save : le daemon valide (UTF-8, controles, caracteres invisibles, 8000 caracteres,
// charte active sans texte) et ne change rien en cas de refus. Une seule couche
// encodeURIComponent arrive ici : rawurldecode (voir fw.rdpproxy.mfa.php messages_save).
function charter_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    if(rdpproxy_meta_refuse_post(true)){return;}
    $enabled=intval(rawurldecode((string)($_POST["RDPProxyCharterEnabled"] ?? "0")))==1;
    $text=rawurldecode((string)($_POST["RDPProxyCharterText"] ?? ""));
    $err=rdpproxy_charter_save($enabled,$text);
    if($err!=""){echo $tpl->post_error($err);return;}
    $c=rdpproxy_charter($e2);
    admin_tracks("Save RDS Proxy usage charter (".($enabled?"enabled":"disabled").", ".intval($c["chars"] ?? 0)." characters, fingerprint ".($c["fingerprint"] ?? "").")");
}

function theme_general_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{skin}: {general}","$page?theme-general-popup=yes",650);
}
function theme_screen_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{skin}: {screen}","$page?theme-screen-popup=yes",650);
}
function theme_fields_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{skin}: {fields}","$page?theme-fields-popup=yes",650);
}
function theme_notice_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{skin}: {rdpproxy_webnotice}","$page?theme-notice-popup=yes",650);
}
function theme_notice_popup():bool{
    $tpl=new template_admin();
    $err="";
    $n=rdpproxy_web_notice($err);
    if($err!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error($err));return true;}
    rdpproxy_meta_lock_form($tpl);
    $html=array();
    $html[]=$tpl->div_explain("{rdpproxy_webnotice}||{rdpproxy_webnotice_explain}");
    $form[]=$tpl->field_hidden("RDPWebNoticeSave","1");
    if($n["licensed"]){
        $custom=($n["bgcolor"]=="" && $n["fgcolor"]=="")?0:1;
        $form[]=$tpl->field_checkbox("RDPWebNoticeCustom","{rdpproxy_webnotice_custom_colors}",$custom,"RDPWebNoticeBg,RDPWebNoticeFg","{rdpproxy_webnotice_custom_colors_explain}");
        $form[]=$tpl->field_color("RDPWebNoticeBg","{background_color}",$n["effective_bgcolor"]);
        $form[]=$tpl->field_color("RDPWebNoticeFg","{text_color}",$n["effective_fgcolor"]);
    }else{
        $html[]=$tpl->div_warning("{rdpproxy_theme_license_required}");
    }
    $form[]=$tpl->field_array_hash(array(1=>"1",2=>"2",3=>"3"),"RDPWebNoticeLines","nonull:{rdpproxy_webnotice_lines}",$n["lines"]);
    $form[]=$tpl->field_numeric("RDPWebNoticeSeconds","{rdpproxy_webnotice_seconds}",$n["seconds"],"{rdpproxy_webnotice_seconds_explain}");
    $html[]=$tpl->form_outside("",$form,null,"{apply}","dialogInstance2.close();LoadAjax('rdpproxy-theme','".CurrentPageName()."?rdpproxy-theme=yes');","AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function theme_notice_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    if(rdpproxy_meta_locked()){echo $tpl->post_error("{rdpproxy_meta_locked_refused}");return;}
    $fields=array(
        "lines"=>trim((string)($_POST["RDPWebNoticeLines"] ?? "")),
        "seconds"=>trim((string)($_POST["RDPWebNoticeSeconds"] ?? "")),
    );
    // la case est postee seulement quand la licence permet les couleurs (fenetre sans case sinon)
    if(isset($_POST["RDPWebNoticeCustom"])){
        $custom=intval($_POST["RDPWebNoticeCustom"])==1;
        $fields["bgcolor"]=$custom?trim((string)($_POST["RDPWebNoticeBg"] ?? "")):"";
        $fields["fgcolor"]=$custom?trim((string)($_POST["RDPWebNoticeFg"] ?? "")):"";
    }
    $err=rdpproxy_web_notice_save($fields);
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks("Saved the notification band of the RDS Proxy web targets: lines=".intval($fields["lines"]).", seconds=".intval($fields["seconds"]));
}

function theme_selector_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{skin}: {selector}","$page?theme-selector-popup=yes",650);
}

// Activation + logo. Le televersement a son propre bouton : il enregistre le
// fichier tout de suite (file_uploaded), independamment du bouton Appliquer.
function theme_general_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $v=theme_values();
    $logo=htmlspecialchars($v["RDPProxyLogo"],ENT_QUOTES);
    $form[]=$tpl->field_checkbox("RDPProxyTheme","{enable_feature}",$v["RDPProxyTheme"]);
    $html[]="<div style='text-align:center;margin:5px 0 10px 0'>";
    $logo_bg=theme_css_color($v["RDPProxyBackGroundColor"]);
    $html[]="<div style='display:inline-block;padding:8px;border:1px solid #CCCCCC;background:$logo_bg'><img src='$logo' style='max-width:100%'></div>";
    $html[]="<table style='margin:0 auto'>";
    rdpproxy_meta_lock_form($tpl);
    $html[]=$tpl->form_button_upload("{picture} (*.png)",$page,null,rdpproxy_meta_sec("AsSquidAdministrator"));
    $html[]="</table>";
    $html[]="</div>";
    $html[]=$tpl->form_outside("",$form,null,"{apply}",theme_jsafter(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function theme_screen_popup():bool{
    $tpl=new template_admin();
    $v=theme_values();
    rdpproxy_meta_lock_form($tpl);
    $html[]=$tpl->div_explain("{background_color}||{rdpproxy_bgcolor_always}");
    $form[]=$tpl->field_color("RDPProxyBackGroundColor","{background_color}",$v["RDPProxyBackGroundColor"]);
    $form[]=$tpl->field_color("RDPProxyFgcolor","{text_color}",$v["RDPProxyFgcolor"]);
    $form[]=$tpl->field_color("RDPProxyFocusColor","{focus_color}",$v["RDPProxyFocusColor"]);
    $form[]=$tpl->field_color("RDPProxyErrorColor","{error_color}",$v["RDPProxyErrorColor"]);
    $form[]=$tpl->field_color("RDPProxySeparatorColor","{separator_color}",$v["RDPProxySeparatorColor"]);
    $html[]=$tpl->form_outside("",$form,null,"{apply}",theme_jsafter(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function theme_fields_popup():bool{
    $tpl=new template_admin();
    $v=theme_values();
    rdpproxy_meta_lock_form($tpl);
    $form[]=$tpl->field_color("RDPProxyEditBgcolor","{edit_bgcolor}",$v["RDPProxyEditBgcolor"]);
    $form[]=$tpl->field_color("RDPProxyEditFgcolor","{edit_fgcolor}",$v["RDPProxyEditFgcolor"]);
    $form[]=$tpl->field_color("RDPProxyEditFocusColor","{edit_focus_color}",$v["RDPProxyEditFocusColor"]);
    echo $tpl->form_outside("",$form,null,"{apply}",theme_jsafter(),"AsSquidAdministrator");
    return true;
}

function theme_selector_popup():bool{
    $tpl=new template_admin();
    $v=theme_values();
    rdpproxy_meta_lock_form($tpl);
    $form[]=$tpl->field_color("RDPProxySelectorLine1Bgcolor","{row_color} (1)",$v["RDPProxySelectorLine1Bgcolor"]);
    $form[]=$tpl->field_color("RDPProxySelectorLine1Fgcolor","{text_color} {row} (1)",$v["RDPProxySelectorLine1Fgcolor"]);
    $form[]=$tpl->field_color("RDPProxySelectorLine2Bgcolor","{row_color} (2)",$v["RDPProxySelectorLine2Bgcolor"]);
    $form[]=$tpl->field_color("RDPProxySelectorLine2Fgcolor","{text_color} {row} (2)",$v["RDPProxySelectorLine2Fgcolor"]);
    $form[]=$tpl->field_color("RDPProxySelectorSelectedBgcolor","{selector_selected_bgcolor}",$v["RDPProxySelectorSelectedBgcolor"]);
    $form[]=$tpl->field_color("RDPProxySelectorSelectedFgcolor","{selector_selected_fgcolor}",$v["RDPProxySelectorSelectedFgcolor"]);
    $form[]=$tpl->field_color("RDPProxySelectorFocusBgcolor","{selector_focus_bgcolor}",$v["RDPProxySelectorFocusBgcolor"]);
    $form[]=$tpl->field_color("RDPProxySelectorFocusFgcolor","{selector_focus_fgcolor}",$v["RDPProxySelectorFocusFgcolor"]);
    $form[]=$tpl->field_color("RDPProxySelectorLabelBgcolor","{selector_label_bgcolor}",$v["RDPProxySelectorLabelBgcolor"]);
    $form[]=$tpl->field_color("RDPProxySelectorLabelFgcolor","{selector_label_fgcolor}",$v["RDPProxySelectorLabelFgcolor"]);
    echo $tpl->form_outside("",$form,null,"{apply}",theme_jsafter(),"AsSquidAdministrator");
    return true;
}

function listen_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{listen_interface}","$page?listen-popup=yes",650);
}
function dh_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{dh_parameters}","$page?dh-popup=yes",650);
}
function timeouts_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{timeouts}","$page?timeouts-popup=yes",650);
}
function rdp_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("RDP: {parameters}","$page?rdp-popup=yes",650);
}
function tls_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{tls_title}","$page?tls-popup=yes",850);
}

// ------------------------------------------------------------ cle SSH du bastion
// Privilege des autres reglages de cette page. La cle privee ne sort jamais du daemon :
// seule la cle publique (a deposer dans authorized_keys) et son empreinte sont lues.

function ssh_key_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_ssh_bastion_key}","$page?ssh-key-popup=yes",800);
}

function ssh_key_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]=$tpl->div_explain("{rdpproxy_ssh_bastion_key}||{rdpproxy_ssh_bastion_key_explain}");
    $html[]="<div id='rdpproxy-ssh-key-card' style='margin-top:10px'></div>";
    $html[]="<script>LoadAjax('rdpproxy-ssh-key-card','$page?ssh-key-card=yes');</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function ssh_key_card():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $error="";
    $absent=false;
    $k=rdpproxy_bastion_key($error,$absent);
    if($error!=""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($error));
        return false;
    }
    if($absent){return ssh_key_card_absent();}
    $type=htmlspecialchars((string)($k["type"] ?? ""),ENT_QUOTES);
    $fp=htmlspecialchars((string)($k["fingerprint"] ?? ""),ENT_QUOTES);
    $pub=htmlspecialchars((string)($k["public_key"] ?? ""),ENT_QUOTES);
    $created=$k["created"] ?? "";
    if(is_numeric($created) && intval($created)>0){$created=date("Y-m-d H:i",intval($created));}
    $created=htmlspecialchars((string)$created,ENT_QUOTES);
    $id="sshpub".md5($pub);
    $h=array();
    $h[]="<table class='table table-condensed' style='margin-bottom:10px'>";
    $h[]="<tr><td style='width:25%'><strong>{type}</strong></td><td><code>$type</code></td></tr>";
    $h[]="<tr><td><strong>{fingerprint}</strong></td><td><code style='word-break:break-all'>$fp</code></td></tr>";
    if($created!=""){$h[]="<tr><td><strong>{created}</strong></td><td>$created</td></tr>";}
    $h[]="</table>";
    $h[]="<label><strong>{public_key}</strong></label>";
    $h[]="<textarea id='$id' class='form-control' readonly rows='3' style='font-family:monospace;font-size:12px' onclick='this.select()'>$pub</textarea>";
    $h[]="<div style='margin-top:10px'>";
    $h[]="<a class='btn btn-primary' href=\"javascript:blur();\" OnClick=\"RdpSshCopy('$id')\"><i class='fas fa-copy'></i> {copy}</a> ";
    $h[]="<span id='$id-ok' class='text-success' style='display:none'><i class='fas fa-check'></i> {copied}</span>";
    if($tpl->IsSecurity("AsSquidAdministrator")){
        $h[]="<a class='btn btn-danger pull-right' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?ssh-key-regen-js=yes')\"><i class='fas fa-rotate'></i> {rdpproxy_ssh_regenerate}...</a>";
    }
    $h[]="</div>";
    $js="<script>
function RdpSshCopy(id){
 var e=document.getElementById(id);if(!e)return;
 var ok=function(){var o=document.getElementById(id+'-ok');if(o){o.style.display='inline';setTimeout(function(){o.style.display='none';},2500);}};
 if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(e.value).then(ok);return;}
 e.focus();e.select();try{document.execCommand('copy');ok();}catch(x){}
}
</script>";
    // Le script sort du parseur : ses accolades seraient lues comme des {jetons}.
    echo $tpl->_ENGINE_parse_body(@implode("\n",$h)).$js;
    return true;
}

// Pas encore de paire : la page ne la cree jamais en lisant, seulement sur ce bouton (POST).
function ssh_key_card_absent():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();
    $html[]=$tpl->_ENGINE_parse_body($tpl->div_warning("{rdpproxy_ssh_bastion_key}||{rdpproxy_ssh_no_bastion_key}"));
    if($tpl->IsSecurity("AsSquidAdministrator")){
        $tpl->field_hidden("ssh-bastion-create","yes");
        $form=array();
        $html[]=$tpl->form_outside("",$form,null,"{create}",
            "LoadAjax('rdpproxy-ssh-key-card','$page?ssh-key-card=yes');","AsSquidAdministrator");
    }
    echo @implode("\n",$html);
    return true;
}

// N'ecrit RIEN en cas de succes.
function ssh_key_create_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $error=rdpproxy_bastion_key_create();
    if($error!=""){
        admin_tracks("Failed to create the RDS Proxy bastion SSH key");
        echo $tpl->post_error($error);return;
    }
    admin_tracks("Created the RDS Proxy bastion SSH key");
}

// Cibles dont la cle deposee deviendrait caduque : SSH + secret = cle du bastion.
function ssh_key_affected(){
    $out=array();
    $err="";
    foreach(rdpproxy_targets($err) as $t){
        if(strtoupper((string)($t["proto_dest"] ?? ""))!="SSH"){continue;}
        if(($t["ssh_secret_kind"] ?? "")!="bastion_key"){continue;}
        $out[]=(string)($t["alias"] ?? "");
    }
    return $out;
}

function ssh_key_regen_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog3("{rdpproxy_ssh_regenerate}","$page?ssh-key-regen-popup=yes",650);
}

function ssh_key_regen_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $aff=ssh_key_affected();
    $n=count($aff);
    $html=array();
    $msg="{rdpproxy_ssh_regenerate_explain}";
    if($n>0){
        $names=array();
        foreach(array_slice($aff,0,10) as $a){$names[]="<code>".str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($a,ENT_QUOTES))."</code>";}
        $more=$n>10?" &hellip;":"";
        $msg.="<br><strong>$n {rdpproxy_ssh_regenerate_affected}</strong>: ".@implode(", ",$names).$more;
    }
    $html[]=$tpl->div_warning("{rdpproxy_ssh_regenerate}||$msg");
    $tpl->field_hidden("ssh-bastion-regenerate","yes");
    $TYPES=array("ed25519"=>"ed25519","rsa"=>"RSA 4096");
    $form[]=$tpl->field_array_hash($TYPES,"ssh_key_type","nonull:{type}","ed25519");
    $html[]=$tpl->form_outside("",$form,null,"{rdpproxy_ssh_regenerate}",
        "dialogInstance3.close();LoadAjax('rdpproxy-ssh-key-card','$page?ssh-key-card=yes');","AsSquidAdministrator");
    echo @implode("\n",$html);
    return true;
}

// N'ecrit RIEN en cas de succes.
function ssh_key_regen_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $type=url_decode_special_tool((string)($_POST["ssh_key_type"] ?? ""))==="rsa"?"rsa":"ed25519";
    $error="";
    $r=rdpproxy_bastion_key_regenerate($type,$error);
    if($error!=""){
        admin_tracks("Failed to regenerate the RDS Proxy bastion SSH key");
        echo $tpl->post_error($error);return;
    }
    admin_tracks("Regenerated the RDS Proxy bastion SSH key ($type), ".intval($r["affected"])." target(s) to redeploy");
}

function target_ciphers_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_target_ciphers}","$page?target-ciphers-popup=yes",850);
}

// Suites TLS proposees aux cibles : une case par suite du catalogue du daemon, jamais
// de saisie libre. Les cases portent l'INDEX de la suite ; le nom est relu cote daemon
// a l'enregistrement, qui refuse de toute facon un nom hors catalogue.
function target_ciphers_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $error="";
    $list=rdpproxy_target_ciphers($error);
    if($error!=""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($error));
        return false;
    }
    $sections=array("recommended"=>"{rdpproxy_ciphers_recommended}",
        "compatible"=>"{rdpproxy_ciphers_compatible}","legacy"=>"{rdpproxy_ciphers_legacy}");
    $form[]=$tpl->field_hidden("RDPProxyTargetCiphersSave","1");
    foreach($sections as $level=>$title){
        $first=true;
        foreach($list as $i=>$c){
            if(!isset($c["level"])||$c["level"]!=$level){continue;}
            if($first){$form[]=$tpl->field_section($title);$first=false;}
            $label=htmlspecialchars((string)$c["name"]);
            if(!empty($c["default"])){$label.=" <span class='label label-primary'>{default}</span>";}
            if($level=="legacy"){$label.=" <span class='label label-warning'>{rdpproxy_cipher_deprecated}</span>";}
            $form[]=$tpl->field_checkbox("tcipher_".intval($i),$label,!empty($c["selected"])?1:0);
        }
    }
    rdpproxy_meta_lock_form($tpl);
    $defaults=rdpproxy_meta_locked()?"":$tpl->button_autnonome("{rdpproxy_ciphers_defaults}",
        "Loadjs('$page?target-ciphers-default-js=yes')",ico_refresh,"AsSquidAdministrator");
    $html[]=$tpl->div_explain("{rdpproxy_target_ciphers}||{rdpproxy_target_ciphers_explain}");
    $html[]="<div style='text-align:right;margin-bottom:10px'>$defaults</div>";
    $html[]=$tpl->form_outside("",$form,null,"{apply}",rdpproxy_close_and_refresh(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function target_ciphers_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    if(rdpproxy_meta_refuse_post(true)){return;}
    $tpl->CLEAN_POST();
    $error="";
    $list=rdpproxy_target_ciphers($error);
    if($error!=""){echo $tpl->post_error($error);return;}
    $names=array();
    foreach($list as $i=>$c){
        if(intval($_POST["tcipher_".intval($i)] ?? 0)==1){$names[]=(string)$c["name"];}
    }
    if(count($names)==0){echo $tpl->post_error("{rdpproxy_ciphers_none}");return;}
    $error=rdpproxy_target_ciphers_save($names);
    if($error!=""){echo $tpl->post_error($error);}
}

function target_ciphers_default_js(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    if(rdpproxy_meta_refuse_js()){return;}
    $error=rdpproxy_target_ciphers_default();
    if($error!=""){$tpl->js_error($error);return;}
    echo rdpproxy_close_and_refresh();
}

function dh_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/dh3072"));
    if(property_exists($json,"Info")){
        $RDPProxyDH3072=$json->Info;
        $form[]=$tpl->field_textareacode("DHPARAM","",$RDPProxyDH3072->Content);
        $html[]= $tpl->form_outside("",$form,null,"",
            rdpproxy_close_and_refresh()
            ,"AsSquidAdministrator",false,true);
        echo $tpl->_ENGINE_parse_body($html);

    }
    return true;
}

function listen_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $RDPProxyListen = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyListen");
    $RDPProxyPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyPort"));
    $IsRDPProxyAuthDebug = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsRDPProxyAuthDebug"));
    if($RDPProxyPort<5){$RDPProxyPort="3389";}
    $jsrestart=$tpl->framework_buildjs("/rdpproxy/restart",
        "squid.rdpproxy.progress","squid.rdpproxy.progress.txt","progress-rdpproxy-restart",
        rdpproxy_refresh_card());
    $form[]=$tpl->field_interfaces("RDPProxyListen","{listen_interface}",$RDPProxyListen);
    if(rdpproxy_meta_locked()){
        $html[]=rdpproxy_meta_banner();
        $form[]=rdpproxy_meta_ro($tpl,"{listen_port}",$RDPProxyPort);
    }else{
        $form[]=$tpl->field_numeric("RDPProxyPort","{listen_port}",$RDPProxyPort);
    }
    $form[]=$tpl->field_checkbox("IsRDPProxyAuthDebug","{debug}",$IsRDPProxyAuthDebug);
    $html[]= $tpl->form_outside("",$form,null,"{apply}",
        "dialogInstance2.close();$jsrestart"
        ,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rdp_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $RDPDisconnectOnLogonUserChange=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPDisconnectOnLogonUserChange"));
    $RDPIgnoreLogonPassword=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPIgnoreLogonPassword"));
    $ForwardCredentials = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ForwardCredentials"));
    $AuthorizeTSElogin  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthorizeTSElogin"));
    // Lus par authhook a chaque connexion (RDPEngine) : ecran de connexion interactif
    // de ReDemPtion quand le client n'a pas prepare ses identifiants, et fermeture
    // sans boite d'erreur d'une connexion refusee.
    $AllowAuthenticateScreen = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowAuthenticateScreen"));
    $RDPRejectErrors = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPRejectErrors"));
    $RDPProxyOCRTesseract=rdpproxy_ocr_checkbox_value((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyOCRTesseract"));
    $ocrLegacy=(rdpproxy_ocr_engine()=="legacy");

    $locked=rdpproxy_meta_locked();
    if($locked){
        $html[]=rdpproxy_meta_banner();
        $form[]=rdpproxy_meta_ro_bool($tpl,"{ignore_logon_password}",$RDPIgnoreLogonPassword);
        $form[]=rdpproxy_meta_ro_bool($tpl,"{DisconnectOnLogonUserChange}",$RDPDisconnectOnLogonUserChange);
    }else{
        $form[]=$tpl->field_checkbox("RDPIgnoreLogonPassword","{ignore_logon_password}",$RDPIgnoreLogonPassword);
        $form[]=$tpl->field_checkbox("RDPDisconnectOnLogonUserChange","{DisconnectOnLogonUserChange}",$RDPDisconnectOnLogonUserChange,false,"{DisconnectOnLogonUserChange_explain}");
    }
    $form[]=$tpl->field_checkbox("AuthorizeTSElogin","{AuthorizeTSElogin}",$AuthorizeTSElogin,"ForwardCredentials","{AuthorizeTSElogin_explain}");
    $form[]=$tpl->field_checkbox("ForwardCredentials","{ForwardCredentials}",$ForwardCredentials,false,"{ForwardCredentials_explain}");
    if($locked){
        $form[]=rdpproxy_meta_ro_bool($tpl,"{AllowAuthenticateScreen}",$AllowAuthenticateScreen);
    }else{
        $form[]=$tpl->field_checkbox("AllowAuthenticateScreen","{AllowAuthenticateScreen}",$AllowAuthenticateScreen,false,"{AllowAuthenticateScreen_explain}");
    }
    if($locked){
        $form[]=rdpproxy_meta_ro_bool($tpl,"{rdpproxy_reject_errors}",$RDPRejectErrors);
        $form[]=rdpproxy_meta_ro_bool($tpl,"{rdpproxy_ocr_tesseract}",$RDPProxyOCRTesseract);
    }else{
        $form[]=$tpl->field_checkbox("RDPRejectErrors","{rdpproxy_reject_errors}",$RDPRejectErrors,false,"{rdpproxy_reject_errors_explain}");
        $form[]=$tpl->field_checkbox("RDPProxyOCRTesseract","{rdpproxy_ocr_tesseract}",$RDPProxyOCRTesseract,false,"{rdpproxy_ocr_tesseract_explain}",$ocrLegacy);
    }
    $html[]= $tpl->form_outside("",$form,null,"{apply}",
        rdpproxy_close_and_refresh()
        ,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}
function tls_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // RDPClientMinTLS / RDPClientCiphers remplacent RDPMinTLS / RDPSslCipher : l'ancienne
    // modale enregistrait 0 (TLS 1.0, suites SHA-1) des qu'on la validait avec ses valeurs
    // par defaut. Valeur vide = TLS 1.2 et suites par defaut (RDPProxy/client_settings.go).
    $RDPClientMinTLS=rdpproxy_client_min_tls();
    $RDPClientCiphers=rdpproxy_client_ciphers();
    $RDPProxySSLCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySSLCertificate"));
    $RDPTlsFallbackLegacy= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPTlsFallbackLegacy"));
    $form[]=$tpl->field_certificate("RDPProxySSLCertificate","{use_certificate_from_certificate_center}",$RDPProxySSLCertificate);

    if(rdpproxy_meta_locked()){
        $html[]=rdpproxy_meta_banner();
        $form[]=rdpproxy_meta_ro_bool($tpl,"{tls_fallback_legacy}",$RDPTlsFallbackLegacy);
        $form[]=rdpproxy_meta_ro($tpl,"{minimal_tls_version}",rdpproxy_client_tls_levels()[$RDPClientMinTLS] ?? $RDPClientMinTLS);
        $form[]=rdpproxy_meta_ro($tpl,"{ssl_ciphers}",rdpproxy_client_cipher_levels()[$RDPClientCiphers] ?? $RDPClientCiphers);
    }else{
        $form[]=$tpl->field_checkbox("RDPTlsFallbackLegacy","{tls_fallback_legacy}",$RDPTlsFallbackLegacy);
        $form[]=$tpl->field_array_hash(rdpproxy_client_tls_levels(),"RDPClientMinTLS","nonull:{minimal_tls_version}",$RDPClientMinTLS);
        $form[]=$tpl->field_array_hash(rdpproxy_client_cipher_levels(),"RDPClientCiphers","nonull:{ssl_ciphers}",$RDPClientCiphers);
    }
    // NLA/Kerberos vers les serveurs : decide par cible (targets.nla_mode, envoye par
    // authhook a chaque session) ; les anciennes cases RDPUseNla/RDPUseKerberos etaient sans effet.
    $form[]=$tpl->field_section("NLA &amp; Kerberos||{rdpproxy_nla_per_target}");
    $html[]= $tpl->form_outside("",$form,null,"{apply}",
        rdpproxy_close_and_refresh()
        ,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function timeouts_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $RDPHandshakeTimeout=rdpproxy_handshake_timeout();
    $RDPSessionTimeout=rdpproxy_session_timeout();

    $RDPKeepAlive=rdpproxy_keepalive();

    $jsrestart=$tpl->framework_buildjs("/rdpproxy/restart",
        "squid.rdpproxy.progress","squid.rdpproxy.progress.txt","progress-rdpproxy-restart",
        rdpproxy_refresh_card());
    rdpproxy_meta_lock_form($tpl);
    $form[]=$tpl->field_numeric("RDPHandshakeTimeout","{RDPHandshakeTimeout} ({seconds})",$RDPHandshakeTimeout);
    $form[]=$tpl->field_numeric("RDPSessionTimeout","{disconnect_after} ({seconds})",$RDPSessionTimeout);
    $form[]=$tpl->field_numeric("RDPKeepAlive","{keep_alive} ({minutes}, 0 = {disabled})",$RDPKeepAlive);
    $form[]=$tpl->field_numeric("RDPCloseBoxTimeout","{rdpproxy_close_box_timeout} ({seconds})",rdpproxy_close_box_timeout(),"{rdpproxy_close_box_timeout_explain}");


    $html[]= $tpl->form_outside("",$form,null,"{apply}",
        "dialogInstance2.close();$jsrestart"
        ,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table_start(){

    $page=CurrentPageName();

    echo "<div id='rdpproxy-status-table' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-status-table','$page?table=yes');</script>";
}

function authook(){
    $tpl=new template_admin();
    $page=CurrentPageName();


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.rdpproxy.auth.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.rdpproxy.auth.progress.txt";
    $ARRAY["CMD"]="/rdpproxy/auth/restart";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="blur()";
    $prgress=base64_encode(serialize($ARRAY));
    $restartService="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-rdpproxy-restart');";


    // IsRDPProxyAuthDebug est la cle lue par authhook ; l'ancienne RDPProxyAuthookDebug n'etait lue par
    // personne (ticket RWE #1793376467 : debug « actif » sans aucune ligne [DEBUG]).
    $IsRDPProxyAuthDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsRDPProxyAuthDebug"));
    $form[]=$tpl->field_checkbox("IsRDPProxyAuthDebug","{debug}",$IsRDPProxyAuthDebug);
    echo $tpl->form_outside("",$form,null,"{apply}",$restartService,"AsSquidAdministrator");
    echo "<script>".main_tiny_default("{APP_RDPPROXY_AUTHHOOK}: {parameters}")."</script>";

}

//profile=baseline preset=ultrafast flags=+qscale b=30000
// Valeurs effectives de l'enregistrement video, defauts compris : lues une seule
// fois pour les tuiles ET les deux fenetres.
function video_values():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $v=array();
    $v["RDPProxyVideoRententionDays"]=intval($s->GET_INFO("RDPProxyVideoRententionDays"));
    if($v["RDPProxyVideoRententionDays"]==0){$v["RDPProxyVideoRententionDays"]=365;}
    $v["RDPProxyVideoPath"]=trim((string)$s->GET_INFO("RDPProxyVideoPath"));
    if($v["RDPProxyVideoPath"]==""){$v["RDPProxyVideoPath"]="/home/artica/rds/videos";}
    $v["RDPProxyVideoPreset"]=trim((string)$s->GET_INFO("RDPProxyVideoPreset"));
    if($v["RDPProxyVideoPreset"]==""){$v["RDPProxyVideoPreset"]="ultrafast";}
    $v["RDPProxyVideoTun"]=trim((string)$s->GET_INFO("RDPProxyVideoTun"));
    $v["RDPProxyVideoBitRate"]=intval($s->GET_INFO("RDPProxyVideoBitRate"));
    if($v["RDPProxyVideoBitRate"]==0){$v["RDPProxyVideoBitRate"]=30000;}
    $v["RDPProxyVideoFrameRate"]=intval($s->GET_INFO("RDPProxyVideoFrameRate"));
    if($v["RDPProxyVideoFrameRate"]==0){$v["RDPProxyVideoFrameRate"]=5;}
    return $v;
}

function video_presets():array{
    $z=array();
    foreach(array("ultrafast","superfast","veryfast","faster","fast","medium","slow","slower","veryslow") as $p){
        $z[$p]=$p;
    }
    return $z;
}

function video_tunes():array{
    $z=array();
    foreach(array("film","animation","grain","stillimage","zerolatency") as $t){
        $z[$t]=$t;
    }
    return $z;
}

function video_framerates():array{
    $z=array();
    for($i=1;$i<41;$i++){$z[$i]=$i;}
    return $z;
}

// Apres un enregistrement : fermer la fenetre, redemarrer le service, puis
// recharger les tuiles pour qu'elles montrent la valeur ecrite.
function video_jsafter():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs("/rdpproxy/restart",
        "squid.rdpproxy.progress","squid.rdpproxy.progress.txt","progress-rdpproxy-restart",
        "LoadAjaxSilent('rdpproxy-video-flat','$page?video-flat=yes');");
    return "dialogInstance2.close();$jsrestart";
}

// Onglet « Enregistrement video » : un conteneur, les tuiles s'y chargent et y
// sont rechargees apres chaque enregistrement.
function video_params(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-video-flat'></div>
    <script>LoadAjaxSilent('rdpproxy-video-flat','$page?video-flat=yes');</script>";
}

// ---------------------------------------------------------------------------
// Onglet « Enregistrement video » (?video-flat=yes) -- maquette
// /home/claude/images/stitch_redemption_rdp_video.zip.
//
// Un bandeau qui explique la fonction (3 etapes), puis deux cartes : Stockage et
// Qualite video, chacune avec SON bouton « Modifier » (les fenetres existantes).
// ReDemPtion sans FFmpeg : etape 2 grisee, encadre d'avertissement, bouton qualite
// desactive ; le stockage reste modifiable, la retention purge aussi les
// enregistrements bruts. Reutilise les briques rst-* des reglages (rst_css()).
// ---------------------------------------------------------------------------

function rvd_css():string{
    $ac=rst_accents();
    $a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];$al=$ac["accent_line"];
    return "<style>
.rvd{max-width:1280px;margin-top:14px;display:flex;flex-direction:column;gap:24px;color:#1e293b}
.rvd-hero{background:#fff;border:1px solid #e2e8f0;border-left:4px solid $a;border-radius:8px;padding:26px 28px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.rvd-hero-top{display:flex;justify-content:space-between;align-items:flex-start;gap:24px}
.rvd-hero h2{font-size:22px;font-weight:600;color:#1e293b;margin:0;line-height:1.35}
.rvd-hero p{font-size:15px;color:#475569;line-height:1.6;margin:8px 0 0 0;max-width:820px}
.rvd-link{flex:0 0 auto;display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:$ad;text-decoration:none;margin-top:4px}
.rvd-link:hover{text-decoration:underline;color:$ad}
.rvd-link:focus{outline:2px solid $a;outline-offset:2px}
.rvd-flow{display:flex;align-items:center;gap:16px;margin-top:24px;padding-top:22px;border-top:1px solid #eef2f6}
.rvd-step{flex:1 1 0;display:flex;align-items:center;gap:12px;min-width:0}
.rvd-step-t{font-size:14px;font-weight:600;color:#1e293b;line-height:1.3}
.rvd-step-s{font-size:12px;color:#64748b;line-height:1.45;margin-top:2px}
.rvd-step-off .ak-ico{--ak-tbg:#f1f5f9;--ak-tfg:#94a3b8}
.rvd-step-off .rvd-step-t{color:#64748b;text-decoration:line-through}
.rvd-arrow{flex:0 0 auto;color:#cbd5e1;font-size:16px}
.rvd-ok{margin-top:18px}
.rvd-warn{display:flex;gap:12px;margin-top:18px;padding:14px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;color:#92400e;font-size:13px;line-height:1.55}
.rvd-warn b{display:block;color:#78350f;font-size:14px;margin-bottom:2px}
.rvd-cards{display:flex;gap:24px;align-items:stretch}
.rvd-cards > .rst-card{flex:1 1 0;min-width:0}
@media(max-width:1200px){.rvd-cards{flex-direction:column}.rvd-flow{flex-wrap:wrap}.rvd-arrow{display:none}}
.rvd-block{padding:18px 0;border-bottom:1px solid #eef2f6}
.rvd-block:last-child{border-bottom:0;padding-bottom:4px}
.rvd-block-l{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:500;color:#334155}
.rvd-big{display:flex;align-items:baseline;gap:8px;margin-top:8px}
.rvd-big b{font-size:32px;font-weight:700;color:#0f172a;line-height:1;font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace}
.rvd-big span{font-size:13px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
.rvd-help{font-size:12px;color:#64748b;margin-top:6px;line-height:1.45}
.rvd-path{display:inline-block;margin-top:10px;font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#0f172a;background:#f1f5f9;border:1px solid #cbd5e1;border-radius:5px;padding:7px 12px;word-break:break-all;user-select:all}
.rvd-approx{display:block;font-size:12px;color:#64748b;font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace;margin-top:3px}
.rvd-dis{color:#94a3b8 !important}
.rvd .ak-btn[disabled]{cursor:not-allowed}
.rvd-note{display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 18px;font-size:13px;font-weight:500;color:#334155}
.rvd-note .rst-ico{color:#2563eb;font-size:16px;padding:0}
.rvd .rst-sub br:last-child,.rvd-help br:last-child,.rvd-note br:last-child,.rvd-hero p br:last-child{display:none}
</style>";
}

function rvd_flow_step($ico,$title,$sub,$off=false):string{
    $cls=$off?"rvd-step rvd-step-off":"rvd-step";
    return "<div class='$cls'><span class='ak-ico'><i class='$ico'></i></span>".
        "<div><div class='rvd-step-t'>$title</div><div class='rvd-step-s'>$sub</div></div></div>";
}

// Disponible : pastille verte. Absent : l'avertissement existant, dans le bandeau.
function rvd_encoder_status($videoOK):string{
    if($videoOK){
        return "<span class='ak-pill ak-ok rvd-ok'><i class='fa-solid fa-check'></i>{rdpproxy_video_encoder_ok}</span>";
    }
    return "<div class='rvd-warn' role='status'><span style='font-size:18px;color:#d97706'><i class='fa-solid fa-triangle-exclamation'></i></span>".
        "<div><b>{rdpproxy_video_unavailable}</b>{rdpproxy_video_unavailable_explain}</div></div>";
}

function rvd_hero($videoOK):string{
    $arrow="<span class='rvd-arrow'><i class='fa-solid fa-arrow-right'></i></span>";
    $text="{rdpproxy_video_hero_intro}";
    // Phrase SSH seulement quand le bastion relaie SSH : sinon elle promettrait un enregistrement qui n'existe pas.
    if(rdpproxy_ssh_supported()){$text.=" {rdpproxy_ssh_always_recorded}.[br]";}
    $text.=" {rdpproxy_video_hero_purge}";
    $h=array();
    $h[]="<section class='rvd-hero'>";
    $h[]="<div class='rvd-hero-top'><div><h2>{rdpproxy_video_hero_title}</h2><p>$text</p></div>";
    $h[]="<a class='rvd-link' href='/rdpproxy-connections'>{rdpproxy_video_enable_link}<i class='fa-solid fa-arrow-right'></i></a></div>";
    $h[]="<div class='rvd-flow'>";
    $h[]=rvd_flow_step("fa-solid fa-circle-dot","{rdpproxy_video_step_record}","{rdpproxy_video_step_record_explain}");
    $h[]=$arrow;
    $h[]=rvd_flow_step("fa-solid fa-gear","{rdpproxy_video_step_convert}","{rdpproxy_video_step_convert_explain}",!$videoOK);
    $h[]=$arrow;
    $h[]=rvd_flow_step("fa-regular fa-clock","{rdpproxy_video_step_purge}","{rdpproxy_video_step_purge_explain}");
    $h[]="</div>";
    $h[]=rvd_encoder_status($videoOK);
    $h[]="</section>";
    return @implode("",$h);
}

function rvd_card_storage($tpl,$v):string{
    $page=CurrentPageName();
    $button="";
    if($tpl->IsSecurity("AsSquidAdministrator")){
        $button="<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?video-storage-js=yes')\"><i class='fa-solid fa-pen'></i>{modify}</button>";
    }
    $rows=array();
    $rows[]="<div class='rvd-block'><div class='rvd-block-l'>".rst_ico("fa-regular fa-clock")."{retention_time}</div>".
        "<div class='rvd-big'><b>".intval($v["RDPProxyVideoRententionDays"])."</b><span>{days}</span></div>".
        "<div class='rvd-help'>{rdpproxy_video_retention_help}</div></div>";
    $rows[]="<div class='rvd-block'><div class='rvd-block-l'>".rst_ico("fa-regular fa-folder")."{storage_directory}</div>".
        "<span class='rvd-path'>".htmlspecialchars($v["RDPProxyVideoPath"],ENT_QUOTES)."</span></div>";
    return rst_card("fa-solid fa-database","{storage}","{rdpproxy_video_storage_explain}",$rows,$button);
}

// 30000 -> "≈ 30 kbit/s" ; 2500000 -> "≈ 2.5 Mbit/s".
function rvd_bitrate_approx($b):string{
    if($b>=1000000){return "&asymp; ".round($b/1000000,1)." Mbit/s";}
    return "&asymp; ".round($b/1000,1)." kbit/s";
}

function rvd_card_quality($tpl,$v,$videoOK):string{
    $page=CurrentPageName();
    $button="";
    if($tpl->IsSecurity("AsSquidAdministrator")){
        if($videoOK){
            $button="<button type='button' class='ak-btn' OnClick=\"Loadjs('$page?video-quality-js=yes')\"><i class='fa-solid fa-pen'></i>{modify}</button>";
        }else{
            $button="<button type='button' class='ak-btn' disabled title=\"{rdpproxy_video_unavailable}\"><i class='fa-solid fa-pen'></i>{modify}</button>";
        }
    }
    $dis=$videoOK?"":" rvd-dis";
    $tune=$v["RDPProxyVideoTun"]==""?"{none}":htmlspecialchars($v["RDPProxyVideoTun"],ENT_QUOTES);
    $bitrate=intval($v["RDPProxyVideoBitRate"]);
    $rows=array();
    $rows[]=rst_row("fa-solid fa-bolt","{RDPProxyVideoPreset}","{rdpproxy_video_preset_help}",
        "<span class='ak-chip$dis'>".htmlspecialchars($v["RDPProxyVideoPreset"],ENT_QUOTES)."</span>");
    $rows[]=rst_row("fa-solid fa-sliders","{RDPProxyVideoTun}","{rdpproxy_video_tune_help}",
        "<span class='ak-chip$dis'>$tune</span>");
    $rows[]=rst_row("fa-solid fa-film","{framerate}","{rdpproxy_video_framerate_help}",
        "<span class='ak-chip$dis'>".intval($v["RDPProxyVideoFrameRate"])." i/s</span>");
    $rows[]=rst_row("fa-solid fa-chart-simple","{RDPProxyVideoBitRate}","{rdpproxy_video_bitrate_help}",
        "<div><span class='ak-chip$dis'>".$tpl->FormatNumber($bitrate)." bit/s</span><span class='rvd-approx'>".rvd_bitrate_approx($bitrate)."</span></div>");
    return rst_card("fa-solid fa-video","{video_quality}","{rdpproxy_video_quality_explain}",$rows,$button);
}

// Vrai : video_jsafter() appelle /rdpproxy/restart apres chaque enregistrement.
function rvd_restart_note():string{
    return "<div class='rvd-note'>".rst_ico("fa-solid fa-circle-info")."<span>{rdpproxy_video_restart_note}</span></div>";
}

function video_flat():bool{
    $tpl=new template_admin();
    $v=video_values();
    $videoOK=rdpproxy_video_supported();
    $html=array();
    $html[]="<div class='rvd ak'>";
    $html[]=rvd_hero($videoOK);
    $html[]="<div class='rvd-cards'>".rvd_card_storage($tpl,$v).rvd_card_quality($tpl,$v,$videoOK)."</div>";
    $html[]=rvd_restart_note();
    // video_jsafter() redemarre le service : les sessions Web gardees sont fermees a l'arret.
    if(rdpproxy_web_keep_state()["supported"]){
        $html[]="<div class='rvd-note'>".rst_ico("fa-solid fa-triangle-exclamation")."<span>{rdpproxy_webkeep_restart_warning}</span></div>";
    }
    $html[]="</div>";
    echo rst_css().rvd_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    echo "<script>".main_tiny_default("{video_recording}")."</script>";
    return true;
}

function video_storage_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{video_recording}: {storage}","$page?video-storage-popup=yes",650);
}

function video_quality_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{video_recording}: {video_quality}","$page?video-quality-popup=yes",650);
}

function video_storage_popup():bool{
    $tpl=new template_admin();
    $v=video_values();
    $form[]=$tpl->field_numeric("RDPProxyVideoRententionDays","{retention_time} ({days})",$v["RDPProxyVideoRententionDays"]);
    $form[]=$tpl->field_browse_directory("RDPProxyVideoPath","{storage_directory}",$v["RDPProxyVideoPath"]);
    echo $tpl->form_outside("",$form,null,"{apply}",video_jsafter(),"AsSquidAdministrator");
    return true;
}

function video_quality_popup():bool{
    $tpl=new template_admin();
    $v=video_values();
    $form[]=$tpl->field_array_hash(video_presets(),"RDPProxyVideoPreset","nonull:{RDPProxyVideoPreset}",
        $v["RDPProxyVideoPreset"],false,"{RDPProxyVideoPreset_explain}");
    $form[]=$tpl->field_array_hash(video_tunes(),"RDPProxyVideoTun","nonull:{RDPProxyVideoTun}",
        $v["RDPProxyVideoTun"],false,"{RDPProxyVideoTun_explain}");
    $form[]=$tpl->field_array_hash(video_framerates(),"RDPProxyVideoFrameRate","nonull:{framerate}",
        $v["RDPProxyVideoFrameRate"],false,null);
    $form[]=$tpl->field_numeric("RDPProxyVideoBitRate","{RDPProxyVideoBitRate}",$v["RDPProxyVideoBitRate"]);
    echo $tpl->form_outside("",$form,null,"{apply}",video_jsafter(),"AsSquidAdministrator");
    return true;
}
function authhook_debug_save(){
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS}");return;}
    $v=intval($_POST["IsRDPProxyAuthDebug"])==1?1:0;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("IsRDPProxyAuthDebug",$v);
    admin_tracks("RDS Proxy authenticator debug set to $v");
}

function video_params_save(){
    $tpl=new template_admin();
    // Masquer le bouton « Appliquer » ne protege pas le POST lui-meme.
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS}");return;}
    // SAVE_POSTs ecrit toute cle postee : sous verrou Meta, une cle du catalogue = refus, rien d'ecrit.
    if(rdpproxy_meta_refuse_post()){return;}
    rdpproxy_meta_strip_lock_keys();
    $tpl->SAVE_POSTs();
    $error=rdpproxy_videos_scan();
    if($error!=""){echo $tpl->post_error($error);return;}
    admin_tracks_post("Saving RDP proxy video recording / auth hook settings");
}

// Les huit messages que voit un utilisateur RDP refuse. Valeurs de repli
// reprises telles quelles de l'ancienne fonction settings(): ce sont celles que
// le daemon affiche quand le reglage est vide, les changer changerait le
// comportement observe sans le dire.
function rdpproxy_error_defaults():array{
    return array(
        1=>"No password entered in your Remote Desktop client",
        2=>"Internal Error, please contact your administrator",
        3=>"You are not authorized to use this method",
        4=>"Your computer is not authorized to use this method",
        5=>"Permission denied",
        6=>"Please prepare credentials before connecting",
        7=>"Non-existent user",
        8=>"No password typed",
        // authhook >= 1.9.11 : refus d'authentification detailles (login inconnu ou mauvais
        // mot de passe = 9, compte verrouille/desactive/expire = 10)
        9=>"Invalid login or password",
        10=>"Your account is not allowed to sign in (locked, disabled or expired)");
}

// Le message courant d'un index, et s'il vient du reglage ou du repli.
function rdpproxy_error_message($index):array{
    $defaults=rdpproxy_error_defaults();
    $fallback=isset($defaults[$index])?$defaults[$index]:"";
    $value=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDP_ERROR_MSG$index"));
    if($value==""){return array("text"=>$fallback,"custom"=>false);}
    return array("text"=>$value,"custom"=>true);
}

function errmsg_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{error_messages_customization}","$page?errmsg-popup=yes",720);
}

function errmsg_popup():bool{
    $tpl=new template_admin();
    $form=array();
    for($i=1;$i<=count(rdpproxy_error_defaults());$i++){
        $m=rdpproxy_error_message($i);
        $form[]=$tpl->field_text("RDP_ERROR_MSG$i","{error} $i",$m["text"],true);
    }
    $html=array();
    $html[]=$tpl->div_explain("{error_messages_customization}||{rdpproxy_errmsg_section_explain}");
    // La carte est celle de l'onglet « Modele » : c'est elle qu'il faut recharger.
    $page=CurrentPageName();
    $html[]=$tpl->form_outside("",@implode("\n",$form),null,"{apply}",
        "dialogInstance2.close();LoadAjaxSilent('rdpproxy-theme','$page?rdpproxy-theme=yes');","AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// Fermer NE SUFFIT PAS: form_outside ne touche a rien d'autre sur la page, et
// la carte continuerait d'afficher l'ancienne valeur -- ce qui se lit comme un
// enregistrement rate, et fait recommencer.
function rdpproxy_close_and_refresh():string{
    return "dialogInstance2.close();".rdpproxy_refresh_card();
}

function rdpproxy_refresh_card():string{
    $page=CurrentPageName();
    return "LoadAjaxSilent('rdpproxy-config','$page?settings=yes');";
}

// ---------------------------------------------------------------------------
// Reglages de l'onglet « Etat » (?settings=yes) -- maquette
// /home/claude/images/stitch_redemption_rdp_settings_flat.zip.
//
// Quatre cartes sur deux colonnes : Ecoute reseau + Bastion et detection a
// gauche, Chiffrement + Session RDP a droite. Deux piles independantes plutot
// qu'une grille : une grille aligne ses rangees, et la carte Session (7 lignes)
// laisserait un trou sous la carte Ecoute (2 lignes).
//
// Une ligne seule ouvre SA fenetre (crayon, ligne cliquable) ; la carte Session
// n'a qu'un bouton « Modifier » dans son en-tete, parce que ses 7 reglages
// s'editent dans la meme fenetre (rdp-js). Sans le privilege, ni crayon ni bouton.
// Un widget = une fonction ; la feuille sort du parseur (accolades = {jetons}).
// ---------------------------------------------------------------------------

/** Accents de la marque (memes valeurs que la maquette sur un boitier de serie). */
function rst_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

function rst_css():string{
    $ac=rst_accents();
    $a=$ac["accent"];$ad=$ac["accent_dark"];$as=$ac["accent_soft"];$al=$ac["accent_line"];
    return "<style>
.rst{color:#1e293b;margin-top:14px}
.rst-label{margin:0 0 14px 2px}
.rst-label h2{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#334155;margin:0 0 2px 0}
.rst-label p{font-size:12px;color:#64748b;margin:0}
.rst-cols{display:flex;gap:24px;align-items:flex-start}
.rst-col{flex:1 1 0;min-width:0;display:flex;flex-direction:column;gap:24px}
@media(max-width:1439px){.rst-cols{flex-direction:column}.rst-col{width:100%}}
.rst-card{padding:18px 20px}
.rst-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:12px;border-bottom:1px solid #eef2f6}
.rst-head-l{display:flex;align-items:center;gap:10px;min-width:0}
.rst-head h3{font-size:15px;font-weight:600;color:#0f172a;margin:0;line-height:1.35}
.rst-head p{font-size:12px;color:#64748b;margin:1px 0 0 0;line-height:1.4}
.rst-row{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:12px 8px;margin:0 -8px;border-bottom:1px solid #eef2f6;border-radius:6px}
.rst-row:last-child{border-bottom:0}
.rst-row-click{cursor:pointer}
.rst-row-click:hover{background:#f8fafc}
.rst-row-click:focus,.rst-link:focus{outline:2px solid $a;outline-offset:2px}
.rst-row-l{display:flex;align-items:flex-start;gap:10px;min-width:0;flex:1 1 auto}
.rst-ico{flex:0 0 16px;font-size:13px;color:#64748b;padding-top:2px;text-align:center}
.rst-name{font-size:14px;font-weight:500;color:#1e293b;line-height:1.4}
.rst-sub{font-size:12px;color:#64748b;line-height:1.45;margin-top:1px}
.rst-row-r{flex:0 1 auto;max-width:48%;display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap;text-align:right}
.rst-pen{font-size:12px;color:#cbd5e1;flex:0 0 auto}
.rst-row-click:hover .rst-pen,.rst-row-click:focus .rst-pen{color:$ad}
.rst-pairs{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:5px;padding:5px 8px;line-height:1.6}
.rst-pairs b{color:#0f172a;font-weight:600}
.rst-note{display:flex;align-items:flex-start;gap:6px;margin-top:6px;font-size:12px;line-height:1.45;padding:5px 8px;border-radius:5px;border:1px solid}
.rst-note-warn{background:#fffbeb;color:#92400e;border-color:#fde68a}
.rst-note-info{background:#f8fafc;color:#475569;border-color:#e2e8f0}
.rst-count{font-size:14px;font-weight:600;color:#1e293b;white-space:nowrap}
.rst-link{display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:600;color:$ad;cursor:pointer;text-decoration:none}
.rst-link:hover{text-decoration:underline;color:$ad}
</style>";
}

function rst_ico($ico,$class="rst-ico"):string{
    return "<span class='$class'><i class='$ico'></i></span>";
}

// Pastille d'etat : le texte dit toujours l'etat, la couleur ne fait que le souligner.
function rst_state_pill($on):string{
    if($on){return "<span class='ak-pill ak-ok'><i class='fa-solid fa-check'></i>{enabled}</span>";}
    return "<span class='ak-pill ak-off'><i class='fa-solid fa-minus'></i>{disabled}</span>";
}

function rst_reduced_pill():string{
    return "<span class='ak-pill ak-warn'><i class='fa-solid fa-triangle-exclamation'></i>{rdpproxy_reduced_security}</span>";
}

// Ligne de reglage. $js non vide (et privilege accorde par l'appelant) = ligne cliquable
// au clavier comme a la souris, crayon a droite.
function rst_row($ico,$name,$sub,$right,$js="",$note=""):string{
    $attrs="";$cls="rst-row";$pen="";
    if($js<>""){
        $cls.=" rst-row-click";
        $attrs=" role='button' tabindex='0' OnClick=\"$js\" OnKeyDown=\"if(event.keyCode==13||event.keyCode==32){event.preventDefault();$js}\"";
        $pen="<span class='rst-pen'><i class='fa-solid fa-pen'></i></span>";
    }
    $h=array();
    $h[]="<div class='$cls'$attrs>";
    $h[]="<div class='rst-row-l'>".rst_ico($ico)."<div style='min-width:0'>";
    $h[]="<div class='rst-name'>$name</div>";
    if($sub<>""){$h[]="<div class='rst-sub'>$sub</div>";}
    $h[]=$note;
    $h[]="</div></div>";
    $h[]="<div class='rst-row-r'>$right$pen</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function rst_card($ico,$title,$explain,$rows,$button=""):string{
    $h=array();
    $h[]="<section class='ak-card rst-card'>";
    $h[]="<div class='rst-head'><div class='rst-head-l'>".rst_ico($ico,"ak-ico ak-ico-sm ak-ico-sq");
    $h[]="<div><h3>$title</h3><p>$explain</p></div></div>$button</div>";
    $h[]=@implode("",$rows);
    $h[]="</section>";
    return @implode("",$h);
}

// JS d'une ligne, vide sans le privilege : la ligne reste lisible, plus rien a cliquer.
function rst_js($tpl,$js,$privilege="AsSquidAdministrator"):string{
    if(!$tpl->IsSecurity($privilege)){return "";}
    return $js;
}

function rst_section_label($port):string{
    return "<div class='rst-label'><h2>{rdpproxy_network_section}</h2>".
        "<p>{rdpproxy_network_section_explain} &mdash; {listen_port}: $port</p></div>";
}

function rst_card_listen($tpl,$listen,$port):string{
    $page=CurrentPageName();
    $rows=array();
    $rows[]=rst_row("fa-solid fa-ethernet","{listen_interface}","{rdpproxy_listen_explain}",
        "<span class='ak-chip'>".htmlspecialchars("$listen:$port",ENT_QUOTES)."</span>",
        rst_js($tpl,"Loadjs('$page?listen-js=yes')"));

    // Les delais en paires empilees : en phrase, ils etaient coupes a 45% de la ligne.
    $pairs=array();
    $pairs[]="{rdpproxy_handshake_short}: <b>".rdpproxy_handshake_timeout()." s</b>";
    $pairs[]="{disconnect_after}: <b>".rdpproxy_session_timeout()." s</b>";
    $keep=rdpproxy_keepalive();
    if($keep>0){$pairs[]="{keep_alive}: <b>$keep {minutes}</b>";}
    $pairs[]="{rdpproxy_close_box_short}: <b>".rdpproxy_close_box_timeout()." s</b>";
    $rows[]=rst_row("fa-regular fa-clock","{timeouts}","{rdpproxy_timeouts_explain}",
        "<div class='rst-pairs'>".@implode("<br>",$pairs)."</div>",
        rst_js($tpl,"Loadjs('$page?timeouts-js=yes')"));

    // Lecture seule : le mode verbeux se regle dans l'onglet de l'authentificateur.
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsRDPProxyAuthDebug"))==1){
        $rows[]=rst_row("fa-solid fa-bug","{debug}","",
            "<span class='ak-pill ak-warn'><i class='fa-solid fa-circle' style='font-size:6px'></i>{active2}</span>");
    }
    return rst_card("fa-solid fa-network-wired","{rdpproxy_card_listen}","{rdpproxy_card_listen_explain}",$rows);
}

function rst_card_crypto($tpl,$dhSize):string{
    $page=CurrentPageName();
    $rows=array();
    $MinTLS=rdpproxy_client_tls_levels();
    $Ciphers=rdpproxy_client_cipher_levels();
    $cert=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySSLCertificate"));
    $cert=($cert=="")?"{default}":htmlspecialchars($cert,ENT_QUOTES);
    $sub="{certificate}: $cert &bull; {ssl_ciphers}: ".$Ciphers[rdpproxy_client_ciphers()];
    $note="";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPTlsFallbackLegacy"))==1){
        $note="<div class='rst-note rst-note-warn'><i class='fa-solid fa-triangle-exclamation' style='margin-top:2px'></i><span>{tls_fallback_legacy}</span></div>";
    }
    $rows[]=rst_row("fa-solid fa-lock","{tls_title}",$sub,
        "<span class='ak-pill ak-ok'>{minimal_tls_version}: ".$MinTLS[rdpproxy_client_min_tls()]."</span>",
        rst_js($tpl,"Loadjs('$page?tls-js=yes')"),$note);

    $cerr="";
    $ciphers=rdpproxy_target_ciphers($cerr);
    if($cerr==""){
        $nsel=0;$legacy=0;
        foreach($ciphers as $c){
            if(empty($c["selected"])){continue;}
            $nsel++;
            if(isset($c["level"])&&$c["level"]=="legacy"){$legacy++;}
        }
        $right="<span class='ak-chip'>$nsel / ".count($ciphers)."</span>";
        if($legacy>0){$right.="<span class='ak-pill ak-warn'>$legacy {rdpproxy_cipher_deprecated}</span>";}
        $rows[]=rst_row("fa-solid fa-server","{rdpproxy_target_ciphers}","{rdpproxy_target_ciphers_short}",
            $right,rst_js($tpl,"Loadjs('$page?target-ciphers-js=yes')"));
    }
    $perr="";
    $tlsp=rdpproxy_tls_policies($perr);
    if($perr==""){
        $right="<span class='ak-chip'>".count($tlsp)."</span>";
        $legacy=0;
        foreach($tlsp as $p){if(intval($p["tls_min_level"])<2||!empty($p["rdp_legacy"])){$legacy++;}}
        if($legacy>0){$right.="<span class='ak-pill ak-warn'>$legacy {rdpproxy_tls_policy_lowered}</span>";}
        $rows[]=rst_row("fa-solid fa-scale-unbalanced","{rdpproxy_tls_policies}","{rdpproxy_tls_policies_short}",
            $right,rst_js($tpl,"Loadjs('$page?tls-policies-js=yes')"));
    }

    if($dhSize>10){
        $rows[]=rst_row("fa-solid fa-key","{dh_parameters}","",
            "<span class='ak-chip'>$dhSize {bytes}</span>",rst_js($tpl,"Loadjs('$page?dh-js=yes')"));
    }
    return rst_card("fa-solid fa-shield-halved","{encryption}","{rdpproxy_card_crypto_explain}",$rows);
}

function rst_card_bastion($tpl):string{
    $page=CurrentPageName();
    $rows=array();
    // Le contenu de la cle n'est lu qu'a l'ouverture de la fenetre (la premiere lecture cree la paire).
    if(rdpproxy_ssh_supported()){
        $js=rst_js($tpl,"Loadjs('$page?ssh-key-js=yes')");
        $right=($js=="")?"":"<button type='button' class='ak-btn' OnClick=\"$js\"><i class='fa-solid fa-terminal'></i>{rdpproxy_ssh_show_key}</button>";
        $rows[]=rst_row("fa-solid fa-key","{rdpproxy_ssh_bastion_key}","{rdpproxy_ssh_bastion_key_short}",$right);
    }else{
        // Lecture seule : la cause vient de /rdpproxy/state, rien a editer ici.
        $note="<div class='rst-note rst-note-info'><i class='fa-solid fa-circle-info' style='margin-top:2px'></i><span>".rdpproxy_ssh_unavailable_text($tpl)."</span></div>";
        $rows[]=rst_row("fa-solid fa-key","{rdpproxy_ssh_targets}","",
            "<span class='ak-pill ak-off'>{unavailable}</span>","",$note);
    }

    $gperr="";
    $gpn=count(rdpproxy_patterns(0,$gperr));
    if($gperr==""){
        // Meme privilege que les ecritures de fw.rdpproxy.connections.php (rdpx_pattern_*).
        $js=rst_js($tpl,"Loadjs('fw.rdpproxy.connections.php?pattern-global-js=yes')","AllowAddUsers");
        $right="<span class='rst-count'>$gpn {patterns}</span>";
        if($js<>""){
            $right.="<a class='rst-link' href=\"javascript:blur();\" OnClick=\"$js\"><i class='fa-solid fa-sliders'></i>{manage}</a>";
        }
        $rows[]=rst_row("fa-solid fa-filter","{rdpproxy_global_patterns}","",$right);
    }
    return rst_card("fa-solid fa-terminal","{rdpproxy_card_bastion}","{rdpproxy_card_bastion_explain}",$rows);
}

function rst_card_session($tpl):string{
    $page=CurrentPageName();
    $get=function($key){return intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($key))==1;};
    $ocr=rdpproxy_ocr_checkbox_value((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyOCRTesseract"))==1
        && rdpproxy_ocr_engine()!="legacy";

    // icone, libelle, sous-titre, etat, « securite reduite » quand actif
    $items=array(
        array("fa-solid fa-lock","{ignore_logon_password}","{ignore_logon_password_explain}",$get("RDPIgnoreLogonPassword"),true),
        array("fa-solid fa-user-clock","{DisconnectOnLogonUserChange}","{rdpproxy_userchange_short}",$get("RDPDisconnectOnLogonUserChange"),false),
        array("fa-solid fa-display","{AuthorizeTSElogin}","{rdpproxy_tse_short}",$get("AuthorizeTSElogin"),true),
        array("fa-solid fa-right-left","{ForwardCredentials}","{rdpproxy_forward_short}",$get("ForwardCredentials"),true),
        array("fa-regular fa-window-maximize","{AllowAuthenticateScreen}","",$get("AllowAuthenticateScreen"),false),
        array("fa-solid fa-ban","{rdpproxy_reject_errors}","",$get("RDPRejectErrors"),false),
        array("fa-regular fa-eye","{rdpproxy_ocr_tesseract}","",$ocr,false),
    );
    $rows=array();
    foreach($items as $it){
        $right=rst_state_pill($it[3]);
        if($it[3] && $it[4]){$right=rst_reduced_pill().$right;}
        $rows[]=rst_row($it[0],$it[1],$it[2],$right);
    }
    $button="";
    $js=rst_js($tpl,"Loadjs('$page?rdp-js=yes')");
    if($js<>""){
        $button="<button type='button' class='ak-btn' OnClick=\"$js\"><i class='fa-solid fa-pen-to-square'></i>{modify}</button>";
    }
    return rst_card("fa-solid fa-users-gear","{rdpproxy_session_section}","{rdpproxy_session_section_explain}",$rows,$button);
}

// ------------------------------------------------------- NLA client (Kerberos)
// Carte "NLA authentication (Kerberos)" : instance NLA dediee du bastion, jointe a l'AD par
// un compte machine dedie (routes /rdpproxy/nla*). Sans licence : cadenas, aucune action, sauf
// "Quitter le domaine" quand le bastion est joint (desescalade : la route leave n'a pas de
// garde de licence cote daemon). Les controles (kinit, horloge, SPN) ne partent qu'a la demande.
function rdpproxy_nla_reason_text($reason):string{
    $reason=strtolower(trim((string)$reason));
    if($reason==""){return "";}
    $known=array("license_required","disabled","ad_disabled","engine_outdated","authhook_outdated","not_joined","keytab_invalid",
        "not_installed","port_conflict","listen_unavailable","not_running","config_failed","start_failed");
    if(in_array($reason,$known,true)){return "{rdpproxy_nla_reason_$reason}";}
    return htmlspecialchars($reason,ENT_QUOTES);
}

// Sources autorisees du port NLA (RDPProxyNLAAllowedSources) : liste, ou "toutes les
// sources" avec une pastille d'avertissement. Liste enregistree illisible : le pare-feu
// n'ouvre rien (echec ferme), la carte le dit au lieu d'afficher "toutes".
function rdpproxy_nla_sources_cell($st):string{
    if(trim((string)($st["sources_error"] ?? ""))!=""){
        return "<span class='ak-pill ak-off' style='white-space:normal'><i class='fa-solid fa-circle-xmark'></i>{rdpproxy_nla_sources_invalid}</span>";
    }
    $sources=(isset($st["sources"])&&is_array($st["sources"]))?$st["sources"]:array();
    if(count($sources)==0){
        return "<span class='ak-chip'>{rdpproxy_nla_sources_all}</span> <span class='ak-pill ak-warn' style='white-space:normal'><i class='fa-solid fa-triangle-exclamation'></i>{rdpproxy_nla_sources_all_warning}</span>";
    }
    $max=3;
    $shown=array();
    foreach(array_slice($sources,0,$max) as $s){$shown[]=htmlspecialchars((string)$s,ENT_QUOTES);}
    $more=count($sources)-count($shown);
    $txt=implode(", ",$shown);
    if($more>0){$txt.=" +$more";}
    return "<span class='ak-chip'>$txt</span>";
}
function rst_card_nla($tpl):string{
    $page=CurrentPageName();
    $error="";
    $st=rdpproxy_nla($error);
    if($error!=""){
        // Daemon trop ancien (route absente) ou injoignable : la carte le dit, sans action.
        $note="<div class='rst-note rst-note-info'><i class='fa-solid fa-circle-info' style='margin-top:2px'></i><span>{rdpproxy_nla_unavailable}</span></div>";
        $rows=array(rst_row("fa-solid fa-circle-half-stroke","{status}","","<span class='ak-pill ak-off'>{unavailable}</span>","",$note));
        return rst_card("fa-solid fa-ticket","{rdpproxy_nla_title}","{rdpproxy_nla_explain}",$rows);
    }
    $licensed=!empty($st["licensed"]);
    $joined=!empty($st["joined"]);
    $active=!empty($st["active"]);
    $enabled=!empty($st["enabled"]);
    $realm=htmlspecialchars((string)($st["realm"] ?? ""),ENT_QUOTES);
    $dns=htmlspecialchars((string)($st["dns_name"] ?? ""),ENT_QUOTES);
    $computer=htmlspecialchars((string)($st["computer"] ?? ""),ENT_QUOTES);
    $port=intval($st["port"] ?? 0);
    $listen=trim((string)($st["listen"] ?? ""));
    $listen=($listen=="")?"{all_interfaces}":htmlspecialchars($listen,ENT_QUOTES);
    $settingsJs=$licensed?rst_js($tpl,"Loadjs('$page?nla-settings-js=yes')"):"";

    $rows=array();
    if($active){
        $right="<span class='ak-pill ak-ok'><i class='fa-solid fa-check'></i>{rdpproxy_nla_active}</span>";
        $sub="";
    }else{
        $right="<span class='ak-pill ak-off'><i class='fa-solid fa-minus'></i>{rdpproxy_nla_inactive}</span>";
        $sub=rdpproxy_nla_reason_text($st["reason"] ?? "");
    }
    $rows[]=rst_row("fa-solid fa-circle-half-stroke","{status}",$sub,$right);
    $rows[]=rst_row("fa-solid fa-power-off","{rdpproxy_nla_enable}","{rdpproxy_nla_enable_short}",rst_state_pill($enabled),$settingsJs);
    $rows[]=rst_row("fa-solid fa-ethernet","{listen_port}","{rdpproxy_nla_listen_short}",
        "<span class='ak-chip'>$listen:$port</span>",$settingsJs);
    $rows[]=rst_row("fa-solid fa-shield-halved","{rdpproxy_nla_sources}","{rdpproxy_nla_sources_short}",
        rdpproxy_nla_sources_cell($st),$settingsJs);
    // Ecoute principale (3389) : arretable quand le NLA est actif ; revient seule si le NLA tombe.
    $mlErr="";
    $ml=rdpproxy_main_listener($mlErr);
    if($mlErr==""){
        $mlPort=intval($ml["port"] ?? 0);
        if($mlPort<1){$mlPort=3389;}
        if(!empty($ml["off"])){
            $mlRight="<span class='ak-pill ak-off'><i class='fa-solid fa-power-off'></i>{stopped}</span>";
            $mlSub="{rdpproxy_main_listener_off}";
        }elseif(empty($ml["enabled"])){
            $mlRight="<span class='ak-pill ak-warn' style='white-space:normal'><i class='fa-solid fa-triangle-exclamation'></i>{rdpproxy_main_listener_kept}</span>";
            $mlSub="{rdpproxy_main_listener_kept_explain}";
        }else{
            $mlRight=rst_state_pill(true);
            $mlSub="{rdpproxy_main_listener_short}";
        }
        $rows[]=rst_row("fa-solid fa-door-open","{rdpproxy_main_listener} ($mlPort)",$mlSub,$mlRight,$settingsJs);
    }
    if($joined){
        $sub=($computer=="")?"":"{rdpproxy_nla_computer}: $computer";
        $rows[]=rst_row("fa-solid fa-sitemap","{rdpproxy_nla_realm}",$sub,"<span class='ak-chip'>$realm</span>");
    }else{
        $rows[]=rst_row("fa-solid fa-sitemap","{rdpproxy_nla_realm}","","<span class='ak-pill ak-off'>{rdpproxy_nla_reason_not_joined}</span>");
    }
    if($dns!=""){
        $rows[]=rst_row("fa-solid fa-globe","{rdpproxy_nla_dns_name}","","<span class='ak-chip'>$dns</span>");
    }
    $lastRenew=intval($st["last_renew"] ?? 0);
    if($joined && $lastRenew>0){
        $rows[]=rst_row("fa-solid fa-rotate","{rdpproxy_nla_last_renew}","","<span class='ak-chip'>".date("Y-m-d H:i",$lastRenew)."</span>");
    }
    if($joined){
        // A la demande : kinit, ntpdate et LDAP prennent jusqu'a 40 s cote daemon.
        $cjs=$licensed?rst_js($tpl,"LoadAjax('rdp-nla-checks','$page?nla-checks=yes');"):"";
        $right=($cjs=="")?"":"<a class='rst-link' href=\"javascript:blur();\" OnClick=\"$cjs\"><i class='fa-solid fa-stethoscope'></i>{rdpproxy_nla_run_checks}</a>";
        $rows[]=rst_row("fa-solid fa-list-check","{rdpproxy_nla_checks}","{rdpproxy_nla_checks_short}",$right,"","<div id='rdp-nla-checks'></div>");
    }
    // Avertissement fixe : un poste qui vise l'IP ne trouve pas de SPN et retombe sur NTLM.
    $rows[]="<div class='rst-note rst-note-warn' style='margin-top:10px'><i class='fa-solid fa-triangle-exclamation' style='margin-top:2px'></i><span>{rdpproxy_nla_dns_ip_warning}</span></div>";

    $btns=array();
    if($licensed){
        if(!$joined){
            $js=rst_js($tpl,"Loadjs('$page?nla-join-js=yes')");
            if($js<>""){$btns[]="<button type='button' class='ak-btn' OnClick=\"$js\"><i class='fa-solid fa-link'></i>{rdpproxy_nla_join}</button>";}
        }else{
            $js=rst_js($tpl,"Loadjs('$page?nla-renew-js=yes')");
            if($js<>""){$btns[]="<button type='button' class='ak-btn' OnClick=\"$js\"><i class='fa-solid fa-rotate'></i>{rdpproxy_nla_renew}</button>";}
        }
    }
    if($joined){
        $js=rst_js($tpl,"Loadjs('$page?nla-leave-js=yes')");
        if($js<>""){$btns[]="<button type='button' class='ak-btn' OnClick=\"$js\"><i class='fa-solid fa-link-slash'></i>{rdpproxy_nla_leave}</button>";}
    }
    if(count($btns)>0){
        $rows[]="<div style='display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;margin-top:12px'>".@implode("",$btns)."</div>";
    }
    $title="{rdpproxy_nla_title}";
    if(!$licensed){
        $title.=" <span class='ak-pill ak-warn' style='margin-left:6px;white-space:normal'><i class='fa-solid fa-lock'></i>{rdpproxy_nla_license_required}</span>";
    }
    return rst_card("fa-solid fa-ticket",$title,"{rdpproxy_nla_explain}",$rows);
}

function settings_flat(){
    $tpl=new template_admin();
    $html=array();

    $dhSize=0;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/dh3072"));
    if(is_object($json) && property_exists($json,"Info")){
        $DH=$json->Info;
        if($DH->Pid>0){
            $html[]=$tpl->div_warning("DH 3072: {running} (pid: $DH->Pid) {since} $DH->Time {minutes} - {$DH->Size}bytes) {please_wait_working}");
        }elseif($DH->Size>10){
            $dhSize=intval($DH->Size);
        }
    }

    $listen=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyListen");
    $port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyPort"));
    if($port<5){$port=3389;}
    if($listen==""){$listen="0.0.0.0";}

    // Les messages d'erreur sont dans l'onglet « Modele » (rth_card_errmsg()).
    $html[]=rdpproxy_meta_banner();
    $html[]="<div class='rst ak'>";
    $html[]=rst_section_label($port);
    $html[]="<div class='rst-cols'>";
    $html[]="<div class='rst-col'>".rst_card_listen($tpl,$listen,$port).rst_card_bastion($tpl)."</div>";
    $html[]="<div class='rst-col'>".rst_card_crypto($tpl,$dhSize).rst_card_nla($tpl).rst_card_session($tpl)."</div>";
    $html[]="</div></div>";
    echo rst_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}


// rdpproxy_ssh_unavailable_text : pourquoi le bastion ne relaie pas SSH, d'apres
// /rdpproxy/state (ssh_unavailable_reason). « engine » = ReDemPtion sans module SSH,
// « authhook » = hook en cours plus ancien que ssh_min_authhook (ou version non prouvee).
// Les jetons a %s sont traduits AVANT sprintf.
function rdpproxy_ssh_unavailable_text($tpl):string{
    // Etat deja lu par rdpproxy_ssh_supported() dans cette requete (cache de rdpproxy_state).
    $error="";
    $st=rdpproxy_state($error);
    // Daemon injoignable : on ne sait rien, ne pas pretendre a une mise a jour.
    if($error!=""){return $tpl->_ENGINE_parse_body("{rdpproxy_state_unavailable}");}
    $reason=isset($st["ssh_unavailable_reason"])?(string)$st["ssh_unavailable_reason"]:"";
    if($reason=="engine"){return $tpl->_ENGINE_parse_body("{rdpproxy_ssh_reason_engine}");}
    if($reason=="authhook"){
        $running=trim(isset($st["ssh_authhook_version"])?(string)$st["ssh_authhook_version"]:"");
        $min=trim(isset($st["ssh_min_authhook"])?(string)$st["ssh_min_authhook"]:"");
        if($running==""){$running=$tpl->_ENGINE_parse_body("{unknown}");}
        if($min==""){$min=$tpl->_ENGINE_parse_body("{unknown}");}
        return sprintf($tpl->_ENGINE_parse_body("{rdpproxy_ssh_reason_authhook}"),
            htmlspecialchars($running,ENT_QUOTES),htmlspecialchars($min,ENT_QUOTES));
    }
    // Daemon joignable mais trop ancien pour dire la cause.
    return $tpl->_ENGINE_parse_body("{rdpproxy_ssh_reason_unknown}");
}

function SaveSettings():bool{
    $tpl=new template_admin();
    // Masquer le bouton « Appliquer » ne protege pas le POST lui-meme.
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS}");return false;}
    // Verrou Artica Meta : une cle du catalogue Meta dans le POST = refus, rien n'est ecrit.
    if(rdpproxy_meta_refuse_post()){return false;}
    unset($_POST["RDPProxyMetaRO"]);
    $err=rdpproxy_settings_validate($_POST);
    if($err!=""){echo $tpl->post_error($err);return false;}
    // Port principal = port de l'ecoute NLA du bastion : les deux instances se disputeraient
    // le port. Daemon injoignable = pas de preuve, pas de refus (le daemon le signale aussi).
    if(isset($_POST["RDPProxyPort"])){
        $nlaErr="";
        $nla=rdpproxy_nla($nlaErr);
        if($nlaErr=="" && rdpproxy_port_conflicts_nla($_POST["RDPProxyPort"],$nla)){
            echo $tpl->post_error("{rdpproxy_port_used_by_nla}");return false;
        }
    }
    rdpproxy_meta_strip_lock_keys();
    $tpl->SAVE_POSTs();
    admin_tracks_post("Save RDS Proxy Settings");
    // Sans cette reecriture, les fenetres TLS et options de session n'etaient
    // appliquees qu'au redemarrage suivant. Pas de restart : il relance authhook,
    // qui ferme toutes les sessions ouvertes ; ReDemPtion relit l'.ini a chaque connexion.
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_INSTALLED"))==1){
        $error=rdpproxy_config_apply();
        if($error!=""){echo $tpl->post_error("{rdpproxy_config_not_applied}: $error");return false;}
    }
    return true;
}

// Vrai si $port (port principal saisi) est celui de l'ecoute NLA active ou activee.
function rdpproxy_port_conflicts_nla($port,$nla):bool{
    if(!is_array($nla) || (empty($nla["active"]) && empty($nla["enabled"]))){return false;}
    $nlaPort=intval($nla["port"] ?? 0);
    return $nlaPort>0 && intval(trim((string)$port))==$nlaPort;
}

/**
 * Bornes des reglages lus par le daemon (RDPProxy/client_settings.go) : le daemon
 * retombe sur le defaut hors bornes, la console refuse donc d'enregistrer une valeur
 * qui serait ignoree en silence. Rend "" si tout est valide, sinon le message.
 */
function rdpproxy_settings_validate(array $post):string{
    $ints=array(
        "RDPHandshakeTimeout"=>array(5,300),
        // Pas de 0 = illimite : 0 est la valeur des appliances jamais reglees (= 900).
        "RDPSessionTimeout"=>array(30,86400),
        "RDPKeepAlive"=>array(0,1440),
        // Pas de 0 : le moteur laisserait l'ecran d'erreur ouvert sans fin.
        "RDPCloseBoxTimeout"=>array(10,3600),
    );
    foreach($ints as $key=>$b){
        if(!isset($post[$key])){continue;}
        $v=trim((string)$post[$key]);
        if(!preg_match('/^[0-9]{1,6}$/D',$v)){return "{wrong_value}: $key";}
        $n=intval($v);
        if($n<$b[0] || $n>$b[1]){
            return "{wrong_value}: $key ({$b[0]}-{$b[1]})";
        }
    }
    if(isset($post["RDPClientMinTLS"])){
        if(!isset(rdpproxy_client_tls_levels()[(string)$post["RDPClientMinTLS"]])){return "{wrong_value}: RDPClientMinTLS";}
    }
    if(isset($post["RDPClientCiphers"])){
        if(!isset(rdpproxy_client_cipher_levels()[(string)$post["RDPClientCiphers"]])){return "{wrong_value}: RDPClientCiphers";}
    }
    foreach(array("RDPTlsFallbackLegacy","RDPIgnoreLogonPassword","RDPDisconnectOnLogonUserChange",
                "AllowAuthenticateScreen","RDPRejectErrors","RDPProxyOCRTesseract") as $key){
        if(!isset($post[$key])){continue;}
        $v=(string)$post[$key];
        if($v!=="0" && $v!=="1"){return "{wrong_value}: $key";}
    }
    return "";
}

/** [client] tls_min_level de ReDemPtion : 0 = TLS 1.0 ... 3 = TLS 1.3. */
function rdpproxy_client_tls_levels():array{
    return array("0"=>"TLS 1.0 ({rdpproxy_legacy_clients})","1"=>"TLS 1.1","2"=>"TLS 1.2 ({default})","3"=>"TLS 1.3");
}
/** [client] ssl_cipher_list : legacy = avec SHA-1, default = defaut moteur, strict = AES-GCM seul. */
function rdpproxy_client_cipher_levels():array{
    return array("legacy"=>"{RDP_CIPHER_0}","default"=>"{RDP_CIPHER_1}","strict"=>"{RDP_CIPHER_2}");
}
function rdpproxy_client_min_tls():string{
    $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPClientMinTLS"));
    if(!isset(rdpproxy_client_tls_levels()[$v])){return "2";}
    return $v;
}
function rdpproxy_client_ciphers():string{
    $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPClientCiphers"));
    if(!isset(rdpproxy_client_cipher_levels()[$v])){return "default";}
    return $v;
}
// Valeurs EFFECTIVES (memes replis que readClientSettings du daemon) : une valeur
// ancienne hors bornes s'affiche comme le daemon l'applique, et la modale ne
// propose jamais un nombre que SaveSettings refuserait.
/** RDPHandshakeTimeout en secondes [5,300], sinon 10. */
function rdpproxy_handshake_timeout():int{
    $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPHandshakeTimeout"));
    if(!preg_match('/^[0-9]{1,6}$/D',$v)){return 10;}
    $n=intval($v);
    if($n<5||$n>300){return 10;}
    return $n;
}
/** RDPSessionTimeout en secondes [30,86400] ; vide, 0 ou hors bornes = 900. */
function rdpproxy_session_timeout():int{
    $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPSessionTimeout"));
    if(!preg_match('/^[0-9]{1,6}$/D',$v)){return 900;}
    $n=intval($v);
    if($n<30||$n>86400){return 900;}
    return $n;
}
/** RDPCloseBoxTimeout en secondes [10,3600], sinon 600 : duree de l'ecran d'erreur. */
function rdpproxy_close_box_timeout():int{
    $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPCloseBoxTimeout"));
    if(!preg_match('/^[0-9]{1,6}$/D',$v)){return 600;}
    $n=intval($v);
    if($n<10||$n>3600){return 600;}
    return $n;
}
/** RDPKeepAlive en minutes [0,1440] (0 = desactive), sinon 0. */
function rdpproxy_keepalive():int{
    $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPKeepAlive"));
    if(!preg_match('/^[0-9]{1,6}$/D',$v)){return 0;}
    $n=intval($v);
    if($n>1440){return 0;}
    return $n;
}

function status_top():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    // RDPPROXY_AUTH_VERSION : ecrite par authhook a son demarrage et par le watchdog
    // articarest (authhook -version). RDPPROXY_AUTH_LIB_VERSION n'a plus aucun ecrivain.
    $RDPPROXY_AUTH_VERSION=trim(strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPPROXY_AUTH_VERSION")));
    if($RDPPROXY_AUTH_VERSION==""){$RDPPROXY_AUTH_VERSION="-";}
    $state=rdpproxy_state($error);
    $RDPPROXY_RULES=isset($state["policies_enabled"])?intval($state["policies_enabled"]):0;

    $lib=new lib_memcached();
    $RDPPROXY_CNX=intval($lib->getKey("RDPPROXY_CNX"));
    $AUTHVERSION = $tpl->widget_h("green", "fas fa-info", $RDPPROXY_AUTH_VERSION, "{APP_RDPPROXY_AUTHHOOK}:{version}");

    $AUTHCNX = $tpl->widget_h("lazur", "far fa-bolt", $tpl->FormatNumber($RDPPROXY_CNX), "{connections}");
    if($error!=""){
        $RULESTATUS = $tpl->widget_h("grey", "fas fa-list-ol", "{error}", "{rules}");
    }elseif($RDPPROXY_RULES>0){
        $RULESTATUS = $tpl->widget_h("green", "fas fa-list-ol", $tpl->FormatNumber($RDPPROXY_RULES), "{rules}");
    }else{
        $RULESTATUS = $tpl->widget_h("grey", "fas fa-list-ol", "{no_rule}", "{rules}");
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$AUTHVERSION</td>";
    $html[]="<td style='width:33%;padding-left:10px'>$AUTHCNX</td>";
    $html[]="<td style='width:33%;padding-left:10px'>$RULESTATUS</td>";

    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");

    $html=$tpl->page_header("{APP_RDPPROXY}","fas fa-tachometer-alt","{rdpproxy_explain_web}",
        "$page?tabs=yes","events","progress-rdpproxy-restart",false,"table-rdpproxy-status");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {service_status}",$html);
        echo $tpl->build_firewall();
        return;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function status_authHook():string{
    $tpl=new template_admin();
    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/auth/status");
    $json = json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsrestart=$tpl->framework_buildjs("/rdpproxy/auth/restart",
        "squid.rdpproxy.auth.progress","squid.rdpproxy.auth.progress.txt","progress-rdpproxy-restart");
    return $tpl->SERVICE_STATUS($ini, "APP_RDPPROXYHOOK",$jsrestart);
}

function status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]= status_rdpproxy();
    $html[]= status_authHook();
    $html[]="<script>";
    $html[]="LoadAjaxSilent('rdpproxy-top','$page?rdpproxy-top=yes');";
    $html[]="LoadAjaxSilent('rdpproxy-config','$page?settings=yes');";
    $html[]=main_tiny_default();

    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body( $html);
    return true;
}
function status_rdpproxy():string{
    $tpl=new template_admin();
    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/status");
    $json = json_decode($data);

    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    // Ecoute 3389 volontairement arretee (NLA seul) : etat voulu, pas une panne. Pas de bouton
    // Restart : il ne relancerait rien (Start() respecte la decision).
    $mlErr="";
    $ml=rdpproxy_main_listener($mlErr);
    if($mlErr=="" && !empty($ml["off"])){
        return $tpl->widget_vert("{APP_RDPPROXY}","{rdpproxy_main_listener_nla_only}");
    }
    $jsrestart=$tpl->framework_buildjs("/rdpproxy/restart",
        "squid.rdpproxy.progress","squid.rdpproxy.progress.txt","progress-rdpproxy-restart",
        rdpproxy_refresh_card());
    return $tpl->SERVICE_STATUS($ini, "APP_RDPPROXY",$jsrestart).webview_keep_restart_note();
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px;vertical-align:top'>";
    $html[]="<div id='rdpproxy-status'></div>";
	$html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px;'>";
    $html[]="<div id='rdpproxy-top' style='margin-top: -8px'></div>";
    $html[]="<div id='rdpproxy-config'></div>";
    $html[]="</td>";
    $html[]="</tr>";

    $Interval=$tpl->RefreshInterval_js("rdpproxy-status",$page,"status=yes",3);

    $html[]="</table>";
    $html[]="<script>";
    $html[]=$Interval;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

// ---------------------------------------------------------------------------
// Onglet « A propos » -- maquette stitch_redemption_rdp_proxy.
//
// Un texte de presentation, puis trois cartes : passerelle, chiffrement,
// connexions. Chaque carte lit l'etat REEL de l'appliance : les valeurs de la
// maquette (v2.4.1, TLS 1.3, 8 sessions, Let's Encrypt) ne sont que des exemples.
// Un widget = une fonction.
// ---------------------------------------------------------------------------

/** Feuille du kit rab-*, a concatener APRES _ENGINE_parse_body(). */
function about_css():string{
    $accent="#10b981";$accent_soft="rgba(16,185,129,.12)";$accent_dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$accent_soft=$a["accent_soft"];$accent_dark=$a["accent_dark"];
    }
    return "<style>
.rab{margin:30px 40px;max-width:1700px;color:#1e293b}
.rab-title{font-size:30px;font-weight:400;margin:0 0 28px 0;color:#1e293b}
.rab-intro{padding:26px 36px;font-size:15px;line-height:1.75;margin-bottom:32px}
.rab-intro a{color:$accent_dark;text-decoration:underline}
.rab-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:30px}
@media(max-width:1100px){.rab-cards{grid-template-columns:1fr}}
.rab-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:26px 30px;display:flex;flex-direction:column}
.rab-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
.rab-label{font-size:13px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#475569;line-height:1.4}
.rab-ico{font-size:18px;color:$accent}
.rab-value{font-size:24px;font-weight:700;color:#0f172a;margin-top:6px;font-family:Consolas,Menlo,monospace;word-break:break-all}
.rab-sub{font-size:14px;color:#64748b;margin-top:12px;min-height:20px}
.rab-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;border-top:1px solid #e2e8f0;margin-top:auto;padding-top:16px;font-size:14px;color:#475569}
.rab-body{flex:1 1 auto;padding-bottom:22px}
.rab-foot-v{font-weight:600;color:#0f172a;text-align:right}
.rab-foot-ok{color:$accent_dark}
.rab-mono{font-family:Consolas,Menlo,monospace;font-weight:700}
.rab-dot{margin-right:7px;vertical-align:middle}
</style>";
}

/** Le texte de presentation : le jeton historique, liens poses par la page. */
function about_intro():string{
    $tpl=new templates();
    $about=$tpl->_ENGINE_parse_body("{wallix_redemption}");
    $about=str_replace("%ChristopheGrosjean", "<a href=\"https://us.linkedin.com/pub/christophe-grosjean/8/5b6/8a8\" target=\"_blank\" rel=\"noopener\">Christophe Grosjean</a>", $about);
    $about=str_replace("%Wallix","<a href=\"http://www.wallix.com\" target=\"_blank\" rel=\"noopener\">Wallix</a>",$about);
    return "<div class='ak-card rab-intro'>$about</div>";
}

/**
 * Etat du service, lu dans l'INI de /rdpproxy/status : "on", "off" ou "unk"
 * quand le daemon ne repond pas -- injoignable n'est pas « arrete ».
 */
function about_service_state():string{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/status"));
    if(!is_object($json) || !property_exists($json,"Status") || !$json->Status){return "unk";}
    if(!property_exists($json,"Info")){return "unk";}
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    if(!isset($ini->_params["APP_RDPPROXY"])){return "unk";}
    return intval($ini->_params["APP_RDPPROXY"]["running"] ?? 0)==1?"on":"off";
}

/** En-tete commun aux trois cartes : libelle a gauche, pastille ou icone a droite. */
function about_card_head($label,$right):string{
    return "<div class='rab-head'><div class='rab-label'>$label</div><div>$right</div></div>";
}

/** Carte 1 : la passerelle -- etat, version, interface et port d'ecoute. */
function about_card_gateway():string{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $state=about_service_state();
    $pill="<span class='ak-pill ak-off'>-</span>";
    if($state=="on"){$pill="<span class='ak-pill ak-ok'>{running}</span>";}
    if($state=="off"){
        $mlErr="";
        $ml=rdpproxy_main_listener($mlErr);
        $pill=($mlErr=="" && !empty($ml["off"]))?"<span class='ak-pill ak-ok'>{rdpproxy_main_listener_nla_only}</span>":"<span class='ak-pill ak-err'>{stopped}</span>";
    }

    $version=trim((string)$s->GET_INFO("APP_RDPPROXY_VERSION"));
    if($version==""){$version="-";}
    $listen=trim((string)$s->GET_INFO("RDPProxyListen"));
    if($listen=="" || $listen=="0.0.0.0"){$listen="{all_interfaces}";}else{$listen=htmlspecialchars($listen,ENT_QUOTES);}
    $port=intval($s->GET_INFO("RDPProxyPort"));
    if($port<5){$port=3389;}

    $h=array();
    $h[]="<div class='rab-card'><div class='rab-body'>";
    $h[]=about_card_head("{gateway} ReDemPtion",$pill);
    $h[]="<div class='rab-value'>".htmlspecialchars($version,ENT_QUOTES)."</div>";
    $h[]="<div class='rab-sub'>{listen_interface}: $listen</div>";
    $h[]="</div>";
    $h[]="<div class='rab-foot'><span>{listen_port}</span><span class='rab-foot-v rab-mono'>$port / TCP</span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

/** Carte 2 : chiffrement -- version TLS minimale, NLA, certificat presente. */
function about_card_security():string{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $tls=array("TLS 1.0","TLS 1.1","TLS 1.2","TLS 1.3");
    // Meme cle que le daemon (RDPClientMinTLS, vide = TLS 1.2) ; NLA se regle par cible.
    $min=intval(rdpproxy_client_min_tls());
    $value="&ge; ".$tls[$min];
    $ciphers=rdpproxy_client_cipher_levels()[rdpproxy_client_ciphers()];

    $cert=trim((string)$s->GET_INFO("RDPProxySSLCertificate"));
    $certtxt=$cert==""?"{default}":htmlspecialchars($cert,ENT_QUOTES);
    $certclass=$cert==""?"rab-foot-v":"rab-foot-v rab-foot-ok";

    $h=array();
    $h[]="<div class='rab-card'><div class='rab-body'>";
    $h[]=about_card_head("{encryption} &amp; {security}","<i class='fas fa-lock rab-ico'></i>");
    $h[]="<div class='rab-value'>$value</div>";
    $h[]="<div class='rab-sub'>{ssl_ciphers}: $ciphers</div>";
    $h[]="</div>";
    $h[]="<div class='rab-foot'><span>{certificate}</span><span class='$certclass'>$certtxt</span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

/**
 * Carte 3 : connexions -- sessions en cours (compteur publie par le daemon),
 * groupes actifs, et l'audit video, actif des qu'un groupe actif enregistre.
 */
function about_card_sessions():string{
    $lib=new lib_memcached();
    $cnx=intval($lib->getKey("RDPPROXY_CNX"));

    $state=rdpproxy_state($error);
    $groups=isset($state["policies_enabled"])?intval($state["policies_enabled"]):0;
    $recording=isset($state["policies_recording"])?intval($state["policies_recording"]):0;
    $video="<span class='rab-foot-v'><span class='ak-dot ak-off rab-dot'></span>{inactive}</span>";
    if($error!=""){
        $video="<span class='rab-foot-v'><span class='ak-dot ak-off rab-dot'></span>{error}</span>";
    }elseif(empty($state["video_supported"])){
        $video="<span class='rab-foot-v'><span class='ak-dot ak-off rab-dot'></span>{rdpproxy_video_unavailable}</span>";
    }elseif($recording>0){
        $video="<span class='rab-foot-v rab-foot-ok'><span class='ak-dot ak-err rab-dot'></span>{active2} ($recording {groups})</span>";
    }

    $h=array();
    $h[]="<div class='rab-card'><div class='rab-body'>";
    $h[]=about_card_head("{connections}","<i class='fas fa-users rab-ico'></i>");
    $h[]="<div class='rab-value'>$cnx {sessions}</div>";
    $h[]="<div class='rab-sub'>{groups}: ".($error!=""?"{error}":"$groups {active2}")."</div>";
    $h[]="</div>";
    $h[]="<div class='rab-foot'><span>{video_recording}</span>$video</div>";
    $h[]="</div>";
    return @implode("",$h);
}

function about(){
    $tpl=new template_admin();
    $h=array();
    $h[]="<div class='rab ak'>";
    $h[]=about_intro();
    $h[]="<div class='rab-cards'>";
    $h[]=about_card_gateway();
    $h[]=about_card_security();
    $h[]=about_card_sessions();
    $h[]="</div>";
    $h[]="</div>";
    echo about_css().$tpl->_ENGINE_parse_body(@implode("\n",$h));
    echo "<script>".main_tiny_default("{about2} Wallix proxyRDP ReDemPtion")."</script>";
}

function file_uploaded(){
    $tpl = new template_admin();
    $page= CurrentPageName();
    // Pentest 2026-11-04 : file-uploaded etait recopie sans controle dans un chemin et dans un reglage affiche (XSS, traversee).
    $_GET["file-uploaded"]=basename(str_replace("\\","/",(string)$_GET["file-uploaded"]));
    $file = "/usr/share/artica-postfix/ressources/conf/upload/{$_GET["file-uploaded"]}";
    $destfile="/usr/share/artica-postfix/img/rdpproxy/{$_GET["file-uploaded"]}";
    // Verrou Artica Meta : le logo (RDPProxyLogo) appartient a la politique, rien n'est ecrit.
    if(rdpproxy_meta_locked()){
        @unlink($file);
        rdpproxy_meta_refuse_js();
        return;
    }
    if(!skin_licensed()){
        $tpl->js_error("{rdpproxy_skin_license_required}");
        @unlink("/usr/share/artica-postfix/ressources/conf/upload/".basename($_GET["file-uploaded"]));
        return;
    }
    if(!preg_match("#\.png$#",$_GET["file-uploaded"])){
        $tpl->js_error("Require a PNG format!");
        @unlink($file);
        return;
    }

    $error=rdpproxy_logo_install(basename($_GET["file-uploaded"]));
    if($error=="license_required"){$error="{rdpproxy_skin_license_required}";}
    if($error!="" || !is_file($destfile)){
        $tpl->js_error($error!=""?$error:"Fatal error!");
        @unlink($file);
        return;
    }
    @unlink($file);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RDPProxyLogo","img/rdpproxy/{$_GET["file-uploaded"]}");
    admin_tracks("Installed RDS Proxy login page logo ".basename($_GET["file-uploaded"]));
    echo "if(typeof dialogInstance2!=='undefined' && dialogInstance2){dialogInstance2.close();}\n";
    // Le logo est relu dans rdpproxy.ini a chaque session : reecriture sans redemarrage. Avant,
    // l'envoi relancait authhook (/rdpproxy/auth/restart) et coupait toutes les sessions ouvertes.
    $err=rdpproxy_config_apply();
    if($err!=""){$tpl->js_error($err);return;}
    echo "LoadAjax('rdpproxy-theme','$page?rdpproxy-theme=yes');\n";
    return;

}

// ======================================================================= Onglet WebView
// Cibles WEB du bastion : une session RDP montre UNE application web, rendue par
// artica-webview (Chromium sans affichage dans bubblewrap). Trois sous-onglets, un mode de
// lecture chacun : vue d'ensemble (tuiles), parametres du navigateur (table_form),
// certificats de confiance (tableau awc). Donnees : GET /rdpproxy/webview et
// /rdpproxy/webview/trust (articarest RDPProxy/webview_info.go).

function webview_tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array=array();
    $array["{rdpproxy_webview_overview}"]="$page?webview-status=yes";
    $array["{rdpproxy_webview_parameters}"]="$page?webview-params-host=yes";
    $array["{rdpproxy_webview_trusted_certs}"]="$page?webview-trust=yes";
    $array["{rdpproxy_webkeep_tab}"]="$page?webview-keep=yes";
    echo "<div style='margin-top:10px'>";
    echo $tpl->tabs_default($array);
    echo "</div>";
}

function webview_css():string{
    if(isset($GLOBALS["RWV_CSS"])){return "";}
    $GLOBALS["RWV_CSS"]=true;
    return "<style>
.rwv-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;margin-bottom:24px}
@media(max-width:1300px){.rwv-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:800px){.rwv-cards{grid-template-columns:1fr}}
.rwv-banner{align-items:flex-start;gap:14px;border-radius:12px;padding:18px 24px;margin-bottom:24px;font-size:15px;line-height:1.6}
.rwv-banner i{font-size:20px;margin-top:3px}
.rwv-path{font-size:12px;color:#64748b;word-break:break-all;margin-top:8px}
.rwv-err{color:#ba1a1a;word-break:break-word}
.rwv-flags{display:flex;flex-wrap:wrap;gap:8px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;margin-top:16px}
.rwv-flag{white-space:normal;word-break:break-all}
.rwv-flags-title{width:100%;font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#475569;margin-bottom:4px}
.rwv-tags{display:flex;flex-wrap:wrap;gap:6px}
</style>";
}

/** Une tuile composant : libelle, pastille installe/absent, version, chemin et pied. */
function webview_card($label,$ico,$installed,$version,$sub,$footLabel,$footValue,$footOk=false):string{
    $pill=$installed?"<span class='ak-pill ak-ok'>{installed}</span>":"<span class='ak-pill ak-err'>{not_installed}</span>";
    $v=trim((string)$version)==""?"-":htmlspecialchars((string)$version,ENT_QUOTES);
    $h=array();
    $h[]="<div class='rab-card'><div class='rab-body'>";
    $h[]=about_card_head("<i class='$ico'></i>&nbsp;$label",$pill);
    $h[]="<div class='rab-value'>$v</div>";
    $h[]="<div class='rab-sub'>$sub</div>";
    $h[]="</div>";
    $h[]="<div class='rab-foot'><span>$footLabel</span><span class='".($footOk?"rab-foot-v rab-foot-ok":"rab-foot-v")."'>$footValue</span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

function webview_component($w,$name):array{
    foreach(($w["components"] ?? array()) as $c){
        if(is_array($c)&&($c["name"] ?? "")==$name){return $c;}
    }
    return array("name"=>$name,"installed"=>false,"version"=>"","path"=>"","required"=>"");
}

function webview_status(){
    $tpl=new template_admin();
    $error="";
    $w=rdpproxy_webview($error);
    if($error!=""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($error));
        return;
    }
    $esc=function($s){return req_html($s);}; // Pentest 2026-11-04 : certificats/sites distants (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1.
    $h=array();
    $h[]="<div class='rab ak'>";


    if(!empty($w["supported"])){
        $h[]="<div class='ak-banner ak-ok rwv-banner'><i class='fas fa-circle-check'></i><div>{rdpproxy_webview_available}</div></div>";
    }else{
        $reason=(string)($w["reason"] ?? "engine");
        if(!in_array($reason,array("engine","authhook","bridge","browser"),true)){$reason="engine";}
        $h[]="<div class='ak-banner ak-warn rwv-banner'><i class='fas fa-triangle-exclamation'></i><div>{rdpproxy_web_unavailable_$reason}</div></div>";
    }

    $h[]="<div class='rwv-cards'>";
    // Moteur ReDemPtion : capacite web1 (patch 0009)
    $c=webview_component($w,"engine");
    $h[]=webview_card("{rdpproxy_webview_comp_engine}","fas fa-server",!empty($c["installed"]),$c["version"] ?? "",
        "<div class='rwv-path'>".$esc($c["path"] ?? "")."</div>",
        "{rdpproxy_webview_capability}","<span class='rab-mono'>".$esc($c["required"] ?? "web1")."</span>",!empty($c["installed"]));
    // authhook : version minimale
    $c=webview_component($w,"authhook");
    $h[]=webview_card("{rdpproxy_webview_comp_authhook}","fas fa-key",!empty($c["installed"]),$c["version"] ?? "",
        "<div class='rwv-path'>".$esc($c["path"] ?? "")."</div>",
        "{rdpproxy_webview_required}","<span class='rab-mono'>&ge; ".$esc($c["required"] ?? "")."</span>",!empty($c["installed"]));
    // Pont artica-webview : synchro avec le paquet Artica
    $c=webview_component($w,"bridge");
    if(empty($w["bridge_source"])){
        $sync="{rdpproxy_webview_no_source}";$syncOk=false;
    }elseif(!empty($w["bridge_in_sync"])){
        $sync="{rdpproxy_webview_in_sync}";$syncOk=true;
    }else{
        $sync="{rdpproxy_webview_out_of_sync}";$syncOk=false;
    }
    $h[]=webview_card("{rdpproxy_webview_comp_bridge}","fas fa-bridge",!empty($c["installed"]),$c["version"] ?? "",
        "<div class='rwv-path'>".$esc($c["path"] ?? "")."</div>",
        "{rdpproxy_webview_artica_package}",$sync,$syncOk);
    // chromium-headless-shell et bubblewrap : paquets Debian
    $c=webview_component($w,"browser");
    $h[]=webview_card("{rdpproxy_webview_comp_browser}","fab fa-chrome",!empty($c["installed"]),$c["version"] ?? "",
        "<div class='rwv-path'>".$esc($c["path"] ?? "")."</div>",
        "{rdpproxy_webview_debian_package}","<span class='rab-mono'>chromium-headless-shell</span>");
    $c=webview_component($w,"sandbox");
    $h[]=webview_card("{rdpproxy_webview_comp_sandbox}","fas fa-box",!empty($c["installed"]),$c["version"] ?? "",
        "<div class='rwv-path'>".$esc($c["path"] ?? "")."</div>",
        "{rdpproxy_webview_debian_package}","<span class='rab-mono'>bubblewrap</span>");

    // Cibles web : actives / total, lien vers la page des cibles
    $total=intval($w["web_targets"] ?? 0);
    $enabled=intval($w["web_targets_enabled"] ?? 0);
    $th=array();
    $th[]="<div class='rab-card'><div class='rab-body'>";
    $th[]=about_card_head("<i class='fas fa-globe'></i>&nbsp;{rdpproxy_webview_targets}","");
    $th[]="<div class='rab-value'>$enabled / $total</div>";
    $th[]="<div class='rab-sub'>{rdpproxy_webview_targets_enabled}</div>";
    $th[]="</div>";
    $th[]="<div class='rab-foot'><span>{rdpproxy_webview_trusted_certs}</span><span class='rab-foot-v'>".intval($w["trusted_certs"] ?? 0)."</span></div>";
    // connexion automatique (lot A) : cibles qui l'utilisent, ou « indisponible » si le moteur ou
    // authhook ne la portent pas encore
    $wcfg=(isset($w["config"])&&is_array($w["config"]))?$w["config"]:array();
    $loginTxt=!empty($wcfg["login_supported"])?intval($w["login_targets"] ?? 0):"<span class='ak-pill ak-off'>{rdpproxy_weblogin_unavailable_short}</span>";
    $th[]="<div class='rab-foot'><span>{rdpproxy_weblogin_title}</span><span class='rab-foot-v'>$loginTxt</span></div>";
    // copier / coller (12.3.24, authhook 1.8.1) : réglé par les droits des stratégies
    $clipTxt=!empty($wcfg["clipboard_supported"])?"{rdpproxy_webclip_state_on}":"<span class='ak-pill ak-off'>{rdpproxy_weblogin_unavailable_short}</span>";
    $th[]="<div class='rab-foot'><span>{rdpproxy_webclip_target_title}</span><span class='rab-foot-v'>$clipTxt</span></div>";
    $th[]="<div style='margin-top:12px'><a href='/rdpproxy-connections' class='rab-foot-ok'><i class='fas fa-arrow-right'></i> {rdpproxy_webview_manage_targets}</a></div>";
    $th[]="</div>";
    $h[]=@implode("",$th);
    $h[]="</div>";

    // Installation automatique de chromium-headless-shell et bubblewrap
    $free=intval($w["usr_free_mb"] ?? 0);
    $min=intval($w["deps_min_free_mb"] ?? 600);
    if(!empty($w["deps_running"])){
        $state="<span class='ak-pill ak-off'><i class='fas fa-spinner fa-spin'></i> {rdpproxy_webview_deps_running}</span>";
    }elseif(intval($w["deps_last_try"] ?? 0)==0){
        $state="<span class='ak-pill ak-off'>{rdpproxy_webview_deps_never}</span>";
    }elseif(trim((string)($w["deps_last_error"] ?? ""))!=""){
        $state="<span class='ak-pill ak-err'>{error}</span>";
    }else{
        $state="<span class='ak-pill ak-ok'>{rdpproxy_webview_deps_ok}</span>";
    }
    $dh=array();
    $dh[]="<div class='rab-card' style='margin-bottom:24px'><div class='rab-body'>";
    $dh[]=about_card_head("<i class='fas fa-download'></i>&nbsp;{rdpproxy_webview_deps_title}",$state);
    $dh[]="<div class='rab-sub' style='font-size:15px;line-height:1.6;color:#334155'>{rdpproxy_webview_deps_explain}</div>";
    if(intval($w["deps_last_try"] ?? 0)>0){
        $dh[]="<div class='rab-sub'>{rdpproxy_webview_deps_last}: ".$esc($tpl->time_to_date(intval($w["deps_last_try"]),true))."</div>";
    }
    if(trim((string)($w["deps_last_error"] ?? ""))!=""){
        $dh[]="<div class='rab-sub rwv-err'><i class='fas fa-circle-exclamation'></i> ".$esc($w["deps_last_error"])."</div>";
    }
    $dh[]="</div>";
    $freeClass=$free>=$min?"rab-foot-v rab-foot-ok":"rab-foot-v rwv-err";
    $dh[]="<div class='rab-foot'><span>{rdpproxy_webview_usr_free}</span><span class='$freeClass'>$free MB ({rdpproxy_webview_minimum} $min MB)</span></div>";
    $dh[]="</div>";
    $h[]=@implode("",$dh);
    $h[]=webview_netns_card($w);
    $h[]=webview_proxy_card($w);
    $h[]="</div>";

    $html[]= about_css();
    $html[]= webview_css();
    $html[]=$tpl->_ENGINE_parse_body(@implode("\n",$h));
    $html[]="<script>".webview_tinydefault()."</script>";
    echo @implode("\n",$html);
}

/**
 * Confinement reseau : politiques dont le Chromium ne voit qu'un espace de noms reseau Artica
 * (champ web_netns de la politique), et lien vers la page des espaces quand elle est au menu.
 */

function webview_tinydefault():string{
    $TINY=array();
    $TINY["TITLE"]="{rdpproxy_webview_title}";
    $TINY["ICO"]=ico_ie;
    $TINY["EXPL"]="{rdpproxy_webview_intro2}";
    $TINY["BUTTONS"]="";
    //$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
   return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
}
function main_tiny_default($title="",$desc=""):string{
    if(strlen($title)<2){
        $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
        $title="{APP_RDPPROXY} v$PrivoxyVersion";
    }
    if(strlen($desc)<2){
        $desc="{rdpproxy_explain_web}";
    }
    $TINY=array();
    $TINY["TITLE"]=$title;
    $TINY["ICO"]="fas fa-tachometer-alt";
    $TINY["EXPL"]=$desc;
    $TINY["BUTTONS"]="";
    //$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);

    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
}

function webview_netns_card($w):string{
    $esc=function($s){return req_html($s);}; // Pentest 2026-11-04 : certificats/sites distants (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1.
    $cfg=is_array($w["config"] ?? null)?$w["config"]:array();
    $supported=!empty($cfg["netns_supported"]);
    $pols=is_array($w["policies_netns"] ?? null)?$w["policies_netns"]:array();
    $spaces=is_array($w["namespaces"] ?? null)?$w["namespaces"]:array();
    $exists=array();
    foreach($spaces as $ns){if(is_array($ns)){$exists[intval($ns["id"] ?? 0)]=!empty($ns["exists"])&&!empty($ns["enabled"]);}}
    $pill=$supported?"<span class='ak-pill ak-ok'>{rdpproxy_netns_supported}</span>":"<span class='ak-pill ak-off'>{rdpproxy_netns_not_supported}</span>";
    $h=array();
    $h[]="<div class='rab-card' style='margin-bottom:24px'><div class='rab-body'>";
    $h[]=about_card_head("<i class='fas fa-layer-group'></i>&nbsp;{rdpproxy_netns_title}",$pill);
    $h[]="<div class='rab-sub' style='font-size:15px;line-height:1.6;color:#334155'>{rdpproxy_netns_explain}</div>";
    if(count($pols)==0){
        $h[]="<div class='rab-sub'><span class='awc-none'>{rdpproxy_netns_no_policy}</span></div>";
    }else{
        $h[]="<div class='rab-sub'><div class='rwv-tags'>";
        foreach($pols as $pl){
            if(!is_array($pl)){continue;}
            $ok=$exists[intval($pl["netns_id"] ?? 0)] ?? false;
            $nsname=(string)($pl["netns_name"] ?? "");
            if($nsname==""){$nsname="#".intval($pl["netns_id"] ?? 0);}
            $warn=$ok?"":" <i class='fas fa-triangle-exclamation' style='color:#ba1a1a' title='{rdpproxy_netns_missing}'></i>";
            $h[]="<span class='awc-tag'><i class='fas fa-shield-halved'></i> ".$esc($pl["policy_name"] ?? "")
                ." &rarr; <strong>".$esc($nsname)."</strong>$warn</span>";
        }
        $h[]="</div></div>";
    }
    $h[]="</div>";
    $link="";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNamespaces"))==1){
        $link="<a href=\"javascript:blur();\" OnClick=\"LoadAjax('MainContent','fw.namespaces.php')\" class='rab-foot-ok'><i class='fas fa-arrow-right'></i> {network_namespaces}</a>";
    }
    $h[]="<div class='rab-foot'><span>{rdpproxy_netns_available}: ".count($spaces)."</span><span class='rab-foot-v'>$link</span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

/**
 * Proxys amont : Chromium -> proxy de filtrage de la session -> proxy amont de la politique.
 * Un proxy desactive refuse la session (jamais de sortie directe).
 */
function webview_proxy_card($w):string{
    $esc=function($s){return req_html($s);}; // Pentest 2026-11-04 : certificats/sites distants (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1.
    $pols=is_array($w["policies_proxy"] ?? null)?$w["policies_proxy"]:array();
    $proxies=is_array($w["proxies"] ?? null)?$w["proxies"]:array();
    $on=array();
    foreach($proxies as $px){if(is_array($px)){$on[intval($px["id"] ?? 0)]=!empty($px["enabled"]);}}
    $h=array();
    $h[]="<div class='rab-card' style='margin-bottom:24px'><div class='rab-body'>";
    $h[]=about_card_head("<i class='fas fa-network-wired'></i>&nbsp;{rdpproxy_webproxies}","<span class='ak-pill ak-off'>".count($proxies)."</span>");
    $h[]="<div class='rab-sub' style='font-size:15px;line-height:1.6;color:#334155'>{rdpproxy_webproxies_explain}</div>";
    if(count($pols)==0){
        $h[]="<div class='rab-sub'><span class='awc-none'>{rdpproxy_webproxy_no_policy}</span></div>";
    }else{
        $h[]="<div class='rab-sub'><div class='rwv-tags'>";
        foreach($pols as $pl){
            if(!is_array($pl)){continue;}
            $ok=$on[intval($pl["proxy_id"] ?? 0)] ?? false;
            $name=(string)($pl["proxy_name"] ?? "");
            if($name==""){$name="#".intval($pl["proxy_id"] ?? 0);}
            $warn=$ok?"":" <i class='fas fa-triangle-exclamation' style='color:#ba1a1a' title='{rdpproxy_webproxy_disabled_refused}'></i>";
            $h[]="<span class='awc-tag'><i class='fas fa-shield-halved'></i> ".$esc($pl["policy_name"] ?? "")
                ." &rarr; <strong>".$esc($name)."</strong>$warn</span>";
        }
        $h[]="</div></div>";
    }
    $h[]="</div>";
    $h[]="<div class='rab-foot'><span></span><span class='rab-foot-v'><a href='/rdpproxy-connections' class='rab-foot-ok'><i class='fas fa-arrow-right'></i> {rdpproxy_webproxy_manage}</a></span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

// Hote de l'onglet Parametres : rechargeable apres l'enregistrement des plafonds de reprise.
function webview_params_host(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-webview-params'></div><script>LoadAjaxSilent('rdpproxy-webview-params','$page?webview-params=yes');</script>";
}

// ============================================== Sessions Web gardees apres une deconnexion
// Navigateurs gardes : GET /rdpproxy/webview/parked (une ligne par pont, jamais de jeton),
// fermeture POST /rdpproxy/webview/parked/{park_id}/close (NON verrouillee par Meta : action
// d'exploitation sur une session en cours, auditee rdpproxy.webview.parked.close ; elle ferme
// aussi une session en cours d'utilisation), plafonds GET/POST /rdpproxy/webview/keep (verrouilles
// par Meta, 423). articarest RDPProxy/webview_parked.go, http_handlers_parked.go, webview_keep.go.

// Onglet : un hote et une fonction de rechargement fixe (RwkReload) appelee apres chaque action.
function webview_keep_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $TINY=array();
    $TINY["TITLE"]="{rdpproxy_webkeep_tab}";
    $TINY["ICO"]="fas fa-clock-rotate-left";
    $TINY["EXPL"]="{rdpproxy_webkeep_explain}";
    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?webview-keep-js=yes')",ico_params,"{rdpproxy_webkeep_settings}");
    $topbuttons[]=array("RwkReload();",ico_refresh,"{refresh}");
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    echo "<div style='margin-top:10px'></div><div id='rdpproxy-webview-keep'></div>";
    echo "<script>\nfunction RwkReload(){LoadAjaxSilent('rdpproxy-webview-keep','$page?webview-keep-table=yes');}\nRwkReload();\n";
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');\n</script>";
}

// Texte d'un operateur ou d'une cible : echappe, accolades neutralisees (jamais un jeton), points
// en entite (js_dialog_confirm_action coupe la ligne apres chaque point), antislash en entite.
function webview_keep_h($v):string{
    return str_replace(array("{","}",".","\\"),array("&#123;","&#125;","&#46;","&#92;"),req_html((string)$v));
}

function webview_keep_rank($state):int{
    $r=array("parked"=>0,"active"=>1,"closing"=>2,"ended"=>3);
    return isset($r[$state])?$r[$state]:4;
}

function webview_keep_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $st=rdpproxy_web_keep_state();
    $error="";
    $list=rdpproxy_web_parked($error);
    $top=array();
    $top[]="<div class='ak'>";
    if(!$st["supported"]){
        $top[]="<div class='ak-banner ak-warn rwv-banner'><i class='fas fa-triangle-exclamation'></i><div>{rdpproxy_web_keep_unsupported_".$st["cause"]."}</div></div>";
    }
    if($error!=""){
        $top[]="</div>";
        echo webview_css().$tpl->_ENGINE_parse_body(@implode("\n",$top).$tpl->div_error(req_html($error)));
        return;
    }
    $gerr="";
    $g=rdpproxy_web_keep_globals($gerr);
    if($gerr==""){
        $max=intval($g["max"])==0?"{unlimited}":intval($g["max"]);
        $top[]="<div class='ak-banner ak-info rwv-banner'><i class='fas fa-circle-info'></i><div>{rdpproxy_webkeep_explain}<br>"
            ."{rdpproxy_webkeep_max}: <strong>$max</strong> &middot; {rdpproxy_webkeep_min_free}: <strong>".intval($g["min_free_mb"])." MB</strong></div></div>";
    }
    $top[]="</div>";
    usort($list,function($a,$b){
        $ra=webview_keep_rank(is_array($a)?(string)($a["state"] ?? ""):"");
        $rb=webview_keep_rank(is_array($b)?(string)($b["state"] ?? ""):"");
        if($ra!=$rb){return $ra<$rb?-1:1;}
        $ea=is_array($a)?intval($a["expires"] ?? 0):0;
        $eb=is_array($b)?intval($b["expires"] ?? 0):0;
        if($ea==$eb){return 0;}
        return $ea<$eb?-1:1;
    });
    $canClose=$tpl->IsSecurity("AsSquidAdministrator");
    $now=time();
    $rows=array();
    foreach($list as $s){
        if(!is_array($s)){continue;}
        $pid=strtolower((string)($s["park_id"] ?? ""));
        if(!preg_match('/^[0-9a-f]{32}$/',$pid)){continue;}
        $state=(string)($s["state"] ?? "");
        if(!in_array($state,array("active","parked","closing","ended"),true)){$state="ended";}
        $alive=!empty($s["alive"]);
        $user=trim((string)($s["user"] ?? ""));
        $target=trim((string)($s["target"] ?? ""));
        if($target==""){$target="#".intval($s["target_id"] ?? 0);}
        $ip=trim((string)($s["client_ip"] ?? ""));
        $keep=intval($s["keep_minutes"] ?? 0);
        $parkedAt=intval($s["parked_at"] ?? 0);
        $expires=intval($s["expires"] ?? 0);
        $rss=intval($s["rss_mb"] ?? 0);
        $off=($state=="closing"||$state=="ended");

        $r="<tr>";
        $r.="<td><div class='awc-ident'><span class='awc-ico".($off?" awc-ico-off":"")."'><i class='fas fa-user'></i></span>"
            ."<span><span class='awc-name".($off?" awc-name-off":"")."'>".($user==""?"-":webview_keep_h($user))."</span>"
            ."<div class='awc-meta'>{rdpproxy_webkeep_policy} #".intval($s["policy_id"] ?? 0)."</div></span></div></td>";
        $r.="<td>".webview_keep_h($target)."</td>";
        $r.="<td class='awc-mono' style='white-space:nowrap'>".($ip==""?"&mdash;":webview_keep_h($ip))."</td>";
        if($state=="parked" && $parkedAt>0){
            $ago=max(0,intval(($now-$parkedAt)/60));
            $r.="<td style='white-space:nowrap'>".webview_keep_h($tpl->time_to_date($parkedAt,true))."<div class='awc-meta'>$ago min</div></td>";
        }elseif($state=="active"){
            $r.="<td><span class='awc-none'>{rdpproxy_webkeep_in_use}</span></td>";
        }else{
            $r.="<td>&mdash;</td>";
        }
        if($off){
            $r.="<td>&mdash;</td>";
        }elseif($expires>0){
            $left=max(0,intval(($expires-$now+59)/60));
            $r.="<td style='white-space:nowrap'>".webview_keep_h($tpl->time_to_date($expires,true))."<div class='awc-meta'>{rdpproxy_webkeep_remaining}: $left min</div></td>";
        }else{
            $r.="<td style='white-space:nowrap'><span class='awc-none'>$keep min {rdpproxy_webkeep_after_disc}</span></td>";
        }
        $r.="<td class='awc-mono' style='white-space:nowrap'>".($alive&&$rss>0?"$rss MB":"&mdash;")."</td>";
        if($state=="active"){
            $badge="<span class='awc-badge awc-badge-on'>{rdpproxy_webkeep_state_active}</span>";
        }elseif($state=="parked"){
            $badge="<span class='awc-badge' style='background:#865300'>{rdpproxy_webkeep_state_parked}</span>";
        }else{
            $badge="<span class='awc-badge awc-badge-off'>{rdpproxy_webkeep_state_$state}</span>";
        }
        $r.="<td class='center'>$badge</td>";
        $act="";
        if($canClose && $alive && ($state=="active"||$state=="parked")){
            $act="<a class='awc-act awc-act-danger' title='{rdpproxy_webkeep_close}' aria-label='{rdpproxy_webkeep_close}' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?webview-parked-close-js=$pid')\"><i class='fas fa-power-off'></i></a>";
        }
        $r.="<td><div class='awc-acts'>$act</div></td>";
        $r.="</tr>";
        $rows[]=$r;
    }
    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{rdpproxy_webkeep_col_user}</th><th>{rdpproxy_webkeep_col_target}</th><th>{rdpproxy_webkeep_col_client}</th>";
    $html[]="<th>{rdpproxy_webkeep_col_since}</th><th>{rdpproxy_webkeep_col_expires}</th><th>{rdpproxy_webkeep_col_memory}</th>";
    $html[]="<th class='center'>{status}</th><th style='text-align:right'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='rwk-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='8'><div class='awc-empty'>{rdpproxy_webkeep_none}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>".count($rows)."</span><span class='awc-pg' id='rwk-pager'></span></div>";
    $html[]="</div></div>";
    echo awc_table_style().webview_css().$tpl->_ENGINE_parse_body(@implode("\n",$top).@implode("\n",$html))."<script>NoSpinner();</script>".awc_pager_js(25,"rwk-rows","rwk-pager");
}

// Confirmation : nomme l'utilisateur et la cible ; une session EN COURS d'utilisation est
// coupee aussi (le daemon ferme les deux etats), le texte le dit.
function webview_parked_close_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $pid=strtolower(preg_replace('/[^A-Fa-f0-9]/','',(string)$_GET["webview-parked-close-js"]));
    if(strlen($pid)!=32){$tpl->js_error("{rdpproxy_webkeep_err_not_found}");return;}
    $user="";$target="";$state="";
    $err="";
    foreach(rdpproxy_web_parked($err) as $s){
        if(!is_array($s)||strtolower((string)($s["park_id"] ?? ""))!==$pid){continue;}
        $user=trim((string)($s["user"] ?? ""));
        $target=trim((string)($s["target"] ?? ""));
        $state=(string)($s["state"] ?? "");
    }
    if($state==""){$tpl->js_error("{rdpproxy_webkeep_err_not_found}");return;}
    $text=$state=="active"?"{rdpproxy_webkeep_close_confirm_active}":"{rdpproxy_webkeep_close_confirm}";
    $who="<b>".($user==""?"-":webview_keep_h($user))."</b> &rarr; <b>".webview_keep_h($target)."</b>";
    $tpl->js_dialog_confirm_action("$who<br>$text","RDPWebParkedClose",$pid,"if(typeof RwkReload==='function'){RwkReload();}");
}

// Reponse vide = succes ; sinon le texte est affiche par la confirmation (HTML).
function webview_parked_close_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $pid=strtolower(preg_replace('/[^A-Fa-f0-9]/','',(string)$_POST["RDPWebParkedClose"]));
    $err=rdpproxy_web_parked_close($pid);
    if($err!=""){echo $tpl->_ENGINE_parse_body(htmlspecialchars($err,ENT_QUOTES));return;}
    admin_tracks("Closed the kept Web session $pid of the RDS Proxy");
}

function webview_keep_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_webkeep_settings}","$page?webview-keep-popup=yes",720);
}

// Plafonds globaux. Sous verrou Meta : lecture seule (le daemon refuse aussi, 423).
function webview_keep_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $err="";
    $g=rdpproxy_web_keep_globals($err);
    if($err!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error(req_html($err)));return;}
    $html=array();
    $form=array();
    $html[]=$tpl->div_explain("{rdpproxy_webkeep_settings}||{rdpproxy_webkeep_settings_explain}");
    if(rdpproxy_meta_locked()){
        $html[]=rdpproxy_meta_banner();
        rdpproxy_meta_lock_form($tpl);
        $form[]=rdpproxy_meta_ro($tpl,"{rdpproxy_webkeep_max}",intval($g["max"]));
        $form[]=rdpproxy_meta_ro($tpl,"{rdpproxy_webkeep_min_free}",intval($g["min_free_mb"])." MB");
    }else{
        $form[]=$tpl->field_hidden("RDPWebKeepSave","1");
        $form[]=$tpl->field_numeric("RDPWebKeepMax","{rdpproxy_webkeep_max}",intval($g["max"]),"{rdpproxy_webkeep_max_explain}");
        $form[]=$tpl->field_numeric("RDPWebKeepMinFree","{rdpproxy_webkeep_min_free}",intval($g["min_free_mb"]),"{rdpproxy_webkeep_min_free_explain}");
    }
    $after="dialogInstance2.close();if(typeof RwkReload==='function'){RwkReload();}"
        ."if(document.getElementById('rdpproxy-webview-params')){LoadAjaxSilent('rdpproxy-webview-params','$page?webview-params=yes');}";
    $html[]=$tpl->form_outside("",$form,"","{apply}",$after,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}

// Reponse vide = succes (form_outside) ; erreur par post_error (fenetre "Oops").
function webview_keep_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    if(rdpproxy_meta_locked()){echo $tpl->post_error("{rdpproxy_meta_locked_refused}");return;}
    $max=trim((string)($_POST["RDPWebKeepMax"] ?? ""));
    $min=trim((string)($_POST["RDPWebKeepMinFree"] ?? ""));
    $err=rdpproxy_web_keep_globals_save($max,$min);
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks("Saved the kept Web sessions limits of the RDS Proxy: max=".intval($max).", min_free_mb=".intval($min));
}

// Rappel a cote des actions qui arretent le service : "" quand la reprise n'est pas prise en charge.
function webview_keep_restart_note():string{
    if(!rdpproxy_web_keep_state()["supported"]){return "";}
    return "<div class='alert alert-warning' style='margin:8px 0 0 0;font-size:13px'><i class='fas fa-triangle-exclamation'></i>&nbsp;{rdpproxy_webkeep_restart_warning}</div>";
}

// Parametres du navigateur : lus chez artica-webview (-print-config), jamais recopies ici.
// Lecture seule, sauf la section des sessions Web gardees (plafonds globaux, crayon desarme apres).
function webview_params(){
    $tpl=new template_admin();
    $error="";
    $w=rdpproxy_webview($error);
    if($error!=""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($error));
        return;
    }
    $cfg=$w["config"] ?? null;
    if(!is_array($cfg)){
        $why=htmlspecialchars((string)($w["config_error"] ?? ""),ENT_QUOTES);
        echo $tpl->_ENGINE_parse_body("<div style='margin-top:10px'>".$tpl->div_warning("{rdpproxy_webview_no_config}||$why")."</div>");
        return;
    }
    $esc=function($s){return req_html($s);}; // Pentest 2026-11-04 : certificats/sites distants (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1.
    $list=function($a) use ($esc){
        if(!is_array($a)||count($a)==0){return "-";}
        $o=array();
        foreach($a as $v){$o[]=$esc($v);}
        return @implode(", ",$o);
    };
    $hd=is_array($cfg["hardening"] ?? null)?$cfg["hardening"]:array();
    $ng=is_array($cfg["network_guard"] ?? null)?$cfg["network_guard"]:array();
    $tr=is_array($cfg["trust"] ?? null)?$cfg["trust"]:array();
    $mb=function($k) use ($hd){return isset($hd[$k])?intval($hd[$k])." MB":"-";};

    // Sessions Web gardees apres une deconnexion : seuls reglages modifiables de l'onglet.
    $page=CurrentPageName();
    $kst=rdpproxy_web_keep_state();
    $kerr="";
    $kg=rdpproxy_web_keep_globals($kerr);
    $tpl->table_form_section("{rdpproxy_webkeep_settings}","{rdpproxy_webkeep_settings_explain}");
    if(!$kst["supported"]){$tpl->table_form_field_warn("{rdpproxy_web_keep_unsupported_".$kst["cause"]."}");}
    if($kerr!=""){
        $tpl->table_form_field_warn(req_html($kerr));
    }else{
        $tpl->table_form_field_js("Loadjs('$page?webview-keep-js=yes')","AsSquidAdministrator");
        $tpl->table_form_field_text("{rdpproxy_webkeep_max}",intval($kg["max"])==0?"{unlimited}":intval($kg["max"]),ico_params);
        $tpl->table_form_field_js("Loadjs('$page?webview-keep-js=yes')","AsSquidAdministrator");
        $tpl->table_form_field_text("{rdpproxy_webkeep_min_free}",intval($kg["min_free_mb"])." MB",ico_memory);
    }
    $tpl->table_form_field_js("");

    $tpl->table_form_section("{rdpproxy_webview_sec_browser}");
    $tpl->table_form_field_text("{rdpproxy_webview_browser_path}",$esc($cfg["browser"] ?? ""),ico_server);
    $tpl->table_form_field_text("{rdpproxy_webview_sandbox_path}",$esc($cfg["sandbox"] ?? ""),ico_server);
    $tpl->table_form_field_text("{rdpproxy_webview_bridge_version}",$esc($cfg["version"] ?? ""),ico_version);
    $tpl->table_form_field_text("{rdpproxy_webview_uid}",intval($cfg["uid"] ?? 0),ico_user);
    $tpl->table_form_field_text("{rdpproxy_webview_screencast}||{rdpproxy_webview_screencast_explain}",
        strtoupper($esc($cfg["screencast_format"] ?? ""))." / ".intval($cfg["tile_size"] ?? 0)." px",ico_computer);

    $tpl->table_form_section("{rdpproxy_webview_sec_hardening}","{rdpproxy_webview_sec_hardening_explain}");
    $tpl->table_form_field_text("{rdpproxy_webview_namespaces}",$list($cfg["namespaces"] ?? array()),ico_shield);
    $tpl->table_form_field_bool("{rdpproxy_webview_cap_drop}||{rdpproxy_webview_cap_drop_explain}",!empty($hd["cap_drop_all"])?1:0,ico_shield);
    $tpl->table_form_field_text("{rdpproxy_webview_core_dumps}",!empty($hd["core_dumps"])?"{enabled}":"{disabled}",ico_shield);
    $tpl->table_form_field_text("{rdpproxy_webview_tmp_size}||{rdpproxy_webview_tmp_size_explain}",$mb("tmp_size_mb"),ico_hd);
    $tpl->table_form_field_text("{rdpproxy_webview_shm_size}",$mb("shm_size_mb"),ico_hd);
    $tpl->table_form_field_text("{rdpproxy_webview_max_file}",$mb("max_file_mb"),ico_hd);
    $tpl->table_form_field_text("{rdpproxy_webview_js_heap}",$mb("js_heap_mb"),ico_memory);
    $tpl->table_form_field_text("{rdpproxy_webview_renderer_limit}",isset($hd["renderer_limit"])?intval($hd["renderer_limit"]):"-",ico_memory);
    $tpl->table_form_field_text("{rdpproxy_webview_nofile}",isset($hd["nofile"])?intval($hd["nofile"]):"-",ico_file);
    $tpl->table_form_field_text("{rdpproxy_webview_read_only}||{rdpproxy_webview_read_only_explain}",$list($cfg["read_only"] ?? array()),ico_folder);

    $tpl->table_form_section("{rdpproxy_webview_sec_network}","{rdpproxy_webview_sec_network_explain}");
    $tpl->table_form_field_bool("{rdpproxy_webview_guard_enabled}",!empty($ng["enabled"])?1:0,ico_firewall);
    $tpl->table_form_field_bool("{rdpproxy_webview_deny_local}",!empty($ng["deny_local"])?1:0,ico_firewall);
    $tpl->table_form_field_text("{rdpproxy_webview_denied}",$list($ng["denied"] ?? array()),ico_firewall);
    $tpl->table_form_field_bool("{rdpproxy_netns_title}||{rdpproxy_netns_param_explain}",!empty($cfg["netns_supported"])?1:0,ico_firewall);

    $tpl->table_form_section("{rdpproxy_webview_sec_controls}");
    $controls=is_array($cfg["controls"] ?? null)?$cfg["controls"]:array();
    // un pont qui prend en charge les téléchargements contrôlés ne publie plus downloads_denied
    $dlCtl=!empty($cfg["download_supported"])?"downloads_controlled":"downloads_denied";
    foreach(array("allow_list",$dlCtl,"file_chooser_blocked","popups_closed","clipboard_audited","network_guard") as $k){
        $tpl->table_form_field_bool("{rdpproxy_webview_ctl_$k}",in_array($k,$controls,true)?1:0,ico_check);
    }
    $tpl->table_form_field_bool("{rdpproxy_weblogin_param}||{rdpproxy_weblogin_param_explain}",!empty($cfg["login_supported"])?1:0,ico_check);
    $tpl->table_form_field_bool("{rdpproxy_webkeep_param}||{rdpproxy_webkeep_param_explain}",!empty($cfg["keep_supported"])?1:0,ico_check);

    $tpl->table_form_section("{rdpproxy_webview_sec_trust}");
    $tpl->table_form_field_text("{rdpproxy_webview_trust_count}",intval($tr["count"] ?? 0),ico_certificate);
    $tpl->table_form_field_text("{rdpproxy_webview_trust_file}",$esc($tr["file"] ?? "-"),ico_file);

    $html=array();
    $html[]="<div style='margin-top:10px'>".$tpl->table_form_compile(2)."</div>";
    $flags=is_array($cfg["chromium_flags"] ?? null)?$cfg["chromium_flags"]:array();
    $f=array();
    $f[]="<div class='rwv-flags'><div class='rwv-flags-title'>{rdpproxy_webview_sec_flags}</div>";
    foreach($flags as $fl){$f[]="<span class='ak-tag ak-off rwv-flag'>".$esc($fl)."</span>";}
    $f[]="</div>";
    $html[]=$tpl->_ENGINE_parse_body(@implode("",$f));
    echo webview_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    echo "<script>".webview_tinydefault()."</script>";
}

// --------------------------------------------------------- Certificats de confiance

function webview_trust_start(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=array();

    $TINY=array();
    $TINY["TITLE"]="{rdpproxy_webview_trusted_certs}";
    $TINY["ICO"]=ico_ok;
    $TINY["EXPL"]="{rdpproxy_webview_trust_explain}";

    if(!rdpproxy_meta_locked()){
        $topbuttons[] = array("Loadjs('$page?webview-trust-add-js=yes')"
        , ico_plus, "{rdpproxy_webview_trust_add}");
    }

    $topbuttons[] = array("LoadAjax('rdpproxy-webview-trust','$page?webview-trust-table=yes')"
    , ico_refresh, "{refresh}");

    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);



    $html[]="<div style='margin-top:10px'>&nbsp;</div>";
    $html[]=rdpproxy_meta_banner();
    $html[]="<div id='rdpproxy-webview-trust'></div>";
    $html[]="<script>LoadAjax('rdpproxy-webview-trust','$page?webview-trust-table=yes');";
    $html[]="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function webview_trust_table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $error="";$file=array();
    $certs=rdpproxy_webview_trust($error,$file);
    if($error!=""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($error));
        return;
    }
    $canEdit=$tpl->IsSecurity("AsSquidAdministrator") && !rdpproxy_meta_locked();
    $esc=function($s){return req_html($s);}; // Pentest 2026-11-04 : certificats/sites distants (donnee externe) ; htmlspecialchars UTF-8 rendait "" sur du Latin-1.
    $rows=array();
    foreach($certs as $c){
        if(!is_array($c)){continue;}
        $spki=(string)($c["spki"] ?? "");
        $hex=bin2hex((string)base64_decode($spki,true));
        $expired=!empty($c["expired"]);
        $subject=(string)($c["subject"] ?? "");
        if($subject==""){$subject="-";}
        $type="{rdpproxy_webview_trust_leaf}";$ico="fas fa-certificate";
        if(!empty($c["is_ca"])){$type="{rdpproxy_webview_trust_ca}";$ico="fas fa-building-shield";}
        if(!empty($c["self_signed"])){$type.=" &middot; {rdpproxy_webview_trust_self_signed}";}
        $names=array();
        foreach(($c["dns_names"] ?? array()) as $n){$names[]="<span class='awc-tag'>".$esc($n)."</span>";}
        $namesHtml=count($names)==0?"<span class='awc-none'>{none}</span>":"<div class='rwv-tags'>".@implode("",$names)."</div>";
        $until=intval($c["not_after"] ?? 0)>0?$esc($tpl->time_to_date(intval($c["not_after"]),true)):"-";
        $badge=$expired?"<span class='awc-badge awc-badge-off' style='background:#ba1a1a'>{rdpproxy_webview_trust_expired}</span>":"<span class='awc-badge awc-badge-on'>{valid}</span>";
        $meta="{rdpproxy_webview_trust_issuer}: ".$esc($c["issuer"] ?? "")."<br>{rdpproxy_webview_trust_spki}: <span style='word-break:break-all'>".$esc($spki)."</span>";
        $comment=trim((string)($c["comment"] ?? ""));
        if($comment!=""){$meta.="<br><i class='fas fa-comment'></i> ".$esc($comment);}
        $r="<tr>";
        $r.="<td><div class='awc-ident'><span class='awc-ico".($expired?" awc-ico-off":"")."'><i class='$ico'></i></span>"
            ."<span><span class='awc-name".($expired?" awc-name-off":"")."'>".$esc($subject)."</span>"
            ."<div class='awc-meta'>$meta</div></span></div></td>";
        $r.="<td class='awc-mono' style='white-space:nowrap'>$type</td>";
        $r.="<td>$namesHtml</td>";
        $r.="<td class='awc-mono' style='white-space:nowrap'>$until</td>";
        $r.="<td class='center'>$badge</td>";
        $act="";
        if($canEdit && $hex!=""){
            $act="<a class='awc-act' title='{rdpproxy_tls_policy}' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?webview-trust-edit-js=$hex')\"><i class='fas fa-pen'></i></a>";
            $act.="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?webview-trust-remove-js=$hex')\"><i class='fas fa-trash-can'></i></a>";
        }
        $r.="<td><div class='awc-acts'>$act</div></td>";
        $r.="</tr>";
        $rows[]=$r;
    }
    $html=array();
    if(isset($file["in_sync"]) && !$file["in_sync"]){
        $html[]=$tpl->div_warning("{rdpproxy_webview_trusted_certs}||{rdpproxy_webview_trust_file_out_of_sync}");
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{rdpproxy_webview_trust_subject}</th><th>{rdpproxy_webview_trust_type}</th><th>{rdpproxy_webview_trust_names}</th>";
    $html[]="<th>{rdpproxy_webview_trust_validity}</th><th class='center'>{status}</th><th style='text-align:right'>&nbsp;</th>";
    $html[]="</tr></thead><tbody id='rwv-trust-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='6'><div class='awc-empty'>{rdpproxy_webview_trust_empty}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>".count($rows)." / 64</span><span class='awc-pg' id='rwv-trust-pager'></span></div>";
    $html[]="</div></div>";
    echo awc_table_style().webview_css().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>NoSpinner();</script>";
}

function webview_trust_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_webview_trust_add}","$page?webview-trust-add-popup=yes",850);
}

function webview_trust_add_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return false;}
    $form=array();
    $form[]=$tpl->field_hidden("RDPWebTrustAdd","1");
    $form[]=$tpl->field_textarea_normal("RDPWebTrustPEM","{rdpproxy_webview_trust_pem}","","100%","300px","{rdpproxy_webview_trust_pem_explain}");
    $form[]=$tpl->field_text("RDPWebTrustComment","{comment}","",false);
    $html=array();
    rdpproxy_meta_lock_form($tpl);
    $html[]=$tpl->div_explain("{rdpproxy_webview_trust_add}||{rdpproxy_webview_trust_add_explain}");
    $html[]=$tpl->form_outside("",@implode("\n",$form),null,"{add}",
        "dialogInstance2.close();LoadAjax('rdpproxy-webview-trust','$page?webview-trust-table=yes');","AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// N'ecrit RIEN en cas de succes. Une seule couche encodeURIComponent : rawurldecode.
function webview_trust_add_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $pem=rawurldecode((string)($_POST["RDPWebTrustPEM"] ?? ""));
    $comment=trim(rawurldecode((string)($_POST["RDPWebTrustComment"] ?? "")));
    if(trim($pem)==""){echo $tpl->post_error("{rdpproxy_webview_trust_pem}: {cannot_be_a_null_value}");return;}
    $added=array();
    $err=rdpproxy_webview_trust_add($pem,$comment,$added);
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks("Added ".count($added)." trusted certificate(s) for RDS Proxy web targets (".@implode(", ",$added).")");
}

// Modification : seul le commentaire change (un autre certificat = une autre empreinte = un ajout).
function webview_trust_find($hex):array{
    $spki=base64_encode((string)hex2bin($hex));
    $err="";$file=array();
    foreach(rdpproxy_webview_trust($err,$file) as $c){
        if(is_array($c)&&($c["spki"] ?? "")===$spki){return $c;}
    }
    return array();
}

function webview_trust_edit_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $hex=preg_replace('/[^a-f0-9]/','',strtolower((string)$_GET["webview-trust-edit-js"]));
    return $tpl->js_dialog2("{rdpproxy_webview_trusted_certs}","$page?webview-trust-edit-popup=$hex",650);
}

function webview_trust_edit_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return false;}
    $hex=preg_replace('/[^a-f0-9]/','',strtolower((string)$_GET["webview-trust-edit-popup"]));
    $c=webview_trust_find($hex);
    if(count($c)==0){echo $tpl->_ENGINE_parse_body($tpl->div_error("{rdpproxy_webview_err_not_found}"));return false;}
    $esc=function($v){return req_html($v);}; // Pentest 2026-11-04 : donnee externe ; htmlspecialchars UTF-8 rendait "" sur du Latin-1.
    $form=array();
    rdpproxy_meta_lock_form($tpl);
    $form[]=$tpl->field_hidden("RDPWebTrustEdit",$hex);
    $form[]=$tpl->field_info("subject","{rdpproxy_webview_trust_subject}",$esc($c["subject"] ?? ""));
    $form[]=$tpl->field_info("spki","{rdpproxy_webview_trust_spki}",$esc($c["spki"] ?? ""));
    $form[]=$tpl->field_text("RDPWebTrustComment","{comment}",(string)($c["comment"] ?? ""),false);
    echo $tpl->_ENGINE_parse_body($tpl->form_outside("",@implode("\n",$form),null,"{apply}",
        "dialogInstance2.close();LoadAjax('rdpproxy-webview-trust','$page?webview-trust-table=yes');","AsSquidAdministrator"));
    return true;
}

// N'ecrit RIEN en cas de succes.
function webview_trust_edit_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $hex=preg_replace('/[^a-f0-9]/','',strtolower((string)$_POST["RDPWebTrustEdit"]));
    if($hex==""||strlen($hex)%2!=0){echo $tpl->post_error("{rdpproxy_webview_err_not_found}");return;}
    $spki=base64_encode((string)hex2bin($hex));
    $comment=trim(rawurldecode((string)($_POST["RDPWebTrustComment"] ?? "")));
    $err=rdpproxy_webview_trust_comment($spki,$comment);
    if($err!=""){echo $tpl->post_error($err);return;}
    admin_tracks("Changed the comment of the trusted certificate $spki of RDS Proxy web targets");
}

function webview_trust_remove_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $hex=preg_replace('/[^a-f0-9]/','',strtolower((string)$_GET["webview-trust-remove-js"]));
    $spki=base64_encode((string)hex2bin($hex));
    $label=$spki;
    $err="";$file=array();
    foreach(rdpproxy_webview_trust($err,$file) as $c){
        if(is_array($c)&&($c["spki"] ?? "")===$spki){$label=(string)($c["subject"] ?? $spki);}
    }
    $tpl->js_confirm_delete($label,"RDPWebTrustRemove",$hex,
        "LoadAjax('rdpproxy-webview-trust','$page?webview-trust-table=yes');");
}

// Reponse vide = succes (js_confirm_delete) ; sinon le texte est affiche tel quel (HTML).
function webview_trust_remove_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return;}
    $hex=preg_replace('/[^a-f0-9]/','',strtolower((string)$_POST["RDPWebTrustRemove"]));
    if($hex==""||strlen($hex)%2!=0){echo $tpl->_ENGINE_parse_body("{rdpproxy_webview_err_not_found}");return;}
    $spki=base64_encode((string)hex2bin($hex));
    $err=rdpproxy_webview_trust_remove($spki);
    if($err!=""){echo $tpl->_ENGINE_parse_body(htmlspecialchars($err,ENT_QUOTES));return;}
    admin_tracks("Removed the trusted certificate $spki of RDS Proxy web targets");
}


// ------------------------------------------------------------ Strategies TLS vers les cibles
// Une strategie abaisse la securite TLS/RDP pour les SEULES cibles qui la portent (Windows 7,
// 2008) ; elle se choisit dans la fenetre de la cible. articarest RDPProxy/tls_policies.go.
function tls_policies_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_tls_policies}","$page?tls-policies-popup=yes",950);
}
function tls_policies_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]=rdpproxy_meta_banner();
    $html[]=$tpl->div_explain("{rdpproxy_tls_policies}||{rdpproxy_tls_policies_explain}");
    $add=rdpproxy_meta_locked()?"":$tpl->button_autnonome("{rdpproxy_tls_policy_new}","Loadjs('$page?tls-policy-js=yes&id=0')",ico_plus,"AsSquidAdministrator");
    $html[]="<div style='text-align:right;margin-bottom:10px'>$add</div>";
    $html[]="<div id='rdp-tlsp-list'></div>";
    $html[]="<script>LoadAjaxSilent('rdp-tlsp-list','$page?tls-policies-list=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tls_policy_levels():array{
    return array("0"=>"TLS 1.0","1"=>"TLS 1.1","2"=>"TLS 1.2","3"=>"TLS 1.3");
}
function tls_policies_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $error="";
    $list=rdpproxy_tls_policies($error);
    if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error($error));return false;}
    $levels=tls_policy_levels();
    $html[]="<div class='awc-wrap' style='padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr><th>{name}</th><th>{minimal_tls_version}</th><th>{rdpproxy_tls_policy_options}</th><th class='center'>{targets}</th><th style='text-align:right'>{actions}</th></tr></thead><tbody>";
    if(count($list)==0){
        $html[]="<tr><td colspan='5'><div class='awc-empty'>{rdpproxy_tls_policies_none}</div></td></tr>";
    }
    foreach($list as $p){
        $id=intval($p["id"]);
        $lvl=intval($p["tls_min_level"]);
        $name=htmlspecialchars((string)$p["name"]);
        $desc=htmlspecialchars((string)$p["description"]);
        $opts=array();
        if(!empty($p["rdp_legacy"])){$opts[]="<span class='awc-tag'>{rdpproxy_tls_policy_rdp_legacy}</span>";}
        if(!empty($p["legacy_renegotiation"])){$opts[]="<span class='awc-tag'>{rdpproxy_tls_policy_renegotiation}</span>";}
        $n=is_array($p["ciphers"])?count($p["ciphers"]):0;
        $opts[]="<span class='awc-tag'>".($n==0?"{rdpproxy_tls_policy_bastion_ciphers}":"$n {rdpproxy_tls_policy_ciphers_count}")."</span>";
        $badge=$lvl<2?"<span class='awc-badge' style='background:#865300'>".$levels[$lvl]."</span>":"<span class='awc-badge awc-badge-on'>".$levels[$lvl]."</span>";
        $edit="Loadjs('$page?tls-policy-js=yes&id=$id')";
        $h="<tr><td><div class='awc-ident'><span class='awc-ico'><i class='fa-solid fa-scale-unbalanced'></i></span><span>";
        $h.="<span class='awc-name'><a href=\"javascript:blur();\" OnClick=\"$edit\">$name</a></span>";
        if($desc!=""){$h.="<div class='awc-meta'>$desc</div>";}
        $h.="</span></div></td><td>$badge</td><td>".@implode(" ",$opts)."</td>";
        $h.="<td class='center awc-mono'>".intval($p["targets"])."</td>";
        if(rdpproxy_meta_locked()){$h.="<td>&nbsp;</td></tr>";$html[]=$h;continue;}
        $h.="<td><div class='awc-acts'><a class='awc-act' title='{rdpproxy_tls_policy}' href=\"javascript:blur();\" OnClick=\"$edit\"><i class='fa-solid fa-pen'></i></a>";
        $h.="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?tls-policy-delete-js=$id&name=".urlencode((string)$p["name"])."')\"><i class='fas fa-trash-can'></i></a></div></td></tr>";
        $html[]=$h;
    }
    $html[]="</tbody></table></div></div></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}
function tls_policy_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $title=$id==0?"{rdpproxy_tls_policy_new}":"{rdpproxy_tls_policy}";
    return $tpl->js_dialog3($title,"$page?tls-policy-popup=yes&id=$id",850);
}
function tls_policy_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $p=array("name"=>"","description"=>"","tls_min_level"=>0,"ciphers"=>array(),"rdp_legacy"=>false,"legacy_renegotiation"=>false);
    if($id>0){
        $error="";
        $p=rdpproxy_tls_policy($id,$error);
        if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error($error));return false;}
    }
    $cerr="";
    $catalog=rdpproxy_target_ciphers($cerr);
    if($cerr!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error($cerr));return false;}
    $sel=array();
    foreach((array)$p["ciphers"] as $c){$sel[(string)$c]=true;}
    $form[]=$tpl->field_hidden("RDPProxyTLSPolicySave",$id);
    $form[]=$tpl->field_text("name","{name}",(string)$p["name"],true);
    $form[]=$tpl->field_text("description","{description}",(string)$p["description"]);
    $form[]=$tpl->field_array_hash(tls_policy_levels(),"tls_min_level","{minimal_tls_version}",(string)intval($p["tls_min_level"]),true,"{rdpproxy_tls_policy_min_explain}");
    $form[]=$tpl->field_checkbox("rdp_legacy","{rdpproxy_tls_policy_rdp_legacy}",!empty($p["rdp_legacy"])?1:0,false,"{rdpproxy_tls_policy_rdp_legacy_explain}");
    $form[]=$tpl->field_checkbox("legacy_renegotiation","{rdpproxy_tls_policy_renegotiation}",!empty($p["legacy_renegotiation"])?1:0,false,"{rdpproxy_tls_policy_renegotiation_explain}");
    $form[]=$tpl->field_section("{rdpproxy_target_ciphers}","{rdpproxy_tls_policy_ciphers_explain}");
    foreach($catalog as $i=>$c){
        $label=htmlspecialchars((string)$c["name"]);
        if(isset($c["level"])&&$c["level"]=="legacy"){$label.=" <span class='label label-warning'>{rdpproxy_cipher_deprecated}</span>";}
        $form[]=$tpl->field_checkbox("tpcipher_".intval($i),$label,isset($sel[(string)$c["name"]])?1:0);
    }
    rdpproxy_meta_lock_form($tpl);
    $html[]=$tpl->div_explain("{rdpproxy_tls_policy}||{rdpproxy_tls_policy_explain}");
    $html[]=$tpl->form_outside("",$form,null,$id==0?"{add}":"{apply}",
        "dialogInstance3.close();LoadAjaxSilent('rdp-tlsp-list','$page?tls-policies-list=yes');","AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tls_policy_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $tpl->CLEAN_POST();
    $id=intval($_POST["RDPProxyTLSPolicySave"]);
    $error="";
    $catalog=rdpproxy_target_ciphers($error);
    if($error!=""){echo $tpl->post_error($error);return;}
    $names=array();
    foreach($catalog as $i=>$c){
        if(intval($_POST["tpcipher_".intval($i)] ?? 0)==1){$names[]=(string)$c["name"];}
    }
    $fields=array("name"=>(string)($_POST["name"] ?? ""),"description"=>(string)($_POST["description"] ?? ""),
        "tls_min_level"=>(string)intval($_POST["tls_min_level"] ?? 2),"ciphers"=>implode(":",$names),
        "rdp_legacy"=>intval($_POST["rdp_legacy"] ?? 0)==1?"1":"0",
        "legacy_renegotiation"=>intval($_POST["legacy_renegotiation"] ?? 0)==1?"1":"0");
    $error=rdpproxy_tls_policy_save($id,$fields);
    if($error!=""){echo $tpl->post_error($error);return;}
    admin_tracks_post("Save RDS Proxy TLS policy ".$fields["name"]);
}
function tls_policy_delete_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["tls-policy-delete-js"]);
    $name=(string)($_GET["name"] ?? "");
    $tpl->js_confirm_delete($name,"tls-policy-delete",$id,"LoadAjaxSilent('rdp-tlsp-list','$page?tls-policies-list=yes');");
}
function tls_policy_delete(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $id=intval($_POST["tls-policy-delete"]);
    $error=rdpproxy_tls_policy_delete($id);
    if($error!=""){echo $tpl->post_error($error);return;}
    admin_tracks("Delete RDS Proxy TLS policy $id");
}

// ------------------------------------------------------- NLA client : fenetres et actions
// Les enregistrements n'ecrivent RIEN en cas de succes (le $jsafter ferme et rafraichit).
// Le mot de passe de l'administrateur AD n'entre ni dans admin_tracks ni dans un journal :
// admin_tracks() avec un texte choisi, jamais admin_tracks_post().
function nla_post($key,$raw=false):string{
    // XHR.appendData envoie escape(encodeURIComponent(v)) : $_POST contient encodeURIComponent(v).
    // rawurldecode en est l'inverse exact (url_decode_special_tool ne decode qu'une liste fixe :
    // un mot de passe AD avec u-umlaut/eszett arrivait encode et verrouillait le compte).
    $v=isset($_POST[$key])?rawurldecode((string)$_POST[$key]):"";
    return $raw?$v:trim($v);
}
function nla_popup_state($tpl,$needLicense=true){
    $error="";
    $st=rdpproxy_nla($error);
    if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error($error));return null;}
    if($needLicense && empty($st["licensed"])){echo $tpl->_ENGINE_parse_body($tpl->div_error("{rdpproxy_nla_license_required}"));return null;}
    return $st;
}
function nla_join_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_nla_join}","$page?nla-join-popup=yes",720);
}
function nla_join_popup():bool{
    $tpl=new template_admin();
    $st=nla_popup_state($tpl);
    if(!is_array($st)){return false;}
    // Nom DNS prerempli avec celui deja enregistre, sinon le FQDN de l'appliance.
    $dns=trim((string)($st["dns_name"] ?? ""));
    if($dns==""){$dns=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("fqdn_hostname"));}
    if($dns==""){$dns=(string)gethostname();}
    $computer=trim((string)($st["computer"] ?? ""));
    if($computer==""){$computer=trim((string)($st["computer_hint"] ?? ""));}
    $form[]=$tpl->field_hidden("nla-join-save","1");
    $form[]=$tpl->field_text("domain","{rdpproxy_nla_domain}",strtolower((string)($st["realm"] ?? "")),true,"{rdpproxy_nla_domain_explain}");
    $form[]=$tpl->field_text("admin","{rdpproxy_nla_admin}","",true,"{rdpproxy_nla_admin_explain}");
    $form[]=$tpl->field_password("password","{password}","",true);
    $form[]=$tpl->field_text("dns_name","{rdpproxy_nla_dns_name}",$dns,true,"{rdpproxy_nla_dns_name_explain}");
    $form[]=$tpl->field_text("computer","{rdpproxy_nla_computer}",$computer,false,"{rdpproxy_nla_computer_explain}");
    $html[]=$tpl->div_explain("{rdpproxy_nla_join}||{rdpproxy_nla_join_explain}");
    $html[]=$tpl->div_warning("{rdpproxy_nla_dns_ip_warning}");
    $html[]=$tpl->form_outside("",$form,null,"{rdpproxy_nla_join}",rdpproxy_close_and_refresh(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nla_join_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $password=nla_post("password",true);
    $data=array("domain"=>nla_post("domain"),"admin"=>nla_post("admin"),"password"=>$password,
        "dns_name"=>nla_post("dns_name"),"computer"=>nla_post("computer"));
    if($data["domain"]==""||$data["admin"]==""||$password==""||$data["dns_name"]==""){
        echo $tpl->post_error("{rdpproxy_nla_err_required}");return;
    }
    $error="";
    if(!rdpproxy_nla_join($data,$error)){
        // Defense en profondeur : le daemon gomme deja le secret de ses messages.
        if(strlen($password)>=3){$error=str_replace($password,"***",$error);}
        echo $tpl->post_error($error);return;
    }
    admin_tracks("Joined the RDS bastion client NLA to the Active Directory domain ".
        preg_replace('/[^A-Za-z0-9.\-]/','',$data["domain"]));
}
function nla_leave_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_nla_leave}","$page?nla-leave-popup=yes",650);
}
function nla_leave_popup():bool{
    $tpl=new template_admin();
    // Pas de garde de licence : quitter le domaine reste possible apres expiration.
    $st=nla_popup_state($tpl,false);
    if(!is_array($st)){return false;}
    $form[]=$tpl->field_hidden("nla-leave","1");
    $form[]=$tpl->field_text("admin","{rdpproxy_nla_admin}","",false,"{rdpproxy_nla_leave_admin_explain}");
    $form[]=$tpl->field_password("password","{password}","",false);
    $html[]=$tpl->div_explain("{rdpproxy_nla_leave}||{rdpproxy_nla_leave_explain}");
    $html[]=$tpl->form_outside("",$form,null,"{rdpproxy_nla_leave}",rdpproxy_close_and_refresh(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nla_leave_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $admin=nla_post("admin");
    $password=nla_post("password",true);
    if($admin!="" && $password==""){echo $tpl->post_error("{rdpproxy_nla_err_password_required}");return;}
    if($admin==""){$password="";}
    $error="";
    if(!rdpproxy_nla_leave(array("admin"=>$admin,"password"=>$password),$error)){
        if(strlen($password)>=3){$error=str_replace($password,"***",$error);}
        echo $tpl->post_error($error);return;
    }
    admin_tracks("Left the Active Directory domain on the RDS bastion client NLA".
        ($admin!=""?" (computer account deleted)":" (computer account kept)"));
}
function nla_settings_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{rdpproxy_nla_settings}","$page?nla-settings-popup=yes",650);
}
function nla_settings_popup():bool{
    $tpl=new template_admin();
    $st=nla_popup_state($tpl);
    if(!is_array($st)){return false;}
    $port=intval($st["port"] ?? 0);
    if($port<1){$port=intval($st["default_port"] ?? 3390);}
    $form[]=$tpl->field_hidden("nla-settings-save","1");
    $form[]=$tpl->field_checkbox("enabled","{rdpproxy_nla_enable}",!empty($st["enabled"])?1:0,false,"{rdpproxy_nla_enable_explain}");
    $form[]=$tpl->field_numeric("port","{listen_port}",$port,"{rdpproxy_nla_port_explain}");
    $form[]=$tpl->field_interfaces("listen","nooloop:{listen_interface}",(string)($st["listen"] ?? ""));
    // Sources autorisees : une IPv4 ou un CIDR IPv4 par ligne, vide = toutes (field_textarea_normal encode la valeur).
    // Liste enregistree illisible : PAS de textarea (vide, elle ouvrirait le port a toutes les sources sans choix
    // explicite) ; seule une case explicite efface la liste.
    $sourcesError=trim((string)($st["sources_error"] ?? ""))!="";
    if($sourcesError){
        $form[]=$tpl->field_checkbox("sources_clear","{rdpproxy_nla_sources_clear}",0,false,"{rdpproxy_nla_sources_clear_explain}");
    }else{
        $sources=(isset($st["sources"])&&is_array($st["sources"]))?$st["sources"]:array();
        $form[]=$tpl->field_textarea_normal("sources","{rdpproxy_nla_sources}",implode("\n",$sources),"100%","110px","{rdpproxy_nla_sources_explain}");
    }
    // Ecoute principale 3389 : route absente (articarest ancien) = case absente.
    $mlErr="";
    $ml=rdpproxy_main_listener($mlErr);
    if($mlErr==""){
        $form[]=$tpl->field_checkbox("main_listener","{rdpproxy_main_listener_keep}",!empty($ml["enabled"])?1:0,false,"{rdpproxy_main_listener_keep_explain}");
    }
    $html[]=$tpl->div_explain("{rdpproxy_nla_settings}||{rdpproxy_nla_settings_explain}");
    if($sourcesError){$html[]=$tpl->div_error("{rdpproxy_nla_sources_invalid}");}
    $html[]=$tpl->form_outside("",$form,null,"{apply}",rdpproxy_close_and_refresh(),"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nla_settings_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
    $port=intval(nla_post("port"));
    if($port<1024||$port>65535){echo $tpl->post_error("{rdpproxy_nla_err_port}");return;}
    $listen=nla_post("listen");
    if($listen!="" && !preg_match('/^[A-Za-z0-9._:@-]{3,32}$/D',$listen)){echo $tpl->post_error("{wrong_value}: {listen_interface}");return;}
    $data=array("enabled"=>intval(nla_post("enabled"))==1?"1":"0","port"=>(string)$port,"listen"=>$listen);
    // Le daemon valide les sources (IPv4 seulement, une ligne invalide = refus de toute la liste) ;
    // cle absente = liste inchangee.
    $sources=rdpproxy_nla_sources_payload($_POST);
    if($sources!==null){$data["sources"]=$sources;}
    $error="";
    if(!rdpproxy_nla_settings($data,$error)){echo $tpl->post_error($error);return;}
    $srcTxt="unchanged";
    if($sources!==null){
        $nsrc=count(array_filter(array_map("trim",explode("\n",$sources)),"strlen"));
        $srcTxt=($nsrc==0)?"all":(string)$nsrc;
    }
    admin_tracks("Saved the RDS bastion client NLA settings: enabled=".$data["enabled"].
        ", port=$port, listen=".($listen==""?"all":$listen).", allowed sources=$srcTxt");
    // Ecoute principale : envoyee seulement si la case est postee et change.
    if(isset($_POST["main_listener"])){
        $want=intval(nla_post("main_listener"))==1;
        $mlErr="";
        $ml=rdpproxy_main_listener($mlErr);
        if($mlErr=="" && $want!=(!empty($ml["enabled"]))){
            $e=rdpproxy_main_listener_save($want);
            if($e!=""){echo $tpl->post_error($e);return;}
            admin_tracks(($want?"Enabled":"Disabled")." the main RDS bastion listener (3389)");
        }
    }
}
// Valeur "sources" a envoyer au daemon, null = ne pas l'envoyer (liste inchangee). Textarea postee :
// son contenu (vide = toutes, choix explicite). Liste illisible (pas de textarea) : "" seulement si la
// case sources_clear est cochee ; sinon rien, jamais une ouverture a toutes les sources par defaut.
function rdpproxy_nla_sources_payload($post){
    if(isset($post["sources"])){
        return str_replace("\r","",trim(rawurldecode((string)$post["sources"])));
    }
    if(isset($post["sources_clear"]) && intval(rawurldecode((string)$post["sources_clear"]))==1){return "";}
    return null;
}
function nla_renew_js(){
    $tpl=new template_admin();
    $tpl->js_confirm_execute("{rdpproxy_nla_renew_confirm}","nla-renew","1",rdpproxy_refresh_card());
}
// Reponse lue par js_confirm_execute (texte brut en swal, pas de prefixe jserror:).
function nla_renew_save(){
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo html_safe_fragment($tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}"));return;}
    $error="";
    if(!rdpproxy_nla_renew($error)){echo html_safe_fragment($tpl->_ENGINE_parse_body($error));return;}
    admin_tracks("Renewed the RDS bastion client NLA keytab");
}
function nla_checks():bool{
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsSquidAdministrator")){echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));return false;}
    $error="";
    $st=rdpproxy_nla($error,true);
    if($error!=""){echo $tpl->_ENGINE_parse_body($tpl->div_error($error));return false;}
    $html=array();
    $checksError=trim((string)($st["checks_error"] ?? ""));
    if($checksError!=""){
        $html[]="<div class='rst-note rst-note-warn'><i class='fa-solid fa-triangle-exclamation' style='margin-top:2px'></i><span>".htmlspecialchars($checksError,ENT_QUOTES)."</span></div>";
    }
    $known=array("dns","clock","spn_in_keytab","spn_unique","last_renew");
    $checks=isset($st["checks"])&&is_array($st["checks"])?$st["checks"]:array();
    foreach($checks as $c){
        $name=(string)($c["name"] ?? "");
        $label=in_array($name,$known,true)?"{rdpproxy_nla_check_$name}":htmlspecialchars($name,ENT_QUOTES);
        $ok=!empty($c["ok"]);
        $ico=$ok?"fa-solid fa-circle-check":"fa-solid fa-circle-xmark";
        $color=$ok?"#0d9488":"#b91c1c";
        $detail=htmlspecialchars((string)($c["detail"] ?? ""),ENT_QUOTES);
        $html[]="<div style='display:flex;gap:6px;align-items:flex-start;margin-top:6px;font-size:12px;line-height:1.45'>".
            "<i class='$ico' style='color:$color;margin-top:2px'></i><span><b>$label</b>".($detail!=""?" &mdash; $detail":"")."</span></div>";
    }
    if(count($html)==0){
        $html[]="<div class='rst-note rst-note-info'><i class='fa-solid fa-circle-info' style='margin-top:2px'></i><span>{rdpproxy_nla_checks_none}</span></div>";
    }
    echo $tpl->_ENGINE_parse_body(@implode("",$html));
    return true;
}
