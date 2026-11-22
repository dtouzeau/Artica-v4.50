<?php
// Debian agent » "Network Access Restrictions" tab (3rd tab of the agent dialog opened from
// fw.activedirectory.rest.php, js_dialog2 650 px). Maquette stitch_network_access_restrictions_redesign.
//
// The agent (ArticaLightAgent internal/server) refuses with 403 every address that is not in
// security.ip_whitelist, when security.enable_ip_whitelist is on. An entry without "/" is a single
// address; an invalid entry is skipped silently; an EMPTY list falls back to the private networks
// (10/8, 172.16/12, 192.168/16, 127/8, ::1). The local admin socket is never filtered.
// GET /debianagent/whitelists/{csv} rewrites the WHOLE list: an empty csv reaches the agent as [""]
// (strings.Split), a non-empty list with no valid network, which refuses EVERY address. Hence the
// last entry can never be removed from this tab.
//
// Entry points (dispatched before anything else):
//   (no parameter)            -> host + DagrReload()
//   ?list=yes                 -> state strip + list (reloaded after each change)
//   ?add-js=yes / ?add-popup  -> modal (js_dialog6)
//   POST dagr-add             -> add one entry      (NOTHING on success)
//   ?delete-js=<value>        -> confirmation
//   POST dagr-delete          -> remove one entry   (NOTHING on success)
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["list"])){dagr_list();exit;}
if(isset($_GET["add-js"])){dagr_add_js();exit;}
if(isset($_GET["add-popup"])){dagr_add_popup();exit;}
if(isset($_POST["dagr-add"])){dagr_add_save();exit;}
if(isset($_GET["delete-js"])){dagr_delete_js();exit;}
if(isset($_POST["dagr-delete"])){dagr_delete_save();exit;}
page();

// Same right as the agent's Parameters and Certificate tabs (debian_agent_certificate_admin()).
function dagr_can_write():bool{
    $users=new usersMenus();
    return !empty($users->AsSystemAdministrator);
}
function dagr_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}
function dagr_fmt(string $token,string $v):string{
    $tpl=new template_admin();
    return str_replace("%s",$v,$tpl->_ENGINE_parse_body("{".$token."}"));
}

// ---- Model ---------------------------------------------------------------------------------------
// The agent settings, read once. Empty strings (left by an earlier "remove the last one") are dropped.
function dagr_model():array{
    $m=array("ok"=>false,"error"=>"","enabled"=>true,"list"=>array());
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/params");
    $j=json_decode((string)$raw,true);
    if(!is_array($j) || !isset($j["Params"]) || !is_array($j["Params"])){
        $m["error"]=is_array($j)&&!empty($j["Error"])?(string)$j["Error"]:"{dagr_unreadable_generic}";
        return $m;
    }
    $m["ok"]=true;
    $sec=isset($j["Params"]["security"]) && is_array($j["Params"]["security"])?$j["Params"]["security"]:array();
    if(array_key_exists("enable_ip_whitelist",$sec)){ $m["enabled"]=!empty($sec["enable_ip_whitelist"]); }
    foreach((isset($sec["ip_whitelist"]) && is_array($sec["ip_whitelist"]))?$sec["ip_whitelist"]:array() as $v){
        $v=trim((string)$v);
        if($v!==""){ $m["list"][]=$v; }
    }
    return $m;
}
// Parses an entry the way the agent does: an address alone, or address/prefix. null = invalid.
function dagr_parse(string $s){
    $s=trim($s);
    if($s===""){ return null; }
    $ip=$s; $prefix=null;
    if(strpos($s,"/")!==false){
        list($ip,$p)=explode("/",$s,2);
        if(!preg_match('/^\d{1,3}$/',$p)){ return null; }
        $prefix=intval($p);
    }
    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)!==false){
        if($prefix===null){ $prefix=32; }
        if($prefix>32){ return null; }
        return array("family"=>4,"prefix"=>$prefix);
    }
    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)!==false){
        if($prefix===null){ $prefix=128; }
        if($prefix>128){ return null; }
        return array("family"=>6,"prefix"=>$prefix);
    }
    return null;
}
// What an entry covers, as a chip: label HTML + tone ("" neutral, "warn").
function dagr_cover(string $s):array{
    $p=dagr_parse($s);
    if($p===null){ return array("{dagr_invalid_entry}","err"); }
    if($p["family"]==4){
        if($p["prefix"]==32){ return array("{dagr_single}",""); }
        if($p["prefix"]==0){ return array("{dagr_any_ipv4}","warn"); }
        return array(dagr_fmt("dagr_network_n",number_format(pow(2,32-$p["prefix"]),0,"."," ")),"");
    }
    if($p["prefix"]==128){ return array("{dagr_single_v6}",""); }
    if($p["prefix"]==0){ return array("{dagr_any_ipv6}","warn"); }
    return array("{dagr_network_v6}","");
}
// Writes the whole list, then restarts the agent. Returns the error text, "" on success.
function dagr_write(array $list):string{
    $csv=urlencode(implode(",",$list));
    $j=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/whitelists/$csv"),true);
    if(!is_array($j)){ return "{dagr_save_failed}"; }
    if(empty($j["Status"])){ return !empty($j["Error"])?(string)$j["Error"]:"{dagr_save_failed}"; }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/debianagent/restart");
    return "";
}

// ---- Page (tab content) ----------------------------------------------------------------------------
function page(){
    $page=CurrentPageName();
    echo "<div id='dagr-host'></div><script>
window.DagrReload=function(){ if(document.getElementById('dagr-host')){ LoadAjaxSilent('dagr-host','$page?list=yes'); } };
DagrReload();
</script>";
}

// ---- Widgets ---------------------------------------------------------------------------------------
function dagr_strip(array $m):string{
    if(!$m["ok"]){
        return "<div class='ak-banner ak-err dagr-strip'><i class='fas fa-circle-exclamation' aria-hidden='true'></i>"
            ."<div class='ak-banner-m'><strong>{dagr_unreadable}</strong> ".dagr_h($m["error"])."</div></div>";
    }
    if(!$m["enabled"]){
        return "<div class='ak-banner ak-warn dagr-strip'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>"
            ."<div class='ak-banner-m'><strong>{dagr_off}</strong><div>{dagr_off_txt}</div></div></div>";
    }
    if(count($m["list"])==0){
        $chips=array();
        foreach(array("10.0.0.0/8","172.16.0.0/12","192.168.0.0/16","127.0.0.0/8","::1/128") as $n){ $chips[]="<span class='ak-chip'>$n</span>"; }
        return "<div class='ak-banner ak-info dagr-strip'><i class='fas fa-shield' aria-hidden='true'></i>"
            ."<div class='ak-banner-m'><strong>{dagr_on}</strong><div>{dagr_on_default}</div><div class='ak-chips dagr-chips'>".implode("",$chips)."</div></div></div>";
    }
    return "<div class='ak-banner ak-ok dagr-strip'><i class='fas fa-shield-halved' aria-hidden='true'></i>"
        ."<div class='ak-banner-m'><strong>{dagr_on}</strong><div>".(count($m["list"])==1?"{dagr_on_one}":dagr_fmt("dagr_on_n",(string)count($m["list"])))."</div></div></div>";
}
function dagr_toolbar(array $m):string{
    $page=CurrentPageName();
    $add=dagr_can_write()
        ?"<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?add-js=yes')\"><i class='fas fa-plus'></i> {dagr_add}</button>"
        :"";
    $search=count($m["list"])>1
        ?"<label class='ak-search dagr-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><input type='search' id='dagr-q' placeholder=\"{dagr_filter}\" aria-label=\"{dagr_filter}\" autocomplete='off' OnInput=\"DagrFilter(this.value)\"></label>"
        :"<span></span>";
    return "<div class='dagr-tools'>$search$add</div>";
}
function dagr_row(string $v,bool $last):string{
    $page=CurrentPageName();
    $p=dagr_parse($v);
    $single=$p!==null && (($p["family"]==4 && $p["prefix"]==32) || ($p["family"]==6 && $p["prefix"]==128));
    list($label,$tone)=dagr_cover($v);
    $chip="<span class='ak-tag".($tone!==""?" ak-$tone":" ak-off")."'>".($tone==="warn"?"<i class='fas fa-triangle-exclamation' aria-hidden='true'></i> ":"").$label."</span>";
    $h=dagr_h($v);
    $act="";
    if(dagr_can_write()){
        if($last){
            $act="<button type='button' class='ak-act' disabled aria-label=\"{dagr_last_short}\" title=\"{dagr_last_short}\"><i class='fas fa-trash-can' aria-hidden='true'></i></button>";
        }else{
            $act="<button type='button' class='ak-act ak-danger' aria-label=\"{dagr_remove} $h\" title=\"{dagr_remove} $h\" OnClick=\"Loadjs('$page?delete-js=".urlencode($v)."')\"><i class='fas fa-trash-can' aria-hidden='true'></i></button>";
        }
    }
    return "<tr data-s=\"".strtolower($h)."\"><td><div class='dagr-name'><span class='ak-ico ak-ico-sq ak-ico-sm ak-off'><i class='fas ".($single?"fa-desktop":"fa-network-wired")."' aria-hidden='true'></i></span>"
        ."<span class='dagr-v'>$h</span>$chip</div></td><td class='ak-end'><div class='ak-acts'>$act</div></td></tr>";
}
function dagr_table(array $m):string{
    $page=CurrentPageName();
    if(count($m["list"])==0){
        $btn=dagr_can_write()?"<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?add-js=yes')\"><i class='fas fa-plus'></i> {dagr_add}</button>":"";
        return "<div class='ak-card'><div class='ak-empty-c'><i class='fas fa-shield' aria-hidden='true'></i><div>{dagr_empty}</div>$btn</div></div>";
    }
    $last=count($m["list"])==1;
    $rows=array();
    foreach($m["list"] as $v){ $rows[]=dagr_row($v,$last); }
    $h="<div class='ak-card'><table class='ak-tbl dagr-tbl'><thead><tr><th>{dagr_col_entry}</th><th class='ak-end'>{dagr_col_action}</th></tr></thead>"
        ."<tbody id='dagr-rows'>".implode("",$rows)."<tr id='dagr-nomatch' hidden><td colspan='2' class='ak-muted'>{dagr_no_match}</td></tr></tbody></table>"
        ."<div class='dagr-foot'>".(count($m["list"])==1?"{dagr_entries_one}":dagr_fmt("dagr_entries_n",(string)count($m["list"])))."</div></div>";
    if($last && dagr_can_write()){
        $h.="<div class='dagr-last'><i class='fas fa-lock' aria-hidden='true'></i> {dagr_last}</div>";
    }
    return $h;
}

function dagr_list():bool{
    $tpl=new template_admin();
    $m=dagr_model();
    $h=array();
    $h[]="<div class='ak dagr'>";
    $h[]="<p class='dagr-intro'>{dagr_intro}</p>";
    $h[]=dagr_strip($m);
    if($m["ok"]){
        $h[]=dagr_toolbar($m);
        $h[]=dagr_table($m);
    }
    $h[]="</div>";
    echo dagr_style().$tpl->_ENGINE_parse_body(implode("",$h)).dagr_js();
    return true;
}

// ---- Add -----------------------------------------------------------------------------------------
function dagr_add_js():bool{
    $tpl=new template_admin();
    if(!dagr_can_write()){ $tpl->js_error("{ERROR_NO_PRIVS}"); return false; }
    return $tpl->js_dialog6("{dagr_add}",CurrentPageName()."?add-popup=yes",560);
}
function dagr_add_popup():bool{
    $tpl=new template_admin();
    if(!dagr_can_write()){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS}")); return false; }
    $form=array();
    $form[]=$tpl->field_hidden("dagr-add","1");
    $form[]=$tpl->field_text("dagr_value","{dagr_field}","",true,"{dagr_field_help}");
    $form[]="<tr><td colspan='2'><div class='dagr-check' id='dagr-check' aria-live='polite'></div>"
        ."<div class='dagr-restart'><i class='fas fa-rotate' aria-hidden='true'></i> {dagr_restart_note}</div></td></tr>";
    $id=md5("dagr_value".$tpl->suffixid);
    $T=array("bad"=>dagr_plain("{dagr_check_bad}"),"single"=>dagr_plain("{dagr_single}"),"single6"=>dagr_plain("{dagr_single_v6}"),
        "net"=>dagr_plain("{dagr_network_n}"),"net6"=>dagr_plain("{dagr_network_v6}"),"any4"=>dagr_plain("{dagr_any_ipv4}"),"any6"=>dagr_plain("{dagr_any_ipv6}"));
    $html=array();
    $html[]="<p class='dagr-intro'>{dagr_add_intro}</p>";
    $html[]=$tpl->form_outside("",$form,"","{dagr_add_btn}","dialogInstance6.close();if(window.DagrReload){DagrReload();}","AsSystemAdministrator");
    echo dagr_style().$tpl->_ENGINE_parse_body(implode("",$html)).dagr_check_js($id,$T);
    return true;
}
function dagr_plain(string $t):string{
    $tpl=new template_admin();
    // The engine has already turned [br] into <br>: turn it into a space before stripping the tags.
    $x=preg_replace('#<br\s*/?>#i'," ",str_replace("[br]"," ",$tpl->_ENGINE_parse_body($t)));
    return trim(preg_replace('/\s+/'," ",html_entity_decode(strip_tags($x),ENT_QUOTES,"UTF-8")));
}
function dagr_add_save():bool{
    $tpl=new template_admin();
    if(!dagr_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS}"); return false; }
    $v=trim(rawurldecode((string)($_POST["dagr_value"] ?? "")));
    if(dagr_parse($v)===null){ echo $tpl->post_error("{dagr_check_bad}"); return false; }
    $m=dagr_model();
    if(!$m["ok"]){ echo $tpl->post_error($m["error"]); return false; }
    foreach($m["list"] as $e){ if(strcasecmp($e,$v)==0){ echo $tpl->post_error("{dagr_duplicate}"); return false; } }
    $m["list"][]=$v;
    $err=dagr_write($m["list"]);
    if($err!==""){ echo $tpl->post_error($err); return false; }
    return admin_tracks("Debian agent: address or network $v allowed to reach the agent API");
}

// ---- Remove --------------------------------------------------------------------------------------
function dagr_delete_js():bool{
    $tpl=new template_admin();
    if(!dagr_can_write()){ $tpl->js_error("{ERROR_NO_PRIVS}"); return false; }
    $v=trim((string)$_GET["delete-js"]);
    $text=str_replace("%s","<strong>".dagr_h($v)."</strong>",$tpl->_ENGINE_parse_body("{dagr_remove_confirm}"));
    return $tpl->js_confirm_delete($text,"dagr-delete",$v,"if(window.DagrReload){DagrReload();}",true);
}
// js_confirm_delete shows the answer as is (no "jserror:" prefix): escaped text, NOTHING on success.
function dagr_delete_save():bool{
    if(!dagr_can_write()){ echo dagr_h(dagr_plain("{ERROR_NO_PRIVS}")); return false; }
    $v=trim((string)$_POST["dagr-delete"]);
    $m=dagr_model();
    if(!$m["ok"]){ echo dagr_h(dagr_plain($m["error"])); return false; }
    $keep=array();
    foreach($m["list"] as $e){ if($e!==$v){ $keep[]=$e; } }
    // Never write an empty list: the agent would refuse every address (see the header comment).
    if(count($keep)==0){ echo dagr_h(dagr_plain("{dagr_last}")); return false; }
    if(count($keep)==count($m["list"])){ return true; }
    $err=dagr_write($keep);
    if($err!==""){ echo dagr_h(dagr_plain($err)); return false; }
    return admin_tracks("Debian agent: address or network $v no longer allowed to reach the agent API");
}

// ---- Style and scripts ---------------------------------------------------------------------------
function dagr_style():string{
    if(isset($GLOBALS["DAGR_CSS"])){ return ""; }
    $GLOBALS["DAGR_CSS"]=true;
    return "<style>
.dagr{margin-top:10px}
.dagr-intro{font-size:14px;line-height:1.55;color:#3d4947;margin:0 0 12px}
.dagr-strip{margin-bottom:14px}
.dagr-chips{margin-top:6px}
.dagr-tools{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px}
.dagr-search{flex:1 1 auto;max-width:260px}
.dagr-name{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.dagr-v{font-family:monospace;font-size:14px;font-weight:700;color:#111d23;word-break:break-all}
.dagr-tbl td{vertical-align:middle}
.dagr-foot{padding:10px 18px;border-top:1px solid #e2e8f0;font-size:13px;color:#3d4947}
.dagr-last{font-size:13px;color:#865300;margin-top:8px;display:flex;gap:6px;align-items:flex-start}
.dagr-check{font-size:13px;min-height:18px;margin:2px 0 6px}
.dagr-check.ok{color:#047857}.dagr-check.bad{color:#ba1a1a}
.dagr-restart{font-size:13px;color:#6d7a77}
</style>";
}
function dagr_js():string{
    return "<script>
window.DagrFilter=function(q){
    q=String(q||'').toLowerCase(); var b=document.getElementById('dagr-rows'); if(!b){return;}
    var n=0; for(var i=0;i<b.rows.length;i++){ var r=b.rows[i]; if(r.id==='dagr-nomatch'){continue;}
        var on=(q===''||(r.getAttribute('data-s')||'').indexOf(q)>=0); r.style.display=on?'':'none'; if(on){n++;} }
    var e=document.getElementById('dagr-nomatch'); if(e){ e.hidden=(n>0); }
};
</script>";
}
// Live hint while typing (the server re-checks on save). IPv4 counts, IPv6 is recognised loosely.
function dagr_check_js(string $id,array $T):string{
    $j=json_encode($T,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
    return "<script>
(function(){
  var T=$j, f=document.getElementById('$id'), out=document.getElementById('dagr-check'); if(!f||!out){return;}
  function v4(s){ var p=s.split('.'); if(p.length!==4){return false;} for(var i=0;i<4;i++){ if(!/^\\d{1,3}$/.test(p[i])||+p[i]>255){return false;} } return true; }
  function v6(s){ return /^[0-9a-fA-F:]+(\\.[0-9.]+)?$/.test(s) && s.indexOf(':')>=0 && (s.match(/::/g)||[]).length<=1; }
  function check(){
    var s=f.value.trim(), ip=s, pf=null; out.className='dagr-check';
    if(s===''){ out.textContent=''; return; }
    if(s.indexOf('/')>=0){ var a=s.split('/'); ip=a[0]; if(a.length!==2||!/^\\d{1,3}$/.test(a[1])){ ip=''; } else { pf=+a[1]; } }
    var msg='', ok=false;
    if(v4(ip)){ if(pf===null){pf=32;} if(pf<=32){ ok=true; msg=pf===32?T.single:(pf===0?T.any4:T.net.replace('%s',Math.pow(2,32-pf).toLocaleString('fr-FR'))); } }
    else if(v6(ip)){ if(pf===null){pf=128;} if(pf<=128){ ok=true; msg=pf===128?T.single6:(pf===0?T.any6:T.net6); } }
    out.className='dagr-check '+(ok?'ok':'bad'); out.textContent=(ok?'\\u2713 ':'\\u2717 ')+(ok?msg:T.bad);
  }
  f.addEventListener('input',check); f.addEventListener('keyup',check);
})();
</script>";
}
