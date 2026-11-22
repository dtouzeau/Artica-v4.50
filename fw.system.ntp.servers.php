<?php
// fw.system.ntp.servers.php - "NTP servers" window (opened by Loadjs from the time pages).
// Maquette Stitch « ntp server modal redesign » (2026-11-21), real data only:
//   /chrony/servers (+ POST, DELETE, /move)  : my own list (ntpd_servers, ntp.db)
//   /chrony/srvstatus                        : live state of each source (chronyc sources)
//   /chrony/status                           : is the time service running
//   NTPDUseSpecifiedServers / NTPClientDefaultServerList : which list chrony.conf uses
// Every add/remove/move already rewrites chrony.conf: ntpd::SaveToLdap() calls
// /chrony/reconfigure. "Apply now" only replays it with a progress bar.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ntpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["ntpd-table"])){table();exit;}
if(isset($_POST["new-server"])){ntpsv_add_save();exit;}
if(isset($_POST["delete-server"])){ntpsv_delete_save();exit;}
if(isset($_POST["move-server"])){ntpsv_move_save();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog3("{ntp_servers}", "$page?popup=yes",720);
}

// ---------------------------------------------------------------- model

function ntpsv_can_edit():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}
function ntpsv_region():string{
    $r=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPClientDefaultServerList"));
    // Même défaut que le daemon (chronyd/Configurator.go) et la fenêtre Parameters.
    return strlen($r)<3?"Generic":$r;
}
function ntpsv_service_running():int{
    // 1 running, 0 stopped, -1 unknown
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/status"));
    if(!is_object($json) || empty($json->Status) || !isset($json->Info)){return -1;}
    $ini=@parse_ini_string((string)$json->Info,true,INI_SCANNER_RAW);
    if(!is_array($ini) || !isset($ini["APP_NTPD"]) || !is_array($ini["APP_NTPD"])){return -1;}
    return intval(isset($ini["APP_NTPD"]["running"])?$ini["APP_NTPD"]["running"]:0)==1?1:0;
}
// Live sources keyed by lowercase name and IP; "named" = chronyc gave the configured names.
function ntpsv_peers():array{
    $out=array("ok"=>false,"named"=>false,"by"=>array());
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/srvstatus"));
    if(!is_object($json) || empty($json->Status)){return $out;}
    $out["ok"]=true;
    foreach((isset($json->Info) && is_array($json->Info))?$json->Info:array() as $p){
        if(!is_object($p)){continue;}
        $name=strtolower(trim((string)(isset($p->name)?$p->name:"")));
        $addr=strtolower(trim((string)(isset($p->address)?$p->address:"")));
        $ip=strtolower(trim((string)(isset($p->ip)?$p->ip:"")));
        if($name!==""){$out["named"]=true;}
        $row=array("st"=>(string)(isset($p->status)?$p->status:"?"),"stratum"=>intval(isset($p->stratum)?$p->stratum:0),
            "last_rx"=>intval(isset($p->last_rx)?$p->last_rx:-1),"offset"=>floatval(isset($p->offset_s)?$p->offset_s:0),
            "has_offset"=>intval(isset($p->last_rx)?$p->last_rx:-1)>=0 && (isset($p->error_s) || floatval(isset($p->offset_s)?$p->offset_s:0)!=0));
        foreach(array($name,$addr,$ip) as $k){ if($k!=="" && !isset($out["by"][$k])){ $out["by"][$k]=$row; } }
    }
    return $out;
}
function ntpsv_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array("specified"=>intval($sock->GET_INFO("NTPDUseSpecifiedServers"))==1,"region"=>ntpsv_region(),
        "cluster"=>intval($sock->GET_INFO("HaClusterClient"))==1,"cluster_ip"=>"",
        "ad"=>intval($sock->GET_INFO("EnableActiveDirectoryFeature"))==1 && intval($sock->GET_INFO("NtpdateAD"))==1,
        "edit"=>ntpsv_can_edit(),"servers"=>array(),"list_error"=>"","region_known"=>true,
        "running"=>ntpsv_service_running(),"peers"=>ntpsv_peers());
    if($m["cluster"]){
        $u=trim((string)$sock->GET_INFO("HaClusterIP"));
        $h=parse_url(strpos($u,"://")===false?"http://$u":$u,PHP_URL_HOST);
        $m["cluster_ip"]=is_string($h)?$h:"";
    }
    if($m["specified"]){
        $json=json_decode((string)$sock->REST_API("/chrony/servers"));
        if(!is_object($json) || empty($json->Status)){
            $m["list_error"]=is_object($json) && isset($json->Error)?(string)$json->Error:"{ntpsv_list_error}";
        }else{
            foreach((isset($json->Servers) && is_array($json->Servers))?$json->Servers:array() as $s){
                $n=trim((string)(isset($s->Server)?$s->Server:""));
                if($n!==""){ $m["servers"][]=$n; }
            }
        }
    }else{
        $ntp=new ntpd(true);
        $all=$ntp->ServersList();
        if(isset($all[$m["region"]]) && is_array($all[$m["region"]])){ $m["servers"]=array_values($all[$m["region"]]); }
        else{ $m["region_known"]=false; }
    }
    return $m;
}

// ---------------------------------------------------------------- formats

function ntpsv_t(string $token,array $vals=array()):string{
    $tpl=new template_admin();
    $txt=html_entity_decode($tpl->_ENGINE_parse_body("{".$token."}"),ENT_QUOTES,"UTF-8");
    $txt=trim(preg_replace('#\s*<br\s*/?>\s*#i',' ',str_replace("[br]","<br>",$txt)));
    foreach($vals as $v){$p=strpos($txt,"%s");if($p===false){break;}$txt=substr_replace($txt,(string)$v,$p,2);}
    return $txt;
}
function ntpsv_h(string $token,array $vals=array()):string{ return htmlspecialchars(ntpsv_t($token,$vals),ENT_QUOTES,"UTF-8"); }
function ntpsv_secs(float $s):string{
    $a=abs($s);
    if($a<1e-6){$v=$s*1e9;$u="ns";}elseif($a<1e-3){$v=$s*1e6;$u="µs";}elseif($a<1){$v=$s*1e3;$u="ms";}else{$v=$s;$u="s";}
    $av=abs($v);
    $n=$av<10?rtrim(rtrim(sprintf("%.2f",$av),"0"),"."):($av<100?rtrim(rtrim(sprintf("%.1f",$av),"0"),"."):sprintf("%.0f",$av));
    return ($s<0?"−":"+")."$n $u";
}
function ntpsv_span(int $sec):string{
    if($sec<60){return "$sec s";}
    if($sec<3600){return intval(floor($sec/60))." min";}
    if($sec<86400){return intval(floor($sec/3600))." h";}
    return intval(floor($sec/86400))." d";
}

// ---------------------------------------------------------------- widgets

function ntpsv_intro():string{
    return "<div class='ntpsv-intro'><div class='ntpsv-ico' aria-hidden='true'><i class='fas fa-clock'></i></div><div>"
        ."<h3>{ntpsv_title}</h3><p>{ntpsv_text}</p><div class='ntpsv-chips'>"
        ."<span class='ntpsv-chip'><i class='fas fa-shield-halved' aria-hidden='true'></i>{ntpsv_chip_best}</span>"
        ."<span class='ntpsv-chip'><i class='fas fa-rotate' aria-hidden='true'></i>{ntpsv_chip_live}</span></div></div></div>";
}
function ntpsv_mode(array $m):string{
    $why=ntpsv_h("ntpsv_mode_ro");
    $seg="<div class='ntpsv-seg' role='radiogroup' aria-label=\"".ntpsv_h("ntpsv_mode")."\" aria-disabled='true' title=\"$why\">"
        ."<span role='radio' aria-checked='".($m["specified"]?"false":"true")."'".($m["specified"]?"":" class='on'").">{ntpsv_builtin}</span>"
        ."<span role='radio' aria-checked='".($m["specified"]?"true":"false")."'".($m["specified"]?" class='on'":"").">{ntpsv_own}</span></div>";
    $link=$m["edit"]?"<a class='ntpsv-link' href='javascript:blur();' OnClick=\"Loadjs('fw.system.ntpd.php?config-js=yes');\"><i class='fas fa-sliders' aria-hidden='true'></i>{ntpsv_change}</a>":"";
    $sub=$m["specified"]?"{ntpsv_own_sub}":ntpsv_h("ntpsv_builtin_sub",array($m["region"]));
    return "<div class='ntpsv-card ntpsv-mode'><div class='ntpsv-mrow'><span class='ntpsv-ml'>{ntpsv_mode}</span>$seg$link</div><p>$sub</p></div>";
}
function ntpsv_notices(array $m):string{
    $h=array();
    if($m["cluster"]){
        $h[]="<div class='ntpsv-note'><i class='fas fa-circle-info' aria-hidden='true'></i><span>"
            .($m["cluster_ip"]!==""?ntpsv_h("ntpsv_cluster_ip",array($m["cluster_ip"])):"{ntpsv_cluster}")."</span></div>";
    }
    if($m["ad"]){ $h[]="<div class='ntpsv-note'><i class='fas fa-circle-info' aria-hidden='true'></i><span>{ntpsv_ad}</span></div>"; }
    if(!$m["specified"] && !$m["region_known"]){
        $h[]="<div class='ntpsv-note'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>".ntpsv_h("ntpsv_region_unknown",array($m["region"]))."</span></div>";
    }
    return implode("",$h);
}
// Live state pill, or "" when nothing honest can be said.
function ntpsv_state(array $m,string $name):array{
    if($m["running"]===0){ return array("off","fa-circle-pause","{ntpsv_st_stopped}",null); }
    $pe=$m["peers"];
    if(!$pe["ok"]){ return array("","","",null); }
    $k=strtolower($name);
    if(isset($pe["by"][$k])){
        $p=$pe["by"][$k];
        $map=array("*"=>array("ok","fa-circle-check","{ntpsv_st_inuse}"),"+"=>array("ok","fa-circle-plus","{ntpsv_st_also}"),
            "-"=>array("off","fa-circle-minus","{ntpsv_st_notused}"),"?"=>array("err","fa-circle-xmark","{ntpsv_st_noanswer}"),
            "x"=>array("warn","fa-triangle-exclamation","{ntpsv_st_wrong}"),"~"=>array("warn","fa-triangle-exclamation","{ntpsv_st_unstable}"));
        $s=isset($map[$p["st"]])?$map[$p["st"]]:$map["?"];
        return array($s[0],$s[1],$s[2],$p);
    }
    // Absent from chrony's sources: skipped as unresolvable — only when this list IS chrony's
    // list (my own, not overridden by the cluster) and chronyc reports configured names.
    if($m["specified"] && !$m["cluster"] && $pe["named"]){ return array("err","fa-ban","{ntpsv_st_skipped}",null); }
    return array("","","",null);
}
function ntpsv_row(array $m,int $i,string $name,int $n):string{
    list($tone,$ico,$label,$p)=ntpsv_state($m,$name);
    $esc=htmlspecialchars($name,ENT_QUOTES,"UTF-8");
    $pill=$label!==""?"<span class='ntpsv-st $tone'><i class='fas $ico' aria-hidden='true'></i>$label</span>":"";
    $sub=array();
    if(is_array($p)){
        if($p["last_rx"]>=0){ $sub[]=ntpsv_h("ntpsv_last",array(ntpsv_span($p["last_rx"]))); }
        if($p["has_offset"]){ $sub[]=ntpsv_h("ntpsv_diff",array(ntpsv_secs($p["offset"]))); }
        if($p["stratum"]>0){ $sub[]=ntpsv_h("ntpsv_level",array(strval($p["stratum"]))); }
    }
    $line=count($sub)?"<div class='ntpsv-sub'>".implode(" · ",$sub)."</div>":"";
    $pos=$m["specified"]?"<span class='ntpsv-pos' aria-hidden='true'>".($i+1)."</span>":"<span class='ntpsv-pos lock' aria-hidden='true'><i class='fas fa-lock'></i></span>";
    $acts="";$ask="";
    if($m["specified"] && $m["edit"]){
        $js=htmlspecialchars(json_encode($name,JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP),ENT_QUOTES,"UTF-8");
        $up=ntpsv_h("ntpsv_up",array($name));$dn=ntpsv_h("ntpsv_down",array($name));$rm=ntpsv_h("ntpsv_remove_l",array($name));
        $acts="<div class='ntpsv-acts'>"
            ."<button type='button' class='ntpsv-ib' aria-label=\"$up\" title=\"$up\"".($i==0?" disabled":"")." OnClick=\"NtpsvMove($js,'up');\"><i class='fas fa-arrow-up'></i></button>"
            ."<button type='button' class='ntpsv-ib' aria-label=\"$dn\" title=\"$dn\"".($i==$n-1?" disabled":"")." OnClick=\"NtpsvMove($js,'down');\"><i class='fas fa-arrow-down'></i></button>"
            ."<button type='button' class='ntpsv-ib del' aria-label=\"$rm\" title=\"$rm\" OnClick=\"NtpsvAsk(this,true);\"><i class='fas fa-trash-can'></i></button></div>";
        $q=htmlspecialchars(ntpsv_t("ntpsv_remove_q",array("\x01")),ENT_QUOTES,"UTF-8");
        $q=str_replace("\x01","<code>$esc</code>",$q);
        $ask="<div class='ntpsv-ask' hidden><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>$q</span>"
            ."<button type='button' class='ntpsv-btn sec' OnClick=\"NtpsvAsk(this,false);\">{ntpsv_cancel}</button>"
            ."<button type='button' class='ntpsv-btn danger' OnClick=\"NtpsvDelete($js,this);\">{ntpsv_remove}</button></div>";
    }
    return "<div class='ntpsv-item'><div class='ntpsv-row'>$pos<div class='ntpsv-id'><div class='ntpsv-nl'><code class='ntpsv-name' title=\"$esc\">$esc</code>$pill</div>$line</div>$acts</div>$ask</div>";
}
function ntpsv_list(array $m):string{
    $n=count($m["servers"]);
    $title=$m["specified"]?"{ntpsv_own_title}":ntpsv_h("ntpsv_builtin_title",array($m["region"]));
    $count=$n>0?"<span class='ntpsv-count'>".ntpsv_h($n==1?"ntpsv_count1":"ntpsv_count",array(strval($n)))."</span>":"";
    $h=array("<div class='ntpsv-card ntpsv-list'><div class='ntpsv-lh'><b>$title</b>$count</div>");
    if($m["list_error"]!==""){
        $h[]="<div class='ntpsv-err'><i class='fas fa-plug-circle-xmark' aria-hidden='true'></i><span>{ntpsv_list_error}</span><code>".htmlspecialchars(str_replace(array("{","}"),"",$m["list_error"]),ENT_QUOTES,"UTF-8")."</code></div>";
    }elseif($n==0 && $m["specified"]){
        $btn=$m["edit"]?"<button type='button' class='ntpsv-btn' OnClick=\"var e=document.getElementById('ntpsv-new');if(e){e.focus();}\"><i class='fas fa-plus'></i>{ntpsv_add_title}</button>":"";
        $h[]="<div class='ntpsv-empty'><i class='fas fa-clock' aria-hidden='true'></i><b>{ntpsv_empty_title}</b><span>{ntpsv_empty_text}</span>$btn</div>";
    }else{
        foreach($m["servers"] as $i=>$s){ $h[]=ntpsv_row($m,$i,$s,$n); }
    }
    if($m["specified"] && $m["edit"] && $m["list_error"]===""){ $h[]=ntpsv_add(); }
    $h[]="</div>";
    if($m["specified"] && $n>1){ $h[]="<p class='ntpsv-hint'><i class='fas fa-circle-info' aria-hidden='true'></i>{ntpsv_order_hint}</p>"; }
    return implode("",$h);
}
function ntpsv_add():string{
    $ph=ntpsv_h("ntpsv_add_ph");
    return "<div class='ntpsv-add'><label for='ntpsv-new'>{ntpsv_add_title}</label><div class='ntpsv-arow'>"
        ."<input type='text' id='ntpsv-new' maxlength='253' placeholder=\"$ph\" autocomplete='off' spellcheck='false' aria-describedby='ntpsv-add-help ntpsv-add-err' onkeydown=\"if(event.key==='Enter'){NtpsvAdd();event.preventDefault();}\">"
        ."<button type='button' class='ntpsv-btn' id='ntpsv-addb' OnClick=\"NtpsvAdd();\"><i class='fas fa-plus' aria-hidden='true'></i>{ntpsv_add}</button></div>"
        ."<p class='ntpsv-err-l' id='ntpsv-add-err' role='alert'></p><p class='ntpsv-help' id='ntpsv-add-help'>{ntpsv_add_help}</p></div>";
}
function ntpsv_footer(bool $edit):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $after="NtpsvReload();if(document.getElementById('table-loader-ntpd-service')){LoadAjax('table-loader-ntpd-service','fw.system.ntpd.php?tabs=yes');}";
    $apply=$edit?$tpl->framework_buildjs("/chrony/reconfigure","ntpd.progress","ntpd.progress.log","progress-ntpd-restart",$after):"";
    // framework_buildjs() returns JS full of double quotes: it lives in a function, never in OnClick.
    $btn=$edit?"<button type='button' class='ntpsv-btn' OnClick=\"NtpsvApply();\"><i class='fas fa-rotate' aria-hidden='true'></i>{ntpsv_apply}</button>":"";
    $GLOBALS["NTPSV_APPLY_JS"]=$apply;
    return "<div id='progress-ntpd-restart'></div><div class='ntpsv-foot'><span><i class='fas fa-bolt' aria-hidden='true'></i>{ntpsv_foot}</span>"
        ."<span class='ntpsv-fb'><button type='button' class='ntpsv-btn sec' OnClick=\"if(typeof dialogInstance3!=='undefined'){dialogInstance3.close();}\">{ntpsv_close}</button>$btn</span></div>";
}

function ntpsv_style():string{
    return "<style>
.ntpsv{color:#1f2937;font-size:14px}
.ntpsv *{box-sizing:border-box}
.ntpsv-intro{display:flex;gap:16px;align-items:flex-start;background:#f9fbfb;border:1px solid #e5e7eb;border-radius:12px;padding:18px 20px;margin-bottom:14px}
.ntpsv-ico{flex:0 0 40px;height:40px;border-radius:50%;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:18px}
.ntpsv-intro h3{margin:0 0 6px 0;font-size:18px;font-weight:600;color:#111827}
.ntpsv-intro p{margin:0;font-size:14px;color:#4b5563;line-height:1.55}
.ntpsv-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.ntpsv-chip{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;background:#f2f6f6;color:#005447;font-size:13px;border:1px solid #e6eeed}
.ntpsv-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;margin-bottom:14px}
.ntpsv-mode{padding:12px 16px}
.ntpsv-mrow{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.ntpsv-ml{font-size:14px;font-weight:600;color:#374151}
.ntpsv-seg{display:inline-flex;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;padding:3px;gap:2px;cursor:not-allowed}
.ntpsv-seg span{padding:5px 12px;border-radius:6px;font-size:13px;font-weight:600;color:#6b7280}
.ntpsv-seg span.on{background:#fff;color:#005447;box-shadow:0 1px 2px rgba(0,0,0,.08)}
.ntpsv-link{display:inline-flex;align-items:center;gap:6px;margin-left:auto;color:#005447;font-weight:600;font-size:14px;text-decoration:none;padding:5px 8px;border-radius:6px}
.ntpsv-link:hover{background:#e6eeed;color:#003d33;text-decoration:none}
.ntpsv-mode p{margin:8px 0 0 0;font-size:13px;color:#4b5563}
.ntpsv-note{display:flex;gap:10px;align-items:flex-start;margin-bottom:12px;padding:10px 14px;border-radius:10px;background:#fffbeb;color:#92400e;border:1px solid #fde68a;font-size:14px}
.ntpsv-note svg,.ntpsv-note i{margin-top:3px}
.ntpsv-list{overflow:hidden}
.ntpsv-lh{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#f9fafb;border-bottom:1px solid #f1f5f9}
.ntpsv-lh b{font-size:15px;font-weight:600;color:#111827}
.ntpsv-count{padding:1px 10px;border-radius:999px;background:#e5e7eb;color:#374151;font-size:13px;font-weight:600}
.ntpsv-item{border-bottom:1px solid #f1f5f9}
.ntpsv-row{display:flex;align-items:center;gap:12px;padding:10px 16px;min-height:56px}
.ntpsv-pos{flex:0 0 26px;height:26px;border-radius:50%;border:1px solid #d1d5db;color:#374151;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center}
.ntpsv-pos.lock{border:0;color:#9ca3af}
.ntpsv-id{flex:1 1 auto;min-width:0}
.ntpsv-nl{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.ntpsv-name{font-family:monospace;font-size:14px;font-weight:600;color:#111827;background:transparent;padding:0;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ntpsv-st{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}
.ntpsv-st.ok{background:#e6eeed;color:#005447}.ntpsv-st.off{background:#f3f4f6;color:#374151}.ntpsv-st.err{background:#fef2f2;color:#b91c1c}.ntpsv-st.warn{background:#fffbeb;color:#92400e}
.ntpsv-sub{font-size:13px;color:#6b7280;margin-top:3px}
.ntpsv-acts{display:flex;gap:4px}
.ntpsv-ib{width:32px;height:32px;border:0;border-radius:8px;background:transparent;color:#4b5563;cursor:pointer;display:flex;align-items:center;justify-content:center}
.ntpsv-ib:hover:not(:disabled){background:#e6eeed;color:#005447}
.ntpsv-ib.del:hover{background:#fef2f2;color:#b91c1c}
.ntpsv-ib:disabled{color:#d1d5db;cursor:not-allowed}
.ntpsv-ask{display:flex;align-items:center;gap:10px;padding:10px 16px;background:#fffbeb;border-top:1px solid #fde68a;color:#92400e;font-size:14px;flex-wrap:wrap}
.ntpsv-ask[hidden]{display:none}
.ntpsv-ask span{flex:1 1 260px}
.ntpsv-ask code{font-family:monospace;font-weight:600;color:#78350f;background:transparent;padding:0}
.ntpsv-btn{display:inline-flex;align-items:center;gap:8px;height:36px;border:0;border-radius:8px;background:#005447;color:#fff;font-weight:600;font-size:14px;padding:0 16px;cursor:pointer;white-space:nowrap}
.ntpsv-btn:hover{background:#003d33}
.ntpsv-btn:disabled{background:#9ca3af;cursor:wait}
.ntpsv-btn.sec{background:#fff;color:#374151;border:1px solid #d1d5db}
.ntpsv-btn.sec:hover{background:#f9fafb}
.ntpsv-btn.danger{background:#b91c1c}.ntpsv-btn.danger:hover{background:#991b1b}
.ntpsv-btn:focus-visible,.ntpsv-ib:focus-visible,.ntpsv-link:focus-visible{outline:2px solid #005447;outline-offset:2px}
.ntpsv-add{padding:12px 16px;background:#fcfdfd}
.ntpsv-add label{display:block;margin:0 0 6px 0;font-size:14px;font-weight:600;color:#1f2937}
.ntpsv-arow{display:flex;gap:10px}
.ntpsv-arow input{flex:1 1 auto;min-width:0;height:36px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;font-family:monospace;font-size:14px;color:#111827}
.ntpsv-arow input:focus{outline:2px solid #005447;outline-offset:1px;border-color:#005447}
.ntpsv-arow input[aria-invalid='true']{border-color:#b91c1c}
.ntpsv-err-l{margin:6px 0 0 0;font-size:13px;color:#b91c1c;font-weight:600}
.ntpsv-err-l:empty{display:none}
.ntpsv-help{margin:6px 0 0 0;font-size:13px;color:#6b7280;line-height:1.45}
.ntpsv-hint{display:flex;gap:8px;align-items:flex-start;margin:-6px 4px 12px 4px;font-size:13px;color:#6b7280}
.ntpsv-hint svg,.ntpsv-hint i{margin-top:3px}
.ntpsv-empty{display:flex;flex-direction:column;align-items:center;gap:6px;text-align:center;padding:24px 16px;color:#4b5563;font-size:14px}
.ntpsv-empty>svg,.ntpsv-empty>i{font-size:24px;color:#005447;margin-bottom:4px}
.ntpsv-empty b{color:#111827;font-size:15px}
.ntpsv-empty .ntpsv-btn{margin-top:8px}
.ntpsv-err{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:14px 16px;color:#b91c1c;font-size:14px}
.ntpsv-err code{font-family:monospace;font-size:13px;background:#fef2f2;color:#7f1d1d;padding:2px 6px;border-radius:4px}
.ntpsv-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 0 2px 0;border-top:1px solid #e5e7eb;font-size:13px;color:#4b5563}
.ntpsv-foot svg,.ntpsv-foot>span>i{color:#005447;margin-right:6px}
.ntpsv-fb{display:flex;gap:10px}
@media (prefers-reduced-motion:no-preference){.ntpsv-item{animation:ntpsvIn .15s ease-out}}
@keyframes ntpsvIn{from{opacity:0}to{opacity:1}}
</style>";
}
function ntpsv_js():string{
    $page=CurrentPageName();
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $cfg=json_encode(array("page"=>$page,"bad"=>ntpsv_t("ntpsv_add_bad"),"dup"=>ntpsv_t("ntpsv_add_dup"),"net"=>ntpsv_t("ntpsv_net_error")),$J);
    return "<script>NoSpinner();(function(){var C=$cfg;
window.NtpsvApply=function(){".(isset($GLOBALS["NTPSV_APPLY_JS"])?$GLOBALS["NTPSV_APPLY_JS"]:"")."};
window.NtpsvReload=function(){if(document.getElementById('ntpd-servers')){LoadAjaxSilent('ntpd-servers',C.page+'?ntpd-table=yes');}};
function post(data,ok,fail){\$.ajax({url:C.page,type:'POST',data:data,timeout:30000}).done(function(t){t=String(t||'').replace(/^\\s+|\\s+\$/g,'');if(t===''){ok();}else{fail(t.replace(/<[^>]*>/g,' '));}}).fail(function(){fail(C.net);});}
function err(msg){var e=document.getElementById('ntpsv-add-err');var i=document.getElementById('ntpsv-new');if(e){e.textContent=msg||'';}if(i){i.setAttribute('aria-invalid',msg?'true':'false');}}
window.NtpsvAdd=function(){var i=document.getElementById('ntpsv-new');var b=document.getElementById('ntpsv-addb');if(!i||!b||b.disabled){return;}
var v=i.value.replace(/^\\s+|\\s+\$/g,'');if(v===''){i.focus();return;}
if(!/^[a-zA-Z0-9._:-]+\$/.test(v)||v.length>253){err(C.bad);i.focus();return;}
var names=document.querySelectorAll('#ntpd-servers .ntpsv-name');for(var k=0;k<names.length;k++){if(names[k].textContent.toLowerCase()===v.toLowerCase()){err(C.dup);i.focus();return;}}
err('');b.disabled=true;post({'new-server':v},function(){b.disabled=false;NtpsvReload();},function(m){b.disabled=false;err(m);});};
window.NtpsvAsk=function(btn,on){var it=btn.closest('.ntpsv-item');var a=it?it.querySelector('.ntpsv-ask'):null;if(!a){return;}
if(on){a.removeAttribute('hidden');var c=a.querySelector('.sec');if(c){c.focus();}}else{a.setAttribute('hidden','');var d=it.querySelector('.ntpsv-ib.del');if(d){d.focus();}}};
window.NtpsvDelete=function(name,btn){btn.disabled=true;post({'delete-server':name},NtpsvReload,function(m){btn.disabled=false;var s=btn.parentNode.querySelector('span');if(s){s.textContent=m;}});};
window.NtpsvMove=function(name,dir){post({'move-server':name,'direction':dir},NtpsvReload,function(m){alert(m);});};
if(window.NtpsvTimer){clearInterval(window.NtpsvTimer);}
window.NtpsvTimer=setInterval(function(){var h=document.getElementById('ntpd-servers');
if(!h||!document.body.contains(h)){clearInterval(window.NtpsvTimer);window.NtpsvTimer=null;return;}
if(h.offsetParent===null){return;}
if(h.querySelector('.ntpsv-ask:not([hidden])')){return;}
var i=document.getElementById('ntpsv-new');if(i&&(i.value!==''||document.activeElement===i)){return;}
if(document.querySelector('.sweet-alert.visible,.swal2-shown')){return;}
NtpsvReload();},30000);
})();</script>";
}

// ---------------------------------------------------------------- pages

function popup(){
	$tpl=new template_admin();
    $edit=ntpsv_can_edit();
    $html="<div class='ntpsv'>".ntpsv_intro()."<div id='ntpd-servers'></div>".ntpsv_footer($edit)."</div>";
    echo ntpsv_style().$tpl->_ENGINE_parse_body($html).ntpsv_js()
        ."<script>LoadAjaxSilent('ntpd-servers','".CurrentPageName()."?ntpd-table=yes');</script>";
}
// Reloadable part: mode, notices, list, add row.
function table(){
	$tpl=new template_admin();
    $m=ntpsv_model();
    echo $tpl->_ENGINE_parse_body(ntpsv_mode($m).ntpsv_notices($m).ntpsv_list($m))."<script>NoSpinner();</script>";
}

// ---------------------------------------------------------------- actions
// POST handlers: rights checked here (the buttons are only hidden otherwise), echo
// NOTHING on success, the error text otherwise. ntpd::* rewrites chrony.conf.

function ntpsv_post_name(string $key):string{
    return trim((string)(isset($_POST[$key])?$_POST[$key]:""));
}
function ntpsv_guard():bool{
    if(ntpsv_can_edit()){ return true; }
    echo ntpsv_t("ERROR_NO_PRIVS");
    return false;
}
function ntpsv_valid(string $name):bool{
    if($name==="" || strlen($name)>253 || !preg_match('/^[a-zA-Z0-9._:-]+$/',$name)){ echo ntpsv_t("ntpsv_add_bad"); return false; }
    return true;
}
function ntpsv_add_save(){
    if(!ntpsv_guard()){ return; }
    $name=ntpsv_post_name("new-server");
    if(!ntpsv_valid($name)){ return; }
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/servers"));
    if(is_object($json) && !empty($json->Status) && isset($json->Servers) && is_array($json->Servers)){
        foreach($json->Servers as $s){ if(strtolower(trim((string)(isset($s->Server)?$s->Server:"")))===strtolower($name)){ echo ntpsv_t("ntpsv_add_dup"); return; } }
    }
    ob_start(); $ntp=new ntpd(true); $ntp->AddServer($name); $err=trim(ob_get_clean());
    admin_tracks("NTP: add time server $name".($err!==""?" (failed: $err)":""));
    if($err!==""){ echo $err; }
}
function ntpsv_delete_save(){
    if(!ntpsv_guard()){ return; }
    $name=ntpsv_post_name("delete-server");
    if(!ntpsv_valid($name)){ return; }
    ob_start(); $ntp=new ntpd(true); $ntp->DeleteServer($name); $err=trim(ob_get_clean());
    admin_tracks("NTP: remove time server $name".($err!==""?" (failed: $err)":""));
    if($err!==""){ echo $err; }
}
function ntpsv_move_save(){
    if(!ntpsv_guard()){ return; }
    $name=ntpsv_post_name("move-server");
    $dir=ntpsv_post_name("direction");
    if(!ntpsv_valid($name)){ return; }
    if($dir!=="up" && $dir!=="down"){ echo "direction must be up or down"; return; }
    ob_start(); $ntp=new ntpd(true); $ntp->MoveServer($name,$dir); $err=trim(ob_get_clean());
    admin_tracks("NTP: move time server $name $dir".($err!==""?" (failed: $err)":""));
    if($err!==""){ echo $err; }
}
