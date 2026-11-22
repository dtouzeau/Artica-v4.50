<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/settings.exists.inc"); // artica_setting_exists(): settings are root-only, see the file
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsVPNManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["ENABLE_SERVER"])){SAVE_SERVER();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["strongswan-status"])){strongswan_status();exit;}
if(isset($_GET["strongswan-vici"])){strongswan_vici_status();exit;}
if(isset($_GET["strongswan-vici-parser"])){strongswan_vici_parser_status();exit;}
page();


function strongswan_status(){
    $page=CurrentPageName();
    

	$sock=new sockets();
	$sock->getFrameWork("strongswan.php?status=yes");
	$bsini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/strongswan.status");
	$tpl=new template_admin();

	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.install.php";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/strongswan.install.php.log";
	$ARRAY["CMD"]="strongswan.php?restart=yes";
	$ARRAY["TITLE"]="{APP_STRONGSWAN} {restarting_service}";
	$ARRAY["AFTER"]="LoadAjaxSilent('strongswan-status','$page?strongswan-status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";

	$final[]=$tpl->SERVICE_STATUS($bsini, "APP_STRONGSWAN",$jsRestart);

    VERBOSE(count($final)." row",__LINE__);
    echo $tpl->_ENGINE_parse_body($final);
	
}

function strongswan_vici_status(){
    $page=CurrentPageName();

    $sock=new sockets();
    $sock->getFrameWork("strongswan.php?status-vici=yes");
     $bsini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/strongswan.status");
    $tpl=new template_admin();

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.install.php";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.install.php.log";
    $ARRAY["CMD"]="strongswan.php?restart-parser=yes";
    $ARRAY["TITLE"]="{APP_STRONGSWAN_VICI} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('strongswan-status-vici','$page?strongswan-vici=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";

    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_STRONGSWAN_VICI",$jsRestart);

    VERBOSE(count($final)." row",__LINE__);
    echo $tpl->_ENGINE_parse_body($final);

}

function strongswan_vici_parser_status(){
    $page=CurrentPageName();

    $sock=new sockets();
    $sock->getFrameWork("strongswan.php?status-vici-parser=yes");
    $bsini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/strongswan.status");
    $tpl=new template_admin();

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.install.php";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/strongswan.install.php.log";
    $ARRAY["CMD"]="strongswan.php?restart-parser=yes";
    $ARRAY["TITLE"]="{APP_STRONGSWAN_VICI_PARSER} {restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('strongswan-status-vici-parser','$page?strongswan-vici-parser=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-firehol-restart')";

    $final[]=$tpl->SERVICE_STATUS($bsini, "APP_STRONGSWAN_VICI_PARSER",$jsRestart);

    VERBOSE(count($final)." row",__LINE__);
    echo $tpl->_ENGINE_parse_body($final);

}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$StrongswanVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrongswanVersion");
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{STRONGSWAN_SERVER_SETTINGS} v$StrongswanVersion</h1><p>{APP_STRONGSWAN_TEXT}</p>$btn</div>
	
	</div>



	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/ipsec-settings');	
	
	LoadAjax('table-loader','$page?main=yes');

	</script>";

	$tpl=new templates();
    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Settings',$html);echo $tpl->build_firewall();return;}
	echo $tpl->_ENGINE_parse_body($html);

}


// ---------------------------------------------------------------------------
// Reglages generaux du serveur IPsec. La colonne de gauche (etat des trois
// services) n'est pas touchee ; seule la partie droite est construite ici.

function main(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $M=ipsecset_model();

    $form=array();
    $form[]="<div class='ak ipsecset' id='ipsecset'>";
    $form[]=ipsecset_hero();
    $form[]=ipsecset_card_service($M);
    $form[]=ipsecset_card_crl($M);
    $form[]=ipsecset_card_dns($M);
    $form[]=ipsecset_card_dhcp($M);
    $form[]=ipsecset_card_logs($M);
    $form[]=ipsecset_actionbar();
    $form[]="</div>";
    $myform=$tpl->_ENGINE_parse_body(@implode("\n",$form));

    // Colonne de gauche : identique a l'origine (ids, chargements).
    $left=$tpl->_ENGINE_parse_body("<div id='strongswan-status' style='margin-top:15px'></div><div id='strongswan-status-vici' style='margin-top:15px'></div><div id='strongswan-status-vici-parser' style='margin-top:15px'></div>");

    // CSS et JS restent hors du moteur de traduction.
    echo ipsecset_style();
    echo "<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:240px'>$left</td>
		<td	style='vertical-align:top;width:90%;padding-left:16px'>$myform</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('strongswan-status','$page?strongswan-status=yes');</script>
	<script>LoadAjaxSilent('strongswan-status-vici','$page?strongswan-vici=yes');</script>
	<script>LoadAjaxSilent('strongswan-status-vici-parser','$page?strongswan-vici-parser=yes');</script>
	";
    echo ipsecset_js($tpl->button_strongswan_reconfigure(true));
}

// Valeurs par defaut posees une fois, comme avant.
function ipsecset_defaults(){
    $def=array("strongSwanCachecrls"=>0,"strongSwanCharondebug"=>2,"strongSwanCharonstart"=>1,
        "strongSwanStrictcrlpolicy"=>0,"strongSwanUniqueids"=>1);
    foreach($def as $key=>$val){
        if(!artica_setting_exists($key)){$GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,$val);}
    }
}

// Cartes reseau proposees : cle = nom de l'interface.
function ipsecset_interfaces():array{
    $tpl=new template_admin();
    $list=$tpl->list_interfaces();
    if(!is_array($list)){return array();}
    return $list;
}

// Regles d'envoi des evenements IPsec vers un serveur de journaux (page
// Syslog). -1 = la liste n'a pas pu etre lue : on n'affirme rien.
function ipsecset_forward_rules():int{
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/remote/rules"),true);
    if(!is_array($json) || empty($json["Status"])){return -1;}
    if(!isset($json["Rules"]) || !is_array($json["Rules"])){return 0;}
    $n=0;
    foreach($json["Rules"] as $r){
        if(!is_array($r)){continue;}
        if(empty($r["enabled"])){continue;}
        if((isset($r["logtype"])?$r["logtype"]:"")<>"strongswan"){continue;}
        $n++;
    }
    return $n;
}

// Modele : tout ce que la page lit, une seule fois.
function ipsecset_model():array{
    ipsecset_defaults();
    $S=$GLOBALS["CLASS_SOCKETS"];
    $strongswan=new strongswan();
    $G=isset($strongswan->main_array["GLOBAL"]) && is_array($strongswan->main_array["GLOBAL"])?$strongswan->main_array["GLOBAL"]:array();
    $g=function($k) use ($G){ return isset($G[$k])?trim((string)$G[$k]):""; };

    $M=array();
    $M["start"]=intval($S->GET_INFO("strongSwanCharonstart"))==1?1:0;
    $M["cachecrls"]=intval($S->GET_INFO("strongSwanCachecrls"))==1?1:0;
    $M["crl"]=max(0,min(2,intval($S->GET_INFO("strongSwanStrictcrlpolicy"))));
    $M["uniq"]=max(0,min(4,intval($S->GET_INFO("strongSwanUniqueids"))));
    $M["iface"]=trim((string)$S->GET_INFO("StrongswanListenInterface"));
    $M["dns_on"]=intval($S->GET_INFO("StrongswanEnableDNSWINS"))==1?1:0;
    $M["dns1"]=$g("VPN_DNS_1");$M["dns2"]=$g("VPN_DNS_2");
    $M["wins1"]=$g("VPN_WINS_1");$M["wins2"]=$g("VPN_WINS_2");
    $M["dhcp_on"]=intval($S->GET_INFO("strongSwanEnableDHCP"))==1?1:0;
    $M["dhcp_server"]=$g("strongSwanDHCPServer");
    $M["dhcp_iface"]=trim((string)$S->GET_INFO("StrongswanDHCPListenInterface"));
    $M["dhcp_force"]=intval($g("strongSwanDHCPForceServerAddress"))==1?1:0;
    $M["dhcp_identity"]=intval($g("strongSwanDHCPIdentityLease"))==1?1:0;
    $M["retention"]=intval($S->GET_INFO("Strongswanretention"));
    if($M["retention"]<1){$M["retention"]=7;}
    $M["nolocal"]=intval($S->GET_INFO("StrongSwanLogSyslogDoNotStorelogsLocally"))==1?1:0;
    $M["rules"]=ipsecset_forward_rules();
    $M["nics"]=ipsecset_interfaces();
    return $M;
}

function ipsecset_style():string{
    return "<style>
.ipsecset{max-width:1060px;margin:10px 0 0}
.ipsecset .ak-card{margin-bottom:16px}
.ipsecset-hero{display:flex;align-items:flex-start;gap:16px}
.ipsecset-hero h2{margin:0 0 8px;font-size:20px;font-weight:600;line-height:1.25;color:#0f172a}
.ipsecset-hero p{margin:0;font-size:14px;line-height:1.6;color:#475569}
.ipsecset-chips{margin-top:12px}
.ipsecset-chips .ak-pill{font-weight:500;background:#f1f5f9;color:#334155}
.ipsecset-h{display:flex;align-items:center;gap:12px;padding-bottom:14px;margin-bottom:18px;border-bottom:1px solid #f1f5f9}
.ipsecset-h-t{flex:1 1 auto;min-width:0}
.ipsecset-h-t b{display:block;font-size:15px;font-weight:600;color:#0f172a}
.ipsecset-h-t span{display:block;font-size:13px;color:#64748b}
.ipsecset-h-sw{display:flex;align-items:center;gap:10px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;color:#0f172a}
.ipsecset-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 28px;align-items:start}
.ipsecset-full{grid-column:1 / -1}
.ipsecset-f{display:flex;flex-direction:column;gap:6px;min-width:0}
.ipsecset-lbl{margin:0;font-size:13px;font-weight:600;color:#0f172a}
.ipsecset-lbl small{font-size:13px;font-weight:400;color:#64748b}
.ipsecset-help{margin:0;font-size:13px;line-height:1.45;color:#64748b}
.ipsecset-tech{display:block;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#64748b}
.ipsecset .ak-in,.ipsecset .ak-sel{width:100%;height:40px;font-size:14px}
.ipsecset-ip{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.ipsecset-swrow{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
.ipsecset-swrow>div{min-width:0}
.ipsecset-sep{grid-column:1 / -1;height:1px;background:#f1f5f9}
.ipsecset-rcs{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.ipsecset-rc{position:relative;display:block;min-width:0;margin:0;cursor:pointer;font-weight:400}
.ipsecset-rc input{position:absolute;opacity:0;width:1px;height:1px}
.ipsecset-rc-b{position:relative;display:block;height:100%;padding:12px 36px 12px 14px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;transition:border-color .15s,background .15s}
.ipsecset-rc-b b{display:block;font-size:14px;font-weight:600;color:#0f172a}
.ipsecset-rc-b span{display:block;margin-top:4px;font-size:13px;line-height:1.45;color:#475569}
.ipsecset-rc-b em{display:inline-block;margin-top:6px;padding:1px 8px;border-radius:4px;font-size:13px;font-style:normal;font-weight:600;background:#fff;color:var(--ak-fill,#005447);border:1px solid var(--ak-line,#bfd4d1)}
.ipsecset-rc-b::after{content:'';position:absolute;top:12px;right:12px;width:16px;height:16px;border:1px solid #cbd5e1;border-radius:50%;background:#fff}
.ipsecset-rc:hover .ipsecset-rc-b{border-color:var(--ak-line,#bfd4d1)}
.ipsecset-rc input:checked + .ipsecset-rc-b{border:2px solid var(--ak-fill,#005447);padding:11px 35px 11px 13px;background:var(--ak-soft,#e6eeed)}
.ipsecset-rc input:checked + .ipsecset-rc-b::after{top:11px;right:11px;border:5px solid var(--ak-fill,#005447)}
.ipsecset-rc input:focus-visible + .ipsecset-rc-b{outline:2px solid var(--ak-fill,#005447);outline-offset:2px}
.ipsecset-more{margin-top:12px}
.ipsecset-more>summary{display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:6px;font-size:13px;font-weight:600;color:var(--ak-fill,#005447);cursor:pointer;list-style:none}
.ipsecset-more>summary::-webkit-details-marker{display:none}
.ipsecset-more>summary:focus-visible{outline:2px solid var(--ak-fill,#005447);outline-offset:2px}
.ipsecset-more[open]>summary{margin-bottom:10px}
.ipsecset-adv{grid-column:1 / -1;padding:12px 14px;border:1px solid #e2e8f0;border-radius:8px}
.ipsecset-adv[open]>summary{margin-bottom:14px}
.ipsecset-unit{display:flex;align-items:center;gap:10px}
.ipsecset-unit .ak-in{width:110px}
.ipsecset-unit span{font-size:14px;color:#475569}
.ipsecset-note{display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-radius:8px;font-size:13px;line-height:1.45;background:#f1f5f9;color:#334155}
.ipsecset-note.ak-warn{background:#ffddb9;color:#865300}
.ipsecset-note>i,.ipsecset-note>svg{margin-top:2px;flex:0 0 auto}
.ipsecset-fwd{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 14px;border:1px solid #e2e8f0;border-radius:8px}
.ipsecset-dep[disabled]{opacity:.55}
fieldset.ipsecset-dep{min-width:0;margin:0;padding:0;border:0}
.ipsecset-bar{position:sticky;bottom:0;z-index:5;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:14px 18px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 -2px 8px rgba(15,23,42,.06)}
.ipsecset-bar-i{display:flex;align-items:flex-start;gap:8px;flex:1 1 320px;font-size:13px;line-height:1.45;color:#64748b}
.ipsecset-bar-a{display:flex;align-items:center;gap:10px}
.ipsecset-dirty{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:13px;font-weight:600;background:#ffddb9;color:#865300}
.ipsecset-saved{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--ak-fill,#005447)}
.ipsecset-err{margin-bottom:12px;white-space:pre-line}
@media(max-width:1200px){
.ipsecset-grid{grid-template-columns:1fr}
.ipsecset-rcs{grid-template-columns:minmax(0,1fr)}
}
@media (prefers-reduced-motion:reduce){.ipsecset-rc-b{transition:none}}
</style>";
}

// Texte traduit tout de suite, pour y remplacer un %s.
function ipsecset_text($token):string{
    $tpl=new template_admin();
    return (string)$tpl->_ENGINE_parse_body($token);
}

// --- briques de formulaire ---------------------------------------------------

function ipsecset_card_head($ico,$title,$sub,$switch=""):string{
    $h ="<div class='ipsecset-h'>";
    $h.="<span class='ak-ico ak-ico-sm ak-ico-sq'><i class='$ico'></i></span>";
    $h.="<div class='ipsecset-h-t'><b>$title</b><span>$sub</span></div>";
    return $h.$switch."</div>";
}

function ipsecset_sw($id,$on,$label,$disabled=false):string{
    $c=$on?"true":"false";
    $d=$disabled?" disabled":"";
    return "<button type='button' role='switch' class='ak-sw' id='$id' data-ipsecset='sw' aria-checked='$c' aria-labelledby='$id-l'$d></button>";
}

// Interrupteur avec libelle et aide a gauche.
function ipsecset_swrow($id,$on,$label,$help,$tech="",$disabled=false):string{
    $h ="<div class='ipsecset-swrow'><div>";
    $h.="<div class='ipsecset-lbl' id='$id-l'>$label</div>";
    if($tech<>""){$h.="<span class='ipsecset-tech'>$tech</span>";}
    $h.="<p class='ipsecset-help' id='$id-h'>$help</p>";
    return $h."</div>".ipsecset_sw($id,$on,$label,$disabled)."</div>";
}

// Interrupteur d'en-tete de carte (active toute la carte).
function ipsecset_headsw($id,$on,$label):string{
    return "<div class='ipsecset-h-sw'><span id='$id-l'>$label</span>".ipsecset_sw($id,$on,$label)."</div>";
}

function ipsecset_input($id,$value,$label,$help,$placeholder="",$optional=false):string{
    $v=htmlspecialchars((string)$value,ENT_QUOTES);
    $ph=htmlspecialchars((string)$placeholder,ENT_QUOTES);
    $opt=$optional?" <small>{ipsecset_optional}</small>":"";
    $h ="<div class='ipsecset-f'><label class='ipsecset-lbl' for='$id'>$label$opt</label>";
    $h.="<input type='text' class='ak-in ipsecset-ip' id='$id' value=\"$v\" placeholder=\"$ph\" autocomplete='off' spellcheck='false'>";
    if($help<>""){$h.="<p class='ipsecset-help'>$help</p>";}
    return $h."</div>";
}

// Liste des cartes reseau. La valeur enregistree est toujours proposee, meme
// si la carte n'existe plus : sinon l'enregistrement l'effacerait en silence.
function ipsecset_nic_select($id,$value,$nics,$label,$help,$emptyLabel):string{
    if($value<>"" && !isset($nics[$value])){$nics[$value]=$value;}
    $h ="<div class='ipsecset-f'><label class='ipsecset-lbl' for='$id'>$label</label>";
    $h.="<select class='ak-sel' id='$id'>";
    $h.="<option value=''".($value==""?" selected":"").">$emptyLabel</option>";
    foreach($nics as $nic=>$text){
        $sel=((string)$nic===(string)$value)?" selected":"";
        $h.="<option value=\"".htmlspecialchars((string)$nic,ENT_QUOTES)."\"$sel>".htmlspecialchars((string)$text,ENT_QUOTES)."</option>";
    }
    $h.="</select><p class='ipsecset-help'>$help</p>";
    return $h."</div>";
}

// Une carte de choix. $badge : jeton d'une etiquette ("" = aucune).
function ipsecset_rc($name,$val,$current,$title,$text,$badge=""):string{
    $chk=((string)$val===(string)$current)?" checked":"";
    $h ="<label class='ipsecset-rc'><input type='radio' name='$name' value='$val'$chk>";
    $h.="<span class='ipsecset-rc-b'><b>$title</b><span>$text</span>";
    if($badge<>""){$h.="<em>$badge</em>";}
    return $h."</span></label>";
}

// --- cartes ------------------------------------------------------------------

function ipsecset_hero():string{
    $h ="<div class='ak-card ak-card-pad'><div class='ipsecset-hero'>";
    $h.="<span class='ak-ico ak-ico-lg ak-ico-sq'><i class='fas fa-shield-halved'></i></span>";
    $h.="<div><h2>{ipsecset_title}</h2><p>{ipsecset_intro}</p>";
    $h.="<div class='ak-chips ipsecset-chips'>";
    $h.="<span class='ak-pill'><i class='fas fa-circle-check'></i> {ipsecset_chip_defaults}</span>";
    $h.="<span class='ak-pill'><i class='fas fa-floppy-disk'></i> {ipsecset_chip_save}</span>";
    $h.="<span class='ak-pill'><i class='fas fa-rotate-left'></i> {ipsecset_chip_revert}</span>";
    return $h."</div></div></div></div>";
}

function ipsecset_uniq_choices($M):string{
    $h ="<div class='ipsecset-full'><div class='ipsecset-lbl' id='ipsecset-uniq-l'>{ipsecset_uniq}</div>";
    $h.="<p class='ipsecset-help' style='margin-bottom:10px'>{ipsecset_uniq_help}</p>";
    $h.="<div role='radiogroup' aria-labelledby='ipsecset-uniq-l'><div class='ipsecset-rcs'>";
    $h.=ipsecset_rc("ipsecset-uniq",1,$M["uniq"],"{ipsecset_uniq_replace}","{ipsecset_uniq_replace_t}","{ipsecset_recommended}");
    $h.=ipsecset_rc("ipsecset-uniq",0,$M["uniq"],"{ipsecset_uniq_both}","{ipsecset_uniq_both_t}");
    $h.=ipsecset_rc("ipsecset-uniq",4,$M["uniq"],"{ipsecset_uniq_refuse}","{ipsecset_uniq_refuse_t}");
    $h.="</div>";
    // Ouvert d'office si la valeur enregistree est l'un des deux autres choix.
    $open=($M["uniq"]==2 || $M["uniq"]==3)?" open":"";
    $h.="<details class='ipsecset-more'$open><summary>{ipsecset_more} <i class='fas fa-chevron-down'></i></summary>";
    $h.="<div class='ipsecset-rcs'>";
    $h.=ipsecset_rc("ipsecset-uniq",2,$M["uniq"],"{ipsecset_uniq_never}","{ipsecset_uniq_never_t}");
    $h.=ipsecset_rc("ipsecset-uniq",3,$M["uniq"],"{ipsecset_uniq_explicit}","{ipsecset_uniq_explicit_t}");
    return $h."</div></details></div></div>";
}

function ipsecset_card_service($M):string{
    $h ="<div class='ak-card ak-card-pad'>";
    $h.=ipsecset_card_head("fas fa-power-off","{ipsecset_service}","{ipsecset_service_sub}");
    $h.="<div class='ipsecset-grid'>";
    $h.=ipsecset_swrow("ipsecset-start",$M["start"],"{ipsecset_start}","{ipsecset_start_help}","Charon auto start");
    $h.=ipsecset_nic_select("ipsecset-iface",$M["iface"],$M["nics"],"{ipsecset_listen}","{ipsecset_listen_help}","{ipsecset_all_nics}");
    $h.="<div class='ipsecset-sep'></div>";
    $h.=ipsecset_uniq_choices($M);
    return $h."</div></div>";
}

function ipsecset_card_crl($M):string{
    $h ="<div class='ak-card ak-card-pad'>";
    $h.=ipsecset_card_head("fas fa-certificate","{ipsecset_crl}","{ipsecset_crl_sub}");
    $h.="<div class='ipsecset-grid'>";
    $h.="<div class='ipsecset-full'><div class='ipsecset-lbl' id='ipsecset-crl-l'>{ipsecset_crl_check}</div>";
    $h.="<p class='ipsecset-help' style='margin-bottom:10px'>{ipsecset_crl_check_help}</p>";
    $h.="<div class='ipsecset-rcs' role='radiogroup' aria-labelledby='ipsecset-crl-l'>";
    $h.=ipsecset_rc("ipsecset-crl",0,$M["crl"],"{ipsecset_crl_no}","{ipsecset_crl_no_t}","{ipsecset_default}");
    $h.=ipsecset_rc("ipsecset-crl",1,$M["crl"],"{ipsecset_crl_yes}","{ipsecset_crl_yes_t}","{ipsecset_safer}");
    $h.=ipsecset_rc("ipsecset-crl",2,$M["crl"],"{ipsecset_crl_ifuri}","{ipsecset_crl_ifuri_t}");
    $h.="</div></div>";
    $h.="<div class='ipsecset-sep'></div>";
    $h.="<div class='ipsecset-full'>".ipsecset_swrow("ipsecset-cache",$M["cachecrls"],"{ipsecset_cache}","{ipsecset_cache_help}")."</div>";
    return $h."</div></div>";
}

function ipsecset_card_dns($M):string{
    $dis=$M["dns_on"]?"":" disabled";
    $h ="<div class='ak-card ak-card-pad'>";
    $h.=ipsecset_card_head("fas fa-address-book","{ipsecset_dns}","{ipsecset_dns_sub}",
        ipsecset_headsw("ipsecset-dns-on",$M["dns_on"],"{ipsecset_dns_switch}"));
    $h.="<div class='ipsecset-note' style='margin-bottom:18px'><i class='fas fa-lightbulb'></i><span>{ipsecset_dns_example}</span></div>";
    $h.="<fieldset class='ipsecset-dep' data-dep='ipsecset-dns-on'$dis><div class='ipsecset-grid'>";
    $h.=ipsecset_input("ipsecset-dns1",$M["dns1"],"{ipsecset_dns1}","{ipsecset_dns1_help}","192.168.1.10");
    $h.=ipsecset_input("ipsecset-dns2",$M["dns2"],"{ipsecset_dns2}","{ipsecset_dns2_help}","192.168.1.11",true);
    $h.=ipsecset_input("ipsecset-wins1",$M["wins1"],"{ipsecset_wins1}","{ipsecset_wins1_help}","192.168.1.20");
    $h.=ipsecset_input("ipsecset-wins2",$M["wins2"],"{ipsecset_wins2}","{ipsecset_wins2_help}","192.168.1.21",true);
    return $h."</div></fieldset></div>";
}

function ipsecset_card_dhcp($M):string{
    $dis=$M["dhcp_on"]?"":" disabled";
    $h ="<div class='ak-card ak-card-pad'>";
    $h.=ipsecset_card_head("fas fa-network-wired","{ipsecset_dhcp}","{ipsecset_dhcp_sub}",
        ipsecset_headsw("ipsecset-dhcp-on",$M["dhcp_on"],"{ipsecset_dhcp_switch}"));
    $h.="<fieldset class='ipsecset-dep' data-dep='ipsecset-dhcp-on'$dis><div class='ipsecset-grid'>";
    $h.=ipsecset_input("ipsecset-dhcp-server",$M["dhcp_server"],"{ipsecset_dhcp_server}","{ipsecset_dhcp_server_help}","192.168.1.254");
    $h.=ipsecset_nic_select("ipsecset-dhcp-iface",$M["dhcp_iface"],$M["nics"],"{ipsecset_dhcp_nic}","{ipsecset_dhcp_nic_help}","{ipsecset_auto_nic}");
    $open=($M["dhcp_force"] || $M["dhcp_identity"])?" open":"";
    $h.="<details class='ipsecset-more ipsecset-adv'$open><summary>{ipsecset_advanced} <i class='fas fa-chevron-down'></i></summary>";
    $h.="<div class='ipsecset-grid'>";
    $h.=ipsecset_swrow("ipsecset-dhcp-force",$M["dhcp_force"],"{ipsecset_dhcp_force}","{ipsecset_dhcp_force_help}");
    $h.=ipsecset_swrow("ipsecset-dhcp-identity",$M["dhcp_identity"],"{ipsecset_dhcp_identity}","{ipsecset_dhcp_identity_help}");
    return $h."</div></details></div></fieldset></div>";
}

// Envoi vers un serveur de journaux : on montre l'etat REEL (regles de la page
// Syslog), on ne le regle pas ici.
function ipsecset_forwarding($M):string{
    if($M["rules"]<0){$state="{ipsecset_fwd_unknown}";}
    elseif($M["rules"]==0){$state="{ipsecset_fwd_none}";}
    elseif($M["rules"]==1){$state="{ipsecset_fwd_one}";}
    else{
        // Traduire d'abord, remplacer ensuite : avant l'analyse le jeton n'a pas de %s.
        $state=str_replace("%s",intval($M["rules"]),ipsecset_text("{ipsecset_fwd_many}"));
    }
    $h ="<div class='ipsecset-full'><div class='ipsecset-fwd'><div>";
    $h.="<div class='ipsecset-lbl'>{ipsecset_fwd} <small>syslog</small></div>";
    $h.="<p class='ipsecset-help'>$state</p><p class='ipsecset-help'>{ipsecset_fwd_help}</p></div>";
    $h.="<a class='ak-btn' href='/syslogd'><i class='fas fa-arrow-up-right-from-square'></i> {ipsecset_fwd_open}</a>";
    return $h."</div></div>";
}

function ipsecset_card_logs($M):string{
    $h ="<div class='ak-card ak-card-pad'>";
    $h.=ipsecset_card_head("fas fa-file-lines","{ipsecset_logs}","{ipsecset_logs_sub2}");
    $h.="<div class='ipsecset-grid'>";
    $h.="<div class='ipsecset-f'><label class='ipsecset-lbl' for='ipsecset-retention'>{ipsecset_keep}</label>";
    $h.="<div class='ipsecset-unit'><input type='number' min='1' max='3650' class='ak-in' id='ipsecset-retention' value='".intval($M["retention"])."'><span>{ipsecset_days}</span></div>";
    $h.="<p class='ipsecset-help'>{ipsecset_keep_help}</p></div>";
    // Pas de reglage du niveau de detail ici : strongSwanCharondebug est ecrit
    // dans ipsec.conf sous une forme que strongSwan ignore ("2" au lieu de
    // "ike 2, knl 2"), et le niveau du journal est fixe par le generateur.
    // Le reglage enregistre n'est ni affiche ni modifie.
    $h.="<div class='ipsecset-sep'></div>";
    $h.=ipsecset_forwarding($M);
    // Sans regle d'envoi active, l'interrupteur est verrouille : couper le
    // stockage local ferait disparaitre les evenements.
    $locked=$M["rules"]<1;
    $help=$locked?"{ipsecset_nolocal_locked}":"{ipsecset_nolocal_help}";
    $h.="<div class='ipsecset-full'>".ipsecset_swrow("ipsecset-nolocal",$M["nolocal"] && !$locked,"{ipsecset_nolocal}",$help,"",$locked);
    $hidden=($M["nolocal"] && !$locked)?"":" hidden";
    $h.="<div class='ipsecset-note ak-warn' id='ipsecset-nolocal-warn' style='margin-top:10px'$hidden><i class='fas fa-triangle-exclamation'></i><span>{ipsecset_nolocal_warn}</span></div></div>";
    return $h."</div></div>";
}

function ipsecset_actionbar():string{
    $h ="<div class='ak-banner ak-err ipsecset-err' id='ipsecset-error' role='alert' hidden><i class='fas fa-circle-exclamation'></i><span class='ak-banner-m' id='ipsecset-error-t'></span></div>";
    $h.="<div class='ipsecset-bar'>";
    $h.="<div class='ipsecset-bar-i'><i class='fas fa-circle-info' style='margin-top:2px'></i><span>{ipsecset_apply_info}</span></div>";
    $h.="<div class='ipsecset-bar-a'>";
    $h.="<span class='ipsecset-dirty' id='ipsecset-dirty' hidden><i class='fas fa-circle' style='font-size:8px'></i> {ipsecset_unsaved}</span>";
    $h.="<span class='ipsecset-saved' id='ipsecset-saved' role='status' hidden><i class='fas fa-circle-check'></i> {ipsecset_saved}</span>";
    $h.="<button type='button' class='ak-btn ak-btn-lg' id='ipsecset-save'>{ipsecset_save_only}</button>";
    $h.="<button type='button' class='ak-btn ak-btn-lg ak-btn-primary' id='ipsecset-apply'><i class='fas fa-check'></i> {ipsecset_save_apply}</button>";
    return $h."</div></div>";
}

// Interrupteurs, champs dependants, indicateur de modification, enregistrement.
// $applyJs : appel qui lance la reconfiguration du service et sa progression.
function ipsecset_js($applyJs):string{
    $page=CurrentPageName();
    $js='
<script>
(function(){
 var root=document.getElementById("ipsecset"); if(!root){return;}
 function $id(i){return document.getElementById(i);}
 function on(i){var e=$id(i);return (e && e.getAttribute("aria-checked")==="true")?1:0;}
 function val(i){var e=$id(i);return e?String(e.value).trim():"";}
 function radio(n){var e=root.querySelector("input[name=\""+n+"\"]:checked");return e?e.value:"";}
 function dirty(){$id("ipsecset-dirty").hidden=false;$id("ipsecset-saved").hidden=true;}
 function deps(){
   var fs=root.querySelectorAll("fieldset[data-dep]");
   for(var i=0;i<fs.length;i++){fs[i].disabled=!on(fs[i].getAttribute("data-dep"));}
   $id("ipsecset-nolocal-warn").hidden=!on("ipsecset-nolocal");
 }
 root.addEventListener("click",function(ev){
   var sw=ev.target.closest?ev.target.closest("[data-ipsecset=sw]"):null;
   if(!sw || sw.disabled){return;}
   sw.setAttribute("aria-checked",sw.getAttribute("aria-checked")==="true"?"false":"true");
   deps();dirty();
 });
 root.addEventListener("input",function(){dirty();});
 root.addEventListener("change",function(){deps();dirty();});
 function showError(t){
   var doc=new DOMParser().parseFromString(String(t).replace(/<br\s*\/?>/gi,"\n"),"text/html");
   $id("ipsecset-error-t").textContent=doc.body.textContent;
   $id("ipsecset-error").hidden=false;
   $id("ipsecset-error").scrollIntoView({block:"center"});
 }
 function busy(b){
   var bs=[$id("ipsecset-save"),$id("ipsecset-apply")];
   for(var i=0;i<bs.length;i++){bs[i].disabled=b;bs[i].setAttribute("aria-busy",b?"true":"false");}
 }
 function save(apply){
   $id("ipsecset-error").hidden=true;$id("ipsecset-saved").hidden=true;
   var data={
     "ENABLE_SERVER":"1",
     "strongSwanCharonstart":on("ipsecset-start"),
     "StrongswanListenInterface":val("ipsecset-iface"),
     "strongSwanUniqueids":radio("ipsecset-uniq"),
     "strongSwanStrictcrlpolicy":radio("ipsecset-crl"),
     "strongSwanCachecrls":on("ipsecset-cache"),
     "StrongswanEnableDNSWINS":on("ipsecset-dns-on"),
     "VPN_DNS_1":val("ipsecset-dns1"),"VPN_DNS_2":val("ipsecset-dns2"),
     "VPN_WINS_1":val("ipsecset-wins1"),"VPN_WINS_2":val("ipsecset-wins2"),
     "strongSwanEnableDHCP":on("ipsecset-dhcp-on"),
     "strongSwanDHCPServer":val("ipsecset-dhcp-server"),
     "StrongswanDHCPListenInterface":val("ipsecset-dhcp-iface"),
     "strongSwanDHCPForceServerAddress":on("ipsecset-dhcp-force"),
     "strongSwanDHCPIdentityLease":on("ipsecset-dhcp-identity"),
     "Strongswanretention":val("ipsecset-retention"),
     "StrongSwanLogSyslogDoNotStorelogsLocally":on("ipsecset-nolocal")
   };
   busy(true);
   $.post("__PAGE__",data).done(function(resp){
     resp=String(resp||"").trim();
     if(resp.length>3){showError(resp.indexOf("jserror:")===0?resp.substring(8):resp);return;}
     $id("ipsecset-dirty").hidden=true;$id("ipsecset-saved").hidden=false;
     if(apply){ __APPLY__; }
   }).fail(function(x){showError("HTTP "+x.status);}).always(function(){busy(false);});
 }
 $id("ipsecset-save").onclick=function(){save(false);};
 $id("ipsecset-apply").onclick=function(){save(true);};
 deps();
})();
</script>';
    return str_replace(array("__PAGE__","__APPLY__"),array($page,$applyJs),$js);
}

// ---------------------------------------------------------------------------
// Enregistrement. Rien n'est ecrit tant que tout n'est pas valide ; en cas de
// succes la reponse est vide.

function ipsecset_flag($key):int{
    return (isset($_POST[$key]) && intval($_POST[$key])==1)?1:0;
}

// Entier borne ; false si la valeur n'est pas un entier de l'intervalle.
function ipsecset_int($key,$min,$max){
    $v=isset($_POST[$key])?trim((string)$_POST[$key]):"";
    if(!preg_match('/^-?[0-9]{1,5}$/',$v)){return false;}
    $v=intval($v);
    if($v<$min || $v>$max){return false;}
    return $v;
}

// Adresse IP ou vide ; false si la valeur n'est pas une adresse.
function ipsecset_ip($key){
    $v=isset($_POST[$key])?trim((string)$_POST[$key]):"";
    if($v===""){return "";}
    if(filter_var($v,FILTER_VALIDATE_IP)===false){return false;}
    return $v;
}

// Carte reseau : vide, une carte proposee, ou la valeur deja enregistree.
function ipsecset_nic($key,$nics,$stored){
    $v=isset($_POST[$key])?trim((string)$_POST[$key]):"";
    if($v===""){return "";}
    if(isset($nics[$v]) || $v===$stored){return $v;}
    return false;
}

function SAVE_SERVER(){
    $tpl=new template_admin();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $nics=ipsecset_interfaces();
    $V=array();
    $bad=array();

    foreach(array("strongSwanCharonstart","strongSwanCachecrls","StrongswanEnableDNSWINS","strongSwanEnableDHCP",
                "strongSwanDHCPForceServerAddress","strongSwanDHCPIdentityLease") as $key){
        $V[$key]=ipsecset_flag($key);
    }
    $ranges=array("strongSwanStrictcrlpolicy"=>array(0,2,"{ipsecset_crl_check}"),
        "strongSwanUniqueids"=>array(0,4,"{ipsecset_uniq}"),
        "Strongswanretention"=>array(1,3650,"{ipsecset_keep}"));
    foreach($ranges as $key=>$r){
        $V[$key]=ipsecset_int($key,$r[0],$r[1]);
        if($V[$key]===false){$bad[]=$r[2];}
    }
    $ips=array("VPN_DNS_1"=>"{ipsecset_dns1}","VPN_DNS_2"=>"{ipsecset_dns2}","VPN_WINS_1"=>"{ipsecset_wins1}",
        "VPN_WINS_2"=>"{ipsecset_wins2}","strongSwanDHCPServer"=>"{ipsecset_dhcp_server}");
    foreach($ips as $key=>$label){
        $V[$key]=ipsecset_ip($key);
        if($V[$key]===false){$bad[]=$label;}
    }
    $V["StrongswanListenInterface"]=ipsecset_nic("StrongswanListenInterface",$nics,trim((string)$sock->GET_INFO("StrongswanListenInterface")));
    if($V["StrongswanListenInterface"]===false){$bad[]="{ipsecset_listen}";}
    $V["StrongswanDHCPListenInterface"]=ipsecset_nic("StrongswanDHCPListenInterface",$nics,trim((string)$sock->GET_INFO("StrongswanDHCPListenInterface")));
    if($V["StrongswanDHCPListenInterface"]===false){$bad[]="{ipsecset_dhcp_nic}";}

    if(count($bad)>0){
        echo $tpl->post_error("{ipsecset_err_invalid} ".@implode(", ",$bad));
        return false;
    }
    // Le generateur de configuration ecrit DNS 1 et WINS 1 tels quels : les
    // deux restent obligatoires quand l'envoi est active.
    if($V["StrongswanEnableDNSWINS"]==1 && ($V["VPN_DNS_1"]==="" || $V["VPN_WINS_1"]==="")){
        echo $tpl->post_error("{ipsecset_err_dns}");
        return false;
    }
    if($V["strongSwanEnableDHCP"]==1 && $V["strongSwanDHCPServer"]===""){
        echo $tpl->post_error("{ipsecset_err_dhcp}");
        return false;
    }
    // Ne jamais couper le stockage local sans regle d'envoi active ; si la
    // liste des regles est illisible, la valeur enregistree est conservee.
    $rules=ipsecset_forward_rules();
    $nolocal=ipsecset_flag("StrongSwanLogSyslogDoNotStorelogsLocally");
    if($rules==0){$nolocal=0;}
    if($rules<0){$nolocal=intval($sock->GET_INFO("StrongSwanLogSyslogDoNotStorelogsLocally"))==1?1:0;}

    foreach(array("strongSwanCachecrls","strongSwanCharonstart","strongSwanStrictcrlpolicy",
                "strongSwanUniqueids","StrongswanListenInterface","StrongswanEnableDNSWINS","strongSwanEnableDHCP",
                "StrongswanDHCPListenInterface","Strongswanretention") as $key){
        $sock->SET_INFO($key,$V[$key]);
    }
    $sock->SET_INFO("StrongSwanLogSyslogDoNotStorelogsLocally",$nolocal);

    // Le generateur lit ces valeurs dans ArticastrongSwanSettings : les cles
    // qui ne sont pas de ce formulaire y restent telles quelles.
    $ini=new Bs_IniHandler();
    $ini->loadString(@file_get_contents('/etc/artica-postfix/settings/Daemons/ArticastrongSwanSettings'));
    foreach($V as $key=>$val){$ini->_params["GLOBAL"][$key]=$val;}
    $sock->SaveConfigFile($ini->toString(),"ArticastrongSwanSettings");

    $wizard=@unserialize(base64_decode((string)$sock->GET_INFO("strongSwanWizard")));
    if(!is_array($wizard)){$wizard=array();}
    foreach($V as $key=>$val){$wizard[$key]=$val;}
    $sock->SaveConfigFile(base64_encode(serialize($wizard)),"strongSwanWizard");

    admin_tracks("Save the IPsec VPN server settings");
    return true;
}
