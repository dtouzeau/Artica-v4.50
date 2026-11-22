<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Droits : lecture pour AsWebMaster/AsSystemWebMaster, ecritures (seuil, modeles, save) pour AsSystemWebMaster
// (aucun handler POST ne le verifiait).
$nboUsers=new usersMenus();
if(!$nboUsers->AsWebMaster && !$nboUsers->AsSystemWebMaster){exit();}
if(count($_POST)>0 && !$nboUsers->AsSystemWebMaster){$t=new template_admin();echo $t->javascript_parse_text("{ERROR_NO_PRIVS2}");exit;}
if(isset($_GET["LatencyEvents-popup"])){LatencyEvents_popup();exit;}
if(isset($_GET["LatencyEvents-js"])){LatencyEvents_js();exit;}
if(isset($_POST["LatencyEvents"])){LatencyEvents_save();exit;}
if(isset($_GET["template-js"])){template_error_js();exit;}
if(isset($_GET["template-popup"])){template_popup();exit;}
if(isset($_POST["template_error"])){template_save();exit;}
if(isset($_POST["service_id"])){save();exit;}
if(isset($_GET["main"])){page();exit;}

start();


function start():bool {
    $page       = CurrentPageName();
    $serviceid  = intval($_GET["service"]);
    echo "<div id='nginx-options-$serviceid'></div>
    <script>LoadAjaxSilent('nginx-options-$serviceid','$page?main=yes&service=$serviceid');</script>";
    return true;

}

function template_error_js():bool{
    $code=intval($_GET["code"]);
    $service_id=intval($_GET["serviceid"]);
    $tpl    = new template_admin();$tpl->CLUSTER_CLI=true;
    $page   = CurrentPageName();
    return $tpl->js_dialog7("{template} $code","$page?template-popup=$service_id&code=$code",1048);
}
function LatencyEvents_js():bool{
    $service_id=intval($_GET["LatencyEvents-js"]);
    $tpl    = new template_admin();$tpl->CLUSTER_CLI=true;
    $page   = CurrentPageName();
    return $tpl->js_dialog7("{monitoring}","$page?LatencyEvents-popup=$service_id",650);
}
function LatencyEvents_popup():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $service_id=intval($_GET["LatencyEvents-popup"]);
    if($service_id==0){
        echo $tpl->div_error("Wrong ID ???");
        return false;
    }
    $sock               = new socksngix($service_id);
    $LatencyEvents      = intval($sock->GET_INFO("LatencyEvents"));

    $page=CurrentPageName();
    echo $tpl->BigIntegerField("LatencyEvents|service_id:$service_id",
        "{Latency_events}","{Latency_events_reverse}",
        $LatencyEvents,"LoadAjaxSilent('nginx-options-$service_id','$page?main=yes&service=$service_id');");
    return true;
}
function LatencyEvents_save():bool{
    $service_id=$_POST["service_id"];
    $sock               = new socksngix($service_id);
    $LatencyEvents      = intval($sock->GET_INFO("LatencyEvents"));
    if($LatencyEvents==intval($_POST["LatencyEvents"])){
        return true;
    }
    $sock->SET_INFO("LatencyEvents",intval($_POST["LatencyEvents"]));

    if (intval($_POST["LatencyEvents"])>0) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/syslog/reload");
        return admin_tracks("Set monitoring reverse-Proxy latency to >{$_POST["LatencyEvents"]}ms");
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/syslog/reload");
    return  admin_tracks("Set monitoring reverse-Proxy latency to disabled");
}
function template_popup():bool{
    $serviceid  = intval($_GET["template-popup"]);
    $page=CurrentPageName();
    $error_code = intval($_GET["code"]);
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ngx_tpls   = new nginx_templates();
    $data       = $ngx_tpls->LoadTemplate($error_code,$serviceid);
    $data=str_replace("}","}\n",$data);
    $data=str_replace(".h5 small","\n\t.h5 small",$data);
    $data=str_replace("h4 small,","\n\t.h4 small,",$data);
    $data=str_replace(",blockquote.pull-right","\n\t,blockquote.pull-right",$data);
    $tpl->field_hidden("template_error",$error_code);
    $tpl->field_hidden("template_id",$serviceid);
    if($error_code==901){
        $form[]=$tpl->field_textarea("template_data",null,$data);
    }else{
        $form[]=$tpl->field_textareacode("template_data",null,$data);
    }

    echo $tpl->form_outside("{template} {error} $error_code", $form,"",
        "{apply}","LoadAjaxSilent('nginx-options-$serviceid','$page?main=yes&service=$serviceid');",
        "AsSystemWebMaster");
    return true;

}
function template_save():bool{
    $tpl        = new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();
    $code       = intval($_POST["template_error"]);
    $serviceid  = intval($_POST["template_id"]);
    $data       = $_POST["template_data"];
    $key        = "error_page.$code.".$serviceid;
    $sock       = new socksngix($serviceid);
    writelogs("Saving template key=$key data=".strlen($data),__FUNCTION__,__FILE__,__LINE__);
    $sock->SET_INFO($key,$data);
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
    return true;
}

// ---------- Onglet Options : cartes (maquette Stitch 2026-11-05) ----------
// Un modele (nbo_model) lu une fois : les trois listes de regles (header_rules,
// rewrite_rules, sub_filters), le seuil LatencyEvents et l'etat de chaque page d'erreur
// (personnalisee si error_page.<code>.<site> existe, sinon le modele Artica par defaut).

function nbo_h($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),$s);
}
function nbo_t($k,$vars=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$k."}");
    return str_replace(array_keys($vars),array_values($vars),$s);
}
function nbo_can_write():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemWebMaster;
}
function nbo_rules($sock,$key):array{
    $raw=base64_decode((string)$sock->GET_INFO($key));
    if($raw==""){return array();}
    $d=@unserialize($raw);
    return is_array($d)?$d:array();
}
// Ligne d'apercu d'une regle : sa description, sinon ce qu'elle fait.
function nbo_rule_line($kind,$r):string{
    $desc=trim((string)(isset($r["description"])?$r["description"]:""));
    if($desc<>""){return $desc;}
    if($kind=="headers"){
        $h=trim((string)(isset($r["header"])?$r["header"]:""));
        $v=trim((string)(isset($r["header_value"])?$r["header_value"]:""));
        return $v<>""?"$h: $v":$h;
    }
    $p=trim((string)(isset($r["pattern"])?$r["pattern"]:""));
    $rep=trim((string)(isset($r["replace"])?$r["replace"]:""));
    return "$p \xE2\x86\x92 $rep";
}
function nbo_model($serviceid):array{
    $sock=new socksngix($serviceid);
    $m=array("id"=>intval($serviceid),"can_write"=>nbo_can_write(),"sets"=>array(),"latency"=>intval($sock->GET_INFO("LatencyEvents")),"pages"=>array(),"custom"=>0);
    $defs=array(
        "headers"=>array("header_rules","fw.nginx.rules.headers.php","fa-solid fa-heading","nbo-i-blue","header_checks","nbo_headers_desc"),
        "rewrite"=>array("rewrite_rules","fw.nginx.rules.rewrite.php","fa-solid fa-route","nbo-i-violet","rewrite_rules","nbo_rewrite_desc"),
        "replace"=>array("sub_filters","fw.nginx.rules.replace.php","fa-solid fa-right-left","nbo-i-green","replace_rules","nbo_replace_desc"),
    );
    foreach($defs as $kind=>$d){
        $set=array("kind"=>$kind,"url"=>$d[1],"ico"=>$d[2],"tone"=>$d[3],"title"=>$d[4],"desc"=>$d[5],"total"=>0,"active"=>0,"preview"=>array());
        foreach(nbo_rules($sock,$d[0]) as $r){
            if(!is_array($r)){continue;}
            $on=intval(isset($r["enable"])?$r["enable"]:0)==1;
            $set["total"]++;
            if($on){$set["active"]++;}
            if(count($set["preview"])<3){$set["preview"][]=array("on"=>$on,"text"=>nbo_rule_line($kind,$r));}
        }
        $m["sets"][]=$set;
    }
    $names=array(403=>"Forbidden",404=>"Not Found",405=>"Method Not Allowed",496=>"SSL Certificate Required",400=>"Bad Request",
        500=>"Internal Server Error",502=>"Bad Gateway",503=>"Service Unavailable",504=>"Gateway Timeout");
    $ngx_tpls=new nginx_templates();
    foreach($ngx_tpls->valid_error_codes as $code){
        $custom=strlen(trim((string)$sock->GET_INFO("error_page.$code.".intval($serviceid))))>0;
        if($custom){$m["custom"]++;}
        $m["pages"][]=array("code"=>intval($code),"name"=>isset($names[$code])?$names[$code]:"","custom"=>$custom);
    }
    return $m;
}

function page():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $m=nbo_model(intval($_GET["service"]));
    $h=array();
    $h[]="<div class='nbo'>";
    $h[]=nbo_section("{content_replacement}","{nbo_content_sub}","");
    $h[]="<div class='nbo-cards'>";
    foreach($m["sets"] as $set){$h[]=nbo_rule_card($m,$set);}
    $h[]="</div>";
    $h[]=nbo_section("{monitoring}","{nbo_monitoring_sub}","");
    $h[]=nbo_latency_card($m);
    $h[]=nbo_section("{nbo_error_pages}","{nbo_error_pages_sub}","<span class='nbo-counter'>".nbo_t("nbo_n_customized",array("%s"=>$m["custom"],"%t"=>count($m["pages"])))."</span>");
    $h[]=nbo_pages_grid($m);
    $h[]="</div>";
    echo nbo_style().$tpl->_ENGINE_parse_body(implode("",$h));
    return true;
}
function nbo_section($title,$sub,$right):string{
    return "<div class='nbo-sec'><div><h3>$title</h3><p>$sub</p></div>$right</div>";
}
function nbo_rule_card($m,$s):string{
    $js="Loadjs('{$s["url"]}?service-js={$m["id"]}');";
    $state=$s["active"]>0?"<span class='nbo-pill nbo-p-on'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{nbo_active}</span>"
                         :"<span class='nbo-pill nbo-p-off'><i class='fa-solid fa-circle-pause' aria-hidden='true'></i>{nbo_inactive}</span>";
    $h=array();
    $h[]="<article class='nbo-card'>";
    $h[]="<div class='nbo-card-top'><span class='nbo-ico {$s["tone"]}'><i class='{$s["ico"]}' aria-hidden='true'></i></span>$state</div>";
    $h[]="<h4>{".$s["title"]."}</h4><p class='nbo-desc'>{".$s["desc"]."}</p>";
    $h[]="<div class='nbo-count'><strong>{$s["total"]}</strong><span>".($s["total"]==1?"{nbo_rule_configured}":"{nbo_rules_configured}")."</span></div>";
    if($s["total"]==0){
        $h[]="<div class='nbo-status'><i class='fa-solid fa-circle-info' aria-hidden='true'></i>{nbo_no_rule}</div>";
        $h[]="<div class='nbo-empty'><i class='{$s["ico"]}' aria-hidden='true'></i><span>{".$s["desc"]."}</span></div>";
    }else{
        $off=$s["total"]-$s["active"];
        $h[]="<div class='nbo-status'><span class='nbo-on'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>".nbo_t("nbo_n_active",array("%s"=>$s["active"]))."</span>"
            .($off>0?"<span class='nbo-dot' aria-hidden='true'>&middot;</span><span class='nbo-off'><i class='fa-solid fa-circle-pause' aria-hidden='true'></i>".nbo_t("nbo_n_disabled",array("%s"=>$off))."</span>":"")."</div>";
        $h[]="<ul class='nbo-prev'>";
        foreach($s["preview"] as $p){
            $sr=$p["on"]?"":"<span class='nbo-sr'>{nbo_disabled_sr}</span>";
            $h[]="<li class='".($p["on"]?"":"nbo-li-off")."'><span class='nbo-bul' aria-hidden='true'></span><span class='nbo-li-t' title=\"".nbo_h($p["text"])."\">".nbo_h($p["text"])."</span>$sr</li>";
        }
        $h[]="</ul>";
        if($s["total"]>3){$h[]="<button type='button' class='nbo-more' OnClick=\"$js\">".nbo_t("nbo_more",array("%s"=>$s["total"]-3))."</button>";}
    }
    if(!$m["can_write"]){
        $btn="<button type='button' class='nbo-btn nbo-ghost' OnClick=\"$js\"><i class='fa-solid fa-eye' aria-hidden='true'></i>{nbo_view_rules}</button>";
    }elseif($s["total"]==0){
        $btn="<button type='button' class='nbo-btn nbo-primary' OnClick=\"$js\"><i class='fa-solid fa-plus' aria-hidden='true'></i>{nbo_add_rule}</button>";
    }else{
        $btn="<button type='button' class='nbo-btn nbo-ghost' OnClick=\"$js\">{nbo_manage_rules}<i class='fa-solid fa-arrow-right' aria-hidden='true'></i></button>";
    }
    $h[]="<div class='nbo-card-foot'>$btn</div></article>";
    return implode("",$h);
}
function nbo_latency_card($m):string{
    $page=CurrentPageName();
    $on=$m["latency"]>0;
    $pill=$on?"<span class='nbo-pill nbo-p-on'><i class='fa-solid fa-circle-check' aria-hidden='true'></i>{nbo_active}</span>"
             :"<span class='nbo-pill nbo-p-off'><i class='fa-solid fa-circle-pause' aria-hidden='true'></i>{nbo_off}</span>";
    $val=$on?"<span class='nbo-caps'>{nbo_threshold}</span><span class='nbo-thr'>".number_format($m["latency"],0,".",",")." ms</span>"
            :"<span class='nbo-caps'>{nbo_threshold}</span><span class='nbo-thr nbo-thr-off'>{nbo_no_alert}</span>";
    $btn=$m["can_write"]?"<button type='button' class='nbo-btn nbo-ghost' OnClick=\"Loadjs('$page?LatencyEvents-js={$m["id"]}');\"><i class='fa-solid fa-sliders' aria-hidden='true'></i>{nbo_configure}</button>":"";
    return "<article class='nbo-wide'><span class='nbo-ico nbo-i-amber'><i class='fa-solid fa-gauge-high' aria-hidden='true'></i></span>"
        ."<div class='nbo-wide-mid'><div class='nbo-wide-t'><h4>{Latency_events}</h4>$pill</div><p class='nbo-desc'>{nbo_latency_desc}</p></div>"
        ."<div class='nbo-wide-val'>$val</div>$btn</article>";
}
function nbo_pages_grid($m):string{
    $page=CurrentPageName();
    $h=array();
    foreach($m["pages"] as $p){
        $code=$p["code"];
        $label=$code==901?"<i class='fa-solid fa-person-digging' aria-hidden='true'></i>":"<span class='nbo-code'>$code</span>";
        $name=$code==901?"{nbo_maintenance}":nbo_h($p["name"]);
        $aria=nbo_h(trim(html_entity_decode(nbo_t("nbo_edit_page"),ENT_QUOTES,"UTF-8"))." ".($code==901?trim(html_entity_decode(nbo_t("nbo_maintenance"),ENT_QUOTES,"UTF-8")):$code));
        $st=$p["custom"]?"<span class='nbo-pill nbo-p-custom'><i class='fa-solid fa-pen-ruler' aria-hidden='true'></i>{nbo_custom}</span>"
                        :"<span class='nbo-pill nbo-p-off'>{nbo_default}</span>";
        $h[]="<article class='nbo-page".($p["custom"]?" nbo-page-custom":"")."'><div class='nbo-page-top'>$label$st</div><p>$name</p>"
            ."<button type='button' class='nbo-edit' aria-label=\"$aria\" OnClick=\"Loadjs('$page?template-js=yes&code=$code&serviceid={$m["id"]}');\"><i class='fa-solid fa-code' aria-hidden='true'></i>{nbo_edit}</button></article>";
    }
    return "<div class='nbo-pages'>".implode("",$h)."</div>";
}

function nbo_style():string{
    $f=array();
    $f[]="<style>";
    $f[]=".nbo{display:flex;flex-direction:column;gap:16px;margin-top:12px;color:#0f172a;font-size:14px}";
    $f[]=".nbo *{box-sizing:border-box}";
    $f[]=".nbo-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}";
    $f[]=".nbo-sec{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-top:8px}";
    $f[]=".nbo-sec h3{margin:0;font-size:20px;font-weight:600;color:#0f172a}.nbo-sec p{margin:4px 0 0;font-size:14px;color:#64748b}";
    $f[]=".nbo-counter{padding:4px 12px;border-radius:999px;background:#f1f5f9;color:#334155;font-size:13px;font-weight:600;white-space:nowrap}";
    $f[]=".nbo-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}";
    $f[]=".nbo-card{display:flex;flex-direction:column;gap:10px;min-height:250px;padding:20px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);transition:box-shadow .15s}";
    $f[]=".nbo-card:hover,.nbo-wide:hover{box-shadow:0 6px 16px rgba(15,23,42,.10)}";
    $f[]=".nbo-card-top{display:flex;align-items:flex-start;justify-content:space-between}";
    $f[]=".nbo-ico{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;flex-shrink:0;border-radius:10px;font-size:20px}";
    $f[]=".nbo-i-blue{background:#e0f2fe;color:#0369a1}.nbo-i-violet{background:#ccddda;color:#005447}.nbo-i-green{background:#ecfdf5;color:#047857}.nbo-i-amber{background:#fffbeb;color:#b45309;border:1px solid #fde68a}";
    $f[]=".nbo-card h4,.nbo-wide h4{margin:0;font-size:16px;font-weight:600;color:#0f172a}";
    $f[]=".nbo-desc{margin:0;font-size:13px;color:#64748b;line-height:1.5}";
    $f[]=".nbo-count{display:flex;align-items:baseline;gap:8px}.nbo-count strong{font-size:32px;font-weight:700;line-height:1;font-variant-numeric:tabular-nums}.nbo-count span{font-size:14px;color:#475569}";
    $f[]=".nbo-status{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:13px;color:#64748b}";
    $f[]=".nbo-status span{display:inline-flex;align-items:center;gap:6px}";
    $f[]=".nbo-on{color:#047857;font-weight:600}.nbo-off{color:#64748b;font-weight:600}.nbo-dot{color:#cbd5e1}";
    $f[]=".nbo-prev{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px}";
    $f[]=".nbo-prev li{position:relative;display:flex;align-items:center;gap:10px;padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;font-family:monospace;font-size:13px;min-width:0}";
    $f[]=".nbo-li-t{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $f[]=".nbo-bul{width:8px;height:8px;flex-shrink:0;border-radius:50%;background:#047857}";
    $f[]=".nbo-li-off{color:#94a3b8}.nbo-li-off .nbo-bul{background:none;border:2px solid #94a3b8}";
    $f[]=".nbo-more{align-self:flex-start;padding:0;border:0;background:none;color:#0369a1;font-size:13px;font-weight:600;cursor:pointer}.nbo-more:hover{text-decoration:underline}";
    $f[]=".nbo-empty{display:flex;flex-direction:column;align-items:center;gap:8px;padding:18px;border:1px dashed #cbd5e1;border-radius:8px;background:#f8fafc;color:#64748b;font-size:13px;text-align:center}";
    $f[]=".nbo-empty i,.nbo-empty svg{font-size:22px;color:#cbd5e1}";
    $f[]=".nbo-card-foot{display:flex;justify-content:flex-end;margin-top:auto;padding-top:12px;border-top:1px solid #f1f5f9}";
    $f[]=".nbo-btn{display:inline-flex;align-items:center;gap:8px;height:36px;padding:0 16px;border-radius:8px;border:1px solid transparent;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap}";
    $f[]=".nbo-primary{background:#0369a1;border-color:#0369a1;color:#fff}.nbo-primary:hover{background:#075985}";
    $f[]=".nbo-ghost{background:#f1f5f9;border-color:#e2e8f0;color:#334155}.nbo-ghost:hover{background:#e2e8f0}";
    $f[]=".nbo-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;border:1px solid transparent;font-size:12px;font-weight:600;white-space:nowrap}";
    $f[]=".nbo-p-on{background:#ecfdf5;color:#047857;border-color:#a7f3d0}.nbo-p-off{background:#f1f5f9;color:#475569;border-color:#e2e8f0}.nbo-p-custom{background:#f2f6f6;color:#005447;border-color:#bfd4d1}";
    $f[]=".nbo-wide{display:flex;align-items:center;gap:20px;padding:20px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);transition:box-shadow .15s}";
    $f[]=".nbo-wide-mid{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px}";
    $f[]=".nbo-wide-t{display:flex;align-items:center;gap:10px}";
    $f[]=".nbo-wide-val{display:flex;flex-direction:column;align-items:flex-end;gap:2px}";
    $f[]=".nbo-caps{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b}";
    $f[]=".nbo-thr{font-family:monospace;font-size:20px;font-weight:700;white-space:nowrap}.nbo-thr-off{font-family:inherit;font-size:14px;font-weight:600;color:#475569}";
    $f[]=".nbo-pages{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:16px}";
    $f[]=".nbo-page{position:relative;display:flex;flex-direction:column;gap:6px;min-height:118px;padding:14px 14px 10px 18px;background:#fff;border:1px solid #e2e8f0;border-radius:10px}";
    $f[]=".nbo-page:before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;border-radius:10px 0 0 10px;background:#e2e8f0}";
    $f[]=".nbo-page-custom:before{background:#005447}";
    $f[]=".nbo-page-top{display:flex;align-items:center;justify-content:space-between;gap:8px;min-height:26px}";
    $f[]=".nbo-page-top>i,.nbo-page-top>svg{font-size:20px;color:#b45309}";
    $f[]=".nbo-code{font-family:monospace;font-size:22px;font-weight:700}";
    $f[]=".nbo-page p{margin:0;font-size:13px;color:#475569}";
    $f[]=".nbo-edit{align-self:flex-end;display:inline-flex;align-items:center;gap:6px;margin-top:auto;padding:4px 6px;border:0;border-radius:6px;background:none;color:#334155;font-size:13px;font-weight:600;cursor:pointer}";
    $f[]=".nbo-page-custom .nbo-edit{color:#005447}.nbo-edit:hover{background:#f1f5f9}";
    $f[]=".nbo button:focus-visible{outline:2px solid #0369a1;outline-offset:2px}";
    $f[]="@media (max-width:1199px){.nbo-cards{grid-template-columns:repeat(2,minmax(0,1fr))}.nbo-pages{grid-template-columns:repeat(4,minmax(0,1fr))}}";
    $f[]="@media (prefers-reduced-motion:reduce){.nbo *{transition:none!important}}";
    $f[]="</style>";
    return implode("",$f);
}

function save(){
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $tpl->CLEAN_POST();

    $KeyToRemove[]="hide_headers";
    $KeyToRemove[]="header_rules";
    $KeyToRemove[]="rewrite_rules";
    $KeyToRemove[]="replace_rules";
    $KeyToRemove[]="gzip_rules";

    foreach ($KeyToRemove as $supkey){
        unset($_POST[$supkey]);
    }

    $service_id=$_POST["service_id"];
    $sock=new socksngix($service_id);
    unset($_POST["service_id"]);
    foreach ($_POST as $key=>$val){
        $sock->SET_INFO($key,$val);
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$service_id");
}
