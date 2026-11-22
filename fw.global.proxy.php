<?php
// fw.global.proxy.php - how the appliance ITSELF reaches the Internet (updates,
// signatures, licenses). Maquette Stitch « appliance internet proxy console »
// (2026-11-21), real data only. One form, one save handler with a whitelist:
// the former save() serialised every $_POST into ArticaProxySettings, so saving
// the downloader options wiped the company proxy.
//   NoInternetAccess                      -> /system/nointernetaccess
//   ArticaProxySettings [PROXY] INI       -> company proxy
//   NoCheckSquid (+ SQUIDEnable)          -> local proxy
//   NoProxyAdditional                     -> exclusions (NO_PROXY, added to the defaults)
//   CurlUserAgent / WgetBindIpAddress     -> read by httpclient.LoadProxySettings()
// Priority applied by articarest httpclient.SaveProxySettings(): company proxy,
// else the local Squid when installed and allowed, else direct.
// Dropped: EnableMsftncsi / DisableMsftncsi / WgetTimeOut — read by no program
// (the msftncsi internet check was decommissioned in articarest dnswatchdog).
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["gpx-save"])){gpx_save();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{internet_access} (Proxy)",
        "fas fa-globe","{internet_access_proxy_text}",
    "$page?table=yes","global-proxy","progress-globalproxy-restart",false,"table-loader-globalproxy-service");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{internet_access}",$html);
		echo $tpl->build_firewall();
		return true;
	}
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ---------------------------------------------------------------- model

function gpx_default_ua():string{return "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0";}
function gpx_can_edit():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}
function gpx_ini():array{
    $ini=new Bs_IniHandler();
    $ini->loadString((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaProxySettings"));
    $p=(isset($ini->_params["PROXY"]) && is_array($ini->_params["PROXY"]))?$ini->_params["PROXY"]:array();
    $g=function($k)use($p){return isset($p[$k])?trim((string)$p[$k]):"";};
    $en=strtolower($g("ArticaProxyServerEnabled"));
    $port=intval($g("ArticaProxyServerPort"));
    return array("enabled"=>($en=="1"||$en=="yes"),"server"=>$g("ArticaProxyServerName"),"port"=>$port>0?$port:3128,
        "user"=>$g("ArticaProxyServerUsername"),"password"=>$g("ArticaProxyServerUserPassword"));
}
function gpx_exclusions():array{
    $raw=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoProxyAdditional"));
    $out=array();
    foreach(explode(",",$raw) as $e){$e=trim($e);if($e!==""){$out[]=$e;}}
    return $out;
}
function gpx_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $m=gpx_ini();
    $m["offline"]=intval($s->GET_INFO("NoInternetAccess"))==1;
    $m["squid"]=intval($s->GET_INFO("SQUIDEnable"))==1;
    $m["use_local"]=$m["squid"] && intval($s->GET_INFO("NoCheckSquid"))==0;
    $m["route"]=$m["enabled"]?"company":($m["use_local"]?"local":"direct");
    $m["exclusions"]=gpx_exclusions();
    $m["ua"]=trim((string)$s->GET_INFO("CurlUserAgent"));
    $m["bind"]=trim((string)$s->GET_INFO("WgetBindIpAddress"));
    $m["edit"]=gpx_can_edit();
    return $m;
}

// ---------------------------------------------------------------- widgets

function gpx_t(string $token,array $vals=array()):string{
    $tpl=new template_admin();
    $txt=html_entity_decode($tpl->_ENGINE_parse_body("{".$token."}"),ENT_QUOTES,"UTF-8");
    $txt=trim(preg_replace('#\s*<br\s*/?>\s*#i',' ',str_replace("[br]","<br>",$txt)));
    foreach($vals as $v){$p=strpos($txt,"%s");if($p===false){break;}$txt=substr_replace($txt,(string)$v,$p,2);}
    return $txt;
}
function gpx_h(string $token,array $vals=array()):string{return htmlspecialchars(gpx_t($token,$vals),ENT_QUOTES,"UTF-8");}
function gpx_e($v):string{return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES,"UTF-8"));}

function gpx_banner(array $m):string{
    $h=array("<section class='gpx-card gpx-banner'><div class='gpx-bico' aria-hidden='true'><i class='fas fa-globe'></i></div><div class='gpx-btxt'>");
    $h[]="<h2>{gpx_title}</h2><p>{gpx_text}</p><div class='gpx-chips'>";
    if($m["offline"]){
        $route="<i class='fas fa-plane' aria-hidden='true'></i>{gpx_chip_offline}";
    }elseif($m["route"]=="company"){
        $route="<i class='fas fa-route' aria-hidden='true'></i>".gpx_h("gpx_chip_company",array($m["server"]!==""?$m["server"].":".$m["port"]:"?"));
    }elseif($m["route"]=="local"){
        $route="<i class='fas fa-route' aria-hidden='true'></i>{gpx_chip_local}";
    }else{
        $route="<i class='fas fa-route' aria-hidden='true'></i>{gpx_chip_direct}";
    }
    $h[]="<span class='gpx-chip'>$route</span>";
    $n=count($m["exclusions"]);
    $h[]="<span class='gpx-chip'><i class='fas fa-list-check' aria-hidden='true'></i>".($n==0?"{gpx_chip_excl0}":gpx_h($n==1?"gpx_chip_excl1":"gpx_chip_excl",array($n)))."</span>";
    if($m["password"]!==""){ $h[]="<span class='gpx-chip'><i class='fas fa-lock' aria-hidden='true'></i>{gpx_chip_pwd}</span>"; }
    $h[]="</div></div></section>";
    return implode("",$h);
}
function gpx_offline_card(array $m):string{
    $on=$m["offline"];
    $dis=$m["edit"]?"":" disabled";
    return "<section class='gpx-card gpx-off".($on?" on":"")."' id='gpx-offcard'><div class='gpx-oico' aria-hidden='true'><i class='fas fa-plane'></i></div>"
        ."<div class='gpx-otxt'><h3 id='gpx-off-l'>{gpx_off_title}</h3><p id='gpx-off-d'>{gpx_off_help}</p>"
        ."<p class='gpx-onote' id='gpx-off-note'".($on?"":" hidden")."><i class='fas fa-circle-info' aria-hidden='true'></i>{gpx_off_note}</p></div>"
        ."<button type='button' class='gpx-sw' role='switch' id='gpx-offline' aria-checked='".($on?"true":"false")."' aria-labelledby='gpx-off-l' aria-describedby='gpx-off-d'$dis OnClick=\"GpxOffline(this);\"><i></i></button></section>";
}
function gpx_route_card(string $key,string $ico,string $title,string $text,string $foot,string $fico,bool $on,bool $avail,string $badge=""):string{
    $dis=$avail?"":" aria-disabled='true' aria-describedby='gpx-rt-$key-why'";
    $why=$avail?"":"<p class='gpx-why' id='gpx-rt-$key-why'>{gpx_rt_local_na}</p>";
    return "<div class='gpx-rt".($on?" on":"").($avail?"":" na")."' role='radio' tabindex='".($on?"0":"-1")."' aria-checked='".($on?"true":"false")."' data-r='$key'$dis>"
        ."<div class='gpx-rth'><span class='gpx-rti' aria-hidden='true'><i class='fas $ico'></i></span><span class='gpx-rck' aria-hidden='true'><i class='fas fa-check'></i></span></div>"
        ."<b>$title$badge</b><p>$text</p>$why<div class='gpx-rtf'><i class='fas $fico' aria-hidden='true'></i>$foot</div></div>";
}
function gpx_routes(array $m):string{
    $h=array("<section class='gpx-card gpx-route' id='gpx-routebox'><div class='gpx-ch'><div><h3>{gpx_rt_title}</h3><p>{gpx_rt_help}</p></div></div>");
    $h[]="<div class='gpx-rts' role='radiogroup' aria-label=\"".gpx_h("gpx_rt_title")."\" id='gpx-rts'>";
    $h[]=gpx_route_card("direct","fa-arrow-right-long","{gpx_rt_direct}","{gpx_rt_direct_t}","{gpx_rt_direct_f}","fa-network-wired",$m["route"]=="direct",true);
    $h[]=gpx_route_card("company","fa-building","{gpx_rt_company}","{gpx_rt_company_t}","{gpx_rt_company_f}","fa-shield-halved",$m["route"]=="company",true);
    $badge=$m["squid"]?" <span class='gpx-badge'>{gpx_rt_local_b}</span>":"";
    $h[]=gpx_route_card("local","fa-server","{gpx_rt_local}","{gpx_rt_local_t}","{gpx_rt_local_f}","fa-bolt",$m["route"]=="local",$m["squid"],$badge);
    $h[]="</div>";
    $h[]=gpx_company_fields($m);
    $h[]="<p class='gpx-prio' id='gpx-prio'><i class='fas fa-circle-info' aria-hidden='true'></i><span data-r='company'>{gpx_prio_company}</span><span data-r='local'>{gpx_prio_local}</span><span data-r='direct'>{gpx_prio_direct}</span></p>";
    $h[]="</section>";
    return implode("",$h);
}
function gpx_field(string $id,string $label,string $input,string $help,bool $req=false):string{
    return "<div class='gpx-f'><label for='$id'>$label".($req?" <span class='gpx-req' aria-hidden='true'>*</span>":"")."</label>$input"
        ."<p class='gpx-err' id='$id-err' role='alert'></p><p class='gpx-help' id='$id-help'>$help</p></div>";
}
function gpx_company_fields(array $m):string{
    $pwd=$m["password"]!=="";
    $phd=$pwd?gpx_h("gpx_pwd_saved_ph"):"";
    $eye=gpx_h("gpx_pwd_show");
    $h=array("<div class='gpx-cf' id='gpx-cf'".($m["route"]=="company"?"":" hidden")."><div class='gpx-cfh'><b><i class='fas fa-sliders' aria-hidden='true'></i>{gpx_cf_title}</b><span>{gpx_cf_req}</span></div><div class='gpx-grid2'>");
    $h[]=gpx_field("gpx-server","{gpx_server}","<input type='text' id='gpx-server' class='mono' value=\"".gpx_e($m["server"])."\" placeholder='proxy.corp.local' autocomplete='off' spellcheck='false' aria-describedby='gpx-server-help gpx-server-err' aria-required='true'>","{gpx_server_help}",true);
    $h[]=gpx_field("gpx-port","{gpx_port}","<input type='number' id='gpx-port' class='mono' min='1' max='65535' value='".intval($m["port"])."' aria-describedby='gpx-port-help gpx-port-err' aria-required='true'>","{gpx_port_help}",true);
    $h[]=gpx_field("gpx-user","{gpx_user}","<input type='text' id='gpx-user' class='mono' value=\"".gpx_e($m["user"])."\" autocomplete='off' spellcheck='false' aria-describedby='gpx-user-help'>","{gpx_user_help}");
    $h[]=gpx_field("gpx-pwd","{gpx_pwd}","<div class='gpx-pw'><input type='password' id='gpx-pwd' value='' placeholder=\"$phd\" autocomplete='new-password' aria-describedby='gpx-pwd-help'>"
        ."<button type='button' class='gpx-eye' aria-label=\"$eye\" title=\"$eye\" OnClick=\"GpxEye();\"><i class='fas fa-eye'></i></button></div>",
        "{gpx_pwd_help}".($pwd?" <span class='gpx-saved'><i class='fas fa-circle-check' aria-hidden='true'></i>{gpx_chip_pwd}</span>":""));
    $h[]="</div></div>";
    return implode("",$h);
}
function gpx_exclusions_card(array $m):string{
    $n=count($m["exclusions"]);
    $val=gpx_e(implode("\n",$m["exclusions"]));
    $cnt=$n==0?"{gpx_chip_excl0}":gpx_h($n==1?"gpx_excl_n1":"gpx_excl_n",array($n));
    return "<section class='gpx-card gpx-excl' id='gpx-exclbox'><div class='gpx-ch'><div><h3>{gpx_excl_title}</h3><p>{gpx_excl_help}</p></div><span class='gpx-tech'>NO_PROXY</span></div>"
        ."<label for='gpx-excl' class='gpx-sr'>{gpx_excl_title}</label>"
        ."<textarea id='gpx-excl' rows='6' spellcheck='false' placeholder=\"intranet.local&#10;*.corp.local&#10;10.0.0.0/8\" aria-describedby='gpx-excl-help gpx-excl-err'>$val</textarea>"
        ."<p class='gpx-err' id='gpx-excl-err' role='alert'></p><p class='gpx-help' id='gpx-excl-help'>{gpx_excl_hint}</p>"
        ."<div class='gpx-cfoot'><span>{gpx_excl_foot}</span><b id='gpx-excl-n'>$cnt</b></div></section>";
}
function gpx_options_card(array $m):string{
    $tpl=new template_admin();
    $nics=array();
    try{ $nics=$tpl->list_interfaces(false,array()); }catch(Exception $e){ $nics=array(); }
    $opts="<option value=''".($m["bind"]===""?" selected":"").">".gpx_h("gpx_bind_auto")."</option>";
    $found=false;
    foreach($nics as $k=>$label){
        $sel=$m["bind"]===(string)$k;
        if($sel){$found=true;}
        $opts.="<option value=\"".gpx_e($k)."\"".($sel?" selected":"").">".gpx_e(trim($label))."</option>";
    }
    if($m["bind"]!=="" && !$found){ $opts.="<option value=\"".gpx_e($m["bind"])."\" selected>".gpx_e($m["bind"])."</option>"; }
    $ua=$m["ua"]!==""?$m["ua"]:"";
    $uaLbl=$m["ua"]===""||$m["ua"]===gpx_default_ua()?gpx_h("gpx_ua_default"):gpx_e(strlen($m["ua"])>40?substr($m["ua"],0,37)."…":$m["ua"]);
    $bindLbl=$m["bind"]===""?gpx_h("gpx_bind_auto"):gpx_e(isset($nics[$m["bind"]])?trim($nics[$m["bind"]]):$m["bind"]);
    return "<section class='gpx-card gpx-opts'><div class='gpx-ch'><div><h3>{gpx_opt_title}</h3><p>{gpx_opt_help}</p></div>"
        ."<button type='button' class='gpx-link' aria-expanded='false' aria-controls='gpx-optbody' OnClick=\"GpxOpts(this);\">{gpx_opt_show} <i class='fas fa-chevron-down' aria-hidden='true'></i></button></div>"
        ."<dl class='gpx-sum' id='gpx-optsum'><div><dt>{gpx_ua}</dt><dd class='mono'>$uaLbl</dd></div><div><dt>{gpx_bind}</dt><dd>$bindLbl</dd></div></dl>"
        ."<div id='gpx-optbody' hidden>"
        .gpx_field("gpx-ua","{gpx_ua} <small>User-Agent</small>","<input type='text' id='gpx-ua' class='mono' value=\"".gpx_e($ua)."\" placeholder=\"".gpx_e(gpx_default_ua())."\" autocomplete='off' spellcheck='false' aria-describedby='gpx-ua-help'>","{gpx_ua_help}")
        .gpx_field("gpx-bind","{gpx_bind} <small>{gpx_bind_tech}</small>","<select id='gpx-bind' aria-describedby='gpx-bind-help'>$opts</select>","{gpx_bind_help}")
        ."</div></section>";
}
function gpx_footer(array $m):string{
    if(!$m["edit"]){
        return "<div class='gpx-foot'><span><i class='fas fa-lock' aria-hidden='true'></i>{gpx_ro}</span></div>";
    }
    return "<div class='gpx-foot'><span id='gpx-msg' role='status'><i class='fas fa-clock' aria-hidden='true'></i>{gpx_foot}</span>"
        ."<span class='gpx-fb'><button type='button' class='gpx-btn sec' OnClick=\"GpxReload();\">{gpx_cancel}</button>"
        ."<button type='button' class='gpx-btn' id='gpx-apply' OnClick=\"GpxApply();\"><i class='fas fa-check' aria-hidden='true'></i>{gpx_apply}</button></span></div>";
}

function gpx_style():string{
    return "<style>
.gpx{margin:10px 0 90px 0;color:#1f2937;font-size:14px}
.gpx *{box-sizing:border-box}
.gpx .mono,.gpx textarea{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.gpx-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);margin-bottom:16px}
.gpx-banner{display:flex;gap:18px;align-items:flex-start;padding:20px 24px;border-left:4px solid #005447}
.gpx-bico{flex:0 0 44px;height:44px;border-radius:50%;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:19px}
.gpx-btxt h2{margin:0 0 6px 0;font-size:20px;font-weight:600;color:#111827}
.gpx-btxt p{margin:0;font-size:14px;color:#4b5563;line-height:1.55;max-width:900px}
.gpx-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.gpx-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f2f6f6;color:#005447;font-size:13px;border:1px solid #e6eeed}
.gpx-off{display:flex;gap:16px;align-items:flex-start;padding:18px 22px}
.gpx-off.on{background:#fffbeb;border-color:#fde68a}
.gpx-oico{flex:0 0 40px;height:40px;border-radius:10px;background:#f3f4f6;color:#6b7280;display:flex;align-items:center;justify-content:center;font-size:17px}
.gpx-off.on .gpx-oico{background:#fef3c7;color:#92400e}
.gpx-otxt{flex:1 1 auto;min-width:0}
.gpx-otxt h3{margin:0 0 4px 0;font-size:16px;font-weight:600;color:#111827}
.gpx-otxt p{margin:0;font-size:13px;color:#4b5563;line-height:1.5}
.gpx-onote{display:flex;gap:8px;margin-top:8px !important;color:#92400e !important;font-weight:600}
.gpx-onote[hidden]{display:none}
.gpx-sw{position:relative;flex:0 0 48px;width:48px;height:26px;border-radius:13px;border:0;background:#d1d5db;cursor:pointer;padding:0;margin-top:4px}
.gpx-sw i{position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;transition:left .15s;box-shadow:0 1px 2px rgba(0,0,0,.2)}
.gpx-sw[aria-checked='true']{background:#b45309}
.gpx-sw[aria-checked='true'] i{left:25px}
.gpx-sw:disabled{opacity:.5;cursor:not-allowed}
.gpx-route{padding:20px 22px}
.gpx-ch{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:14px}
.gpx-ch h3{margin:0;font-size:16px;font-weight:600;color:#111827}
.gpx-ch p{margin:4px 0 0 0;font-size:13px;color:#6b7280;max-width:720px}
.gpx-rts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.gpx-rt{border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;cursor:pointer;display:flex;flex-direction:column;background:#fff;min-width:0}
.gpx-rt:hover{border-color:#bfd4d1}
.gpx-rt.on{border:2px solid #005447;padding:15px 17px;background:#fbfdfd}
.gpx-rt.na{cursor:not-allowed;background:#f9fafb;color:#6b7280}
.gpx-rt:focus-visible,.gpx-sw:focus-visible,.gpx-btn:focus-visible,.gpx-link:focus-visible,.gpx-eye:focus-visible{outline:2px solid #005447;outline-offset:2px}
.gpx-rth{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.gpx-rti{width:40px;height:40px;border-radius:10px;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:17px}
.gpx-rt.on .gpx-rti{background:#005447;color:#fff}
.gpx-rt.na .gpx-rti{background:#f3f4f6;color:#9ca3af}
.gpx-rck{width:24px;height:24px;border-radius:50%;border:2px solid #d1d5db;display:flex;align-items:center;justify-content:center;font-size:12px;color:transparent}
.gpx-rt.on .gpx-rck{background:#005447;border-color:#005447;color:#fff}
.gpx-rt b{font-size:15px;font-weight:600;color:#111827;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.gpx-rt.na b{color:#6b7280}
.gpx-badge{font-size:13px;font-weight:600;padding:1px 8px;border-radius:999px;background:#e6eeed;color:#005447}
.gpx-rt p{margin:6px 0 0 0;font-size:13px;color:#4b5563;line-height:1.5}
.gpx-why{color:#92400e !important;font-weight:600}
.gpx-rtf{display:flex;gap:8px;align-items:center;margin-top:auto;padding-top:12px;border-top:1px solid #f1f5f9;font-size:13px;color:#6b7280}
.gpx-rt p+.gpx-rtf,.gpx-rt .gpx-why+.gpx-rtf{margin-top:12px}
.gpx-rt.on .gpx-rtf{color:#005447;font-weight:600}
.gpx-cf{margin-top:18px;padding-top:16px;border-top:1px solid #f1f5f9}
.gpx-cf[hidden]{display:none}
.gpx-cfh{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:13px;color:#6b7280}
.gpx-cfh b{display:flex;gap:8px;align-items:center;font-size:15px;color:#111827;font-weight:600}
.gpx-cfh b svg,.gpx-cfh b i{color:#005447}
.gpx-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:4px 20px}
.gpx-f{margin-bottom:10px;min-width:0}
.gpx-f label{display:block;margin:0 0 6px 0;font-size:14px;font-weight:600;color:#1f2937}
.gpx-f label small{font-size:13px;font-weight:400;color:#6b7280;margin-left:6px}
.gpx-req{color:#b91c1c}
.gpx-f input,.gpx-f select,.gpx-excl textarea{width:100%;height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;font-size:14px;color:#111827;background:#fff}
.gpx-excl textarea{height:auto;padding:10px;line-height:1.6;resize:vertical}
.gpx-f input:focus,.gpx-f select:focus,.gpx-excl textarea:focus{outline:2px solid #005447;outline-offset:1px;border-color:#005447}
.gpx-f input[aria-invalid='true'],.gpx-excl textarea[aria-invalid='true']{border-color:#b91c1c}
.gpx-f input:disabled,.gpx-excl textarea:disabled,.gpx-f select:disabled{background:#f3f4f6;color:#6b7280}
.gpx-pw{position:relative}
.gpx-pw input{padding-right:40px}
.gpx-eye{position:absolute;right:4px;top:4px;width:30px;height:30px;border:0;background:transparent;color:#6b7280;border-radius:6px;cursor:pointer}
.gpx-eye:hover{background:#e6eeed;color:#005447}
.gpx-err{margin:4px 0 0 0;font-size:13px;color:#b91c1c;font-weight:600}
.gpx-err:empty{display:none}
.gpx-help{margin:4px 0 0 0;font-size:13px;color:#6b7280;line-height:1.45}
.gpx-saved{display:inline-flex;gap:5px;align-items:center;color:#005447;font-weight:600;margin-left:6px}
.gpx-prio{display:flex;gap:8px;align-items:flex-start;margin:12px 0 0 0;padding-top:12px;border-top:1px solid #f1f5f9;font-size:13px;color:#4b5563}
.gpx-prio svg,.gpx-prio>i{color:#005447;margin-top:3px}
.gpx-prio span{display:none}
.gpx-prio[data-r='company'] span[data-r='company'],.gpx-prio[data-r='local'] span[data-r='local'],.gpx-prio[data-r='direct'] span[data-r='direct']{display:inline}
.gpx-cols{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;align-items:start}
.gpx-excl,.gpx-opts{padding:20px 22px;margin-bottom:0}
.gpx-tech{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;padding:2px 8px;border-radius:6px;background:#e6eeed;color:#005447;white-space:nowrap}
.gpx-cfoot{display:flex;justify-content:space-between;gap:12px;margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:13px;color:#6b7280}
.gpx-cfoot b{color:#005447;font-weight:600}
.gpx-link{display:inline-flex;align-items:center;gap:6px;border:0;background:transparent;color:#005447;font-weight:600;font-size:14px;cursor:pointer;white-space:nowrap;padding:4px 6px;border-radius:6px}
.gpx-link:hover{background:#e6eeed}
.gpx-link[aria-expanded='true'] svg,.gpx-link[aria-expanded='true'] i{transform:rotate(180deg)}
.gpx-sum{display:flex;flex-direction:column;gap:6px;margin:0 0 4px 0;padding:10px 12px;border:1px dashed #e5e7eb;border-radius:8px;background:#fafbfb}
.gpx-sum div{display:flex;justify-content:space-between;gap:12px;font-size:13px}
.gpx-sum dt{color:#6b7280;font-weight:400}
.gpx-sum dd{margin:0;color:#111827;text-align:right;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#gpx-optbody{margin-top:12px}
#gpx-optbody[hidden]{display:none}
.gpx-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
.gpx-dim{opacity:.55}
.gpx-foot{position:sticky;bottom:0;z-index:5;display:flex;justify-content:space-between;align-items:center;gap:16px;margin-top:16px;padding:12px 20px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 -2px 8px rgba(0,0,0,.05);font-size:13px;color:#4b5563}
.gpx-foot>span:first-child{display:flex;gap:8px;align-items:center}
.gpx-foot .ok{color:#005447;font-weight:600}
.gpx-foot .bad{color:#b91c1c;font-weight:600}
.gpx-fb{display:flex;gap:10px}
.gpx-btn{display:inline-flex;align-items:center;gap:8px;height:38px;border:0;border-radius:8px;background:#005447;color:#fff;font-weight:600;font-size:14px;padding:0 18px;cursor:pointer}
.gpx-btn:hover{background:#003d33}
.gpx-btn:disabled{background:#9ca3af;cursor:wait}
.gpx-btn.sec{background:#fff;color:#374151;border:1px solid #d1d5db}
.gpx-btn.sec:hover{background:#f9fafb}
@media (max-width:1200px){.gpx-rts,.gpx-cols{grid-template-columns:minmax(0,1fr)}}
@media (prefers-reduced-motion:reduce){.gpx-sw i{transition:none}}
</style>";
}

function gpx_js(array $m):string{
    $page=CurrentPageName();
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $cfg=json_encode(array("page"=>$page,"route"=>$m["route"],"squid"=>$m["squid"],"edit"=>$m["edit"],
        "eServer"=>gpx_t("gpx_e_server"),"ePort"=>gpx_t("gpx_e_port"),"eExcl"=>gpx_t("gpx_e_excl"),
        "saved"=>gpx_t("gpx_saved"),"net"=>gpx_t("gpx_net_error"),"nExcl"=>gpx_t("gpx_excl_n"),"nExcl1"=>gpx_t("gpx_excl_n1"),"nExcl0"=>gpx_t("gpx_chip_excl0"),
        "show"=>gpx_t("gpx_opt_show"),"hide"=>gpx_t("gpx_opt_hide")),$J);
    return "<script>NoSpinner();(function(){var C=$cfg;
window.GpxState={route:C.route};
function el(id){return document.getElementById(id);}
function setRoute(r){var cards=document.querySelectorAll('#gpx-rts .gpx-rt');for(var i=0;i<cards.length;i++){var on=cards[i].getAttribute('data-r')===r;
 cards[i].className='gpx-rt'+(on?' on':'')+(cards[i].getAttribute('aria-disabled')==='true'?' na':'');cards[i].setAttribute('aria-checked',on?'true':'false');cards[i].tabIndex=on?0:-1;}
 GpxState.route=r;var cf=el('gpx-cf');if(cf){cf.hidden=(r!=='company');}var p=el('gpx-prio');if(p){p.setAttribute('data-r',r);}}
window.GpxPick=function(card){if(!C.edit||card.getAttribute('aria-disabled')==='true'||isOff()){return;}setRoute(card.getAttribute('data-r'));card.focus();};
function isOff(){var s=el('gpx-offline');return s&&s.getAttribute('aria-checked')==='true';}
function dim(){var off=isOff();['gpx-routebox','gpx-exclbox'].forEach(function(id){var b=el(id);if(b){b.className=b.className.replace(/ gpx-dim/g,'')+(off?' gpx-dim':'');}});
 var f=document.querySelectorAll('#gpx-routebox input,#gpx-exclbox textarea');for(var i=0;i<f.length;i++){f[i].disabled=off||!C.edit;}
 if(!C.edit){var o=document.querySelectorAll('#gpx-optbody input,#gpx-optbody select');for(var j=0;j<o.length;j++){o[j].disabled=true;}}
 var n=el('gpx-off-note');if(n){n.hidden=!off;}var c=el('gpx-offcard');if(c){c.className='gpx-card gpx-off'+(off?' on':'');}}
window.GpxOffline=function(b){b.setAttribute('aria-checked',b.getAttribute('aria-checked')==='true'?'false':'true');dim();};
window.GpxEye=function(){var i=el('gpx-pwd');if(i){i.type=i.type==='password'?'text':'password';}};
window.GpxOpts=function(b){var o=b.getAttribute('aria-expanded')!=='true';b.setAttribute('aria-expanded',o?'true':'false');var d=el('gpx-optbody');if(d){d.hidden=!o;}
 var s=el('gpx-optsum');if(s){s.hidden=o;}var t=b.firstChild;if(t&&t.nodeType===3){t.nodeValue=(o?C.hide:C.show)+' ';}};
window.GpxReload=function(){LoadAjaxSilent('table-loader-globalproxy-service',C.page+'?table=yes');};
function ferr(id,msg){var e=el(id+'-err'),i=el(id);if(e){e.textContent=msg||'';}if(i){i.setAttribute('aria-invalid',msg?'true':'false');}}
function badExcl(v){var bad=[];v.split(/\\r?\\n/).forEach(function(l){l=l.replace(/^\\s+|\\s+\$/g,'');if(l===''||l.charAt(0)==='#'){return;}
 l.split(/[\\s,;]+/).forEach(function(x){if(x!==''&&!/^[A-Za-z0-9.:\\/*_-]+\$/.test(x)){bad.push(x);}});});return bad;}
function countExcl(v){var n=0;v.split(/\\r?\\n/).forEach(function(l){l=l.replace(/^\\s+|\\s+\$/g,'');if(l===''||l.charAt(0)==='#'){return;}l.split(/[\\s,;]+/).forEach(function(x){if(x!==''){n++;}});});return n;}
function msg(cls,t){var m=el('gpx-msg');if(!m){return;}m.className=cls;m.textContent=t;}
window.GpxApply=function(){var ok=true,off=isOff(),r=GpxState.route;
 ferr('gpx-server','');ferr('gpx-port','');ferr('gpx-excl','');
 if(!off&&r==='company'){var s=(el('gpx-server').value||'').replace(/^\\s+|\\s+\$/g,'');if(!/^[A-Za-z0-9.\\-:\\[\\]]+\$/.test(s)){ferr('gpx-server',C.eServer);ok=false;}
  var p=parseInt(el('gpx-port').value,10);if(!(p>=1&&p<=65535)){ferr('gpx-port',C.ePort);ok=false;}}
 var ex=el('gpx-excl')?el('gpx-excl').value:'';var bad=badExcl(ex);if(bad.length){ferr('gpx-excl',C.eExcl.replace('%s',bad.join(', ')));ok=false;}
 if(!ok){var f=document.querySelector('.gpx [aria-invalid=true]');if(f){f.focus();}return;}
 var b=el('gpx-apply');b.disabled=true;
 var d={'gpx-save':1,'offline':off?1:0,'route':r,'server':el('gpx-server')?el('gpx-server').value:'','port':el('gpx-port')?el('gpx-port').value:'',
  'user':el('gpx-user')?el('gpx-user').value:'','pwd':el('gpx-pwd')?el('gpx-pwd').value:'','excl':ex,'ua':el('gpx-ua')?el('gpx-ua').value:'','bind':el('gpx-bind')?el('gpx-bind').value:''};
 \$.ajax({url:C.page,type:'POST',data:d,timeout:30000}).done(function(t){b.disabled=false;t=String(t||'').replace(/^\\s+|\\s+\$/g,'');
   if(t===''){msg('ok',C.saved);setTimeout(GpxReload,900);return;}msg('bad',t.replace(/<[^>]*>/g,' '));})
  .fail(function(){b.disabled=false;msg('bad',C.net);});};
var rts=el('gpx-rts');
if(rts){rts.addEventListener('click',function(e){var c=e.target.closest?e.target.closest('.gpx-rt'):null;if(c){GpxPick(c);}});
 rts.addEventListener('keydown',function(e){var k=e.key;if(['ArrowRight','ArrowDown','ArrowLeft','ArrowUp',' ','Enter'].indexOf(k)<0){return;}e.preventDefault();
  var cards=[].slice.call(rts.querySelectorAll('.gpx-rt')).filter(function(c){return c.getAttribute('aria-disabled')!=='true';});
  var i=cards.indexOf(document.activeElement);if(k===' '||k==='Enter'){if(i>=0){GpxPick(cards[i]);}return;}
  var n=(k==='ArrowRight'||k==='ArrowDown')?i+1:i-1;if(n<0){n=cards.length-1;}if(n>=cards.length){n=0;}GpxPick(cards[n]);});}
var ta=el('gpx-excl');if(ta){ta.addEventListener('input',function(){var n=countExcl(ta.value),o=el('gpx-excl-n');if(o){o.textContent=n===0?C.nExcl0:(n===1?C.nExcl1:C.nExcl.replace('%s',n));}});}
setRoute(C.route);dim();
})();</script>";
}

function table():bool{
    $tpl=new template_admin();
    $m=gpx_model();
    $h=array("<div class='gpx'>",gpx_banner($m),gpx_offline_card($m),gpx_routes($m),
        "<div class='gpx-cols'>",gpx_exclusions_card($m),gpx_options_card($m),"</div>",gpx_footer($m),"</div>");
    $TINY=array("TITLE"=>"{internet_access} (Proxy)","ICO"=>"fas fa-globe","EXPL"=>"{gpx_tiny}","URL"=>"global-proxy","BUTTONS"=>"");
    $tiny="<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
    echo gpx_style().$tpl->_ENGINE_parse_body(implode("",$h)).gpx_js($m).$tiny;
    return true;
}

// ---------------------------------------------------------------- save
// One POST, whitelisted fields, rights checked here, echo NOTHING on success.

function gpx_clean_line($v):string{ return trim(str_replace(array("\r","\n"),"",(string)$v)); }
function gpx_save(){
    if(!gpx_can_edit()){ echo gpx_t("ERROR_NO_PRIVS"); return; }
    $s=$GLOBALS["CLASS_SOCKETS"];
    $offline=intval(isset($_POST["offline"])?$_POST["offline"]:0)==1;
    $route=(string)(isset($_POST["route"])?$_POST["route"]:"direct");
    if(!in_array($route,array("direct","company","local"))){ $route="direct"; }
    $squid=intval($s->GET_INFO("SQUIDEnable"))==1;
    if($route=="local" && !$squid){ $route="direct"; }

    $old=gpx_ini();
    $server=gpx_clean_line(isset($_POST["server"])?$_POST["server"]:"");
    $port=intval(isset($_POST["port"])?$_POST["port"]:0);
    $user=gpx_clean_line(isset($_POST["user"])?$_POST["user"]:"");
    $pwd=gpx_clean_line(isset($_POST["pwd"])?$_POST["pwd"]:"");
    if($pwd===""){ $pwd=$old["password"]; }
    if($user===""){ $pwd=""; }
    if($route=="company" && !$offline){
        if(!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/',$server)){ echo gpx_t("gpx_e_server"); return; }
        if($port<1 || $port>65535){ echo gpx_t("gpx_e_port"); return; }
    }
    if($server!=="" && !preg_match('/^[A-Za-z0-9.\-:\[\]]+$/',$server)){ $server=$old["server"]; }
    if($port<1 || $port>65535){ $port=$old["port"]; }

    // Exclusions: same rules as the former no_proxy_save().
    $final=array();$bad=array();
    $raw=str_replace("\r","\n",(string)(isset($_POST["excl"])?$_POST["excl"]:""));
    foreach(explode("\n",$raw) as $line){
        $line=trim($line);
        if($line=="" || substr($line,0,1)=="#"){continue;}
        foreach(preg_split('/[\s,;]+/',$line) as $item){
            $item=trim($item);
            if($item==""){continue;}
            if(!preg_match('/^[A-Za-z0-9.:\/*_-]+$/',$item)){$bad[]=$item;continue;}
            $final[$item]=true;
        }
    }
    if(count($bad)>0){ echo gpx_t("gpx_e_excl",array(implode(", ",$bad))); return; }

    $ua=gpx_clean_line(isset($_POST["ua"])?$_POST["ua"]:"");
    if($ua===gpx_default_ua()){ $ua=""; }
    $bind=gpx_clean_line(isset($_POST["bind"])?$_POST["bind"]:"");
    if($bind!=="" && !preg_match('/^[A-Za-z0-9.:_-]+$/',$bind)){ echo gpx_t("gpx_e_bind"); return; }

    // [PROXY] INI: the company proxy is kept even when another route is chosen,
    // so switching back does not lose it. ArticaCompiledProxyUri: user and
    // password now really end up in it (the old code lost the username).
    $enabled=($route=="company")?1:0;
    $auth="";
    if($user!==""){ $auth=rawurlencode($user).($pwd!==""?":".rawurlencode($pwd):"")."@"; }
    // Written only when the company proxy is ON: a reader that does not check the
    // Enabled flag must never pick up a proxy the administrator turned off.
    $uri=($enabled==1 && $server!=="")?"http://$auth$server:$port":"";
    $conf=array("[PROXY]","ArticaProxyServerEnabled=$enabled","ArticaProxyServerName=$server","ArticaProxyServerPort=$port",
        "ArticaProxyServerUsername=$user","ArticaProxyServerUserPassword=$pwd","ArticaCompiledProxyUri=$uri");
    $wasOffline=intval($s->GET_INFO("NoInternetAccess"))==1;

    $s->REST_API("/reset/cache");
    $s->SaveConfigFile(implode("\n",$conf)."\n","ArticaProxySettings");
    $s->SET_INFO("NoCheckSquid",$route=="local"?0:1);
    $s->SET_INFO("NoProxyAdditional",implode(",",array_keys($final)));
    $s->SET_INFO("CurlUserAgent",$ua);
    $s->SET_INFO("WgetBindIpAddress",$bind);
    $s->SET_INFO("NoInternetAccess",$offline?1:0);
    $s->REST_API("/system/proxy/save");
    if($wasOffline!==$offline){ $s->REST_API("/system/nointernetaccess"); }
    admin_tracks("Internet access: route=$route".($route=="company"?" ($server:$port".($user!==""?" as $user":"").")":"")
        .", offline=".($offline?"yes":"no").", exclusions=".count($final).($bind!==""?", bind=$bind":""));
}
