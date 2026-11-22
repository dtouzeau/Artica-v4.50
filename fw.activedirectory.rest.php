<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
// La Web API execute des scripts et gere les snapshots : la page, comme son entree
// du menu gauche, est reservee aux administrateurs systeme. Le controle couvre aussi
// chaque POST et chaque action GET aiguilles plus bas.
$GLOBALS["ADREST_USER"]=new usersMenus();
if(!$GLOBALS["ADREST_USER"]->AsSystemAdministrator){
    if(isset($_POST) && count($_POST)>0){ $tplDeny=new template_admin(); echo $tplDeny->_ENGINE_parse_body("{ERROR_NO_PRIVS2}"); exit; }
    if(function_exists("header")){ @header("Status: 403"); }
    $tplDeny=new template_admin();
    echo $tplDeny->_ENGINE_parse_body("<h2>{ERROR_NO_PRIVS2}</h2>");
    exit;
}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_POST["ActiveDirectoryRestInterface"])){save();exit;}
if(isset($_POST["adrf-save"])){adrf_save();exit;}
if(isset($_POST["ActiveDirectoryRestShellEnable"])){save();exit;}
if(isset($_POST["ActiveDirectoryRestTestUser"])){save();exit;}
if(isset($_POST["ActiveDirectoryRestLetsEncryptIface"])){save();exit;}
if(isset($_GET["revoke-token-js"])){revoke_token_js();exit;}
if(isset($_POST["debian-agent-revoke-token"])){revoke_tokens();exit;}
if(isset($_POST["debian-agent-create-token"])){create_token();exit;}

if(isset($_GET["status"])){webapi_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_GET["debianagent-status"])){debianagent_status();exit;}
if(isset($_GET["da-uninstall-js"])){adrest_uninstall_js();exit;}
if(isset($_GET["nac-account-js"])){nac_account_js();exit;}
if(isset($_POST["da-uninstall-confirm"])){adrest_uninstall_confirm();exit;}
if(isset($_GET["debian-agent-certificate"])){debian_agent_certificate();exit;}
if(isset($_GET["debian-agent-account"])){debian_agent_account_tab();exit;}
if(isset($_GET["debian-agent-account-status"])){debian_agent_account_status();exit;}
if(isset($_GET["debian-agent-certificate-body"])){debian_agent_certificate_tab();exit;}
if(isset($_GET["debian-agent-certificate-state"])){debian_agent_certificate_state();exit;}
if(isset($_POST["debian-agent-certificate-load"])){debian_agent_certificate_load();exit;}
if(isset($_POST["debian-agent-certificate-account"])){debian_agent_certificate_account();exit;}
if(isset($_GET["debian-agent-params-js"])){debian_agent_params_js();exit;}
if(isset($_GET["debian-agent-params-tabs"])){debian_agent_params_tabs();exit;}
if(isset($_GET["debian-agent-params-popup"])){debian_agent_params_popup();exit;}
if(isset($_POST["ListenInterface"])){debian_agent_params_save();exit;}

if(isset($_GET["section-memcache-js"])){section_memcache_js();exit;}
if(isset($_GET["section-memcache-popup"])){section_memcache_popup();exit;}
if(isset($_POST["UseMemCacheClient"])){section_memcache_save();exit;}

if(isset($_GET["section-service-js"])){section_service_js();exit;}
if(isset($_GET["section-service-popup"])){section_service_popup();exit;}

if(isset($_GET["section-features-js"])){section_features_js();exit;}
if(isset($_GET["section-features-popup"])){section_features_popup();exit;}

if(isset($_GET["section-auth-js"])){section_auth_js();exit;}
if(isset($_GET["section-auth-popup"])){section_auth_popup();exit;}

if(isset($_GET["section-letsencrypt-js"])){section_letsencrypt_js();exit;}
if(isset($_GET["section-letsencrypt-popup"])){section_letsencrypt_popup();exit;}

if(isset($_GET["dbrecover-js"])){dbrecover_js();exit;}
if(isset($_GET["dbrecover-popup"])){dbrecover_popup();exit;}
if(isset($_GET["dbrecover-run"])){dbrecover_run();exit;}

if(isset($_GET["tiny"])){Tiny();exit;}

page();
function page(){
    //
    $page=CurrentPageName();
    $raccourci="ad-webapi";
    $tpl=new template_admin();

    $ARTICAREST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");

    $html=$tpl->page_header("{SQUID_AD_RESTFULL} v$ARTICAREST_VERSION",
        "fad fa-monitor-heart-rate","{SQUID_AD_RESTFULL_EXPLAIN}",
        "$page?tabs=yes",$raccourci,"progress-webapi-restart",false,"table-loader-webapi");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}
// Jeton d'enrolement : jamais une lettre ou un chiffre de plus que ce qu'emet le daemon.
function adrest_token_param($v):string{
    $v=trim((string)$v);
    return preg_match('/^[A-Za-z0-9_-]{8,128}$/',$v)?$v:"";
}
function adrest_status_refresh_js():string{
    $page=CurrentPageName();
    return "LoadAjaxSilent('debianagent-status','$page?debianagent-status=yes');";
}
// ?revoke-token-js=yes&token=... : la revocation passe par une confirmation.
function revoke_token_js():bool{
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $token=adrest_token_param($_GET["token"] ?? "");
    if($token==""){ $tpl->js_error("{error}"); return false; }
    return $tpl->js_dialog_confirm_action("{adrest_confirm_revoke}","debian-agent-revoke-token",$token,adrest_status_refresh_js());
}
// POST debian-agent-revoke-token : ne renvoie que l'erreur.
function revoke_tokens():bool{
    $tpl=new template_admin();
    $token=adrest_token_param($_POST["debian-agent-revoke-token"] ?? "");
    if($token==""){ echo $tpl->post_error("{error}"); return false; }
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/delete-token/".urlencode($token)));
    if(!is_object($json) || empty($json->Status)){
        $err=(is_object($json) && !empty($json->Error))?(string)$json->Error:"{error_backend}";
        echo $tpl->post_error($err);
        return false;
    }
    return admin_tracks("Revoke Debian agent token ".substr($token,0,8)."...");
}
// POST debian-agent-create-token : le jeton n'est plus cree en affichant la page.
function create_token():bool{
    $tpl=new template_admin();
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/create-token"));
    if(!is_object($json) || empty($json->Status)){
        $err=(is_object($json) && !empty($json->Error))?(string)$json->Error:"{error_backend}";
        echo $tpl->post_error($err);
        return false;
    }
    return admin_tracks("Created a Debian agent enrollment token");
}

function config_file_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return false;}
    return $tpl->js_dialog1("{APP_UNBOUND} >> {config_file}", "$page?config-file-popup=yes");

}
function section_service_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{parameters}","$page?section-service-popup=yes");
}

function section_memcache_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{use_memory_cache_service}","$page?section-memcache-popup=yes");
}
function debian_agent_params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{parameters}","$page?debian-agent-params-tabs=yes",950);
}
function section_memcache_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ENabled=intval(ValkeyClient::httpGetInfo("UseMemCacheClient"));
    echo $tpl->BigCircleCheckbox("UseMemCacheClient","{use_memory_cache_service}","{use_memory_cache_service_explain}",$ENabled,"BootstrapDialog1.close();LoadAjaxSilent('progress-webapi-start','$page?table1=yes');");
    return true;
}
function section_memcache_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $UseMemCacheClient=$_POST=intval($_POST["UseMemCacheClient"]);
    $ENabled=intval(ValkeyClient::httpGetInfo("UseMemCacheClient"));
    writelogs("$ENabled === $UseMemCacheClient",__FUNCTION__,__FILE__,__LINE__);
    if($ENabled==$UseMemCacheClient){
        return false;
    }
    if($UseMemCacheClient==1){
        writelogs("Install Memcache service for Artica REST API service",__FUNCTION__,__FILE__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/artmem/install");
        return admin_tracks("Memcache service installed for Artica REST API service");
    }
    writelogs("uninstall Memcache service for Artica REST API service",__FUNCTION__,__FILE__,__LINE__);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/artmem/uninstall");
    return admin_tracks("Memcache service uninstalled for Artica REST API service");
}

function section_features_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{features}","$page?section-features-popup=yes");
}
function section_auth_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{test_authentication}","$page?section-auth-popup=yes");
}
function section_letsencrypt_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{letsencrypt_http_service}","$page?section-letsencrypt-popup=yes");
}

function section_js_formNoRestart():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxSilent('progress-webapi-start','$page?table1=yes');";
}
function section_js_form():string{
    $page=CurrentPageName();
    $jsRestart=restart_array();
    return "BootstrapDialog1.close();LoadAjaxSilent('progress-webapi-start','$page?table1=yes');$jsRestart";
}
function section_service_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestInterface   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestInterface"));
    $ActiveDirectoryRestPort        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPort"));
    $ActiveDirectoryRestSSL         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestSSL"));
    $ActiveDirectoryRestCert        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestCert"));
    $ActiveDirectoryRestDebug       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestDebug"));

    if($ActiveDirectoryRestInterface==null){$ActiveDirectoryRestInterface="lo";}
    if($ActiveDirectoryRestPort==0){$ActiveDirectoryRestPort=9503;}

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestDebug", "{debug}", $ActiveDirectoryRestDebug);
    $form[] = $tpl->field_interfaces("ActiveDirectoryRestInterface", "nodef:{listen_interfaces}", $ActiveDirectoryRestInterface);
    $form[] = $tpl->field_numeric("ActiveDirectoryRestPort", "{listen_port}", $ActiveDirectoryRestPort);
    $form[] = $tpl->field_checkbox("ActiveDirectoryRestSSL", "{ssl}", $ActiveDirectoryRestSSL,false);
    $form[] = $tpl->field_certificate("ActiveDirectoryRestCert","{certificate}",$ActiveDirectoryRestCert);

    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function debian_agent_params_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $params=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/params"),true);
    $Conf=$params["Params"];
    $form[]=$tpl->field_interfaces("ListenInterface","{listen_interface}",$Conf["listen_interface"]);
    $form[]=$tpl->field_numeric("ListenPort","{listen_port}",$Conf["listen_port"]);
    $security="AsSystemAdministrator";
    $js="dialogInstance2.close();LoadAjaxSilent('progress-webapi-start','$page?table1=yes');";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", $js,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function debian_agent_params_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ListenInterface=$_POST["ListenInterface"];
    $ListenPort=intval($_POST["ListenPort"]);
    $uri="/debianagent/iface/$ListenInterface/$ListenPort";
    $GLOBALS["CLASS_SOCKETS"]->REST_API($uri);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/restart");
    return admin_tracks("Save Debian Agent listen to $ListenInterface:$ListenPort whitelist:{$_POST["ip_whitelist"]}");
}
// Modale "Features" (maquette stitch_artica_web_api_dialog_redesign) : clef d'API X-Auth-Token
// et deux permissions. Prefixe adrf_. Enregistrement par POST adrf-save (adrf_save()), qui
// n'ecrit jamais la clef dans le journal d'audit, puis redemarrage du service (section_js_form()).
// NB : ActiveDirectoryRestShellEnable / ActiveDirectoryRestSnapsEnable ne sont lus par aucun
// code d'articarest a ce jour ; la modale les conserve tels quels.
function section_features_popup():bool{
    $m=adrf_model();
    $id="adrf-".substr(md5(microtime(true).rand()),0,8);
    $h=array();
    $h[]="<div class='adrf' id='$id'>";
    $h[]=adrf_intro();
    $h[]=adrf_exposure($m);
    $h[]=adrf_key_card($m,$id);
    $h[]=adrf_perms_card($m,$id);
    $h[]=adrf_footer($id);
    $h[]="</div>";
    $tpl=new template_admin();
    echo adrf_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).adrf_js($id,$m);
    return true;
}

function adrf_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $a=adrest_model();
    return array(
        "key"=>trim((string)$s->GET_INFO("ActiveDirectoryRestShellPass")),
        "scripts"=>$a["scripts"],
        "snaps"=>$a["snaps"],
        "exposed"=>$a["exposed"],
        "listeners"=>$a["listeners"],
        "iface"=>$a["iface"],
        "port"=>$a["port"],
        "ssl"=>$a["ssl"],
    );
}

function adrf_t(string $k,array $args=array()):string{
    $tpl=new template_admin();
    $s=trim(preg_replace('/\s+/',' ',str_replace(array("[br]","<br>")," ",$tpl->_ENGINE_parse_body("{".$k."}"))));
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

function adrf_intro():string{
    return "<div class='adrf-intro'><span class='adrf-tile'><i class='fas fa-key' aria-hidden='true'></i></span>"
        ."<span>{adrf_subtitle}</span></div>";
}

// 2. Ou ecoute l'API : ce que le daemon annonce (listeners), sinon les reglages.
function adrf_exposure(array $m):string{
    if(!$m["exposed"]){
        return "<div class='adrf-strip adrf-strip-local'><i class='fas fa-house-laptop' aria-hidden='true'></i>"
            ."<span>".adrf_t("adrf_listen_local",array("<span class='adrf-mono'>127.0.0.1:".intval($m["port"])."</span>"))."</span></div>";
    }
    $addr=count($m["listeners"])>0?implode(", ",$m["listeners"]):($m["iface"].":".intval($m["port"]));
    $proto=$m["ssl"]?"HTTPS":"HTTP";
    return "<div class='adrf-strip adrf-strip-net'><i class='fas fa-network-wired' aria-hidden='true'></i>"
        ."<span>".adrf_t("adrf_listen_net",array("<span class='adrf-mono'>".adrest_h($addr)."</span>",$proto))."</span></div>";
}

// 3. Clef d'API : champ masque, afficher/copier, Generate, bandeaux d'etat (pilotes en JS).
function adrf_key_card(array $m,string $id):string{
    $show=adrf_t("adrf_show_key");$copy=adrf_t("adrf_copy_key");
    $h=array();
    $h[]="<section class='adrf-card'>";
    $h[]="<label for='$id-key' class='adrf-label'>{adrf_key_label}</label>";
    $h[]="<div class='adrf-help'>".adrf_t("adrf_key_help",array("<span class='adrf-code'>X-Auth-Token</span>"))."</div>";
    $h[]="<div class='adrf-keyrow'><div class='adrf-keybox'>";
    $h[]="<input type='password' id='$id-key' class='adrf-key' value=\"".adrest_h($m["key"])."\" spellcheck='false' autocomplete='off' placeholder='&bull;&bull;&bull;&bull;-&bull;&bull;&bull;&bull;-&bull;&bull;&bull;&bull;-&bull;&bull;&bull;&bull;' aria-describedby='$id-hint'>";
    $h[]="<span class='adrf-keyact'>";
    $h[]="<button type='button' class='adrf-ico' id='$id-eye' aria-label=\"$show\" title=\"$show\" aria-pressed='false'><span class='adrf-eye-on'><i class='far fa-eye' aria-hidden='true'></i></span><span class='adrf-eye-off' hidden><i class='far fa-eye-slash' aria-hidden='true'></i></span></button>";
    $h[]="<button type='button' class='adrf-ico' id='$id-copy' aria-label=\"$copy\" title=\"$copy\"><i class='far fa-copy' aria-hidden='true'></i></button>";
    $h[]="<span class='adrf-tip' id='$id-tip' role='status' hidden>{adrest_copied}</span>";
    $h[]="</span></div>";
    $h[]="<button type='button' class='adrf-btn2' id='$id-gen' title=\"".adrf_t("adrest_gen_key_tip")."\"><i class='fas fa-wand-magic-sparkles' aria-hidden='true'></i> {adrest_gen_key}</button>";
    $h[]="</div>";
    $h[]="<div class='adrf-help' id='$id-hint'>".adrf_t("adrf_key_format",array("<span class='adrf-code'>A1B2-C3D4-E5F6-G7H8</span>"))."</div>";
    $h[]=adrf_key_states($m,$id);
    $h[]="</section>";
    return @implode("",$h);
}

// Les quatre etats possibles ; adrf_js() n'en montre qu'un selon le champ.
function adrf_key_states(array $m,string $id):string{
    $empty=$m["exposed"]
        ?"<div class='adrf-ban adrf-ban-err' data-st='empty' role='alert' hidden><i class='fas fa-circle-exclamation' aria-hidden='true'></i><span>{adrf_empty_net}</span></div>"
        :"<div class='adrf-ban adrf-ban-warn' data-st='empty' role='status' hidden><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>{adrf_empty_local}</span></div>";
    return "<div class='adrf-states' id='$id-st'>$empty"
        ."<div class='adrf-ban adrf-ban-warn' data-st='changed' role='status' hidden><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>{adrf_changed}</span></div>"
        ."<div class='adrf-ok' data-st='set' hidden><i class='fas fa-circle-check' aria-hidden='true'></i><span>{adrf_key_is_set}</span></div>"
        ."<div class='adrf-ban adrf-ban-err' data-st='invalid' role='alert' hidden><i class='fas fa-circle-exclamation' aria-hidden='true'></i><span>{adrf_key_invalid}</span></div>"
        ."</div>";
}

function adrf_switch(string $id,string $field,bool $on,string $label,string $help):string{
    $st=$on?"true":"false";
    return "<div class='adrf-perm'><div class='adrf-perm-t'><div class='adrf-perm-l' id='$id-$field-l'>$label</div><div class='adrf-help'>$help</div></div>"
        ."<button type='button' role='switch' class='adrf-sw' data-field='$field' aria-checked='$st' aria-labelledby='$id-$field-l'>"
        ."<span class='adrf-sw-k' aria-hidden='true'></span></button>"
        ."<span class='adrf-sw-s' aria-hidden='true'><span class='adrf-on'>{adrest_on}</span><span class='adrf-off'>{adrest_off}</span></span></div>";
}

// 4. Permissions.
function adrf_perms_card(array $m,string $id):string{
    return "<section class='adrf-card'><div class='adrf-label adrf-mb'>{adrest_permissions}</div>"
        .adrf_switch($id,"ActiveDirectoryRestShellEnable",$m["scripts"],"{allow_execute_scripts}","{adrf_scripts_help}")
        .adrf_switch($id,"ActiveDirectoryRestSnapsEnable",$m["snaps"],"{allow_snapshots}","{adrf_snaps_help}")
        ."</section>";
}

// 5. Pied : rappel du redemarrage, Cancel / Apply.
function adrf_footer(string $id):string{
    return "<div class='adrf-foot'><span class='adrf-foot-n'><i class='fas fa-rotate' aria-hidden='true'></i> {adrf_restart_note}</span>"
        ."<span class='adrf-grow'></span><span class='adrf-err' id='$id-err' role='alert'></span>"
        ."<button type='button' class='adrf-btn2' OnClick=\"BootstrapDialog1.close();\">{adrf_cancel}</button>"
        ."<button type='button' class='adrf-btn' id='$id-apply'><i class='fas fa-check' aria-hidden='true'></i> {adrf_apply}</button></div>";
}

// Clef acceptee : ASCII visible sans espace ni guillemets, 0 a 128 caracteres (vide = pas de clef).
function adrf_key_valid(string $k):bool{
    if($k===""){return true;}
    return preg_match('/^[\x21-\x7E]{1,128}$/',$k)===1 && strpbrk($k,"\"'\\<>")===false;
}

function adrf_save():bool{
    $tpl=new template_admin();
    if(!$GLOBALS["ADREST_USER"]->AsSystemAdministrator){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $key=trim((string)($_POST["key"] ?? ""));
    if(!adrf_key_valid($key)){echo adrf_t("adrf_key_invalid");return false;}
    $s=$GLOBALS["CLASS_SOCKETS"];
    $old=trim((string)$s->GET_INFO("ActiveDirectoryRestShellPass"));
    $scripts=intval($_POST["ActiveDirectoryRestShellEnable"] ?? 0)==1?1:0;
    $snaps=intval($_POST["ActiveDirectoryRestSnapsEnable"] ?? 0)==1?1:0;
    $s->SET_INFO("ActiveDirectoryRestShellPass",$key);
    $s->SET_INFO("ActiveDirectoryRestShellEnable",$scripts);
    $s->SET_INFO("ActiveDirectoryRestSnapsEnable",$snaps);
    $what="unchanged";
    if($key!==$old){$what=($key==="")?"removed":"changed";}
    return admin_tracks("Artica Web API features saved: API key $what, execute scripts=$scripts, manage snapshots=$snaps");
}

function adrf_js(string $id,array $m):string{
    $page=CurrentPageName();
    $after=section_js_form();
    $copyErr=str_replace(array("\\","'"),array("\\\\","\\'"),html_entity_decode(adrf_t("adrf_copy_manual"),ENT_QUOTES,"UTF-8"));
    return "<script>
(function(){
var R=document.getElementById('$id'); if(!R){return;}
var K=document.getElementById('$id-key'), saved=K.value;
var RE=/^[\\x21-\\x7E]{1,128}$/;
function st(){
  var v=K.value.trim(), s='set';
  if(v===''){s='empty';}
  else if(!RE.test(v) || /[\"'\\\\<>]/.test(v)){s='invalid';}
  else if(v!==saved){s='changed';}
  var b=document.getElementById('$id-st').children;
  for(var i=0;i<b.length;i++){ b[i].hidden=(b[i].getAttribute('data-st')!==s); }
  return s;
}
K.addEventListener('input',st);
document.getElementById('$id-eye').onclick=function(){
  var on=K.type==='password'; K.type=on?'text':'password';
  this.setAttribute('aria-pressed',on?'true':'false');
  this.querySelector('.adrf-eye-on').hidden=on; this.querySelector('.adrf-eye-off').hidden=!on;
};
document.getElementById('$id-copy').onclick=function(){
  var v=K.value, tip=document.getElementById('$id-tip');
  function ok(){ tip.hidden=false; setTimeout(function(){tip.hidden=true;},1500); }
  function legacy(){
    var t=document.createElement('textarea'); t.value=v; t.setAttribute('readonly',''); t.style.position='absolute'; t.style.left='-9999px';
    document.body.appendChild(t); t.select(); var r=false; try{ r=document.execCommand('copy'); }catch(e){} document.body.removeChild(t);
    if(r){ok();}else{ window.prompt('$copyErr',v); }
  }
  if(v===''){return;}
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(v).then(ok,legacy); }else{ legacy(); }
};
document.getElementById('$id-gen').onclick=function(){
  var c=window.crypto||window.msCrypto, A='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', k='', n=0, b=new Uint8Array(1);
  if(!c || !c.getRandomValues){ return; }
  while(n<16){
    c.getRandomValues(b);
    if(b[0]>=252){continue;}            /* 252 = 7*36 : pas de biais de modulo */
    if(n>0 && n%4===0){k+='-';}
    k+=A.charAt(b[0]%36); n++;
  }
  K.value=k; K.type='text';
  var e=document.getElementById('$id-eye'); e.setAttribute('aria-pressed','true');
  e.querySelector('.adrf-eye-on').hidden=true; e.querySelector('.adrf-eye-off').hidden=false;
  K.classList.remove('adrf-flash'); void K.offsetWidth; K.classList.add('adrf-flash');
  st();
};
var sw=R.querySelectorAll('.adrf-sw');
for(var i=0;i<sw.length;i++){ sw[i].onclick=function(){ this.setAttribute('aria-checked',this.getAttribute('aria-checked')==='true'?'false':'true'); }; }
document.getElementById('$id-apply').onclick=function(){
  if(st()==='invalid'){ K.focus(); return; }
  var btn=this, err=document.getElementById('$id-err'), d={'adrf-save':'yes','key':K.value.trim()};
  for(var j=0;j<sw.length;j++){ d[sw[j].getAttribute('data-field')]=sw[j].getAttribute('aria-checked')==='true'?1:0; }
  btn.disabled=true; btn.classList.add('busy'); err.textContent='';
  $.post('$page',d).done(function(r){
    r=String(r||'').trim(); btn.disabled=false; btn.classList.remove('busy');
    if(r.length>0){ err.textContent=r.replace(/<[^>]*>/g,''); return; }
    $after
  }).fail(function(x){ btn.disabled=false; btn.classList.remove('busy'); err.textContent='HTTP '+x.status; });
};
st();
})();
</script>";
}

function adrf_style():string{
    $ac=array("accent"=>"#0E7C66","accent_dark"=>"#0b6654","accent_soft"=>"rgba(14,124,102,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        $ac["accent"]=$b["accent"];$ac["accent_dark"]=$b["accent_dark"];$ac["accent_soft"]=$b["accent_soft"];
    }
    $A=$ac["accent"];$AD=$ac["accent_dark"];$AS=$ac["accent_soft"];
    return "<style>
.adrf{font-size:13px;color:#111827;display:flex;flex-direction:column;gap:16px}
.adrf *{box-sizing:border-box}
.adrf-mono,.adrf-code,.adrf-key{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace}
.adrf-intro{display:flex;align-items:center;gap:12px;color:#6B7280;font-size:13px}
.adrf-tile{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:6px;background:$AS;color:$AD;flex:none}
.adrf-strip{display:flex;align-items:center;gap:10px;min-height:40px;padding:8px 14px;border-radius:6px;font-size:13px;font-weight:500}
.adrf-strip-local{background:#F3F4F6;color:#374151;border:1px solid #E5E7EB}
.adrf-strip-net{background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE}
.adrf-card{border:1px solid #E5E7EB;border-radius:8px;padding:16px;background:#fff}
.adrf-label{display:block;font-size:13px;font-weight:600;color:#111827;margin:0}
.adrf-mb{margin-bottom:8px}
.adrf-help{font-size:12px;color:#6B7280;margin-top:2px}
.adrf-code{font-size:12px;background:#F3F4F6;border-radius:4px;padding:1px 6px;color:#374151}
.adrf-keyrow{display:flex;gap:8px;align-items:center;margin:12px 0 8px}
.adrf-keybox{position:relative;flex:1}
.adrf-key{width:100%;height:40px;padding:0 84px 0 14px;font-size:13px;letter-spacing:.05em;border:1px solid #E5E7EB;border-radius:6px;background:#fff;color:#111827}
.adrf-key:focus{outline:2px solid $A;outline-offset:1px;border-color:transparent}
.adrf-key.adrf-flash{animation:adrfFlash .6s ease-out}
@keyframes adrfFlash{0%{background:$AS}100%{background:#fff}}
.adrf-keyact{position:absolute;right:6px;top:50%;transform:translateY(-50%);display:flex;gap:4px}
.adrf-ico{width:30px;height:30px;border:0;border-radius:4px;background:transparent;color:#6B7280;cursor:pointer;display:inline-flex;align-items:center;justify-content:center}
.adrf-ico:hover{background:#F3F4F6;color:#111827}
.adrf-tip{position:absolute;right:0;top:-30px;background:#111827;color:#fff;font-size:12px;padding:2px 8px;border-radius:4px;white-space:nowrap}
.adrf-btn,.adrf-btn2{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 16px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.adrf-btn{border:0;background:$A;color:#fff}
.adrf-btn:hover{background:$AD}
.adrf-btn[disabled]{opacity:.6;cursor:wait}
.adrf-btn.busy svg,.adrf-btn.busy i{animation:adrfSpin 1s linear infinite}
@keyframes adrfSpin{to{transform:rotate(360deg)}}
.adrf-btn2{border:1px solid #E5E7EB;background:#fff;color:#374151;font-weight:500}
.adrf-btn2:hover{background:#F9FAFB}
.adrf-btn2 svg,.adrf-btn2 i{color:$A}
.adrf button:focus-visible{outline:2px solid $A;outline-offset:2px}
.adrf-states{margin-top:12px}
.adrf-ban{display:flex;gap:10px;align-items:flex-start;padding:12px;border-radius:0 6px 6px 0;font-size:13px;line-height:1.45}
.adrf-ban[hidden],.adrf-ok[hidden]{display:none}
.adrf-ban-err{background:#FEF2F2;border-left:4px solid #DC2626;color:#991B1B}
.adrf-ban-err svg,.adrf-ban-err i{color:#DC2626;margin-top:2px;flex:none}
.adrf-ban-warn{background:#FFFBEB;border-left:4px solid #D97706;color:#92400E}
.adrf-ban-warn svg,.adrf-ban-warn i{color:#D97706;margin-top:2px;flex:none}
.adrf-ok{display:flex;gap:8px;align-items:center;color:#047857;font-size:13px}
.adrf-perm{display:flex;align-items:center;gap:16px;padding:12px 0;border-top:1px solid #F3F4F6}
.adrf-mb+.adrf-perm{border-top:0;padding-top:4px}
.adrf-perm-t{flex:1}
.adrf-perm-l{font-weight:500;color:#111827}
.adrf-sw{position:relative;width:44px;height:24px;border-radius:12px;border:0;background:#D1D5DB;cursor:pointer;flex:none;transition:background .15s;padding:0}
.adrf-sw-k{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.adrf-sw[aria-checked=true]{background:$A}
.adrf-sw[aria-checked=true] .adrf-sw-k{left:23px}
.adrf-sw-s{width:28px;font-size:12px;color:#6B7280}
.adrf-perm .adrf-sw[aria-checked=true]+.adrf-sw-s .adrf-off,.adrf-perm .adrf-sw[aria-checked=false]+.adrf-sw-s .adrf-on{display:none}
.adrf-foot{display:flex;align-items:center;gap:8px;margin:0 -15px -15px;padding:12px 16px;background:#F9FAFB;border-top:1px solid #E5E7EB;border-radius:0 0 6px 6px}
.adrf-foot-n{font-size:12px;color:#6B7280}
.adrf-grow{flex:1}
.adrf-err{font-size:12px;color:#B91C1C;max-width:260px}
@media (prefers-reduced-motion:reduce){.adrf-key.adrf-flash,.adrf-btn.busy svg,.adrf-btn.busy i{animation:none}.adrf-sw,.adrf-sw-k{transition:none}}
</style>";
}

function section_letsencrypt_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestLetsEncrypt    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestLetsEncrypt"));
    $ActiveDirectoryRestLetsEncryptIface    = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestLetsEncryptIface"));

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestLetsEncrypt","{enable_feature}",$ActiveDirectoryRestLetsEncrypt);
    $form[]=$tpl->field_interfaces("ActiveDirectoryRestLetsEncryptIface","{interface}",$ActiveDirectoryRestLetsEncryptIface);

     $security="AsSystemAdministrator";
     $letsencrypt_http_service_explain2=$tpl->_ENGINE_parse_body("{letsencrypt_http_service_explain2}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),"{letsencrypt_http_service_explain}<br>$letsencrypt_http_service_explain2","{apply}", section_js_formNoRestart(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function section_auth_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ActiveDirectoryRestTestUser    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestUser"));
    $ActiveDirectoryRestUser        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestUser"));
    $ActiveDirectoryRestPass        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPass"));
    $ActiveDirectoryRestTestURL     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestURL"));

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestTestUser","{enable_feature}",$ActiveDirectoryRestTestUser,
        "ActiveDirectoryRestTestURL,ActiveDirectoryRestUser,ActiveDirectoryRestPass");
    $form[] = $tpl->field_text("ActiveDirectoryRestTestURL","{uri_test}",$ActiveDirectoryRestTestURL);
    $form[] = $tpl->field_text("ActiveDirectoryRestUser","{username}",$ActiveDirectoryRestUser);
    $form[] = $tpl->field_password2("ActiveDirectoryRestPass","{password}",$ActiveDirectoryRestPass);


    $security="AsSystemAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{networks_restrictions}"]="fw.activedirectory.rest.restrictions.php";
    $array["{events}"]="fw.activedirectory.rest.events.php";
    echo $tpl->tabs_default($array);
    return true;
}
function debian_agent_params_tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?debian-agent-params-popup=yes";
    $array["{certificate}"]="$page?debian-agent-certificate=yes";
    $array["{da_meta_account_title}"]="$page?debian-agent-account=yes";
    $array["{networks_restrictions}"]="fw.debian-agent.restrictions.php";
    echo $tpl->tabs_default($array);
    return true;
}

function restart_array():string{
    $page   = CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("watch:/articarest/rest",
        "active-directory-rest.restart","active-directory-rest.restart.log",
        "progress-webapi-restart","LoadAjaxSilent('adrest-status','$page?status=yes');Loadjs('$page?tiny=yes');");


    //REST_ARTWATCH
}

function webunix_status():string{
    $tpl    = new template_admin();
    $htopwebrestart=$tpl->framework_buildjs("/system/htopweb/restart","htopweb.restart.progress","htopweb.restart.progress.log","progress-webapi-restart");
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/webunix/status");

    if(!function_exists("json_decode")){
        return $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");

    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }

    if(!property_exists($json,"Info")){
        return  $tpl->widget_rouge("{error}","protocol error");

    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($bsini, "APP_SHELLINABOX",$htopwebrestart);
}


function artmem_status():string{
    $tpl    = new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/artmem/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsrestart=$tpl->framework_buildjs(
        "/artmem/restart","artmem.progress","artmem.progress.logs",
        "progress-webapi-restart");

    return $tpl->SERVICE_STATUS($ini, "APP_ARTMEM",$jsrestart);
}
function redis_status():string{
    $tpl    = new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/redis/status"));
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsrestart=$tpl->framework_buildjs(
        "/redis/restart","progress-webapi-restart","progress-webapi-restart.logs",
        "progress-webapi-restart");

    return $tpl->SERVICE_STATUS($ini, "APP_REDIS_SERVER",$jsrestart);
}

function pogocache_status():string{
    if(!class_exists("ValkeyClient")){
        return redis_status();
    }

    $UseMemCacheClient=intval(ValkeyClient::httpGetInfo("UseMemCacheClient"));
    if($UseMemCacheClient==1){
        return artmem_status();
    }
    $PogoCacheEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PogoCacheEnabled");
    if($PogoCacheEnabled==0){
        return redis_status();
    }

    $tpl    = new template_admin();
    $htopwebrestart=$tpl->framework_buildjs("/pogocache/restart","progress-webapi-restart","progress-webapi-restart.log","progress-webapi-restart");
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/pogocache/status");

    if(!function_exists("json_decode")){
        return $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");

    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }

    if(!property_exists($json,"Info")){
        return  $tpl->widget_rouge("{error}","protocol error");

    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($bsini, "APP_POGOCACHE",$htopwebrestart);
}

function webapi_status():bool{

    $tpl    = new template_admin();


    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/adrest/status");

    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }

    if(!property_exists($json,"info")){
        $btn=array();
        $btn[0]["name"] = "{start}";
        $btn[0]["icon"] = ico_run;
        $btn[0]["js"] = restart_array();
        echo $tpl->widget_rouge("{error}","Protocol error",$btn,ico_bug);
        return true;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->info);
    $jsRestart=restart_array();
    $final[]=$tpl->SERVICE_STATUS($bsini, "SQUID_AD_RESTFULL",$jsRestart);
    $final[]=pogocache_status();
    $final[]=webunix_status();
    $page=CurrentPageName();

    $ARTICAREST_VERSION="";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ping"),true);
    if($json["Status"]){
        $ARTICAREST_VERSION=$json["Version"];
    }

    if($ARTICAREST_VERSION=="") {
        $ARTICAREST_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");
    }


    $final[]="<script>";
    $final[]="$('#active-directory-rest-version').html('$ARTICAREST_VERSION');";
    $final[]="</script>";
    echo $tpl->_ENGINE_parse_body($final);
    return true;

}
function table():bool{
    $page=CurrentPageName();
    echo "<div style='margin-top:10px' id='progress-webapi-start'></div>
<script>LoadAjaxSilent('progress-webapi-start','$page?table1=yes')</script>";
    return true;

}

// Onglet Status (maquette stitch_artica_web_api_console, 2026-11-01).
// La colonne de gauche garde les tuiles SERVICE_STATUS (webapi_status, inchange).
// La colonne de droite : un modele de reglages + une fonction par carte ; l'agent
// reseau a son propre hote, recharge par son timer.

function table1(){
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $m    = adrest_model();
    $Interval=$tpl->RefreshInterval_js("adrest-status",$page,"status=yes",3);
    $html=array();
    $html[]="<div class='adr'>";
    $html[]="<div class='adr-side'><div id='adrest-status'></div></div>";
    $html[]="<div class='adr-main'>";
    $html[]=adrest_card_exposure($m);
    $html[]="<div class='adr-pair'>".adrest_card_permissions($m).adrest_card_options($m)."</div>";
    $html[]="<div id='debianagent-status'></div>";
    $html[]="</div></div>";
    $js="<script>$Interval;LoadAjaxSilent('debianagent-status','$page?debianagent-status=yes');Loadjs('$page?tiny=yes');</script>";
    echo adrest_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).adrest_js().$js;
}

/** Les reglages de la Web API, lus une fois. */
function adrest_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $m["iface"]=trim((string)$s->GET_INFO("ActiveDirectoryRestInterface"));
    if($m["iface"]==""){$m["iface"]="127.0.0.1";}
    $m["local"]=($m["iface"]=="127.0.0.1" || $m["iface"]=="lo");
    $m["port"]=intval($s->GET_INFO("ActiveDirectoryRestPort"));
    if($m["port"]==0){$m["port"]=9503;}
    $m["ssl"]=!$m["local"] && intval($s->GET_INFO("ActiveDirectoryRestSSL"))==1;
    $m["cert"]=trim((string)$s->GET_INFO("ActiveDirectoryRestCert"));
    $m["debug"]=intval($s->GET_INFO("ActiveDirectoryRestDebug"))==1;
    $m["key"]=strlen(trim((string)$s->GET_INFO("ActiveDirectoryRestShellPass")))>0;
    $m["scripts"]=intval($s->GET_INFO("ActiveDirectoryRestShellEnable"))==1;
    $m["snaps"]=intval($s->GET_INFO("ActiveDirectoryRestSnapsEnable"))==1;
    $m["test"]=intval($s->GET_INFO("ActiveDirectoryRestTestUser"))==1;
    $m["test_user"]=trim((string)$s->GET_INFO("ActiveDirectoryRestUser"));
    $m["test_url"]=trim((string)$s->GET_INFO("ActiveDirectoryRestTestURL"));
    if($m["test_url"]==""){$m["test_url"]="https://www.clubic.com";}
    $m["le"]=intval($s->GET_INFO("ActiveDirectoryRestLetsEncrypt"))==1;
    $m["le_iface"]=trim((string)$s->GET_INFO("ActiveDirectoryRestLetsEncryptIface"));
    if(strlen($m["le_iface"])<3){$m["le_iface"]="0.0.0.0";}
    $m["memcache"]=intval($s->GET_INFO("UseMemCacheClient"))==1;
    // Ce que le daemon ecoute VRAIMENT. Les reglages ne suffisent pas : une
    // interface disparue (eth0 renommee ens192) laisse l'API sur sa seule
    // socket Unix alors que la page annoncait "joignable depuis le reseau".
    // Un daemon trop ancien n'envoie pas listeners_known : on garde alors les
    // reglages, le cas prudent.
    $m["listeners"]=array();
    $m["exposed"]=!$m["local"];
    $st=json_decode((string)$s->REST_API("/adrest/status"));
    if(is_object($st) && !empty($st->listeners_known)){
        if(isset($st->listeners) && is_array($st->listeners)){
            foreach($st->listeners as $addr){$m["listeners"][]=(string)$addr;}
        }
        $m["exposed"]=count($m["listeners"])>0;
    }
    return $m;
}

function adrest_h($v):string{ return htmlspecialchars((string)$v,ENT_QUOTES); }

/** Pastille d'etat : icone + texte, jamais la couleur seule. */
function adrest_chip(string $cls,string $ico,string $text):string{
    return "<span class='ak-pill ak-$cls'><i class='$ico' aria-hidden='true'></i>&nbsp;$text</span>";
}

/** Bouton "Edit" qui ouvre une modale existante. */
function adrest_edit(string $query,string $label="{adrest_edit}"):string{
    $page=CurrentPageName();
    return "<button type='button' class='ak-link' OnClick=\"Loadjs('$page?$query');\"><i class='fas fa-pen-to-square' aria-hidden='true'></i>&nbsp;$label</button>";
}

function adrest_card_head(string $ico,string $title,string $right):string{
    return "<div class='adr-head'><span class='adr-tile'><i class='$ico' aria-hidden='true'></i></span>"
        ."<div class='adr-title'>$title</div><div class='adr-push'>$right</div></div>";
}

function adrest_row(string $label,string $value,string $action=""):string{
    return "<div class='adr-row'><div class='adr-row-l'>$label</div><div class='adr-row-v'>$value</div>"
        .($action!=""?"<div class='adr-row-a'>$action</div>":"")."</div>";
}

/** 1. Exposition de l'API : ou elle ecoute, en clair ou en TLS, et sa cle. */
function adrest_card_exposure(array $m):string{
    $h=array();
    $h[]="<div class='ak-card adr-card'>";
    $h[]=adrest_card_head("fas fa-sitemap","{adrest_api_exposure}",adrest_edit("section-service-js=yes"));
    $h[]="<div class='adr-expo'>";
    $h[]="<div class='adr-expo-l'><div class='adr-lbl'>{listen_interfaces}</div>";
    if(!$m["exposed"]){
        $h[]="<div class='adr-line'><span class='ak-mono adr-box'>unix:articarest.sock</span>"
            .adrest_chip("off","fas fa-house","{adrest_local_only}")."</div>";
        if(!$m["local"]){
            // Configuree sur une carte, mais rien n'ecoute : le dire, sans rouge.
            $h[]="<div class='adr-sub'><i class='fas fa-circle-info' aria-hidden='true'></i>&nbsp;{adrest_iface_not_listening}: <span class='ak-mono'>".adrest_h($m["iface"].":".$m["port"])."</span></div>";
        }
    }else{
        $tls=$m["ssl"]?adrest_chip("ok","fas fa-lock","{adrest_https}"):adrest_chip("warn","fas fa-lock-open","{adrest_http_plain}");
        $where=count($m["listeners"])>0?implode(", ",$m["listeners"]):$m["iface"].":".$m["port"];
        $h[]="<div class='adr-line'><span class='ak-mono adr-box'>".adrest_h($where)."</span>$tls</div>";
        if($m["ssl"] && $m["cert"]!=""){
            $h[]="<div class='adr-sub'><i class='fas fa-certificate' aria-hidden='true'></i>&nbsp;{certificate}: <span class='ak-mono'>".adrest_h($m["cert"])."</span></div>";
        }
        $h[]="<div class='adr-sub'>{adrest_local_socket}: <span class='ak-mono'>unix:articarest.sock</span></div>";
    }
    $h[]="</div>";
    $h[]="<div class='adr-expo-r'><div class='adr-lbl'>{API_KEY}</div><div class='adr-line'>";
    if($m["key"]){
        $h[]=adrest_chip("ok","fas fa-key","{adrest_key_set}");
    }else{
        $h[]=adrest_chip($m["exposed"]?"err":"off",$m["exposed"]?"fas fa-triangle-exclamation":"fas fa-key","{adrest_key_not_set}");
    }
    $h[]=adrest_edit("section-features-js=yes")."</div>";
    if($m["exposed"] && !$m["key"]){
        $h[]="<div class='adr-alert adr-alert-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>&nbsp;{adrest_public_notice}</div>";
    }
    $h[]="</div></div></div>";
    return @implode("\n",$h);
}

/** 2a. Les deux droits a risque et le test d'authentification. */
function adrest_card_permissions(array $m):string{
    $allowed=adrest_chip("warn","fas fa-triangle-exclamation","{adrest_allowed}");
    $denied=adrest_chip("off","fas fa-lock","{adrest_denied}");
    $test=adrest_chip("off","fas fa-power-off","{adrest_off}");
    if($m["test"]){
        $v=$m["test_user"]."@".$m["test_url"];
        $test="<span class='ak-mono adr-box adr-trunc' title='".adrest_h($v)."'>".adrest_h($v)."</span>";
    }
    $h=array();
    $h[]="<div class='ak-card adr-card'>";
    $h[]=adrest_card_head("fas fa-user-shield","{adrest_permissions}",adrest_edit("section-features-js=yes"));
    $h[]=adrest_row("{allow_execute_scripts}",$m["scripts"]?$allowed:$denied);
    $h[]=adrest_row("{allow_snapshots}",$m["snaps"]?$allowed:$denied);
    $h[]=adrest_row("{test_authentication}",$test,adrest_edit("section-auth-js=yes"));
    $h[]="</div>";
    return @implode("\n",$h);
}

/** 2b. Options : chacune ouvre sa propre modale. */
function adrest_card_options(array $m):string{
    $on=adrest_chip("ok","fas fa-circle-check","{adrest_on}");
    $off=adrest_chip("off","fas fa-power-off","{adrest_off}");
    $le=$m["le"]?"<span class='ak-mono adr-box'>{listen} ".adrest_h($m["le_iface"]).":80</span>":$off;
    $h=array();
    $h[]="<div class='ak-card adr-card'>";
    $h[]=adrest_card_head("fas fa-sliders","{adrest_options}","");
    $h[]=adrest_row("{adrest_debug}",$m["debug"]?$on:$off,adrest_edit("section-service-js=yes"));
    $h[]=adrest_row("{use_memory_cache_service}",$m["memcache"]?$on:$off,adrest_edit("section-memcache-js=yes"));
    $h[]=adrest_row("{letsencrypt_http_service}",$le,adrest_edit("section-letsencrypt-js=yes"));
    $h[]="</div>";
    return @implode("\n",$h);
}

// ---------------------------------------------------------------------------
// Carte de l'agent reseau (hote #debianagent-status), maquette
// stitch_network_agent_card_redesign (2026-11-20). Le modele lit le demon une
// fois ; chaque zone de la carte est sa propre fonction (prefixe nac_).
// Metriques du lien Meta : /debianagent/meta/state (champs du controleur de
// l'agent relayes par articarest) et /debianagent/meta/bandwidth (24 h, 10 min).

/** Horodatage Go (nanosecondes, Z) -> timestamp ; 0 si absent ou illisible. */
function nac_ts($raw):int{
    $raw=trim((string)$raw);
    if($raw=="" || strpos($raw,"0001-01-01")===0){return 0;}
    $raw=preg_replace('/\.(\d{6})\d+/','.$1',$raw);
    try{ $dt=new DateTime($raw); return $dt->getTimestamp(); }catch(Exception $e){ return 0; }
}

/** Tout ce que la carte affiche, lu une fois. */
function nac_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $a=array("installed"=>intval($s->GET_INFO("EnableDebianAgent"))==1,"state"=>"off","version"=>"",
        "memory"=>0,"listen"=>"","error"=>"","mode"=>"token","token"=>"","token_ok"=>false,"expires"=>0,
        "endpoint"=>"","live"=>"","restart_pending"=>false,"last_error"=>"","cert_state"=>"",
        "cert_expires"=>0,"uuid"=>"","fingerprint"=>"","connected_since"=>0,"last_pong"=>0,
        "next_attempt"=>0,"sessions"=>-1,"transfers"=>0,"proxy"=>"","tcp_listening"=>true,
        "metrics"=>false,"bw"=>null,"now"=>time());
    $v=trim((string)$s->GET_INFO("APP_DEBIAN_NETWORK_AGENT_VERSION"));
    if(strlen($v)>3){$a["version"]=$v;}
    if(!$a["installed"]){return $a;}

    $health=json_decode((string)$s->REST_API("/debianagent/health"));
    if(is_object($health) && isset($health->Health->version) && strlen((string)$health->Health->version)>3){
        $a["version"]=(string)$health->Health->version;
    }
    $json=json_decode((string)$s->REST_API("/debianagent/status"));
    if(!is_object($json) || !isset($json->Info)){
        $a["state"]="error"; $a["error"]="{adrest_agent_unreadable}";
    }else{
        $ini=new Bs_IniHandler();
        $ini->loadString((string)$json->Info);
        $p=isset($ini->_params["APP_DEBIAN_NETWORK_AGENT"])?$ini->_params["APP_DEBIAN_NETWORK_AGENT"]:null;
        if(!is_array($p)){
            $a["state"]="error"; $a["error"]="{adrest_agent_unreadable}";
        }else{
            $a["state"]=intval($p["running"] ?? 0)==1?"running":"stopped";
            $a["memory"]=intval($p["master_memory"] ?? 0);
        }
    }
    $params=json_decode((string)$s->REST_API("/debianagent/params"));
    if(is_object($params) && isset($params->Params)){
        $addr=(string)($params->Params->listen_addr ?? "");
        $port=intval($params->Params->listen_port ?? 0);
        if($addr!="" || $port>0){$a["listen"]=$port>0?"$addr:$port":$addr;}
    }
    if($a["state"]!="running"){ $a["mode"]="offline"; return $a; }

    $state=json_decode((string)$s->REST_API("/debianagent/meta/state"),true);
    if(is_array($state) && ($state["success"] ?? false)===true){
        nac_model_meta($a,$state);
    }
    if($a["mode"]=="reverse"){
        $bw=json_decode((string)$s->REST_API("/debianagent/meta/bandwidth"),true);
        if(is_array($bw) && ($bw["success"] ?? false)===true && is_array($bw["points"] ?? null)){
            $a["bw"]=nac_model_bandwidth($bw);
        }
        return $a;
    }
    // Mode direct : le jeton d'enrolement (lecture seule, la creation est un bouton).
    $data=json_decode((string)$s->REST_API("/debianagent/tokens"));
    if(is_object($data) && isset($data->Info) && is_object($data->Info)){
        $a["token_ok"]=true;
        $list=(isset($data->Info->tokens) && is_array($data->Info->tokens))?$data->Info->tokens:array();
        if(count($list)>0){
            $a["token"]=adrest_token_param($list[0]->token ?? "");
            $a["expires"]=nac_ts($list[0]->expires_at ?? "");
        }
    }
    return $a;
}

/** Etat Meta : identite publique, etat fin du lien et metriques du controleur. */
function nac_model_meta(array &$a,array $state){
    $a["cert_state"]=(string)($state["certificate_state"] ?? "");
    $id=is_array($state["identity"] ?? null)?$state["identity"]:array();
    $a["cert_expires"]=nac_ts($id["expires_at"] ?? "");
    $a["uuid"]=preg_replace('/[^A-Za-z0-9-]/','',(string)($id["agent_uuid"] ?? ""));
    $a["fingerprint"]=preg_replace('/[^A-Fa-f0-9:]/','',(string)($id["fingerprint"] ?? ""));
    $err=(string)($state["last_error"] ?? "");
    // Seul un jeton {da_meta_*} du demon est affiche, jamais un texte brut :
    // sinon le bandeau garde sa propre phrase.
    if($err!="" && preg_match('/^\{da_meta_[a-z_]{1,48}\}$/',$err)){ $a["last_error"]=$err; }
    if(($state["mode"] ?? "")!=="reverse"){ return; }
    $a["mode"]="reverse";
    $port=intval($state["port"] ?? 0);
    $addr=(string)($state["address"] ?? "");
    $a["endpoint"]=$port>0?"$addr:$port":$addr;
    $a["restart_pending"]=!empty($state["restart_pending"]);
    $live=is_array($state["connection"] ?? null)?$state["connection"]:array();
    $a["live"]=(string)($live["state"] ?? "");
    $a["proxy"]=(string)($live["proxy"] ?? "");
    $a["tcp_listening"]=!empty($live["tcp_listening"]);
    // Un articarest d'avant le 2026-11-20 ne relaie aucune metrique : on le sait
    // a l'absence de active_data_connections, et les tuiles ne s'affichent pas.
    if(array_key_exists("active_data_connections",$live)){
        $a["metrics"]=true;
        $a["sessions"]=max(0,intval($live["active_data_connections"]));
        $a["transfers"]=max(0,intval($live["active_bulk_transfers"] ?? 0));
        $a["connected_since"]=nac_ts($live["last_connected"] ?? "");
        $a["last_pong"]=nac_ts($live["last_pong"] ?? "");
        $a["next_attempt"]=nac_ts($live["next_attempt"] ?? "");
    }
}

/** 144 tranches de 10 min : envoye a Meta (tx) et recu de Meta (rx). */
function nac_model_bandwidth(array $bw):array{
    $tx=array(); $rx=array();
    foreach($bw["points"] as $p){
        if(!is_array($p)){continue;}
        $tx[]=floatval($p["tx_bytes"] ?? 0);
        $rx[]=floatval($p["rx_bytes"] ?? 0);
    }
    $period=is_array($bw["period"] ?? null)?$bw["period"]:array();
    return array("tx"=>$tx,"rx"=>$rx,"sent"=>floatval($period["tx_bytes"] ?? array_sum($tx)),
        "received"=>floatval($period["rx_bytes"] ?? array_sum($rx)));
}

function debianagent_status():bool{
    $tpl=new template_admin();
    $a=nac_model();
    echo nac_style().$tpl->_ENGINE_parse_body(nac_card($a)).adrest_agent_timer_js();
    return true;
}

// ---- NAC widgets begin ----

// Les accolades sont encodees : une valeur venue du demon ne doit jamais etre lue comme un {jeton}.
function nac_h($v):string{ return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES)); }

/** Traduit le gabarit AVANT d'y mettre les valeurs (sinon %s casse le jeton). */
function nac_t(string $k,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$k."}");
    if(count($args)>0){ $s=vsprintf($s,$args); }
    return $s;
}

/** Duree lisible : "3 d 4 h", "12 min", "45 s". */
function nac_dur(int $sec):string{
    if($sec<0){$sec=0;}
    if($sec<60){ return $sec." ".nac_t("nac_u_s"); }
    if($sec<3600){ return intval(floor($sec/60))." ".nac_t("nac_u_min"); }
    if($sec<86400){
        $h=intval(floor($sec/3600)); $m=intval(floor(($sec%3600)/60));
        return $h." ".nac_t("nac_u_h").($m>0?" ".$m." ".nac_t("nac_u_min"):"");
    }
    $d=intval(floor($sec/86400)); $h=intval(floor(($sec%86400)/3600));
    return $d." ".nac_t("nac_u_d").($h>0?" ".$h." ".nac_t("nac_u_h"):"");
}

function nac_bytes(float $b):string{
    if($b<1024){ return intval($b)." B"; }
    return FormatBytes($b/1024,true);
}

/** Age du dernier signe de vie de Meta : battement, a defaut la connexion. -1 = inconnu. */
function nac_pong_age(array $a):int{
    $ref=$a["last_pong"]>0?$a["last_pong"]:$a["connected_since"];
    return $ref>0?max(0,$a["now"]-$ref):-1;
}

/** L'etat que montre le bandeau, deduit une seule fois du modele. */
function nac_hero_state(array $a):string{
    if(!$a["installed"]){ return "install"; }
    if($a["state"]=="error"){ return "error"; }
    if($a["state"]!="running"){ return "stopped"; }
    if($a["mode"]!="reverse"){ return "direct"; }
    if($a["restart_pending"]){ return "starting"; }
    $live=$a["live"];
    if(in_array($live,array("auth_failed","needs_enrollment","certificate_expired")) || $a["cert_state"]=="certificate_expired"){ return "enroll"; }
    if($live=="connected"){
        // Battement toutes les 25 s, l'agent coupe apres 15 s de silence de plus.
        return nac_pong_age($a)>40?"silent":"connected";
    }
    if($live=="connecting"){ return "starting"; }
    return "lost";
}

/** Bouton de la carte. $kind : primary, default, quiet. */
function nac_btn(string $js,string $ico,string $label,string $kind="default",string $id=""):string{
    $idAttr=$id!=""?" id='$id'":"";
    return "<button type='button'$idAttr class='nac-btn nac-btn-$kind' OnClick=\"$js\"><i class='$ico' aria-hidden='true'></i>&nbsp;$label</button>";
}

function nac_js_install():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/debianagent/install","debian-agent.progress","debian-agent.progress.log","progress-webapi-restart",adrest_status_refresh_js());
}
function nac_js_start():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/debianagent/start","debian-agent.start","debian-agent.start.log","progress-webapi-restart",adrest_status_refresh_js());
}
function nac_js_restart():string{
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/debianagent/restart","debian-agent.progress","debian-agent.progress.log","progress-webapi-restart",adrest_status_refresh_js());
}
function nac_js_params():string{ $page=CurrentPageName(); return "Loadjs('$page?debian-agent-params-js=yes');"; }
function nac_js_account():string{ $page=CurrentPageName(); return "Loadjs('$page?nac-account-js=yes');"; }

/** La carte entiere, zone par zone. */
function nac_card(array $a):string{
    $hs=nac_hero_state($a);
    $h=array();
    $h[]="<section class='ak-card nac' id='adr-agent' aria-labelledby='nac-title'>";
    $h[]=nac_header($a);
    $h[]=nac_value_strip($a);
    $h[]=nac_hero($a,$hs);
    if($hs=="connected" || $hs=="silent"){ $h[]=nac_tiles($a); }
    if($a["mode"]=="reverse"){ $h[]=nac_activity($a); }
    if($a["installed"]){ $h[]=nac_details($a,$hs); }
    $h[]=nac_footer($a,$hs);
    $h[]="</section>";
    return @implode("\n",$h);
}

/** 1. En-tete : titre, version, etat du processus (discret : ce n'est pas l'etat du lien). */
function nac_header(array $a):string{
    $ver=$a["version"]!=""?"<span class='nac-ver'>v".nac_h($a["version"])."</span>":"";
    if(!$a["installed"]){
        $proc="{adrest_not_installed}";
    }elseif($a["state"]=="running"){
        $proc=nac_t("nac_process_running",array(nac_bytes($a["memory"]*1024)));
    }elseif($a["state"]=="stopped"){
        $proc="{nac_process_stopped}";
    }else{
        $proc="{nac_process_unknown}";
    }
    $h=array();
    $h[]="<div class='nac-head'><span class='nac-tile' aria-hidden='true'><i class='fas fa-network-wired'></i></span>";
    $h[]="<h3 class='nac-title' id='nac-title'>{APP_DEBIAN_NETWORK_AGENT}</h3>$ver";
    $h[]="<span class='nac-proc'><i class='fas fa-microchip' aria-hidden='true'></i>&nbsp;$proc</span></div>";
    $h[]="<p class='nac-intro'>{nac_intro}</p>";
    return @implode("",$h);
}

/** 2. Accroche + puces de reassurance, chacune affichee seulement si elle est vraie. */
function nac_value_strip(array $a):string{
    $reverse=$a["mode"]=="reverse";
    $chips=array();
    if($reverse && !$a["tcp_listening"]){ $chips[]=array("fas fa-right-from-bracket","{nac_chip_outbound}"); }
    if($reverse && $a["cert_state"]=="enrolled"){ $chips[]=array("fas fa-lock","{nac_chip_encrypted}"); }
    if($reverse && $a["proxy"]!=""){ $chips[]=array("fas fa-route","{nac_chip_proxy}"); }
    $h=array();
    $h[]="<div class='nac-strip'><div class='nac-strip-title'>{nac_strip_title}</div><div class='nac-strip-text'>{nac_strip_text}</div>";
    if(count($chips)>0){
        $h[]="<div class='nac-chips'>";
        foreach($chips as $c){ $h[]="<span class='nac-chip'><i class='{$c[0]}' aria-hidden='true'></i>&nbsp;{$c[1]}</span>"; }
        $h[]="</div>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

/** 3. Le bandeau d'etat : icone + titre + phrase + faits + l'action qui regle le probleme. */
function nac_hero(array $a,string $hs):string{
    $facts=array(); $btn="";
    switch($hs){
        case "connected":
        case "silent":
            if($a["endpoint"]!=""){ $facts[]="<span class='nac-mono'>".nac_h($a["endpoint"])."</span>"; }
            if($a["connected_since"]>0){ $facts[]=nac_t("nac_fact_connected_for",array(nac_dur($a["now"]-$a["connected_since"]))); }
            $age=nac_pong_age($a);
            if($a["last_pong"]>0){ $facts[]=nac_t("nac_fact_heartbeat",array(nac_dur($age))); }
            $cls=$hs=="connected"?"ok":"warn"; $ico=$hs=="connected"?"fas fa-circle-check":"fas fa-heart-pulse";
            $title=$hs=="connected"?"{nac_hero_connected}":"{nac_hero_silent}";
            $text=$hs=="connected"?"{nac_hero_connected_text}":nac_t("nac_hero_silent_text",array(nac_dur(max(0,$age))));
            break;
        case "starting":
            $cls="info"; $ico="fas fa-rotate nac-spin"; $title="{nac_hero_starting}"; $text="{nac_hero_starting_text}";
            if($a["endpoint"]!=""){ $facts[]="<span class='nac-mono'>".nac_h($a["endpoint"])."</span>"; }
            break;
        case "lost":
            $cls="err"; $ico="fas fa-plug-circle-xmark"; $title="{nac_hero_lost}";
            $text=$a["last_error"]!=""?$a["last_error"]:"{nac_hero_lost_text}";
            if($a["endpoint"]!=""){ $facts[]="<span class='nac-mono'>".nac_h($a["endpoint"])."</span>"; }
            if($a["next_attempt"]>0){ $facts[]=nac_t("nac_fact_next_attempt",array(nac_dur($a["next_attempt"]-$a["now"]))); }
            if($a["proxy"]!=""){ $facts[]=nac_t("nac_fact_via",array("<span class='nac-mono'>".nac_h($a["proxy"])."</span>")); }
            $btn=nac_btn(nac_js_params(),"fas fa-gear","{parameters}","primary");
            break;
        case "enroll":
            $cls="err"; $ico="fas fa-id-card"; $title="{nac_hero_enroll}";
            $text=$a["last_error"]!=""?$a["last_error"]:"{nac_hero_enroll_text}";
            if($a["cert_expires"]>0 && $a["cert_expires"]<$a["now"]){ $facts[]=nac_t("nac_fact_cert_expired",array(date("d M Y",$a["cert_expires"]))); }
            $btn=nac_btn(nac_js_account(),"fas fa-user-plus","{da_meta_account_title}","primary");
            break;
        case "direct":
            $cls="neutral"; $ico="fas fa-key"; $title="{nac_hero_direct}"; $text="{nac_hero_direct_text}";
            if($a["listen"]!=""){ $facts[]=nac_t("nac_fact_listen",array("<span class='nac-mono'>".nac_h($a["listen"])."</span>")); }
            break;
        case "stopped":
            $cls="err"; $ico="fas fa-circle-stop"; $title="{nac_hero_stopped}"; $text="{nac_hero_stopped_text}";
            $btn=nac_btn(nac_js_start(),"fas fa-play","{start}","primary");
            break;
        case "error":
            $cls="err"; $ico="fas fa-triangle-exclamation"; $title="{nac_hero_error}"; $text=$a["error"];
            $btn=nac_btn(nac_js_restart(),"fas fa-rotate","{restart}","primary");
            break;
        default:
            $cls="neutral"; $ico="fas fa-download"; $title="{nac_hero_install}"; $text="{nac_hero_install_text}";
            $btn=nac_btn(nac_js_install(),"fas fa-download","{adrest_install}","primary");
    }
    $h=array();
    $h[]="<div class='nac-hero nac-hero-$cls' role='status' aria-live='polite' data-state='$hs'>";
    $h[]="<span class='nac-hero-ico' aria-hidden='true'><i class='$ico'></i></span>";
    $h[]="<div class='nac-hero-body'><div class='nac-hero-title'>$title</div><div class='nac-hero-text'>$text</div>";
    if(count($facts)>0){ $h[]="<div class='nac-hero-facts'>".implode(" &middot; ",$facts)."</div>"; }
    $h[]="</div>";
    if($btn!=""){ $h[]="<div class='nac-hero-act'>$btn</div>"; }
    $h[]="</div>";
    return @implode("",$h);
}

/** Une tuile de mesure : libelle, valeur, aide ; "&mdash;" quand la valeur est inconnue. */
function nac_tile(string $label,string $value,string $help,string $cls="",string $title=""):string{
    $t=$title!=""?" title='".nac_h($title)."'":"";
    return "<div class='nac-kpi$cls'><div class='nac-kpi-l'>$label</div><div class='nac-kpi-v'$t>$value</div><div class='nac-kpi-h'>$help</div></div>";
}

/** 4. Les quatre tuiles du lien etabli (seulement si articarest relaie les metriques). */
function nac_tiles(array $a):string{
    if(!$a["metrics"]){ return "<div class='nac-note'><i class='fas fa-circle-info' aria-hidden='true'></i>&nbsp;{nac_metrics_unavailable}</div>"; }
    return "<div class='nac-kpis'>".nac_tile_connected($a).nac_tile_heartbeat($a).nac_tile_sessions($a).nac_tile_route($a)."</div>";
}
function nac_tile_connected(array $a):string{
    if($a["connected_since"]<=0){ return nac_tile("{nac_kpi_connected}","&mdash;","{nac_kpi_unknown}"); }
    return nac_tile("{nac_kpi_connected}",nac_dur($a["now"]-$a["connected_since"]),
        nac_t("nac_kpi_since",array(date("d M, H:i",$a["connected_since"]))),"",date("Y-m-d H:i:s",$a["connected_since"]));
}
function nac_tile_heartbeat(array $a):string{
    $age=nac_pong_age($a);
    if($a["last_pong"]<=0){ return nac_tile("{nac_kpi_heartbeat}","&mdash;","{nac_kpi_heartbeat_wait}"); }
    $late=$age>40;
    return nac_tile("{nac_kpi_heartbeat}",nac_t("nac_ago",array(nac_dur($age))),$late?"{nac_kpi_heartbeat_late}":"{nac_kpi_heartbeat_help}",
        $late?" nac-kpi-warn":"",date("Y-m-d H:i:s",$a["last_pong"]));
}
function nac_tile_sessions(array $a):string{
    if($a["sessions"]==0){ return nac_tile("{nac_kpi_sessions}","0","{nac_kpi_sessions_none}"); }
    $help=$a["transfers"]>0?nac_t($a["transfers"]>1?"nac_kpi_transfers":"nac_kpi_transfer",array($a["transfers"])):"{nac_kpi_sessions_help}";
    return nac_tile("{nac_kpi_sessions}",strval($a["sessions"]),$help);
}
function nac_tile_route(array $a):string{
    if($a["proxy"]!=""){ return nac_tile("{nac_kpi_route}","{nac_route_proxy}","<span class='nac-mono'>".nac_h($a["proxy"])."</span>"); }
    return nac_tile("{nac_kpi_route}","{nac_route_direct}","{nac_route_direct_help}");
}

/** Courbe SVG : deux series contre le maximum reel des deux. */
function nac_sparkline(array $tx,array $rx):string{
    $n=max(count($tx),count($rx));
    if($n<2){ return ""; }
    $max=max(1,max(array_merge($tx,$rx)));
    $line=function(array $s) use($n,$max){
        $pts=array();
        foreach($s as $i=>$v){ $pts[]=round($i*1000/($n-1),1).",".round(46-($v/$max)*42,1); }
        return implode(" ",$pts);
    };
    return "<svg class='nac-spark' viewBox='0 0 1000 48' preserveAspectRatio='none' role='img' aria-hidden='true'>"
        ."<polyline class='nac-spark-rx' points='".$line($rx)."'/>"
        ."<polyline class='nac-spark-tx' points='".$line($tx)."'/></svg>";
}

/** 5. Volume echange avec Meta sur 24 h (tranches de 10 min). */
function nac_activity(array $a):string{
    $bw=$a["bw"];
    $h=array();
    $h[]="<div class='nac-act'><div class='nac-act-head'><div class='nac-sec-title'>{nac_activity_title}</div>";
    if(!is_array($bw)){
        $h[]="</div><div class='nac-note'><i class='fas fa-circle-info' aria-hidden='true'></i>&nbsp;{nac_activity_unavailable}</div></div>";
        return @implode("",$h);
    }
    $h[]="<div class='nac-legend'><span class='nac-lg nac-lg-tx'>{nac_sent_to_meta}</span><span class='nac-lg nac-lg-rx'>{nac_received_from_meta}</span></div></div>";
    if($bw["sent"]+$bw["received"]<=0){
        $h[]="<div class='nac-act-empty'>{nac_activity_empty}</div></div>";
        return @implode("",$h);
    }
    $h[]=nac_sparkline($bw["tx"],$bw["rx"]);
    $h[]="<div class='nac-axis'><span>{nac_axis_24h}</span><span>{nac_axis_now}</span></div>";
    $h[]="<div class='nac-totals'><i class='fas fa-arrow-up' aria-hidden='true'></i>&nbsp;".nac_t("nac_total_sent",array(nac_bytes($bw["sent"])))
        ." &middot; <i class='fas fa-arrow-down' aria-hidden='true'></i>&nbsp;".nac_t("nac_total_received",array(nac_bytes($bw["received"])))."</div>";
    $h[]="</div>";
    return @implode("",$h);
}

/** 6. Details : identite a gauche ; selon le mode, connexion ou jeton a droite. */
function nac_details(array $a,string $hs):string{
    $left=$a["uuid"]!="" || $a["cert_expires"]>0?nac_identity($a,$hs):nac_listen($a);
    if($a["state"]!="running"){ $right=adrest_agent_offline(); }
    elseif($a["mode"]=="reverse"){ $right=nac_connection($a); }
    else{ $right=adrest_agent_token($a); }
    return "<div class='nac-details'><div class='nac-col'>$left</div><div class='nac-col'>$right</div></div>";
}

function nac_identity(array $a,string $hs):string{
    $h=array();
    $h[]="<div class='nac-sec-title'>{nac_identity}</div>";
    if($a["cert_expires"]>0){
        if($a["cert_expires"]<$a["now"]){
            $h[]="<div class='nac-line nac-t-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>&nbsp;".nac_t("nac_cert_expired",array(date("d M Y",$a["cert_expires"])))."</div>";
        }else{
            $auto=($hs=="connected" || $hs=="silent")?" &middot; {nac_cert_renewed}":"";
            $h[]="<div class='nac-line'><i class='fas fa-certificate nac-t-ok' aria-hidden='true'></i>&nbsp;".nac_t("nac_cert_valid",array(date("d M Y",$a["cert_expires"])))."$auto</div>";
        }
    }
    if($a["uuid"]!=""){
        $u=nac_h($a["uuid"]);
        $h[]="<div class='nac-id'><span class='nac-id-l'>{nac_agent_id}</span><span class='nac-mono nac-trunc' id='nac-uuid' title='$u'>$u</span>"
            ."<button type='button' class='nac-ibtn' aria-label=\"{nac_copy_agent_id}\" title=\"{nac_copy_agent_id}\" OnClick=\"NacCopy('nac-uuid','nac-uuid-ok');\"><i class='far fa-copy' aria-hidden='true'></i></button>"
            ."<span class='nac-copied' id='nac-uuid-ok' aria-live='polite'></span></div>";
    }
    if($a["fingerprint"]!=""){
        $h[]="<details class='nac-fp'><summary>{nac_show_fingerprint}</summary><div class='nac-mono nac-fp-v'>".nac_h($a["fingerprint"])."</div></details>";
    }
    return @implode("",$h);
}

/** Mode direct sans identite Meta : l'adresse ou Meta joint l'agent. */
function nac_listen(array $a):string{
    return "<div class='nac-sec-title'>{adrest_listen_address}</div><div class='nac-line'><span class='nac-mono nac-box'>"
        .($a["listen"]!=""?nac_h($a["listen"]):"&mdash;")."</span></div><div class='nac-help'>{nac_listen_help}</div>";
}

/** Mode inverse : vers ou part le lien, et par ou. */
function nac_connection(array $a):string{
    $rows=array();
    $rows[]=array("{nac_meta_server}",$a["endpoint"]!=""?"<span class='nac-mono'>".nac_h($a["endpoint"])."</span>":"&mdash;");
    $rows[]=array("{nac_outgoing_proxy}",$a["proxy"]!=""?"<span class='nac-mono'>".nac_h($a["proxy"])."</span>":"{nac_route_direct}");
    $rows[]=array("{nac_agent_port}",$a["tcp_listening"]?"<span class='nac-mono'>".nac_h($a["listen"])."</span>":"{nac_agent_port_none}");
    $h=array();
    $h[]="<div class='nac-sec-title'>{nac_connection_details}</div>";
    foreach($rows as $r){ $h[]="<div class='nac-row'><span>{$r[0]}</span><span>{$r[1]}</span></div>"; }
    return @implode("",$h);
}

/** 7. Pied : desinstaller en lien discret a gauche, le cycle de vie a droite. */
function nac_footer(array $a,string $hs):string{
    if(!$a["installed"]){ return ""; }
    $page=CurrentPageName();
    $b=array();
    if($a["state"]=="running"){
        $b[]=nac_btn(nac_js_restart(),"fas fa-rotate","{restart}");
        if($hs!="lost"){ $b[]=nac_btn(nac_js_params(),"fas fa-gear","{parameters}"); }
        if($hs!="enroll"){ $b[]=nac_btn(nac_js_account(),"fas fa-user-plus","{da_meta_account_title}",$hs=="direct"?"primary":"default"); }
    }
    $h=array();
    $h[]="<div class='nac-foot'><button type='button' class='nac-uninstall' title=\"{nac_uninstall_help}\" OnClick=\"Loadjs('$page?da-uninstall-js=yes');\"><i class='fas fa-trash' aria-hidden='true'></i>&nbsp;{adrest_uninstall}</button>";
    $h[]="<div class='nac-foot-act'>".implode("",$b)."</div></div>";
    return @implode("",$h);
}

/** Duree jusqu'a l'expiration du jeton, lisible. */
function adrest_left(int $ts):string{
    $left=$ts-time();
    if($left<=0){return "{adrest_token_expired}";}
    if($left<86400){return max(1,intval(ceil($left/3600)))." {adrest_hours_left}";}
    return intval(floor($left/86400))." {adrest_days_left}";
}

/** Mode direct : masque, afficher, copier, revoquer ; ou le creer. */
function adrest_agent_token(array $a):string{
    $page=CurrentPageName();
    $h=array();
    $h[]="<div class='nac-sec-title'>{nac_token_title}<span class='nac-sub-term'>{adrest_enrollment_token}</span></div>";
    if(!$a["token_ok"]){
        $h[]="<div class='nac-line nac-t-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>&nbsp;{adrest_agent_unreadable}</div>";
        return @implode("\n",$h);
    }
    if($a["token"]==""){
        $refresh=adrest_status_refresh_js();
        $js="$.post('$page',{'debian-agent-create-token':'yes'},function(r){r=String(r).trim().replace(/^jserror:/,'');if(r.length>0){alert(r);}$refresh});";
        $h[]="<div class='nac-empty'><span>{adrest_no_token}</span>".nac_btn($js,"fas fa-plus","{adrest_create_token}","primary")."</div>";
        return @implode("\n",$h);
    }
    $t=$a["token"];
    $masked=substr($t,0,3)."&bull;&bull;&bull;&bull;&bull;".substr($t,-3);
    $exp=$a["expires"]>0?date("d M Y H:i",$a["expires"])." &middot; ".adrest_left($a["expires"]):"-";
    $expCls=($a["expires"]>0 && $a["expires"]-time()<3600)?" nac-t-warn":"";
    $h[]="<div class='nac-token'><span class='nac-mono nac-box' id='adr-token' data-token='".nac_h($t)."' data-masked='$masked' data-shown='0'>$masked</span>";
    $h[]="<button type='button' class='nac-btn nac-btn-default' id='adr-token-show' OnClick=\"AdrToggleToken();\">{adrest_show}</button>";
    $h[]="<button type='button' class='nac-ibtn' aria-label=\"{adrest_copy_token}\" title=\"{adrest_copy_token}\" OnClick=\"AdrCopyToken();\"><i class='far fa-copy' aria-hidden='true'></i></button>";
    $h[]="<button type='button' class='nac-btn nac-btn-danger' title=\"{nac_revoke_help}\" OnClick=\"Loadjs('$page?revoke-token-js=yes&token=".urlencode($t)."');\">{adrest_revoke}</button></div>";
    $h[]="<div class='nac-help$expCls'>{expires}: $exp <span class='nac-copied' id='adr-copied' aria-live='polite'></span></div>";
    return @implode("\n",$h);
}

/** Agent arrete : ni jeton ni tunnel a lire tant qu'il ne tourne pas. */
function adrest_agent_offline():string{
    return "<div class='nac-sec-title'>{nac_connection_details}</div>"
        ."<div class='nac-help'><i class='fas fa-circle-info' aria-hidden='true'></i>&nbsp;{adrest_agent_offline}</div>";
}

/** Feuille de la carte, concatenee hors de _ENGINE_parse_body(). */
function nac_style():string{
    $dark="#0d9488"; $soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
        if(!empty($ac["accent_dark"])){$dark=$ac["accent_dark"];}
        if(!empty($ac["accent_soft"])){$soft=$ac["accent_soft"];}
    }
    return "<style>
.nac{padding:24px;display:flex;flex-direction:column;gap:20px;color:#111827;font-size:14px}
.nac *{box-sizing:border-box}
.nac-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.nac-tile{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;flex:0 0 40px;border-radius:8px;background:$soft;color:$dark;font-size:18px}
.nac-title{font-size:17px;font-weight:600;margin:0;color:#111827}
.nac-ver{background:#f3f4f6;color:#4b5563;border-radius:4px;padding:2px 8px;font-family:monospace;font-size:13px}
.nac-proc{margin-left:auto;color:#6b7280;font-size:13px;display:inline-flex;align-items:center}
.nac-intro{margin:-8px 0 0;color:#4b5563;font-size:14px}
.nac-mono{font-family:monospace}
.nac-strip{background:#f9fafb;border:1px solid #f3f4f6;border-radius:8px;padding:16px}
.nac-strip-title{font-size:15px;font-weight:600;color:#111827}
.nac-strip-text{font-size:13px;color:#4b5563;margin-top:2px;line-height:1.5}
.nac-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.nac-chip{display:inline-flex;align-items:center;background:#fff;border:1px solid #e5e7eb;border-radius:999px;padding:4px 12px;font-size:13px;color:#374151}
.nac-chip i,.nac-chip svg{color:#059669}
.nac-hero{display:flex;align-items:center;gap:20px;min-height:112px;border-radius:10px;border:1px solid;padding:20px 24px;transition:background-color .15s,border-color .15s}
.nac-hero-ico{display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;flex:0 0 56px;border-radius:50%;font-size:28px}
.nac-hero-body{flex:1 1 auto;min-width:0}
.nac-hero-title{font-size:20px;font-weight:600;line-height:1.25}
.nac-hero-text{font-size:14px;margin-top:4px;line-height:1.5}
.nac-hero-facts{font-size:13px;margin-top:8px;opacity:.95;overflow-wrap:anywhere}
.nac-hero-act{flex:0 0 auto}
.nac-hero-ok{background:#ecfdf5;border-color:#a7f3d0;color:#065f46}.nac-hero-ok .nac-hero-ico{background:#d1fae5;color:#059669}
.nac-hero-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}.nac-hero-warn .nac-hero-ico{background:#fef3c7;color:#d97706}
.nac-hero-info{background:#eff6ff;border-color:#bfdbfe;color:#1e40af}.nac-hero-info .nac-hero-ico{background:#dbeafe;color:#2563eb}
.nac-hero-err{background:#fef2f2;border-color:#fecaca;color:#991b1b}.nac-hero-err .nac-hero-ico{background:#fee2e2;color:#dc2626}
.nac-hero-neutral{background:#f9fafb;border-color:#e5e7eb;color:#374151}.nac-hero-neutral .nac-hero-ico{background:#f3f4f6;color:#4b5563}
.nac-spin{animation:nacspin 2s linear infinite}
@keyframes nacspin{to{transform:rotate(360deg)}}
@media (prefers-reduced-motion:reduce){.nac-spin{animation:none}.nac-hero{transition:none}}
.nac-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px}
.nac-kpi{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;min-width:0}
.nac-kpi-l{font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em}
.nac-kpi-v{font-size:22px;font-weight:600;color:#111827;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nac-kpi-h{font-size:13px;color:#6b7280;margin-top:2px;overflow-wrap:anywhere}
.nac-kpi-warn .nac-kpi-v{color:#b45309}
.nac-act{border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px}
.nac-act-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px}
.nac-sec-title{font-size:15px;font-weight:600;color:#111827;margin-bottom:10px}
.nac-act-head .nac-sec-title{margin-bottom:0}
.nac-legend{display:flex;gap:16px;font-size:13px;color:#4b5563}
.nac-lg:before{content:'';display:inline-block;width:14px;height:3px;border-radius:2px;margin-right:6px;vertical-align:middle}
.nac-lg-tx:before{background:$dark}.nac-lg-rx:before{background:#94a3b8}
.nac-spark{display:block;width:100%;height:48px}
.nac-spark polyline{fill:none;stroke-width:2;vector-effect:non-scaling-stroke;stroke-linejoin:round}
.nac-spark-tx{stroke:$dark}.nac-spark-rx{stroke:#94a3b8}
.nac-axis{display:flex;justify-content:space-between;font-size:13px;color:#9ca3af;margin-top:2px}
.nac-totals{font-family:monospace;font-size:13px;color:#374151;margin-top:8px}
.nac-act-empty{font-size:13px;color:#6b7280;padding:12px 0}
.nac-note{font-size:13px;color:#4b5563;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px}
.nac-details{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:32px}
.nac-col{min-width:0}
.nac-line{font-size:13px;color:#374151;margin-bottom:10px;line-height:1.5}
.nac-help{font-size:13px;color:#6b7280;margin-top:6px}
.nac-sub-term{font-size:13px;font-weight:400;color:#6b7280;margin-left:8px}
.nac-box{display:inline-block;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;color:#111827}
.nac-id{display:flex;align-items:center;gap:10px;border:1px solid #e5e7eb;border-radius:8px;padding:8px 12px;min-width:0}
.nac-id-l{font-size:13px;color:#4b5563;flex:0 0 auto}
.nac-trunc{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px}
.nac-fp{margin-top:10px;font-size:13px}
.nac-fp summary{color:$dark;cursor:pointer;font-weight:500}
.nac-fp summary:focus-visible{outline:2px solid $dark;outline-offset:2px}
.nac-fp-v{margin-top:6px;font-size:13px;color:#374151;overflow-wrap:anywhere}
.nac-row{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:13px;color:#4b5563}
.nac-row:last-child{border-bottom:0}
.nac-row span:last-child{color:#111827;text-align:right;overflow-wrap:anywhere;min-width:0}
.nac-token{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.nac-empty{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;color:#4b5563;font-size:13px}
.nac-t-ok{color:#059669}.nac-t-warn{color:#b45309}.nac-t-err{color:#b91c1c}
.nac-copied{color:#15803d;font-weight:600;font-size:13px}
.nac-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;border-top:1px solid #e5e7eb;padding-top:16px}
.nac-foot-act{display:flex;gap:8px;flex-wrap:wrap}
.nac-btn{display:inline-flex;align-items:center;height:36px;padding:0 14px;border-radius:6px;font-size:14px;font-weight:500;cursor:pointer;border:1px solid #d1d5db;background:#fff;color:#374151;white-space:nowrap}
.nac-btn:hover{border-color:$dark;color:$dark}
.nac-btn-primary{background:$dark;border-color:$dark;color:#fff}
.nac-btn-primary:hover{color:#fff;filter:brightness(.95)}
.nac-btn-danger{color:#b91c1c;border-color:#fecaca}
.nac-btn:focus-visible,.nac-ibtn:focus-visible,.nac-uninstall:focus-visible{outline:2px solid $dark;outline-offset:2px}
.nac-ibtn{width:34px;height:34px;flex:0 0 34px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#4b5563;cursor:pointer}
.nac-ibtn:hover{color:$dark;border-color:$dark}
.nac-uninstall{background:none;border:0;color:#b91c1c;font-size:14px;font-weight:500;cursor:pointer;padding:6px 4px}
.nac-uninstall:hover{text-decoration:underline}
</style>";
}

// ---- NAC widgets end ----

// ?nac-account-js=yes : ouvre directement le compte d'enrolement Meta.
function nac_account_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo $tpl->js_dialog2("{da_meta_account_title}","$page?debian-agent-account=yes",950);
    return true;
}

/** Recharge la carte toutes les 15 s, sauf modale ouverte ou jeton affiche. */
function adrest_agent_timer_js():string{
    $page=CurrentPageName();
    return "<script>
if(window.AdrestAgentTimer){ clearInterval(window.AdrestAgentTimer); }
window.AdrestAgentTimer=setInterval(function(){
  if(!document.getElementById('adr-agent')){ clearInterval(window.AdrestAgentTimer); window.AdrestAgentTimer=null; return; }
  if(jQuery('.modal.in,.modal.show,.sweet-alert.visible,.swal2-container').length){ return; }
  var t=document.getElementById('adr-token'); if(t && t.getAttribute('data-shown')=='1'){ return; }
  var f=document.querySelector('#adr-agent details[open]'); if(f){ return; }
  LoadAjaxSilent('debianagent-status','$page?debianagent-status=yes');
},15000);
</script>";
}

/** Afficher / copier le jeton (repli execCommand pour les vieux navigateurs). */
function adrest_js():string{
    $tpl=new template_admin();
    $show=str_replace("'","\\'",strip_tags($tpl->_ENGINE_parse_body("{adrest_show}")));
    $hide=str_replace("'","\\'",strip_tags($tpl->_ENGINE_parse_body("{adrest_hide}")));
    $copied=str_replace("'","\\'",strip_tags($tpl->_ENGINE_parse_body("{adrest_copied}")));
    return "<script>
function AdrToggleToken(){ var e=document.getElementById('adr-token'),b=document.getElementById('adr-token-show'); if(!e){return;}
  var on=e.getAttribute('data-shown')!='1'; e.setAttribute('data-shown',on?'1':'0');
  e.innerHTML=on?e.getAttribute('data-token'):e.getAttribute('data-masked'); if(b){ b.innerHTML=on?'$hide':'$show'; } }
function NacCopy(id,okId){ var e=document.getElementById(id); if(!e){return;} var t=e.textContent;
  var done=function(){ var c=document.getElementById(okId); if(c){ c.innerHTML='$copied'; setTimeout(function(){c.innerHTML='';},2000);} };
  var legacy=function(){ var a=document.createElement('textarea'); a.value=t; a.style.position='fixed'; a.style.opacity='0'; document.body.appendChild(a); a.select();
    try{ document.execCommand('copy'); done(); }catch(x){} document.body.removeChild(a); };
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(t).then(done,legacy); return; } legacy(); }
function AdrCopyToken(){ var e=document.getElementById('adr-token'); if(!e){return;} var t=e.getAttribute('data-token');
  var done=function(){ var c=document.getElementById('adr-copied'); if(c){ c.innerHTML='$copied'; setTimeout(function(){c.innerHTML='';},2000);} };
  var legacy=function(){ var a=document.createElement('textarea'); a.value=t; a.style.position='fixed'; a.style.opacity='0'; document.body.appendChild(a); a.select();
    try{ document.execCommand('copy'); done(); }catch(x){} document.body.removeChild(a); };
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(t).then(done,legacy); return; } legacy(); }
</script>";
}

// ?da-uninstall-js=yes : la desinstallation de l'agent demande une confirmation.
function adrest_uninstall_js():bool{
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    $js=$tpl->framework_buildjs("/debianagent/uninstall","debian-agent.progress","debian-agent.progress.log","progress-webapi-restart",adrest_status_refresh_js());
    return $tpl->js_dialog_confirm_action("{adrest_confirm_uninstall}","da-uninstall-confirm","none",$js);
}
// POST da-uninstall-confirm : la page est deja reservee aux administrateurs systeme.
function adrest_uninstall_confirm():bool{
    return admin_tracks("Confirmed the uninstallation of the Network Agent");
}

function adrest_style():string{
    $dark="#0d9488"; $soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.adr{display:grid;grid-template-columns:335px minmax(0,1fr);gap:20px;align-items:start;margin-top:15px;color:#111827;font-size:14px}
.adr *{box-sizing:border-box}
@media (max-width:1200px){.adr{grid-template-columns:minmax(0,1fr)}}
.adr-side,.adr-main{min-width:0}
.adr-main{display:flex;flex-direction:column;gap:16px}
.adr-pair{display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:16px;align-items:stretch}
.adr-card{padding:20px}
.adr-head{display:flex;align-items:center;gap:12px;padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid #e5e7eb}
.adr-tile{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;flex:0 0 40px;border-radius:8px;background:$soft;color:$dark;font-size:18px}
.adr-title{font-size:15px;font-weight:600;display:flex;align-items:center;gap:10px}
.adr-push{margin-left:auto}
.adr-lbl{font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.03em;margin-bottom:6px}
.adr-sub{font-size:13px;color:#4b5563;margin-top:6px}
.adr-box{display:inline-block;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;color:#111827}
.adr-trunc{max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle}
.adr-line{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.adr-expo{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px}
.adr-alert{font-size:13px;border-radius:6px;padding:8px 12px;margin-top:10px;line-height:1.5}
.adr-alert-err{color:#b91c1c;background:#fef2f2;border:1px solid #fecaca}
.adr-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6;min-width:0}
.adr-row:last-child{border-bottom:0}
.adr-row-l{flex:0 0 auto;white-space:nowrap;font-weight:500}
.adr-row-v{flex:1 1 auto;min-width:0;display:flex;justify-content:flex-end;overflow:hidden}
.adr-row-a{flex:0 0 auto}
</style>";
}
// Fin de l'onglet Status


function dbrecover_sqlite_dir():string{ return "/home/artica/SQLITE"; }

// Opens the "Recover databases" modal from the TINY header button.
function dbrecover_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{recover_databases}","$page?dbrecover-popup=yes",640);
}

// Modal body: pick one of the daemon's SQLite databases and launch its recovery.
function dbrecover_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    // Real on-disk list so the operator repairs the exact corrupted file.
    $dbs=glob(dbrecover_sqlite_dir()."/*.db");
    if(!is_array($dbs)){$dbs=array();}
    sort($dbs);
    $hash=array();
    foreach($dbs as $p){$b=basename($p);$hash[$b]=$b;}

    $form=array();
    $form[]=$tpl->field_array_hash_simple($hash,"dbrecover-target","{database}",null);

    $html=array();
    $html[]=$tpl->div_explain("{recover_databases}||{recover_databases_explain}");
    $html[]="<div style='width:98%'>".@implode("\n",$form)."</div>";
    $html[]="<div style='margin-top:8px'>".$tpl->button_autnonome("{recover}","RecoverDBnow()","fas fa-database")."</div>";
    $html[]="<div id='dbrecover-progress' style='margin-top:14px'></div>";

    // Select by name (the helper's element id is an internal md5) then run the async recovery.
    $js="function RecoverDBnow(){var db=$('select[name=dbrecover-target]').val();".
        "if(!db||db==''){return;}".
        "Loadjs('$page?dbrecover-run=yes&db='+encodeURIComponent(db));}";
    $html[]="<script>$js</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

// Fires POST /dbpool/recover (async on the daemon) and animates the progress
// bar in #dbrecover-progress by polling the returned progress file. Empty CMD
// in framework_buildjs = poll-only (the recovery is already launched here).
function dbrecover_run():bool{
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();

    $db=basename(trim($_GET["db"]));                 // basename() strips any path traversal
    if($db==""||substr($db,-3)!=".db"){
        echo "$('#dbrecover-progress').html(\"<div class='error'>".$tpl->javascript_parse_text("{error}")."</div>\");";
        return false;
    }
    $path=dbrecover_sqlite_dir()."/".$db;

    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/dbpool/recover",array("path"=>$path));
    $json=json_decode((string)$resp,true);

    $progress="";
    if(is_array($json)&&!empty($json["progress_file"])){$progress=$json["progress_file"];}
    if($progress==""){
        $err="{failed}";
        if(is_array($json)&&!empty($json["Error"])){$err=$json["Error"];}
        echo "$('#dbrecover-progress').html(\"<div class='error'>".$tpl->javascript_parse_text($err)."</div>\");";
        return false;
    }

    admin_tracks("Recover SQLite database $db");
    echo $tpl->framework_buildjs("",$progress,"","dbrecover-progress");
    return true;
}

function Tiny():bool{
    $tpl                            = new template_admin();
    $ARTICAREST_VERSION="";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/ping"),true);
    if($json["Status"]){
        $ARTICAREST_VERSION=$json["Version"];
    }

    if($ARTICAREST_VERSION=="") {
        $ARTICAREST_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");
    }





    $TINY_ARRAY["TITLE"]="{SQUID_AD_RESTFULL} v<span id='active-directory-rest-version'>$ARTICAREST_VERSION</span>";
    $TINY_ARRAY["ICO"]="fad fa-monitor-heart-rate";
    $TINY_ARRAY["EXPL"]="{SQUID_AD_RESTFULL_EXPLAIN}";
    $TINY_ARRAY["URL"]="ad-webapi";
    $jsrestart=restart_array();
    $topbuttons[] = array($jsrestart, ico_refresh, "{restart_service}");
    $topbuttons[] = array("Loadjs('".CurrentPageName()."?dbrecover-js=yes')", ico_configure, "{recover_databases}");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    header("content-type: application/x-javascript");
    echo $jstiny;
    return true;
}




function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));

    if(isset($_POST["InComingInterfaces"])){
        $array=explode(",",$_POST["InComingInterfaces"]);
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(@implode("\n", $array), "PowerDNSListenAddr");
        unset($_POST["InComingInterfaces"]);
    }

    $tpl->SAVE_POSTs();



    if($_POST["EnableUnBoundSNMPD"]<>$EnableUnBoundSNMPD){$GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");}





}
// Meta enrollment is local administration: never expose secrets to delegated roles.
function debian_agent_certificate_admin():bool{
    $users=new usersMenus();
    if($users->AsSystemAdministrator){return true;}
    http_response_code(403);echo (new template_admin())->post_error("{ERROR_NO_PRIVS}");return false;
}
function debian_agent_certificate_public_error($value):string{
    $allowed=array("{da_meta_busy}","{da_meta_invalid}","{da_meta_failed}","{da_meta_tls_untrusted}","{da_meta_tls_hostname}","{da_meta_tls_expired}","{da_meta_tls_pin}","{da_meta_network_failed}","{da_meta_agent_unavailable}","{da_meta_enrollment_rejected}","{da_meta_status_unavailable}","{da_meta_account_invalid}","{da_meta_account_rejected}","{da_meta_rate_limited}","{da_meta_proxy_auth_failed}","{da_meta_proxy_failed}","{da_meta_account_address_invalid}","{da_meta_account_port_invalid}");
    return is_string($value)&&in_array($value,$allowed,true)?$value:"{da_meta_failed}";
}
function debian_agent_certificate_api():array{
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/meta/state");
    $state=json_decode($raw,true);
    if(!is_array($state) || ($state["success"] ?? false)!==true){throw new RuntimeException(!empty($state["last_error"])?debian_agent_certificate_public_error($state["last_error"]):"{da_meta_status_unavailable}");}
    return $state;
}
function debian_agent_certificate_style():string{
    return <<<'CSS'
<style>
#da-meta-onboarding{--meta-accent:#059669;--meta-line:#dce5ed;color:#334155;font-size:14px;max-width:1080px;margin:0 auto}
#da-meta-onboarding .meta-heading{display:flex;align-items:center;gap:14px;background:linear-gradient(110deg,#059669,#0d9488,#047857);color:#fff;border-radius:12px 12px 0 0;padding:20px 24px}
#da-meta-onboarding .meta-heading h3{color:inherit;margin:0 0 5px;font-size:20px;font-weight:700}
#da-meta-onboarding .meta-heading p{margin:0;opacity:.9}
#da-meta-onboarding .meta-heading-icon{font-size:26px;background:rgba(255,255,255,.15);border-radius:10px;padding:12px}
#da-meta-onboarding .meta-content{padding:24px;border:1px solid var(--meta-line);border-top:0;border-radius:0 0 12px 12px;background:#fff}
#da-meta-onboarding .meta-progress,#da-meta-onboarding .meta-diagnostics{padding:18px;background:#f8fafc;border:1px solid var(--meta-line);border-radius:12px;margin-bottom:22px}
#da-meta-onboarding .meta-section-title{font-size:12px;text-transform:uppercase;letter-spacing:.07em;font-weight:700;color:#64748b;margin:0 0 16px}
#da-meta-onboarding .meta-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));list-style:none;padding:0;margin:0;text-align:center;gap:8px}
#da-meta-onboarding .meta-step{position:relative;z-index:0;font-size:12px;color:#64748b}
#da-meta-onboarding .meta-step:before{content:'';position:absolute;top:17px;left:-50%;right:50%;height:2px;background:#dce5ed;z-index:-1}
#da-meta-onboarding .meta-step:first-child:before{display:none}
#da-meta-onboarding .meta-step span{display:flex;align-items:center;justify-content:center;width:36px;height:36px;margin:0 auto 10px;border:2px solid #cbd5e1;border-radius:50%;background:#fff;font-weight:700}
#da-meta-onboarding .meta-step strong{display:block;font-weight:600}
#da-meta-onboarding .meta-step.is-current{color:#047857}
#da-meta-onboarding .meta-step.is-current span{border-color:#059669;box-shadow:0 0 0 4px #d1fae5}
#da-meta-onboarding .meta-step.is-done span{background:#059669;border-color:#059669;color:#fff}
#da-meta-onboarding .meta-step.is-done:before{background:#059669}
#da-meta-onboarding .meta-callout{display:flex;gap:14px;border:1px solid #d7edf1;border-left:4px solid #14b8a6;background:#f1fafb;border-radius:10px;padding:18px;margin-bottom:24px;line-height:1.7}
#da-meta-onboarding .meta-callout h4{margin:0 0 6px;color:#1e293b;font-weight:700}
#da-meta-onboarding .meta-callout-icon{font-size:22px;color:#0d9488}
#da-meta-onboarding .meta-upload-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-top:20px;border-top:1px solid var(--meta-line);margin:8px 0 14px}
#da-meta-onboarding .meta-upload-heading strong{display:block;margin-bottom:5px}
#da-meta-onboarding .meta-muted{font-size:12px;color:#64748b}
#da-meta-onboarding .meta-dropzone{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:20px;background:#f8fafc;border:2px dashed #cbd5e1;border-radius:10px;margin-bottom:22px;outline-offset:4px;cursor:pointer}
#da-meta-onboarding .meta-dropzone.is-over{border-color:#059669;background:#ecfdf5}
#da-meta-onboarding .meta-file-detail{display:flex;align-items:center;gap:14px;min-width:0}
#da-meta-onboarding .meta-file-icon{font-size:24px;padding:10px;color:#0d9488;background:#e2f4ef;border-radius:8px}
#da-meta-onboarding #da-meta-filename{overflow-wrap:anywhere}
#da-meta-onboarding .meta-badge{display:inline-block;font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;background:#fef3c7;color:#92400e}
#da-meta-onboarding .meta-badge.is-good{background:#d1fae5;color:#047857}
#da-meta-onboarding .meta-diagnostic-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid var(--meta-line);padding-bottom:12px;margin-bottom:14px}
#da-meta-onboarding .meta-diagnostic-heading .meta-section-title{margin:0}
#da-meta-onboarding .meta-diagnostic-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
#da-meta-onboarding .meta-diagnostic-card{background:#fff;border:1px solid var(--meta-line);border-radius:9px;padding:14px;overflow-wrap:anywhere}
#da-meta-onboarding .meta-diagnostic-card small{display:block;font-size:10px;letter-spacing:.04em;text-transform:uppercase;color:#64748b;margin-bottom:10px}
#da-meta-onboarding .meta-diagnostic-card strong{font-size:12px}
#da-meta-onboarding .meta-identity{margin-top:15px;font-size:12px;overflow-wrap:anywhere}
#da-meta-onboarding .meta-identity p{margin:5px 0}
#da-meta-onboarding .meta-footer{display:flex;justify-content:space-between;align-items:center;gap:14px;padding-top:18px;border-top:1px solid var(--meta-line)}
#da-meta-onboarding .meta-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap}
#da-meta-onboarding .meta-primary{background:#059669;border-color:#059669;color:#fff}
#da-meta-onboarding .meta-primary:hover{background:#047857;color:#fff}
#da-meta-onboarding button:disabled{opacity:.55;cursor:not-allowed}
#da-meta-onboarding #da-meta-load-error{margin:10px 0;white-space:pre-wrap}
#da-meta-onboarding .meta-method{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding-top:20px;border-top:1px solid var(--meta-line);margin:8px 0 4px}
#da-meta-onboarding .meta-method-btn[aria-pressed=true]{background:#059669;border-color:#059669;color:#fff}
#da-meta-onboarding .meta-account-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 18px;margin-bottom:18px}
#da-meta-onboarding .meta-account-grid label{font-weight:600}
#da-meta-onboarding .meta-account-grid .meta-muted{display:block;margin-top:4px}
@media(max-width:700px){#da-meta-onboarding .meta-account-grid{grid-template-columns:1fr}#da-meta-onboarding .meta-content{padding:16px}#da-meta-onboarding .meta-diagnostic-grid{grid-template-columns:repeat(2,minmax(0,1fr))}#da-meta-onboarding .meta-upload-heading,#da-meta-onboarding .meta-footer,#da-meta-onboarding .meta-dropzone{align-items:flex-start;flex-direction:column}#da-meta-onboarding .meta-heading{padding:16px}#da-meta-onboarding .meta-step{font-size:11px}}
</style>
CSS;
}
function debian_agent_certificate_progress():string{
    $html="<section class='meta-progress' aria-labelledby='da-meta-progress-title'><h4 class='meta-section-title' id='da-meta-progress-title'>{da_meta_progress}</h4><ol class='meta-steps'>";
    $steps=array("{da_meta_enrollment_file}","{da_meta_validation}","{da_meta_tunnel}");
    foreach($steps as $index=>$label){$number=$index+1;$html.="<li class='meta-step' data-step='$number'><span>$number</span><strong>$label</strong></li>";}
    return $html."</ol></section>";
}
function debian_agent_certificate_tab():bool{
    if(!debian_agent_certificate_admin()){return false;}
    header("Cache-Control: no-store");$tpl=new template_admin();$page=CurrentPageName();
    $state=array("address"=>"","port"=>443);$error="";
    try{$state=debian_agent_certificate_api();}catch(Exception $e){$error=$e->getMessage();}
    $escape_attr=function($v){return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));};
    $form=array();
    $form[]=$tpl->field_checkbox("da-meta-self-signed","{da_meta_allow_self_signed}",0,false,"{da_meta_self_signed_note}");$selfSignedID=md5("da-meta-self-signed".$tpl->suffixid);
    $loaded=!empty($state["identity"]);
    if($loaded){$form[]=$tpl->field_checkbox("da-meta-replace","{replace}",0);}
    $replaceID=md5("da-meta-replace".$tpl->suffixid);
    $fields=$tpl->form_outside("",$form,null,"remove-button",null,"AsSystemAdministrator");
    $button=$tpl->button_autnonome("{da_meta_load_file}","document.getElementById('da-meta-file').click()",ico_upload,"AsSystemAdministrator",0,"btn-info meta-primary");
    $html="<div id='da-meta-onboarding' style='margin-top:10px'><header class='meta-heading'><span class='meta-heading-icon' aria-hidden='true'><i class='fas fa-shield-alt'></i></span><div><h3>{certificate}</h3><p>{da_meta_about}</p></div></header><div class='meta-content'>";
    $html.=debian_agent_certificate_progress();
    $html.="<section class='meta-callout' id='da-meta-file-howto'><span class='meta-callout-icon' aria-hidden='true'><i class='fas fa-info-circle'></i></span><div><h4>{da_meta_about}</h4>{da_meta_enrollment_howto}</div></section>";
    if($error!==""){$html.=$tpl->div_error($error);}
    // form_outside already renders its fields through the framework.
    echo debian_agent_certificate_style().$tpl->_ENGINE_parse_body($html)."<div class='meta-fields'>$fields</div>";
    // Deux methodes : le fichier d'enrolement (jeton) ou un compte d'enrolement du groupe Meta.
    $metaAddress=$escape_attr((string)($state["address"] ?? ""));$metaPort=intval($state["port"] ?? 0);if($metaPort<1||$metaPort>65535){$metaPort=443;}
    // Inscription par compte d'enrolement : onglet dedie (debian_agent_account_tab()).
    $html="";
    $html.="<section id='da-meta-file-section' aria-labelledby='da-meta-upload-title'><div class='meta-upload-heading'><div><strong id='da-meta-upload-title'><i class='fas fa-file-shield' aria-hidden='true'></i> {da_meta_enrollment_file}</strong><span class='meta-muted'>{da_meta_json_format}</span></div><div id='da-meta-load-button'>$button</div></div>";
    $html.="<input type='file' id='da-meta-file' accept='.json,application/json' style='display:none'><div id='da-meta-dropzone' class='meta-dropzone' tabindex='0' role='button' aria-labelledby='da-meta-upload-title' aria-describedby='da-meta-filename'><div class='meta-file-detail'><span class='meta-file-icon' aria-hidden='true'><i class='fas fa-file-code'></i></span><div><span class='meta-badge' id='da-meta-file-badge'>{da_meta_not_enrolled}</span><div id='da-meta-filename' class='meta-muted'>{da_meta_no_file}</div></div></div><span class='meta-muted'>{da_meta_drop}</span></div></section>";
    $html.="<div id='da-meta-load-error' class='text-danger' role='status' aria-live='polite'></div><div id='da-meta-state' aria-live='polite'></div>";
    $html.="<footer class='meta-footer'><span class='meta-muted'><i class='fas fa-lock' aria-hidden='true'></i> HTTPS · mTLS</span><div class='meta-actions'><button type='button' class='btn btn-default' id='da-meta-cancel'>{cancel}</button><button type='button' class='btn btn-default' id='da-meta-refresh'><i class='fas fa-sync-alt' aria-hidden='true'></i> {refresh}</button><button type='button' class='btn meta-primary' id='da-meta-submit' disabled><i class='fas fa-shield-alt' aria-hidden='true'></i> {da_meta_submit}</button></div></footer></div></div>";
    echo $tpl->_ENGINE_parse_body($html);
    $settings=array("page"=>$page,"selfSigned"=>$selfSignedID,"replace"=>$replaceID,"loaded"=>$loaded,"activeCertificate"=>in_array($state["certificate_state"] ?? "",array("enrolled","renewal_prepared"),true));
    foreach(array("invalid"=>"{da_meta_invalid}","failed"=>"{da_meta_failed}","loading"=>"{loading}","empty"=>"{da_meta_no_file}","selected"=>"{da_meta_selected}","active"=>"{da_meta_certificate_valid}","notEnrolled"=>"{da_meta_not_enrolled}","loadedLabel"=>"{certificate}","expired"=>"{expired}","unknown"=>"{unknown}","fileStep"=>"{da_meta_enrollment_file}","accountStep"=>"{da_meta_account_title}","accountInvalid"=>"{da_meta_account_invalid}") as $name=>$token){$settings[$name]=html_entity_decode(strip_tags(str_replace("[br]"," ",$tpl->_ENGINE_parse_body($token))),ENT_QUOTES,"UTF-8");}
    echo debian_agent_certificate_script($settings);
    return true;
}
function debian_agent_certificate_script(array $settings):string{
    $json=json_encode($settings,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    return '<script>(function(){var c='.$json.';var fileInput=document.getElementById("da-meta-file");
    var root=$("#da-meta-onboarding"),selected=null,busy=false,validated=c.activeCertificate,connected=false,certificateActive=c.activeCertificate;
    var selfSigned=$("#"+c.selfSigned),replace=$("#"+c.replace),submit=$("#da-meta-submit"),zone=$("#da-meta-dropzone"),error=$("#da-meta-load-error");
    var method="file",acAddress=$("#da-meta-account-address"),acPort=$("#da-meta-account-port"),acUser=$("#da-meta-account-username"),acPass=$("#da-meta-account-password");
    function accountReady(){var p=parseInt(acPort.val(),10),u=$.trim(acUser.val()),w=acPass.val()||"";return $.trim(acAddress.val())!==""&&p>=1&&p<=65535&&/^[A-Za-z0-9][A-Za-z0-9._@-]{2,63}$/.test(u)&&w.length>=12&&w.length<=72;}
    function pending(){return method==="file"?!!selected:accountReady();}
    var timer=window.DebianAgentMetaStateTimer;clearTimeout(timer);
    function update(){
        submit.prop("disabled",busy || !pending() ||(c.loaded && !replace.is(":checked")));
        var done=[pending()||c.loaded,!pending()&&validated,!pending()&&connected],current=done.indexOf(false)+1;
        root.find(".meta-step[data-step=1] strong").text(method==="file"?c.fileStep:c.accountStep);
        root.find(".meta-step").each(function(i){$(this).toggleClass("is-done",done[i]).toggleClass("is-current",current===i+1).attr("aria-current",current===i+1?"step":null);});
        $("#da-meta-file-badge").text(selected?c.selected:(c.loaded?(certificateActive?c.active:c.loadedLabel):c.notEnrolled)).toggleClass("is-good",!!selected||certificateActive);
    }
    function refresh(){LoadAjaxSilent("da-meta-state",c.page+"?debian-agent-certificate-state=yes");}
    function choose(file){
        if(busy){return;}if(!file){return;}
        if(file.size<1 ||file.size>16384 ||! /\.json$/i.test(file.name)){selected=null;error.text(c.invalid);fileInput.value="";$("#da-meta-filename").text(c.empty);update();return;}
        selected=file;error.text("");$("#da-meta-filename").text(file.name+" · "+Math.ceil(file.size/1024)+" KB");update();
    }
    root.on("meta-state-unavailable",function(){connected=false;validated=false;certificateActive=false;c.loadedLabel=c.unknown;update();});
    root.off("meta-state").on("meta-state",function(event,state){c.loaded=!!state.identity;certificateActive=state.certificate_state==="enrolled"||state.certificate_state==="renewal_prepared";validated=certificateActive;c.loadedLabel=state.certificate_state==="certificate_expired"?c.expired:c.unknown;var live=state.connection;connected=!!live&&live.mode==="reverse"&&live.state==="connected"&&!live.tcp_listening;update();});
    fileInput.onchange=function(){choose(this.files&&this.files[0]);};
    zone.on("click",function(){if(!busy){fileInput.click();}}).on("keydown",function(e){if(e.key==="Enter"||e.key===" "){e.preventDefault();if(!busy){fileInput.click();}}});
    zone.on("dragover",function(e){e.preventDefault();if(!busy){zone.addClass("is-over");}}).on("dragleave",function(){zone.removeClass("is-over");}).on("drop",function(e){e.preventDefault();zone.removeClass("is-over");var files=e.originalEvent.dataTransfer.files;if(files.length!==1){error.text(c.invalid);return;}choose(files[0]);});
    replace.on("change",update);
    root.find(".meta-method-btn").on("click",function(){if(busy){return;}method=$(this).attr("data-method")==="account"?"account":"file";root.find(".meta-method-btn").each(function(){$(this).attr("aria-pressed",$(this).attr("data-method")===method?"true":"false");});$("#da-meta-account-section").toggle(method==="account");$("#da-meta-file-section").toggle(method==="file");$("#da-meta-file-howto").toggle(method==="file");error.text("");update();});
    acAddress.add(acPort).add(acUser).add(acPass).on("input change",update);
    $("#da-meta-refresh").on("click",refresh);
    $("#da-meta-cancel").on("click",function(){if(busy){return;}selected=null;fileInput.value="";acUser.val("");acPass.val("");error.text("");$("#da-meta-filename").text(c.empty);update();});
    submit.on("click",function(){
        if(busy||(c.loaded&&!replace.is(":checked"))){return;}
        if(method==="account"&&!accountReady()){error.text(c.accountInvalid);return;}
        if(method==="file"&&!selected){return;}
        var form=new FormData();form.append("replace",replace.is(":checked")?"1":"0");form.append("trust_self_signed",selfSigned.is(":checked")?"1":"0");
        if(method==="account"){form.append("debian-agent-certificate-account","1");form.append("address",$.trim(acAddress.val()));form.append("port",String(parseInt(acPort.val(),10)));form.append("username",$.trim(acUser.val()));form.append("password",acPass.val());}
        else{form.append("debian-agent-certificate-load","1");form.append("certificate",selected);}
        busy=true;root.attr("aria-busy","true");clearTimeout(window.DebianAgentMetaStateTimer);root.find("input,button").prop("disabled",true);zone.attr("aria-disabled","true");error.text(c.loading);update();
        $.ajax({url:c.page,type:"POST",data:form,processData:false,contentType:false,dataType:"json",timeout:80000})
        .done(function(result){if(!result||result.success!==true){error.text(result&&result.error?result.error:c.failed);return;}
            selected=null;fileInput.value="";acPass.val("");LoadAjaxSilent("debian-agent-certificate-body",c.page+"?debian-agent-certificate-body=yes");})
        .fail(function(){error.text(c.failed);})
        .always(function(){busy=false;root.attr("aria-busy","false");root.find("input,button").prop("disabled",false);zone.attr("aria-disabled","false");update();if(document.getElementById("da-meta-onboarding")===root[0]){refresh();}});
    });
    update();refresh();})();</script>';
}
// Onglet "Compte d'enrolement" : inscription de l'agent local sur Artica Meta avec le nom et le
// mot de passe d'un compte d'enrolement d'un groupe Meta (POST debian-agent-certificate-account,
// traite par debian_agent_certificate_account()). Identifiants propres (daacct-*) : l'onglet
// Certificat peut etre charge en meme temps dans le DOM.
/**
 * ===== Onglet "Enrollment account" (maquette stitch) =====
 * Un modele unique (daacct_model) lu sur /debianagent/meta/state, un widget = une fonction.
 * La bande d'etat est rechargee seule (debian-agent-account-status) : le formulaire n'est
 * jamais redessine pendant la saisie. Le POST reste debian_agent_certificate_account().
 */
function daacct_parse($t):string{
    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body($t);
}
function daacct_nobr($t):string{
    return str_replace(array("<br>","<br/>","<br />"),array(" "," "," "),$t);
}
function daacct_plain($t):string{
    return html_entity_decode(strip_tags(daacct_nobr(str_replace("[br]"," ",daacct_parse($t)))),ENT_QUOTES,"UTF-8");
}
function daacct_esc($v):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}
function daacct_model():array{
    $m=array("error"=>"","address"=>"","port"=>443,"cert"=>"","identity"=>null,"live"=>null,"restart"=>false,"mode"=>"","last_error"=>"");
    try{$s=debian_agent_certificate_api();}catch(Exception $e){$m["error"]=$e->getMessage();return $m;}
    $m["address"]=(string)($s["address"] ?? "");
    $port=intval($s["port"] ?? 0);if($port<1||$port>65535){$port=443;}$m["port"]=$port;
    $m["cert"]=(string)($s["certificate_state"] ?? "");
    $m["identity"]=is_array($s["identity"] ?? null)?$s["identity"]:null;
    $m["live"]=is_array($s["connection"] ?? null)?$s["connection"]:null;
    $m["restart"]=!empty($s["restart_pending"]);
    $m["mode"]=(string)($s["mode"] ?? "");
    if(!empty($s["last_error"])){$m["last_error"]=debian_agent_certificate_public_error($s["last_error"]);}
    return $m;
}
// unknown | restarting | none | expired | offline | connected
function daacct_kind(array $m):string{
    if($m["error"]!==""){return "unknown";}
    if($m["restart"]){return "restarting";}
    if($m["cert"]==="certificate_expired"){return "expired";}
    if(!in_array($m["cert"],array("enrolled","renewal_prepared"),true)){return "none";}
    $live=$m["live"];
    if(is_array($live) && ($live["state"] ?? "")==="connected" && ($live["mode"] ?? "")==="reverse"){return "connected";}
    return "offline";
}
function daacct_restart_needed(array $m):bool{
    $live=$m["live"];
    return $m["mode"]==="reverse" && (!is_array($live) || ($live["mode"] ?? "")!=="reverse");
}
function daacct_banner():string{
    $h=array();
    $h[]="<section class='daacct-ban'><span class='daacct-ban-ico' aria-hidden='true'><i class='fas fa-tower-broadcast'></i></span><div>";
    $h[]="<h3>{daacct_ban_title}</h3><p>{daacct_ban_text}</p><div class='daacct-chips'>";
    $h[]="<span class='daacct-chip'><i class='fas fa-lock' aria-hidden='true'></i> {daacct_chip_tls}</span>";
    $h[]="<span class='daacct-chip'><i class='fas fa-key' aria-hidden='true'></i> {daacct_chip_pwd}</span>";
    $h[]="<span class='daacct-chip'><i class='fas fa-rotate' aria-hidden='true'></i> {daacct_chip_reconnect}</span>";
    $h[]="</div></div></section>";
    return implode("",$h);
}
function daacct_expiry_line(array $m):string{
    $id=$m["identity"];if(!is_array($id)){return "";}
    $ts=strtotime((string)($id["expires_at"] ?? ""));if($ts===false||$ts<=0){return "";}
    $days=(int)ceil(($ts-time())/86400);
    $rel=$days<=0?daacct_parse("{daacct_today}"):str_replace("%N",(string)$days,daacct_parse("{daacct_in_days}"));
    $line=str_replace(array("%D","%N"),array("<span title='".gmdate("Y-m-d H:i:s",$ts)." UTC'>".gmdate("Y-m-d",$ts)."</span>",$rel),daacct_parse("{daacct_valid_until}"));
    return daacct_nobr($line);
}
function daacct_route_line(array $m):string{
    $live=$m["live"];if(!is_array($live)){return "";}
    if(empty($live["proxy"])){return daacct_nobr(daacct_parse("{daacct_route_direct}"));}
    return daacct_nobr(str_replace("%P","<span class='daacct-mono'>".daacct_esc($live["proxy"])."</span>",daacct_parse("{daacct_route_proxy}")));
}
function daacct_agent_id(array $m):string{
    $id=$m["identity"];if(!is_array($id)){return "";}
    $uuid=(string)($id["agent_uuid"] ?? "");if($uuid===""){return "";}
    $short=strlen($uuid)>13?substr($uuid,0,8)."&hellip;".substr($uuid,-4):daacct_esc($uuid);
    $tip="UUID: ".$uuid."\nSHA-256: ".(string)($id["fingerprint"] ?? "");
    return "<div class='daacct-id' title='".daacct_esc($tip)."' tabindex='0' aria-label='".daacct_esc($tip)."'><i class='fas fa-fingerprint' aria-hidden='true'></i> {daacct_agent_id} <strong class='daacct-mono'>$short</strong></div>";
}
function daacct_status(array $m):string{
    $kind=daacct_kind($m);
    $map=array(
        "unknown"=>array("is-bad","fas fa-circle-question","{daacct_st_unknown}",$m["error"]),
        "restarting"=>array("is-info","fas fa-spinner fa-spin","{daacct_st_restart}","{daacct_st_restart_desc}"),
        "none"=>array("is-idle","fas fa-circle-dashed","{daacct_st_none}","{daacct_st_none_desc}"),
        "expired"=>array("is-bad","fas fa-circle-xmark","{daacct_st_exp}","{daacct_st_exp_desc}"),
        "offline"=>array("is-warn","fas fa-triangle-exclamation","{daacct_st_off}",daacct_restart_needed($m)?"{da_meta_restart_needed}":"{daacct_st_off_desc}"),
        "connected"=>array("is-good","fas fa-circle-check","{daacct_st_ok}",""),
    );
    list($cls,$ico,$title,$desc)=$map[$kind];
    $parts=array();
    if($desc!==""){$parts[]=daacct_nobr(daacct_parse($desc));}
    if(in_array($kind,array("connected","offline"),true)){
        $e=daacct_expiry_line($m);if($e!==""){$parts[]=$e;}
        $r=daacct_route_line($m);if($r!==""){$parts[]=$r;}
    }
    if($kind!=="none" && $kind!=="unknown" && $m["address"]!==""){
        $parts[]=daacct_nobr(str_replace("%M","<span class='daacct-mono'>".daacct_esc($m["address"]).":".$m["port"]."</span>",daacct_parse("{daacct_meta_target}")));
    }
    $h=array();
    $h[]="<div class='daacct-status $cls' data-kind='$kind'><span class='daacct-status-ico' aria-hidden='true'><i class='$ico'></i></span>";
    $h[]="<div class='daacct-status-txt'><strong>$title</strong><span>".implode(" &middot; ",$parts)."</span></div>";
    $h[]=daacct_agent_id($m)."</div>";
    if($m["last_error"]!=="" && $kind!=="connected" && $kind!=="unknown"){
        $h[]="<div class='daacct-lasterr'><i class='fas fa-circle-exclamation' aria-hidden='true'></i> ".daacct_nobr(daacct_parse($m["last_error"]))."</div>";
    }
    return implode("",$h);
}
function daacct_steps():string{
    $h=array();
    $h[]="<ol class='daacct-steps'>";
    foreach(array(1=>"{daacct_step1}",2=>"{daacct_step2}",3=>"{daacct_step3}") as $n=>$label){
        $h[]="<li class='daacct-step' id='daacct-step$n'><span class='daacct-step-n' aria-hidden='true'><b>$n</b><span class='daacct-chk'><i class='fas fa-check'></i></span></span><span>$label</span></li>";
    }
    $h[]="</ol>";
    return implode("",$h);
}
function daacct_card_server(array $m):string{
    $addr=daacct_esc($m["address"]);$port=intval($m["port"]);
    $h=array();
    $h[]="<section class='daacct-card'><header class='daacct-card-h'><i class='fas fa-server' aria-hidden='true'></i><h4>{daacct_server_title}</h4><span class='daacct-muted'>{daacct_server_sub}</span></header><div class='daacct-grid'>";
    $h[]="<div class='daacct-field'><label for='daacct-address'>{da_meta_account_address}</label><div class='daacct-input'><input type='text' class='form-control' id='daacct-address' maxlength='253' autocomplete='off' spellcheck='false' value=\"$addr\" placeholder='meta.example.com' aria-describedby='daacct-address-help'><span class='daacct-in-ico' aria-hidden='true'><i class='fas fa-globe'></i></span></div>";
    $h[]="<span class='daacct-help' id='daacct-address-help'>{da_meta_account_address_explain}</span><span class='daacct-example daacct-mono'>{daacct_addr_example}</span></div>";
    $h[]="<div class='daacct-field'><label for='daacct-port'>{da_meta_account_port}</label><div class='daacct-input'><input type='number' class='form-control' id='daacct-port' min='1' max='65535' value='$port' aria-describedby='daacct-port-help'><span class='daacct-in-ico' aria-hidden='true'><i class='fas fa-hashtag'></i></span></div>";
    $h[]="<span class='daacct-help' id='daacct-port-help'>{da_meta_account_port_explain}</span><span class='daacct-example daacct-mono'>{daacct_port_example}</span></div>";
    $h[]="</div></section>";
    return implode("",$h);
}
function daacct_card_account():string{
    $h=array();
    $h[]="<section class='daacct-card'><header class='daacct-card-h'><i class='fas fa-id-badge' aria-hidden='true'></i><h4>{daacct_account_title}</h4></header><p class='daacct-intro'>{daacct_account_text}</p><div class='daacct-grid'>";
    $h[]="<div class='daacct-field'><label for='daacct-username'>{username}</label><div class='daacct-input'><input type='text' class='form-control' id='daacct-username' maxlength='64' autocomplete='off' spellcheck='false' aria-describedby='daacct-user-help'><span class='daacct-in-ico' aria-hidden='true'><i class='fas fa-user'></i></span></div><span class='daacct-help' id='daacct-user-help'>{daacct_user_help}</span></div>";
    $h[]="<div class='daacct-field'><label for='daacct-password'>{password} <span class='daacct-count daacct-mono' id='daacct-pwcount' aria-live='polite'>0 / 72</span></label><div class='daacct-input'><input type='password' class='form-control' id='daacct-password' maxlength='72' autocomplete='new-password' aria-describedby='daacct-pwd-help'><button type='button' class='daacct-eye' id='daacct-eye' aria-label='{daacct_show_pwd}' aria-pressed='false'><span class='daacct-eye-on' aria-hidden='true'><i class='fas fa-eye'></i></span><span class='daacct-eye-off' aria-hidden='true' hidden><i class='fas fa-eye-slash'></i></span></button></div><span class='daacct-help' id='daacct-pwd-help'>{daacct_pwd_help}</span></div>";
    $h[]="</div></section>";
    return implode("",$h);
}
function daacct_switch($id,$title,$sub,$help,$extra=""):string{
    $subHtml=$sub!==""?"<span class='daacct-opt-sub'>$sub</span>":"";
    return "<div class='daacct-opt' id='$id-row'><div class='daacct-opt-txt'><span class='daacct-opt-title' id='$id-label'>$title</span>$subHtml<span class='daacct-help' id='$id-help'>$help</span>$extra</div>"
        ."<button type='button' class='daacct-switch' id='$id' role='switch' aria-checked='false' aria-labelledby='$id-label' aria-describedby='$id-help'><span class='daacct-knob' aria-hidden='true'></span><span class='daacct-sw-word'>{daacct_off}</span></button></div>";
}
function daacct_options(array $m):string{
    $loaded=is_array($m["identity"]);
    $h=array();
    $h[]="<section class='daacct-card'><header class='daacct-card-h'><i class='fas fa-sliders' aria-hidden='true'></i><h4>{daacct_opts_title}</h4></header>";
    $h[]=daacct_switch("daacct-selfsigned","{da_meta_allow_self_signed}","{daacct_ss_sub}","{daacct_ss_help}");
    $warn="<span class='daacct-opt-warn' id='daacct-replace-warn' hidden><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {daacct_rep_warn}</span>";
    $rep=daacct_switch("daacct-replace","{daacct_rep_title}","","{daacct_rep_help}",$warn);
    if(!$loaded){$rep=str_replace("<div class='daacct-opt' id='daacct-replace-row'>","<div class='daacct-opt' id='daacct-replace-row' hidden>",$rep);}
    $h[]=$rep;
    $h[]="</section>";
    return implode("",$h);
}
function daacct_result():string{
    $h=array();
    $h[]="<div id='daacct-result' aria-live='polite'>";
    $h[]="<div class='daacct-res is-work' id='daacct-res-work' hidden><div class='daacct-bar'><span></span></div><span>{daacct_working}</span></div>";
    $h[]="<div class='daacct-res is-ok' id='daacct-res-ok' hidden><i class='fas fa-circle-check' aria-hidden='true'></i><div><strong>{daacct_ok_title}</strong><span>{daacct_ok_text}</span></div></div>";
    $h[]="<div class='daacct-res is-err' id='daacct-res-err' role='alert' hidden><i class='fas fa-circle-xmark' aria-hidden='true'></i><div><strong>{daacct_err_title}</strong><span id='daacct-res-err-msg'></span></div></div>";
    $h[]="</div>";
    return implode("",$h);
}
function daacct_footer():string{
    $h=array();
    $h[]="<footer class='daacct-foot'><div class='daacct-foot-tls'><span class='daacct-foot-ico' aria-hidden='true'><i class='fas fa-lock'></i></span><div><strong>{daacct_footer_tls}</strong><span>{daacct_footer_tls_sub}</span></div></div>";
    $h[]="<div class='daacct-foot-act'><div class='daacct-foot-row'><span class='daacct-reason' id='daacct-reason'>{daacct_reason}</span><button type='button' class='btn daacct-primary' id='daacct-submit' disabled aria-describedby='daacct-reason daacct-conseq'><i class='fas fa-shield-halved' aria-hidden='true'></i> {da_meta_submit}</button></div>";
    $h[]="<span class='daacct-conseq' id='daacct-conseq'>{daacct_consequence}</span></div></footer>";
    return implode("",$h);
}
function daacct_style():string{
    $accent="#059669";$soft="rgba(5,150,105,.12)";$dark="#047857";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$soft=$a["accent_soft"];$dark=$a["accent_dark"];
    }
    $c=array();
    $c[]="#daacct-root{display:flex;flex-direction:column;gap:16px;margin-top:12px;color:#334155;font-size:14px}";
    $c[]="#daacct-root *{box-sizing:border-box}";
    $c[]=".daacct-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px}";
    $c[]=".daacct-muted{color:#64748b;font-size:13px}";
    $c[]=".daacct-ban{display:flex;gap:16px;padding:20px 24px;border-radius:12px;background:linear-gradient(110deg,$accent,$dark);color:#fff}";
    $c[]=".daacct-ban h3{margin:0 0 6px;font-size:20px;font-weight:700;color:#fff}";
    $c[]=".daacct-ban p{margin:0 0 12px;line-height:1.5;color:#fff;opacity:.95}";
    $c[]=".daacct-ban-ico{flex:0 0 48px;height:48px;border-radius:10px;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;font-size:22px}";
    $c[]=".daacct-chips{display:flex;flex-wrap:wrap;gap:8px}";
    $c[]=".daacct-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;background:rgba(255,255,255,.16);font-size:13px;color:#fff}";
    $c[]=".daacct-status{display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:10px;border:1px solid #dce5ed;background:#f8fafc;border-left:4px solid #94a3b8}";
    $c[]=".daacct-status-ico{flex:0 0 36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;background:#e2e8f0;color:#475569}";
    $c[]=".daacct-status-txt{flex:1;min-width:0;display:flex;flex-direction:column;gap:2px}";
    $c[]=".daacct-status-txt strong{font-size:15px;color:#0f172a}";
    $c[]=".daacct-status-txt span{font-size:13px;color:#475569;line-height:1.5}";
    $c[]=".daacct-status.is-good{border-left-color:$accent}.daacct-status.is-good .daacct-status-ico{background:$soft;color:$dark}";
    $c[]=".daacct-status.is-warn{border-left-color:#d97706;background:#fffbeb}.daacct-status.is-warn .daacct-status-ico{background:#fef3c7;color:#b45309}";
    $c[]=".daacct-status.is-bad{border-left-color:#dc2626;background:#fef2f2}.daacct-status.is-bad .daacct-status-ico{background:#fee2e2;color:#b91c1c}";
    $c[]=".daacct-status.is-info{border-left-color:#2563eb;background:#eff6ff}.daacct-status.is-info .daacct-status-ico{background:#dbeafe;color:#1d4ed8}";
    $c[]=".daacct-id{flex:0 0 auto;font-size:13px;color:#64748b;white-space:nowrap;cursor:help;border-radius:6px;padding:2px 4px}";
    $c[]=".daacct-id:focus{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".daacct-lasterr{margin-top:-6px;font-size:13px;color:#b91c1c;padding:0 4px}";
    $c[]=".daacct-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));list-style:none;margin:0;padding:0;text-align:center}";
    $c[]=".daacct-step{position:relative;display:flex;flex-direction:column;align-items:center;gap:6px;font-size:13px;color:#64748b}";
    $c[]=".daacct-step:before{content:'';position:absolute;top:15px;left:-50%;right:50%;height:2px;background:#dce5ed;z-index:0}";
    $c[]=".daacct-step:first-child:before{display:none}";
    $c[]=".daacct-step-n{position:relative;z-index:1;width:32px;height:32px;border-radius:50%;border:2px solid #cbd5e1;background:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;color:#64748b}";
    $c[]=".daacct-step-n .daacct-chk{display:none}";
    $c[]=".daacct-step.is-current{color:#0f172a;font-weight:600}.daacct-step.is-current .daacct-step-n{border-color:$accent;color:$dark}";
    $c[]=".daacct-step.is-done{color:$dark}.daacct-step.is-done .daacct-step-n{background:$accent;border-color:$accent;color:#fff}";
    $c[]=".daacct-step.is-done .daacct-step-n b{display:none}.daacct-step.is-done .daacct-step-n .daacct-chk{display:inline-flex}.daacct-step.is-done:before{background:$accent}";
    $c[]=".daacct-card{background:#fff;border:1px solid #dce5ed;border-radius:12px;padding:18px 20px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".daacct-card-h{display:flex;align-items:center;gap:10px;margin:0 0 14px;color:$dark}";
    $c[]=".daacct-card-h h4{margin:0;font-size:15px;font-weight:700;color:#0f172a}";
    $c[]=".daacct-card-h .daacct-muted{margin-left:auto}";
    $c[]=".daacct-intro{margin:-6px 0 14px;color:#475569;font-size:13px;line-height:1.5}";
    $c[]=".daacct-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 20px}";
    $c[]="@media (max-width:820px){.daacct-grid{grid-template-columns:1fr}}";
    $c[]=".daacct-field{display:flex;flex-direction:column;gap:6px;min-width:0}";
    $c[]=".daacct-field label{display:flex;justify-content:space-between;align-items:baseline;margin:0;font-size:14px;font-weight:600;color:#0f172a}";
    $c[]=".daacct-input{position:relative}";
    $c[]=".daacct-input .form-control{height:40px;padding-right:40px;border-radius:8px;font-size:14px}";
    $c[]=".daacct-input .form-control:focus{border-color:$accent;box-shadow:0 0 0 2px $soft}";
    $c[]=".daacct-in-ico{position:absolute;right:13px;top:11px;color:#94a3b8;pointer-events:none;font-size:14px;line-height:1}";
    $c[]=".daacct-eye{position:absolute;right:4px;top:4px;width:32px;height:32px;border:0;background:transparent;color:#64748b;border-radius:6px}";
    $c[]=".daacct-eye:focus{outline:2px solid $accent}.daacct-eye [hidden]{display:none}";
    $c[]=".daacct-help{font-size:13px;color:#64748b;line-height:1.45}";
    $c[]=".daacct-example{color:#94a3b8;font-size:13px}";
    $c[]=".daacct-count{font-weight:400;color:#94a3b8}.daacct-count.is-ok{color:$dark}";
    $c[]=".daacct-opt{display:flex;align-items:flex-start;gap:20px;padding:14px 0;border-top:1px solid #eef2f6}";
    $c[]=".daacct-card-h+.daacct-opt{border-top:0;padding-top:0}";
    $c[]=".daacct-opt[hidden]{display:none}";
    $c[]=".daacct-opt-txt{flex:1;display:flex;flex-direction:column;gap:3px;min-width:0}";
    $c[]=".daacct-opt-title{font-size:14px;font-weight:600;color:#0f172a}";
    $c[]=".daacct-opt-sub{font-size:13px;color:#94a3b8}";
    $c[]=".daacct-opt-warn{margin-top:6px;padding:8px 10px;border-radius:8px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:13px}";
    $c[]=".daacct-opt-warn[hidden]{display:none}";
    $c[]=".daacct-switch{flex:0 0 auto;display:inline-flex;align-items:center;gap:8px;border:0;background:transparent;padding:2px;cursor:pointer;border-radius:999px}";
    $c[]=".daacct-switch:focus{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".daacct-knob{position:relative;width:40px;height:22px;border-radius:999px;background:#cbd5e1;transition:background .15s}";
    $c[]=".daacct-knob:after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}";
    $c[]=".daacct-switch[aria-checked=true] .daacct-knob{background:$accent}.daacct-switch[aria-checked=true] .daacct-knob:after{left:21px}";
    $c[]=".daacct-sw-word{font-size:13px;color:#64748b;min-width:24px;text-align:left}";
    $c[]=".daacct-res{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:10px;font-size:14px}";
    $c[]=".daacct-res[hidden]{display:none}";
    $c[]=".daacct-res>i{font-size:20px;margin-top:1px}";
    $c[]=".daacct-res div{display:flex;flex-direction:column;gap:2px}";
    $c[]=".daacct-res strong{font-size:15px}";
    $c[]=".daacct-res.is-work{flex-direction:column;gap:8px;background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a}";
    $c[]=".daacct-bar{position:relative;height:4px;width:100%;border-radius:4px;background:#dbeafe;overflow:hidden}";
    $c[]=".daacct-bar span{position:absolute;top:0;left:-40%;width:40%;height:100%;background:#2563eb;animation:daacctbar 1.3s infinite ease-in-out}";
    $c[]="@keyframes daacctbar{0%{left:-40%}100%{left:100%}}";
    $c[]=".daacct-res.is-ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}";
    $c[]=".daacct-res.is-err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}";
    $c[]=".daacct-foot{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;padding:16px 4px 4px;border-top:1px solid #dce5ed}";
    $c[]=".daacct-foot-tls{display:flex;gap:10px;align-items:center;max-width:300px}";
    $c[]=".daacct-foot-ico{flex:0 0 36px;height:36px;border-radius:8px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center}";
    $c[]=".daacct-foot-tls div{display:flex;flex-direction:column;font-size:13px;color:#64748b}";
    $c[]=".daacct-foot-tls strong{color:#0f172a;font-size:13px;letter-spacing:.03em}";
    $c[]=".daacct-foot-act{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex:1;min-width:0}";
    $c[]=".daacct-foot-row{display:flex;align-items:center;gap:14px;justify-content:flex-end}";
    $c[]=".daacct-reason{font-size:13px;color:#64748b;text-align:right;max-width:340px}";
    $c[]=".daacct-reason[hidden]{display:none}";
    $c[]=".daacct-conseq{font-size:13px;color:#64748b;text-align:right}";
    $c[]=".daacct-primary{background:$accent;border-color:$accent;color:#fff;font-weight:600;padding:9px 18px;border-radius:8px;white-space:nowrap}";
    $c[]=".daacct-primary:hover,.daacct-primary:focus{background:$dark;color:#fff}";
    $c[]=".daacct-primary:focus{outline:2px solid $dark;outline-offset:2px}";
    $c[]=".daacct-primary:disabled{opacity:.55;cursor:not-allowed}";
    $c[]="@media (prefers-reduced-motion:reduce){#daacct-root *{transition:none!important;animation:none!important}}";
    return "<style>".implode("\n",$c)."</style>";
}
function daacct_js(string $page):string{
    $msg=array("page"=>$page);
    foreach(array("invalid"=>"{da_meta_account_invalid}","failed"=>"{da_meta_failed}","on"=>"{daacct_on}","off"=>"{daacct_off}","show"=>"{daacct_show_pwd}") as $k=>$t){$msg[$k]=daacct_plain($t);}
    $json=json_encode($msg,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    $js=<<<'JS'
<script>(function(){var c=__DAACCT_CFG__,busy=false,done=false,root=$("#daacct-root");
var a=$("#daacct-address"),p=$("#daacct-port"),u=$("#daacct-username"),w=$("#daacct-password"),btn=$("#daacct-submit");
function sw(id){return $("#"+id).attr("aria-checked")==="true";}
function setSw(el,on){el.attr("aria-checked",on?"true":"false");el.find(".daacct-sw-word").text(on?c.on:c.off);if(el.attr("id")==="daacct-replace"){$("#daacct-replace-warn").prop("hidden",!on);}}
function serverOk(){var n=parseInt(p.val(),10);return $.trim(a.val())!==""&&n>=1&&n<=65535;}
function accountOk(){var pw=w.val()||"";return /^[A-Za-z0-9][A-Za-z0-9._@-]{2,63}$/.test($.trim(u.val()))&&pw.length>=12&&pw.length<=72;}
function step(n,cls){var s=$("#daacct-step"+n);s.removeClass("is-done is-current");if(cls){s.addClass(cls);}}
function show(which,text){$("#daacct-res-work,#daacct-res-ok,#daacct-res-err").prop("hidden",true);if(which){$("#daacct-res-"+which).prop("hidden",false);}if(which==="err"){$("#daacct-res-err-msg").text(text||c.failed);}}
function upd(){var s1=serverOk(),s2=accountOk(),ready=s1&&s2,len=(w.val()||"").length;
 $("#daacct-pwcount").text(len+" / 72").toggleClass("is-ok",len>=12&&len<=72);
 if(done){step(1,"is-done");step(2,"is-done");step(3,"is-done");}else{step(1,s1?"is-done":"is-current");step(2,s2?"is-done":(s1?"is-current":""));step(3,ready?"is-current":"");}
 btn.prop("disabled",busy||!ready);$("#daacct-reason").prop("hidden",ready||busy);
 root.attr("aria-busy",busy?"true":"false");root.find("input,.daacct-switch,.daacct-eye").prop("disabled",busy);}
root.on("input change","input",function(){done=false;if(!busy){show(null);}upd();});
root.on("click",".daacct-switch",function(){if(busy){return;}var el=$(this);setSw(el,el.attr("aria-checked")!=="true");});
$("#daacct-eye").on("click",function(){var on=w.attr("type")==="password";w.attr("type",on?"text":"password");var b=$(this);b.attr("aria-pressed",on?"true":"false");b.find(".daacct-eye-on").prop("hidden",on);b.find(".daacct-eye-off").prop("hidden",!on);});
root.on("daacct-state",function(e,st){if(st&&st.kind==="connected"&&done){$("#daacct-res-ok").prop("hidden",true);}var row=$("#daacct-replace-row");if(st&&st.identity){row.prop("hidden",false);}else{row.prop("hidden",true);setSw($("#daacct-replace"),false);}});
btn.on("click",function(){if(busy){return;}if(!serverOk()||!accountOk()){show("err",c.invalid);return;}busy=true;done=false;show("work");upd();
 var f=new FormData();f.append("debian-agent-certificate-account","1");f.append("address",$.trim(a.val()));f.append("port",$.trim(p.val()));f.append("username",$.trim(u.val()));f.append("password",w.val()||"");
 if(sw("daacct-selfsigned")){f.append("trust_self_signed","1");}if(sw("daacct-replace")&&!$("#daacct-replace-row").prop("hidden")){f.append("replace","1");}
 $.ajax({url:c.page,type:"POST",data:f,processData:false,contentType:false,dataType:"json",timeout:80000})
 .done(function(r){if(!r||r.success!==true){show("err",r&&r.error?r.error:c.failed);return;}w.val("");done=true;show("ok");
   if(document.getElementById("debian-agent-certificate-body")){LoadAjaxSilent("debian-agent-certificate-body",c.page+"?debian-agent-certificate-body=yes");}})
 .fail(function(){show("err",c.failed);})
 .always(function(){busy=false;upd();if(document.getElementById("daacct-status-host")){LoadAjaxSilent("daacct-status-host",c.page+"?debian-agent-account-status=yes");}});});
upd();})();</script>
JS;
    return str_replace("__DAACCT_CFG__",$json,$js);
}
function daacct_poll_js(string $page,array $m):string{
    $state=json_encode(array("identity"=>is_array($m["identity"]),"kind"=>daacct_kind($m)));
    $url=json_encode($page."?debian-agent-account-status=yes",JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    $delay=daacct_kind($m)==="restarting"?4000:10000;
    return "<script>$('#daacct-root').trigger('daacct-state',[$state]);clearTimeout(window.DaAcctTimer);window.DaAcctTimer=setTimeout(function(){var r=document.getElementById('daacct-root');if(!r||!document.getElementById('daacct-status-host')){return;}if(r.getAttribute('aria-busy')==='true'){return;}LoadAjaxSilent('daacct-status-host',$url);},$delay);</script>";
}
// Rafraichissement : seulement la bande d'etat, jamais les champs en cours de saisie.
function debian_agent_account_status():bool{
    if(!debian_agent_certificate_admin()){return false;}
    header("Cache-Control: no-store");
    $m=daacct_model();
    echo daacct_parse(daacct_status($m)).daacct_poll_js(CurrentPageName(),$m);
    return true;
}
function debian_agent_account_tab():bool{
    if(!debian_agent_certificate_admin()){return false;}
    header("Cache-Control: no-store");$page=CurrentPageName();
    $m=daacct_model();
    $h=array();
    $h[]="<div id='daacct-root' aria-busy='false'>";
    $h[]=daacct_banner();
    $h[]="<div id='daacct-status-host'>".daacct_status($m)."</div>";
    $h[]=daacct_steps();
    $h[]=daacct_card_server($m);
    $h[]=daacct_card_account();
    $h[]=daacct_options($m);
    $h[]=daacct_result();
    $h[]=daacct_footer();
    $h[]="</div>";
    echo daacct_style().daacct_parse(implode("",$h)).daacct_js($page).daacct_poll_js($page,$m);
    return true;
}
function debian_agent_certificate():bool{
    echo "<div id='debian-agent-certificate-body'>";debian_agent_certificate_tab();echo "</div>";return true;
}
function debian_agent_certificate_state():bool{
    if(!debian_agent_certificate_admin()){return false;}
    header("Cache-Control: no-store");$tpl=new template_admin();$page=CurrentPageName();
    try{$state=debian_agent_certificate_api();}catch(Exception $e){$messages=array($e->getMessage());if(!empty($_SESSION["debian_agent_meta_error"])){$messages[]=debian_agent_certificate_public_error($_SESSION["debian_agent_meta_error"]);}foreach(array_unique($messages) as $message){echo $tpl->_ENGINE_parse_body($tpl->div_error($message));}echo '<script>$("#da-meta-onboarding").trigger("meta-state-unavailable");</script>'.debian_agent_certificate_poll_script($page);return false;}
    $escape=function($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8");};
    $errors=array();
    if(!empty($_SESSION["debian_agent_meta_error"])){$errors[]=debian_agent_certificate_public_error($_SESSION["debian_agent_meta_error"]);}
    if(!empty($state["last_error"])){$errors[]=debian_agent_certificate_public_error($state["last_error"]);}
    foreach(array_unique($errors) as $message){echo $tpl->_ENGINE_parse_body($tpl->div_error($message));}
    $identity=$state["identity"] ?? null;$live=$state["connection"] ?? null;
    $labels=array("needs_enrollment"=>"{da_meta_not_enrolled}","enrolled"=>"{da_meta_certificate_valid}","certificate_expired"=>"{expired}","renewal_prepared"=>"{da_meta_certificate_valid}");
    $status=$labels[$state["certificate_state"]] ?? "{error}";
    $connected=is_array($live) &&($live["state"] ?? "")==="connected";
    $goodCertificate=in_array($state["certificate_state"],array("enrolled","renewal_prepared"),true);
    $badge=$goodCertificate?" is-good":"";
    $html="<section class='meta-diagnostics' id='da-meta-state-live'><div class='meta-diagnostic-heading'><h4 class='meta-section-title'><i class='fas fa-terminal' aria-hidden='true'></i> {da_meta_diagnostics}</h4><span class='meta-badge$badge'>{certificate}: $status</span></div>";
    if(!empty($state["restart_pending"])){$html.=$tpl->div_warning("{da_meta_restarting}");}
    elseif(($state["mode"] ?? "")==="reverse" &&(!is_array($live)||($live["mode"] ?? "")!=="reverse")){$html.=$tpl->div_warning("{da_meta_restart_needed}");}
    $mode="{unknown}";$listen="{unknown}";$connection="{unknown}";$socket="{unavailable}";
    if(is_array($live)){
        $mode=($live["mode"] ?? "")==="reverse"?"{da_meta_tunnel}":"{na_mtls_direct}";
        $listen=!empty($live["tcp_listening"])?"{enabled}":"{disabled}";
        $connection=$connected?"{connected}":"{disconnected}";
        $socket="{available}";
    }
    // Proxy emprunte par le tunnel (hote:port, jamais les identifiants).
    $proxy=is_array($live)&&!empty($live["proxy"])?$escape($live["proxy"]):"{da_meta_proxy_direct}";
    $html.="<div class='meta-diagnostic-grid'>";
    $cards=array(array("{mode}",$mode),array("{listen_port}",$listen),array("{da_meta_admin_socket}",$socket),array("{status}",$connection),array("{proxy_server}",$proxy));
    foreach($cards as $card){$html.="<div class='meta-diagnostic-card'><small>$card[0]</small><strong>$card[1]</strong></div>";}
    $html.="</div>";
    if(is_array($identity)){
        $expiry=strtotime((string)($identity["expires_at"] ?? ""));$expiry=$expiry===false?"":gmdate("Y-m-d H:i:s",$expiry)." UTC";
        $html.="<div class='meta-identity'><p><strong>UUID:</strong> ".$escape($identity["agent_uuid"] ?? "")."</p><p><strong>{fingerprint} SHA-256:</strong> ".$escape($identity["fingerprint"] ?? "")."</p><p><strong>{expires}:</strong> ".$escape($expiry)."</p></div>";
    }
    $html.="</section>";echo $tpl->_ENGINE_parse_body($html);
    $public=array("identity"=>is_array($identity),"certificate_state"=>(string)$state["certificate_state"],"connection"=>is_array($live)?array("mode"=>$live["mode"] ?? "","state"=>$live["state"] ?? "","tcp_listening"=>!empty($live["tcp_listening"])):null);
    $json=json_encode($public,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    echo '<script>$("#da-meta-onboarding").trigger("meta-state",['.$json.']);</script>'.debian_agent_certificate_poll_script($page);
    return true;
}
function debian_agent_certificate_poll_script(string $page):string{
    $json=json_encode($page,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    return '<script>clearTimeout(window.DebianAgentMetaStateTimer);window.DebianAgentMetaStateTimer=setTimeout(function(){if(document.getElementById("da-meta-onboarding") && $("#da-meta-onboarding").attr("aria-busy")!=="true"){LoadAjaxSilent("da-meta-state",'.$json.'+"?debian-agent-certificate-state=yes");}},10000);</script>';
}
// The enrollment file is the sole source of the destination; posted fields
// must not override the Meta URL validated again by the agent.
function debian_agent_certificate_endpoint(array $bundle):array{
    $raw=$bundle["meta_url"] ?? null;
    if(!is_string($raw) ||strlen($raw)>2048 ||preg_match('/[\x00-\x20\x7f]/',$raw)){throw new RuntimeException("{da_meta_invalid}");}
    $url=parse_url($raw);
    if(!is_array($url) ||($url["scheme"] ?? "")!=="https" ||empty($url["host"]) ||isset($url["user"]) ||isset($url["pass"]) ||isset($url["query"]) ||isset($url["fragment"]) ||!in_array($url["path"] ?? "",array("","/netagent-incoming"),true)){throw new RuntimeException("{da_meta_invalid}");}
    $address=$url["host"];
    if(substr($address,0,1)==="[" &&substr($address,-1)==="]"){$address=substr($address,1,-1);}
    if(filter_var($address,FILTER_VALIDATE_IP)===false &&filter_var($address,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)===false){throw new RuntimeException("{da_meta_invalid}");}
    $port=$url["port"] ?? 443;
    if($port<1 ||$port>65535){throw new RuntimeException("{da_meta_invalid}");}
    return array($address,$port);
}
function debian_agent_certificate_load():bool{
    if(!debian_agent_certificate_admin()){return false;}
    header("Content-Type: application/json");header("Cache-Control: no-store");$tpl=new template_admin();
    unset($_SESSION["debian_agent_meta_error"]);
    try{
        $upload=$_FILES["certificate"] ?? null;
        if(!is_array($upload) || ($upload["error"] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || intval($upload["size"] ?? 0)<1 ||intval($upload["size"])>16384 ||!is_uploaded_file($upload["tmp_name"])){throw new RuntimeException("{da_meta_invalid}");}
        $raw=file_get_contents($upload["tmp_name"],false,null,0,16385);if(!is_string($raw) ||strlen($raw)>16384){throw new RuntimeException("{da_meta_invalid}");}$bundle=json_decode($raw,true);
        if(!is_array($bundle) ||($bundle["format"] ?? "")!=="artica-meta-enrollment" ||($bundle["version"] ?? 0)!==1 ||!isset($bundle["token"]) ||!is_string($bundle["token"]) ||strlen($bundle["token"])<32 ||strlen($bundle["token"])>128){throw new RuntimeException("{da_meta_invalid}");}
        list($address,$port)=debian_agent_certificate_endpoint($bundle);
        $trust=($_POST["trust_self_signed"] ?? "")==="1";
        // "Allow self-signed" widens trust: a bundle without a certificate (or with a
        // CA-issued one) still enrolls through the standard verification done by the agent.
        if($trust &&isset($bundle["server_certificate_pem"]) &&$bundle["server_certificate_pem"]!=="" &&(!is_string($bundle["server_certificate_pem"])||strlen($bundle["server_certificate_pem"])<32||strlen($bundle["server_certificate_pem"])>8192)){throw new RuntimeException("{da_meta_invalid}");}
        $request=array("address"=>$address,"port"=>$port,"bundle"=>$bundle,"replace"=>!empty($_POST["replace"]),"trust_self_signed"=>$trust);
        $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/debianagent/meta/certificate",$request,70);
        $result=json_decode($raw,true);
        if(!is_array($result) ||($result["success"] ?? false)!==true){$message=debian_agent_certificate_public_error($result["error"] ?? "{da_meta_failed}");throw new RuntimeException($message);}
        echo json_encode(array("success"=>true));
    }catch(Exception $e){$publicError=debian_agent_certificate_public_error($e->getMessage());$_SESSION["debian_agent_meta_error"]=$publicError;$message=html_entity_decode(strip_tags(str_replace("[br]"," ",$tpl->_ENGINE_parse_body($publicError))),ENT_QUOTES,"UTF-8");echo json_encode(array("success"=>false,"error"=>$message));}
    return true;
}
// Enrolement par compte : adresse et port saisis, nom + mot de passe d'un compte
// d'enrolement du groupe Meta. Le mot de passe ne transite que vers le demon local :
// jamais en session, jamais journalise, jamais renvoye.
function debian_agent_certificate_account():bool{
    if(!debian_agent_certificate_admin()){return false;}
    header("Content-Type: application/json");header("Cache-Control: no-store");$tpl=new template_admin();
    unset($_SESSION["debian_agent_meta_error"]);
    $password=isset($_POST["password"])&&is_string($_POST["password"])?$_POST["password"]:"";
    unset($_POST["password"]);
    try{
        $address=trim((string)($_POST["address"] ?? ""));
        if(substr($address,0,1)==="[" &&substr($address,-1)==="]"){$address=substr($address,1,-1);}
        if($address===""||strlen($address)>253||preg_match('/[\x00-\x20\x7f\/\\@?#]/',$address)||(filter_var($address,FILTER_VALIDATE_IP)===false &&(filter_var($address,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)===false||strpos($address,":")!==false))){throw new RuntimeException("{da_meta_account_address_invalid}");}
        $port=trim((string)($_POST["port"] ?? ""));
        if(!preg_match('/^[0-9]{1,5}$/D',$port)||intval($port)<1||intval($port)>65535){throw new RuntimeException("{da_meta_account_port_invalid}");}
        $username=trim((string)($_POST["username"] ?? ""));
        if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9._@-]{2,63}$/D',$username)||strlen($password)<12||strlen($password)>72){throw new RuntimeException("{da_meta_account_invalid}");}
        $request=array("address"=>$address,"port"=>intval($port),"account"=>array("username"=>$username,"password"=>$password),"replace"=>!empty($_POST["replace"]),"trust_self_signed"=>($_POST["trust_self_signed"] ?? "")==="1");
        $password="";
        $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/debianagent/meta/certificate",$request,70);
        $request=null;
        $result=json_decode($raw,true);
        if(!is_array($result) ||($result["success"] ?? false)!==true){$message=debian_agent_certificate_public_error($result["error"] ?? "{da_meta_failed}");throw new RuntimeException($message);}
        admin_tracks("Enrolled the local agent on Meta $address:$port with the enrollment account $username");
        echo json_encode(array("success"=>true));
    }catch(Exception $e){$password="";$publicError=debian_agent_certificate_public_error($e->getMessage());$_SESSION["debian_agent_meta_error"]=$publicError;$message=html_entity_decode(strip_tags(str_replace("[br]"," ",$tpl->_ENGINE_parse_body($publicError))),ENT_QUOTES,"UTF-8");echo json_encode(array("success"=>false,"error"=>$message));}
    return true;
}
