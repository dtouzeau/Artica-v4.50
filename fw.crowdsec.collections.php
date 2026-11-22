<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["collection-remove"])){collection_remove_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["collection-remove"])){collection_remove_perform();exit;}

table_start();

function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='crowdsec-collections'></div><script>LoadAjax('crowdsec-collections','$page?table=yes');</script>";
    return true;
}

/* ─────────────────────────────────────────────────────────────────────
 * Collections tab (maquette stitch_crowdsec_collections_redesign) — prefix csc_.
 * Data: CROWDSEC_COLLECTIONS = output of `cscli collections list -o json`,
 * refreshed by articarest /crowdsec/collection/list. One widget = one
 * function, fed by csc_model().
 * ---------------------------------------------------------------------*/

// Collections posees et retirees par Artica (crowdsec/Collections.go) : jamais supprimables ici.
function csc_managed():array{
    return array(
        "crowdsecurity/sshd"=>array("kind"=>"builtin","label"=>"{csc_name_sshd}","icon"=>"fas fa-terminal"),
        "crowdsecurity/linux"=>array("kind"=>"builtin","label"=>"{csc_name_linux}","icon"=>"fab fa-linux"),
        "crowdsecurity/suricata"=>array("kind"=>"ids","label"=>"{csc_name_suricata}","icon"=>"fas fa-shield-virus"),
        "crowdsecurity/nginx"=>array("kind"=>"reverse","label"=>"{csc_name_nginx}","icon"=>"fas fa-globe"),
        "crowdsecurity/base-http-scenarios"=>array("kind"=>"reverse","label"=>"{csc_name_basehttp}","icon"=>"fas fa-globe"),
        "crowdsecurity/http-cve"=>array("kind"=>"reverse","label"=>"{csc_name_httpcve}","icon"=>"fas fa-globe"),
    );
}

function csc_can_write():bool{
    $users=new usersMenus();
    return $users->AsFirewallManager || $users->AsSystemAdministrator;
}

function csc_esc($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

function csc_t($tpl,$token,$args=array()):string{
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

function csc_valid_name($name):bool{
    return preg_match('#^[A-Za-z0-9_.\-]+/[A-Za-z0-9_.\-]+$#',$name)==1;
}

function csc_accent():string{
    $accent="#005447";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
    }
    return $accent;
}

function csc_decode($raw){
    $raw=trim((string)$raw);
    if(strlen($raw)<2){return null;}
    $j=json_decode($raw,true);
    if(!is_array($j)){
        // Ancien stockage en base64.
        $j=json_decode((string)base64_decode($raw,true),true);
    }
    return is_array($j)?$j:null;
}

function csc_model($refresh):array{
    $m=array("error"=>"","rows"=>array(),"count"=>0,"managed"=>0,"updates"=>0,"read"=>false);
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_COLLECTIONS");
    if($refresh || strlen(trim($raw))<10){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/collection/list");
        $raw=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_COLLECTIONS");
    }
    $m["error"]=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_COLLECTIONS_ERROR"));
    $j=csc_decode($raw);
    if(is_array($j) && isset($j["collections"]) && is_array($j["collections"])){
        $m["read"]=true;
        $managed=csc_managed();
        foreach($j["collections"] as $c){
            if(!is_array($c) || !isset($c["name"])){continue;}
            $name=(string)$c["name"];
            $st=strtolower((string)(isset($c["status"])?$c["status"]:""));
            $row=array(
                "name"=>$name,
                "version"=>(string)(isset($c["local_version"])?$c["local_version"]:""),
                "path"=>(string)(isset($c["local_path"])?$c["local_path"]:""),
                "description"=>trim((string)(isset($c["description"])?$c["description"]:"")),
                "active"=>strpos($st,"enabled")!==false,
                "update"=>strpos($st,"update-available")!==false,
                "tainted"=>strpos($st,"tainted")!==false,
                "local"=>strpos($st,"local")!==false && strpos($st,"tainted")===false,
                "managed"=>isset($managed[$name])?$managed[$name]:null,
            );
            if($row["managed"]!==null){$m["managed"]++;}
            if($row["update"]){$m["updates"]++;}
            $m["rows"][]=$row;
        }
        // Collections d'Artica d'abord, puis par nom.
        $order=array_flip(array_keys($managed));
        usort($m["rows"],function($a,$b) use($order){
            $ka=isset($order[$a["name"]])?$order[$a["name"]]:1000;
            $kb=isset($order[$b["name"]])?$order[$b["name"]]:1000;
            if($ka!=$kb){return $ka-$kb;}
            return strcmp($a["name"],$b["name"]);
        });
        $m["count"]=count($m["rows"]);
    }elseif(strlen(trim($raw))>=2 && $m["error"]===""){
        $m["error"]=json_last_error_msg();
    }
    return $m;
}

/* ── Widgets ────────────────────────────────────────────────────────── */

function csc_banner($tpl,array $m):string{
    $chips=array();
    if($m["read"]){
        $chips[]="<span class='csc-chip'><i class='fas fa-layer-group'></i>".csc_t($tpl,"csc_chip_count",array(intval($m["count"])))."</span>";
        if($m["managed"]>0){
            $chips[]="<span class='csc-chip'><i class='fas fa-lock'></i>".csc_t($tpl,"csc_chip_managed",array(intval($m["managed"])))."</span>";
        }
    }
    $chips[]="<span class='csc-chip'><i class='fas fa-rotate'></i>{csc_chip_follow}</span>";
    if($m["updates"]>0){
        $chips[]="<span class='csc-chip csc-chip-warn'><i class='fas fa-circle-exclamation'></i>".csc_t($tpl,"csc_chip_updates",array(intval($m["updates"])))."</span>";
    }
    return "<div class='csc-card csc-banner'>"
        ."<div class='csc-bico' aria-hidden='true'><i class='fas fa-shield-halved'></i></div>"
        ."<div class='csc-btxt'><h2 class='csc-btitle'>{csc_banner_title}</h2>"
        ."<p class='csc-bdesc'>{csc_banner_text}</p>"
        ."<div class='csc-chips'>".implode("",$chips)."</div></div></div>";
}

function csc_refresh_button():string{
    $page=CurrentPageName();
    return "<button type='button' class='csc-btn' OnClick=\"LoadAjax('crowdsec-collections','$page?table=yes&refresh=1')\"><i class='fas fa-rotate' aria-hidden='true'></i> {csc_refresh}</button>";
}

function csc_alert(array $m):string{
    if($m["error"]===""){return "";}
    return "<div class='csc-card csc-alert' role='alert'><span class='csc-aico'><i class='fas fa-triangle-exclamation'></i></span>"
        ."<div class='csc-atxt'><div class='csc-atitle'>{csc_err_title}</div><div class='csc-sub'>{csc_err_text}</div>"
        ."<div class='csc-mono csc-adetail'>".csc_esc($m["error"])."</div></div>".csc_refresh_button()."</div>";
}

function csc_badge($cls,$icon,$label,$tip):string{
    return "<span class='csc-badge csc-badge-$cls' tabindex='0' title='$tip'><i class='fas $icon' aria-hidden='true'></i>$label</span>";
}

function csc_card_badges(array $r):string{
    $b=array();
    if($r["active"]){
        $b[]=csc_badge("ok","fa-circle-check","{csc_b_active}","{csc_b_active_tip}");
    }else{
        $b[]=csc_badge("off","fa-circle-pause","{csc_b_inactive}","{csc_b_inactive_tip}");
    }
    if($r["update"]){$b[]=csc_badge("warn","fa-arrow-up-right-dots","{csc_b_update}","{csc_b_update_tip}");}
    if($r["tainted"]){$b[]=csc_badge("warn","fa-pen","{csc_b_tainted}","{csc_b_tainted_tip}");}
    if($r["local"]){$b[]=csc_badge("grey","fa-file-pen","{csc_b_local}","{csc_b_local_tip}");}
    $mg=$r["managed"];
    if($mg!==null){
        if($mg["kind"]=="builtin"){$b[]=csc_badge("grey","fa-lock","{csc_b_builtin}","{csc_b_builtin_tip}");}
        if($mg["kind"]=="reverse"){$b[]=csc_badge("grey","fa-link","{csc_b_reverse}","{csc_b_reverse_tip}");}
        if($mg["kind"]=="ids"){$b[]=csc_badge("grey","fa-link","{csc_b_ids}","{csc_b_ids_tip}");}
    }
    return "<div class='csc-badges'>".implode("",$b)."</div>";
}

function csc_card_action(array $r,$canWrite):string{
    if($r["managed"]!==null || !$canWrite || !csc_valid_name($r["name"])){return "";}
    $page=CurrentPageName();
    $n=csc_esc($r["name"]);
    $enc=urlencode($r["name"]);
    return "<button type='button' class='csc-del' OnClick=\"Loadjs('$page?collection-remove=$enc')\" aria-label='{csc_remove}: $n'><i class='fas fa-trash-can' aria-hidden='true'></i> {csc_remove}</button>";
}

function csc_card($tpl,array $r,$canWrite):string{
    $mg=$r["managed"];
    $icon=$mg!==null?$mg["icon"]:"fas fa-puzzle-piece";
    $tech=csc_esc($r["name"]);
    $title=$mg!==null?$mg["label"]:$tech;
    $pathTip=$r["path"]!==""?csc_t($tpl,"csc_path_tip",array(csc_esc($r["path"]))):"";
    $ver=$r["version"]!==""?"<span class='csc-ver'>v".csc_esc($r["version"])."</span>":"";
    $desc=$r["description"]!==""?csc_esc($r["description"]):"<span class='csc-none'>{csc_no_desc}</span>";
    return "<article class='csc-card csc-item'>"
        ."<div class='csc-top'><span class='csc-ico' aria-hidden='true'><i class='$icon'></i></span>"
        ."<div class='csc-names'><h3 class='csc-name'>$title</h3><span class='csc-tech' tabindex='0' title='$pathTip'>$tech</span></div>$ver</div>"
        ."<p class='csc-desc' title='".csc_esc($r["description"])."'>$desc</p>"
        ."<div class='csc-bottom'>".csc_card_badges($r).csc_card_action($r,$canWrite)."</div>"
        ."</article>";
}

function csc_empty():string{
    return "<div class='csc-card csc-empty'><div class='csc-eico'><i class='fas fa-layer-group' aria-hidden='true'></i></div>"
        ."<h3>{csc_empty_title}</h3><p>{csc_empty_text}</p>".csc_refresh_button()."</div>";
}

function csc_footer():string{
    return "<div class='csc-foot'><span class='csc-sub'><i class='fas fa-circle-info' aria-hidden='true'></i> {csc_foot}</span>".csc_refresh_button()."</div>";
}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=csc_model(isset($_GET["refresh"]));
    $canWrite=csc_can_write();

    $html=array();
    $html[]="<div class='csc'>";
    $html[]=csc_banner($tpl,$m);
    $html[]=csc_alert($m);
    if($m["count"]==0){
        // Sans lecture reussie, l'alerte suffit : "aucune collection" serait faux.
        if($m["read"] || $m["error"]===""){$html[]=csc_empty();}
    }else{
        $html[]="<div class='csc-grid'>";
        foreach($m["rows"] as $r){$html[]=csc_card($tpl,$r,$canWrite);}
        $html[]="</div>";
        $html[]=csc_footer();
    }
    $html[]="</div>";

    $topbuttons=array();
    $topbuttons[]=array("LoadAjax('crowdsec-collections','$page?table=yes&refresh=1')",ico_refresh,"{csc_refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{collections}";
    $TINY_ARRAY["ICO"]=ico_books;
    $TINY_ARRAY["EXPL"]="{csc_explain}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo csc_style().$tpl->_ENGINE_parse_body(implode("\n",$html))."<script>NoSpinner();$tiny</script>";
    return true;
}

/* Remove: confirmation with the consequence, then POST collection-remove. */
function collection_remove_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $name=(string)$_GET["collection-remove"];
    if(!csc_valid_name($name)){return false;}
    $text=csc_t($tpl,"csc_remove_confirm",array(csc_esc($name)));
    return $tpl->js_dialog_confirm_action($text,"collection-remove",$name,"LoadAjax('crowdsec-collections','$page?table=yes');");
}

function collection_remove_perform():bool{
    $tpl=new template_admin();
    if(!csc_can_write()){
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
        return false;
    }
    $name=trim((string)$_POST["collection-remove"]);
    $managed=csc_managed();
    if(!csc_valid_name($name) || isset($managed[$name])){
        echo $tpl->_ENGINE_parse_body("{csc_remove_refused}");
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/collection/delete/".urlencode($name)));
    if(!is_object($json) || empty($json->Status)){
        echo htmlspecialchars(is_object($json) && isset($json->Error)?(string)$json->Error:"CrowdSec did not answer");
        return false;
    }
    return admin_tracks("Remove CrowdSec collection $name");
}

function csc_style():string{
    $a=csc_accent();
    return "<style>
.csc{display:flex;flex-direction:column;gap:16px;margin-top:10px;color:#111827;font-size:14px}
.csc-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);min-width:0}
.csc-banner{display:flex;gap:18px;align-items:flex-start;padding:20px 24px;border-left:4px solid $a}
.csc-bico{flex:0 0 48px;height:48px;border-radius:12px;background:#e6eeed;color:$a;display:flex;align-items:center;justify-content:center;font-size:20px}
.csc-btxt{min-width:0}
.csc-btitle{margin:0 0 6px 0;font-size:18px;font-weight:600;color:#111827;line-height:1.3}
.csc-bdesc{margin:0;color:#374151;font-size:14px;line-height:1.55;max-width:1000px}
.csc-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.csc-chip{display:inline-flex;align-items:center;gap:7px;background:#f2f6f6;border:1px solid #e1ebe9;color:#1f2937;border-radius:999px;padding:4px 12px;font-size:13px;white-space:nowrap}
.csc-chip i,.csc-chip svg{color:$a;font-size:13px}
.csc-chip-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.csc-chip-warn i,.csc-chip-warn svg{color:#d97706}
.csc-alert{display:flex;gap:14px;align-items:flex-start;padding:16px 20px;background:#fef2f2;border-color:#fecaca}
.csc-aico{color:#dc2626;font-size:18px;margin-top:2px}
.csc-atxt{flex:1;min-width:0}
.csc-atitle{font-size:15px;font-weight:600;color:#991b1b}
.csc-adetail{margin-top:8px;padding:6px 10px;background:#fff;border:1px solid #fecaca;border-radius:6px;color:#7f1d1d;overflow-wrap:anywhere}
.csc-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px}
.csc-sub{font-size:13px;color:#6b7280}
.csc-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.csc-item{display:flex;flex-direction:column;padding:18px 20px;transition:transform .15s ease,box-shadow .15s ease}
.csc-item:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.06)}
.csc-top{display:flex;gap:12px;align-items:flex-start}
.csc-ico{flex:0 0 40px;height:40px;border-radius:10px;background:#e6eeed;color:$a;display:flex;align-items:center;justify-content:center;font-size:17px}
.csc-names{flex:1;min-width:0}
.csc-name{margin:0;font-size:15px;font-weight:600;color:#111827;line-height:1.3;overflow-wrap:anywhere}
.csc-tech{display:inline-block;margin-top:3px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#6b7280;border-bottom:1px dotted #9ca3af;overflow-wrap:anywhere}
.csc-ver{flex:0 0 auto;font-size:13px;color:#374151;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;padding:2px 8px}
.csc-desc{margin:12px 0 14px;color:#374151;font-size:14px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;flex:1}
.csc-none{color:#9ca3af}
.csc-bottom{display:flex;gap:10px;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid #f1f3f5;flex-wrap:wrap}
.csc-badges{display:flex;gap:6px;flex-wrap:wrap}
.csc-badge{display:inline-flex;gap:6px;align-items:center;font-size:13px;font-weight:500;border-radius:6px;padding:3px 9px;border:1px solid;white-space:nowrap;cursor:default}
.csc-badge-ok{background:#f0fdf4;border-color:#bbf7d0;color:#15803d}
.csc-badge-warn{background:#fffbeb;border-color:#fde68a;color:#b45309}
.csc-badge-grey{background:#f3f4f6;border-color:#e5e7eb;color:#374151}
.csc-badge-off{background:#f3f4f6;border-color:#d1d5db;color:#4b5563}
.csc-del{border:0;background:transparent;color:#dc2626;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;gap:6px;align-items:center;padding:5px 8px;border-radius:6px}
.csc-del:hover{background:#fef2f2}
.csc-btn{display:inline-flex;gap:7px;align-items:center;border:1px solid #d1d5db;background:#fff;color:#374151;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.csc-btn:hover{background:#f3f4f6}
.csc-btn:focus-visible,.csc-del:focus-visible,.csc-badge:focus-visible,.csc-tech:focus-visible{outline:2px solid $a;outline-offset:2px}
.csc-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-top:12px;border-top:1px solid #e5e7eb}
.csc-empty{text-align:center;padding:48px 24px}
.csc-eico{width:64px;height:64px;margin:0 auto 14px;border-radius:16px;background:#e6eeed;color:$a;display:flex;align-items:center;justify-content:center;font-size:28px}
.csc-empty h3{margin:0 0 6px;font-size:18px;font-weight:600}
.csc-empty p{margin:0 auto 16px;max-width:560px;color:#4b5563;line-height:1.55}
@media (prefers-reduced-motion: reduce){.csc-item{transition:none}.csc-item:hover{transform:none}}
@media (max-width:1200px){.csc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>";
}
