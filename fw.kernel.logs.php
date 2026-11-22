<?php
// Journal du noyau (/var/log/kern.log), lu et decode par le daemon :
// GET /system/kernel/events (articarest kernlog/). Remplace framework/system.php
// searchlogs_kernel() (tail/grep vers un fichier) et l'analyse PHP qui exigeait
// l'horodatage noyau « [ 123.456] » : les lignes audit/IPv4 qui n'en ont pas
// s'affichaient en <H1> avec la regex (192.168.1.140, 2026-11-11).
// Maquette Stitch « system kernel log console » (2026-11-21), donnees reelles :
// les tuiles comptent les entrees AFFICHEES, la repartition vient de `subsystems`
// (lignes examinees), `partial` dit que seule la fin du fichier a ete lue.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}

if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["kev-host"])){kev_host();exit;}
// Ancien search_block (navigateur reste ouvert avant une mise a jour) : l'hote actuel.
if(isset($_GET["search"])){kev_host();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{events}: {system_kernel}",ico_eye,"{kernel_infos_text}",
        "$page?build-page=yes","kernel-events","progress-kernel-events",false,"table-kernel-events");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{events}: {system_kernel}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// ---------------------------------------------------------------- model

function kev_limits():array{return array(200,500,1000,2000);}
// Parametres : URL d'abord, sinon les choix memorises en session ; toujours en liste blanche.
function kev_params():array{
    $level=isset($_GET["level"])?(string)$_GET["level"]:(string)($_SESSION["KERNEL_LEVEL"] ?? "");
    if(!in_array($level,array("","warning","error"))){$level="";}
    $hide=isset($_GET["hide_audit"])?intval($_GET["hide_audit"]):intval($_SESSION["KERNEL_HIDE_AUDIT"] ?? 0);
    $hide=$hide==1?1:0;
    $limit=isset($_GET["limit"])?intval($_GET["limit"]):intval($_SESSION["KERNEL_LIMIT"] ?? 200);
    $search=trim((string)($_GET["q"] ?? ($_GET["search"] ?? "")));
    // Ancienne syntaxe « limit 200 » encore memorisee par les navigateurs.
    if(preg_match('#\blimit\s+([0-9]+)#i',$search,$re)){
        $limit=intval($re[1]);
        $search=trim(preg_replace('#\blimit\s+[0-9]+#i','',$search));
    }
    if(!in_array($limit,kev_limits())){
        $best=200;
        foreach(kev_limits() as $l){if($limit>=$l){$best=$l;}}
        $limit=$best;
    }
    if(function_exists("mb_substr")){$search=mb_substr($search,0,200);}else{$search=substr($search,0,200);}
    return array("level"=>$level,"hide"=>$hide,"limit"=>$limit,"q"=>$search);
}
function kev_remember(array $p){
    $_SESSION["KERNEL_LEVEL"]=$p["level"];
    $_SESSION["KERNEL_HIDE_AUDIT"]=$p["hide"];
    $_SESSION["KERNEL_LIMIT"]=$p["limit"];
}
function kev_model(array $p):array{
    $m=array("p"=>$p,"error"=>"","entries"=>array(),"file_size"=>0,"scanned"=>0,"matched"=>0,"partial"=>false,
        "subs"=>array(),"audit"=>0,"n_error"=>0,"n_warning"=>0);
    $q="/system/kernel/events?limit={$p["limit"]}&hide_audit={$p["hide"]}&level={$p["level"]}&search=".urlencode($p["q"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($q));
    if(!$json || !property_exists($json,"success")){
        $m["error"]="articarest: /system/kernel/events";
        return $m;
    }
    if(!$json->success){
        $m["error"]=(string)($json->error ?? "");
        return $m;
    }
    $d=$json->data;
    $m["file_size"]=floatval($d->file_size ?? 0);
    $m["scanned"]=intval($d->scanned ?? 0);
    $m["matched"]=intval($d->matched ?? 0);
    $m["partial"]=!empty($d->partial);
    if(isset($d->subsystems) && (is_object($d->subsystems) || is_array($d->subsystems))){
        foreach($d->subsystems as $k=>$v){
            $k=(string)$k;$v=intval($v);
            if($k=="audit" || $k=="kauditd_printk_skb"){$m["audit"]+=$v;}
            $key=$k=="kauditd_printk_skb"?"audit":$k;
            $m["subs"][$key]=($m["subs"][$key] ?? 0)+$v;
        }
        arsort($m["subs"]);
    }
    foreach((isset($d->entries) && is_array($d->entries))?$d->entries:array() as $e){
        if(!is_object($e)){continue;}
        $lv=(string)($e->level ?? "info");
        if(!in_array($lv,array("error","warning","info"))){$lv="info";}
        if($lv=="error"){$m["n_error"]++;}
        if($lv=="warning"){$m["n_warning"]++;}
        $m["entries"][]=array("time"=>intval($e->time ?? 0),"host"=>(string)($e->host ?? ""),"source"=>(string)($e->source ?? ""),
            "uptime"=>(string)($e->uptime ?? ""),"sub"=>(string)($e->subsystem ?? ""),"level"=>$lv,
            "message"=>(string)($e->message ?? ""),"summary"=>(string)($e->summary ?? ""),
            "atype"=>(string)($e->audit_type ?? ""),"raw"=>(string)($e->raw ?? ""));
    }
    return $m;
}

// ---------------------------------------------------------------- formats

function kev_t(string $token,array $vals=array()):string{
    $tpl=new template_admin();
    $txt=html_entity_decode($tpl->_ENGINE_parse_body("{".$token."}"),ENT_QUOTES,"UTF-8");
    $txt=trim(preg_replace('#\s*<br\s*/?>\s*#i',' ',str_replace("[br]","<br>",$txt)));
    foreach($vals as $v){$p=strpos($txt,"%s");if($p===false){break;}$txt=substr_replace($txt,(string)$v,$p,2);}
    return $txt;
}
function kev_h(string $token,array $vals=array()):string{return kev_e(kev_t($token,$vals));}
// Valeur venue du journal : HTML ET accolades (le moteur prendrait {x} pour un token).
function kev_e($v):string{return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));}
function kev_num($n):string{return number_format(floatval($n),0,"."," ");}
function kev_size($b):string{
    $b=floatval($b);
    if($b>=1073741824){return sprintf("%.1f GB",$b/1073741824);}
    if($b>=1048576){return sprintf("%.1f MB",$b/1048576);}
    if($b>=1024){return sprintf("%.0f KB",$b/1024);}
    return sprintf("%d B",$b);
}
function kev_ago(int $ts):string{
    $s=max(0,time()-$ts);
    if($s<60){return kev_t("kev_just_now");}
    if($s<3600){return kev_t("kev_ago_m",array(intval(floor($s/60))));}
    if($s<172800){return kev_t("kev_ago_h",array(intval(floor($s/3600))));}
    return kev_t("kev_ago_d",array(intval(floor($s/86400))));
}
// Nom courant d'une source du noyau (sous-titre gris) ; "" si inconnue.
// Le daemon garde le prefixe complet (« e1000e 0000:00:1f.6 eth0 », « usb 1-1 ») : on lit le premier mot.
function kev_source_label(string $sub):string{
    $full=strtolower(trim($sub));
    if(strpos($full,"out of memory")===0){return kev_t("kev_src_oom");}
    $parts=preg_split('/[\s:]+/',$full);
    $s=$parts[0];
    if($s=="audit"){return kev_t("kev_src_audit");}
    if($s=="ipv4" || $s=="ipv6" || $s=="tcp" || $s=="udp" || strpos($s,"nf_conntrack")===0 || strpos($s,"netfilter")===0){return kev_t("kev_src_net");}
    if(preg_match('/^(e1000e?|igb|igc|ixgbe|i40e|ice|r8169|tg3|bnx2x?|virtio_net|vmxnet3|mlx[45]_core|atlantic|be2net)$/',$s)){return kev_t("kev_src_nic");}
    if(preg_match('/^(ext[234]-fs|xfs|btrfs|jbd2|f2fs)/',$s)){return kev_t("kev_src_fs");}
    if(preg_match('/^(ata\d*|sd|scsi|nvme|blk_update_request|md|raid\d*|device-mapper)/',$s)){return kev_t("kev_src_disk");}
    if(strpos($s,"oom")===0){return kev_t("kev_src_oom");}
    if(strpos($s,"usb")===0){return kev_t("kev_src_usb");}
    if($s=="acpi"){return kev_t("kev_src_acpi");}
    return "";
}

// ---------------------------------------------------------------- widgets

function kev_banner(array $m):string{
    $h=array("<section class='kev-card kev-banner'><div class='kev-bico' aria-hidden='true'><i class='fas fa-microchip'></i></div><div class='kev-btxt'>");
    $h[]="<h2>{kev_title} <small>{kev_title_tech}</small></h2><p>{kev_text}</p><div class='kev-chips'>";
    if($m["file_size"]>0){ $h[]="<span class='kev-chip'><i class='fas fa-file-lines' aria-hidden='true'></i>".kev_h("kev_chip_size",array(kev_size($m["file_size"])))."</span>"; }
    $read=$m["partial"]?kev_h("kev_chip_partial",array(kev_num($m["scanned"]))):"{kev_chip_whole}";
    $h[]="<span class='kev-chip'><i class='fas fa-eye' aria-hidden='true'></i>$read</span>";
    $h[]="<span class='kev-chip'><i class='fas fa-lock' aria-hidden='true'></i>{kev_chip_ro}</span>";
    $h[]="</div></div></section>";
    return implode("",$h);
}
function kev_tile_level(array $m,string $lv):string{
    $n=$lv=="error"?$m["n_error"]:$m["n_warning"];
    $shown=count($m["entries"]);
    $ico=$lv=="error"?"fa-circle-xmark":"fa-triangle-exclamation";
    $title=$lv=="error"?"{kev_t_errors}":"{kev_t_warnings}";
    $sub=$lv=="error"?"{kev_t_errors_sub}":"{kev_t_warnings_sub}";
    $target=$lv=="error"?"error":"warning";
    $aria=kev_h($lv=="error"?"kev_t_errors_aria":"kev_t_warnings_aria");
    $btn=($m["p"]["level"]==$target)?"":"<button type='button' class='kev-tlink' aria-label=\"$aria\" OnClick=\"KevLevel('$target');\">".($lv=="error"?"{kev_filter_errors}":"{kev_filter_warnings}")." &rarr;</button>";
    return "<div class='kev-card kev-tile $lv'><div class='kev-tl'><span>$title</span><i class='fas $ico' aria-hidden='true'></i></div>"
        ."<div class='kev-tv'><b>".kev_num($n)."</b><span>$sub</span></div>"
        ."<div class='kev-tf'><span>".kev_h("kev_t_in_shown",array(kev_num($shown)))."</span>$btn</div></div>";
}
function kev_tile_sources(array $m):string{
    $h=array("<div class='kev-card kev-tile kev-srcs'><div class='kev-tl'><span>{kev_t_sources}</span><small>{kev_t_sources_sub}</small></div>");
    if(count($m["subs"])==0){
        $h[]="<p class='kev-none'>&mdash;</p></div>";
        return implode("",$h);
    }
    $top=array_slice($m["subs"],0,5,true);
    $max=max(1,reset($top));
    $h[]="<ul>";
    foreach($top as $k=>$n){
        $name=$k===""?kev_t("kev_src_none"):$k;
        $lbl=$k===""?"":kev_source_label($k);
        $w=max(2,round(100*$n/$max));
        $q=$k===""?"":$k;
        $aria=kev_h("kev_src_aria",array($name,kev_num($n)));
        $h[]="<li><button type='button' data-q=\"".kev_e($q)."\" aria-label=\"$aria\"".($q===""?" disabled":"")." OnClick=\"KevSearch(this.getAttribute('data-q'));\">"
            ."<span class='l'><span><b>".kev_e($name)."</b>".($lbl!==""?" ".kev_e("(".$lbl.")"):"")."</span><em>".kev_num($n)."</em></span>"
            ."<span class='t'><i style='width:$w%'></i></span></button></li>";
    }
    $h[]="</ul></div>";
    return implode("",$h);
}
function kev_tiles(array $m):string{
    return "<section class='kev-tiles'>".kev_tile_level($m,"error").kev_tile_level($m,"warning").kev_tile_sources($m)."</section>";
}
// Seulement si l'audit domine les lignes lues et que le filtre n'est pas deja pose.
function kev_audit_notice(array $m):string{
    if($m["p"]["hide"]==1 || $m["scanned"]<200 || $m["audit"]*2<=$m["scanned"]){return "";}
    $pct=intval(round($m["audit"]*100/$m["scanned"]));
    return "<section class='kev-notice'><i class='fas fa-shield-halved' aria-hidden='true'></i><div><b>".kev_h("kev_audit_title",array($pct,kev_num($m["audit"])))."</b><p>{kev_audit_text}</p></div>"
        ."<button type='button' class='kev-btn warn' OnClick=\"KevHideAudit(true);\"><i class='fas fa-eye-slash' aria-hidden='true'></i>{kernel_hide_audit}</button></section>";
}
function kev_filters(array $p):string{
    $seg=array(""=>"{kev_lv_all}","warning"=>"{kernel_warnings_errors}","error"=>"{kev_lv_errors}");
    $h=array("<section class='kev-card kev-filters'><div class='kev-frow'>");
    $h[]="<div class='kev-seg' role='radiogroup' aria-label=\"".kev_h("kev_lv_label")."\">";
    foreach($seg as $k=>$lbl){
        $on=$p["level"]===$k;
        $h[]="<button type='button' role='radio' data-l='$k' aria-checked='".($on?"true":"false")."' class='kev-sbtn".($on?" on":"")."' OnClick=\"KevLevel('$k');\">$lbl</button>";
    }
    $h[]="</div><div class='kev-swl'><button type='button' class='kev-sw' role='switch' id='kev-hide' aria-checked='".($p["hide"]?"true":"false")."' aria-labelledby='kev-hide-l' aria-describedby='kev-hide-d' OnClick=\"KevHideAudit(this.getAttribute('aria-checked')!=='true');\"><i></i></button>"
        ."<div><span id='kev-hide-l'>{kernel_hide_audit}</span><small id='kev-hide-d'>{kev_hide_help}</small></div></div></div>";
    $ph=kev_h("kev_search_ph");
    $opts="";
    foreach(kev_limits() as $l){$opts.="<option value='$l'".($l==$p["limit"]?" selected":"").">".kev_h("kev_n_entries",array(kev_num($l)))."</option>";}
    $h[]="<div class='kev-frow'><div class='kev-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='kev-q' value=\"".kev_e($p["q"])."\" placeholder=\"$ph\" aria-label=\"$ph\" autocomplete='off' spellcheck='false'>"
        ."<button type='button' class='kev-clr' aria-label=\"".kev_h("kev_clear")."\" title=\"".kev_h("kev_clear")."\" OnClick=\"KevSearch('');\"><i class='fas fa-xmark'></i></button></div>"
        ."<label class='kev-show' for='kev-limit'>{kev_show}</label><select id='kev-limit' aria-describedby='kev-limit-d' OnChange=\"KevLimit(this.value);\">$opts</select>"
        ."<small class='kev-hint' id='kev-limit-d'>{kev_limit_help}</small>"
        ."<button type='button' class='kev-ib' aria-label=\"".kev_h("kev_refresh")."\" title=\"".kev_h("kev_refresh")."\" OnClick=\"KevReload();\"><i class='fas fa-rotate'></i></button></div>";
    $ex="";
    foreach(array("eth0","oom","DENIED","link is down") as $e){$ex.="<button type='button' OnClick=\"KevSearch('".kev_e($e)."');\">".kev_e($e)."</button>";}
    $h[]="<div class='kev-ex'><span>{kev_examples}</span>$ex<small>{kev_wildcard}</small></div></section>";
    return implode("",$h);
}
function kev_level_pill(string $lv):string{
    if($lv=="error"){return "<span class='kev-lv error'><i class='fas fa-circle-xmark' aria-hidden='true'></i>{kev_lv_error}</span>";}
    if($lv=="warning"){return "<span class='kev-lv warning'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>{kev_lv_warning}</span>";}
    return "<span class='kev-lv info'><i class='fas fa-circle-info' aria-hidden='true'></i>{kev_lv_info}</span>";
}
function kev_row(array $e,int $i):string{
    $did="kev-d-$i";
    if($e["time"]>0){
        $when="<b title='".date("Y-m-d H:i:s",$e["time"])."'>".kev_e(kev_ago($e["time"]))."</b><span>".kev_e(date("Y-m-d H:i:s",$e["time"]))."</span>";
    }else{
        $when="<b class='unk'>{kev_time_unknown}</b>";
    }
    if($e["uptime"]!==""){$when.="<span class='mono'>".kev_h("kev_uptime",array(kev_e($e["uptime"])))."</span>";}
    $isKernel=($e["source"]==="" || strtolower($e["source"])==="kernel");
    $name=$e["sub"]!==""?$e["sub"]:($isKernel?kev_t("kev_src_kernel"):$e["source"]);
    $lbl=$e["sub"]!==""?kev_source_label($e["sub"]):($isKernel?"":kev_t("kev_src_program"));
    $src="<b>".kev_e($name)."</b>".($lbl!==""?"<span>".kev_e($lbl)."</span>":"");
    $what=$e["summary"]!==""?"<b>".kev_e($e["summary"])."</b>":kev_e($e["message"]);
    $det="<tr class='kev-det' id='$did' hidden><td colspan='5'><dl>"
        .($e["host"]!==""?"<div><dt>{kev_d_host}</dt><dd>".kev_e($e["host"])."</dd></div>":"")
        ."<div><dt>{kev_d_source}</dt><dd>".kev_e($e["source"]!==""?$e["source"]:"kernel")."</dd></div>"
        .($e["atype"]!==""?"<div><dt>{kev_d_atype}</dt><dd><span class='kev-chipm'>".kev_e($e["atype"])."</span></dd></div>":"")
        .($e["time"]>0?"<div><dt>{kev_d_time}</dt><dd>".date("Y-m-d H:i:s",$e["time"])."</dd></div>":"")
        ."</dl><div class='kev-raw'><pre>".kev_e($e["raw"]!==""?$e["raw"]:$e["message"])."</pre>"
        ."<button type='button' class='kev-copy'><span>{kev_copy}</span></button></div></td></tr>";
    return "<tr class='kev-row' role='button' tabindex='0' aria-expanded='false' aria-controls='$did' data-lv='{$e["level"]}'>"
        ."<td class='kev-when'>$when</td><td>".kev_level_pill($e["level"])."</td><td class='kev-src'>$src</td>"
        ."<td class='kev-what'><div class='kev-wt'>$what</div></td><td class='kev-chev' aria-hidden='true'><b></b></td></tr>".$det;
}
function kev_empty(array $m):string{
    $p=$m["p"];
    if($p["q"]!==""){
        $t=kev_h("kev_e_search",array($p["q"]));
    }elseif($p["level"]=="error"){
        $t="{kev_e_errors}";
    }elseif($p["level"]=="warning"){
        $t="{kev_e_warnings}";
    }else{
        $t="{kev_e_none}";
    }
    $clear=($p["q"]!=="" || $p["level"]!=="" || $p["hide"]==1)?"<button type='button' class='kev-btn' OnClick=\"KevClearAll();\">{kev_clear_all}</button>":"";
    $ok=($p["level"]!=="" && $p["q"]==="")?" ok":"";
    return "<div class='kev-empty$ok'><i class='fas ".($ok?"fa-circle-check":"fa-magnifying-glass")."' aria-hidden='true'></i><b>$t</b><p>".($m["partial"]?"{kev_e_partial}":"{kev_e_text}")."</p>$clear</div>";
}
function kev_journal(array $m):string{
    $n=count($m["entries"]);
    $h=array("<section class='kev-card kev-journal'>");
    if($n==0){
        $h[]=kev_empty($m)."</section>";
        return implode("",$h);
    }
    $h[]="<div class='kev-scroll'><table class='kev-tbl'><thead><tr><th scope='col'>{kev_c_when}</th><th scope='col'>{kev_c_level}</th><th scope='col'>{kev_c_source}</th><th scope='col'>{kev_c_what}</th><th scope='col'><span class='kev-sr'>{kev_d_title}</span></th></tr></thead><tbody id='kev-rows'>";
    foreach($m["entries"] as $i=>$e){$h[]=kev_row($e,$i);}
    $h[]="</tbody></table></div>";
    $foot=kev_h("kev_showing",array(kev_num($n),kev_num($m["matched"])));
    $part=$m["partial"]?"<span class='kev-part'><i class='fas fa-circle-info' aria-hidden='true'></i>{kev_partial_note}</span>":"";
    $h[]="<footer class='kev-foot'><span id='kev-showing' data-total='".kev_e($foot)."'>$foot</span>$part<nav class='kev-pg' id='kev-pager' aria-label=\"".kev_h("kev_pager")."\"></nav></footer></section>";
    return implode("",$h);
}
function kev_error(string $err):string{
    return "<section class='kev-card kev-journal'><div class='kev-empty err'><i class='fas fa-plug-circle-xmark' aria-hidden='true'></i><b>{kev_err_title}</b><p class='mono'>".kev_e($err)."</p>"
        ."<button type='button' class='kev-btn' OnClick=\"KevReload();\">{kev_retry}</button></div></section>";
}

function kev_style():string{
    return "<style>
.kev{margin:10px 0 20px 0;color:#1f2937;font-size:14px}
.kev *{box-sizing:border-box}
.kev .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.kev-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);margin-bottom:16px}
.kev-banner{display:flex;gap:18px;align-items:flex-start;padding:20px 24px;border-left:4px solid #005447}
.kev-bico{flex:0 0 44px;height:44px;border-radius:50%;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:19px}
.kev-btxt h2{margin:0 0 6px 0;font-size:20px;font-weight:600;color:#111827}
.kev-btxt h2 small{font-size:13px;font-weight:400;color:#6b7280}
.kev-btxt p{margin:0;font-size:14px;color:#4b5563;line-height:1.55;max-width:960px}
.kev-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.kev-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f2f6f6;color:#005447;font-size:13px;border:1px solid #e6eeed}
.kev-tiles{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1.3fr);gap:16px;margin-bottom:16px}
.kev-tile{padding:16px 20px;margin-bottom:0;display:flex;flex-direction:column;min-width:0}
.kev-tl{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#4b5563}
.kev-tl small{font-size:13px;font-weight:400;color:#6b7280}
.kev-tile.error .kev-tl i,.kev-tile.error .kev-tl svg{color:#b91c1c;font-size:18px}
.kev-tile.warning .kev-tl i,.kev-tile.warning .kev-tl svg{color:#b45309;font-size:18px}
.kev-tv{display:flex;align-items:baseline;gap:10px;margin:10px 0 4px 0}
.kev-tv b{font-size:32px;font-weight:700;line-height:1}
.kev-tile.error .kev-tv b{color:#b91c1c}.kev-tile.warning .kev-tv b{color:#b45309}
.kev-tv span{font-size:13px;color:#4b5563}
.kev-tf{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:auto;padding-top:8px;font-size:13px;color:#6b7280}
.kev-tlink{border:0;background:transparent;color:#005447;font-weight:600;font-size:13px;cursor:pointer;padding:2px 4px;border-radius:6px}
.kev-tlink:hover{background:#e6eeed}
.kev-srcs ul{list-style:none;margin:8px 0 0 0;padding:0;display:flex;flex-direction:column;gap:2px}
.kev-srcs button{width:100%;border:0;background:transparent;padding:3px 4px;border-radius:6px;cursor:pointer;text-align:left}
.kev-srcs button:hover:not(:disabled){background:#f2f6f6}
.kev-srcs button:disabled{cursor:default}
.kev-srcs .l{display:flex;justify-content:space-between;gap:8px;font-size:13px;color:#4b5563}
.kev-srcs .l>span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.kev-srcs .l b{color:#111827;font-weight:600}
.kev-srcs .l em{font-style:normal;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#374151}
.kev-srcs .t{display:block;height:5px;border-radius:3px;background:#f1f5f9;margin-top:3px;overflow:hidden}
.kev-srcs .t i{display:block;height:100%;background:#80aaa3;border-radius:3px}
.kev-srcs li:first-child .t i{background:#005447}
.kev-none{margin:12px 0 0 0;color:#6b7280}
.kev-notice{display:flex;gap:14px;align-items:center;padding:14px 18px;border-radius:12px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;margin-bottom:16px}
.kev-notice>svg,.kev-notice>i{font-size:18px;flex:0 0 auto}
.kev-notice div{flex:1 1 auto;min-width:0}
.kev-notice b{font-size:14px}
.kev-notice p{margin:4px 0 0 0;font-size:13px;line-height:1.5}
.kev-filters{padding:14px 16px}
.kev-frow{display:flex;flex-wrap:wrap;align-items:center;gap:12px 16px}
.kev-frow+.kev-frow{margin-top:10px}
.kev-seg{display:inline-flex;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:9px;padding:3px;gap:2px}
.kev-sbtn{border:0;background:transparent;color:#374151;font-size:13px;font-weight:600;padding:6px 14px;border-radius:7px;cursor:pointer;white-space:nowrap}
.kev-sbtn.on{background:#005447;color:#fff}
.kev-swl{display:flex;gap:10px;align-items:flex-start}
.kev-swl span{display:block;font-size:14px;font-weight:600;color:#1f2937}
.kev-swl small{display:block;font-size:13px;color:#6b7280}
.kev-sw{position:relative;flex:0 0 44px;width:44px;height:24px;border-radius:12px;border:0;background:#d1d5db;cursor:pointer;padding:0;margin-top:2px}
.kev-sw i{position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;transition:left .15s}
.kev-sw[aria-checked='true']{background:#005447}
.kev-sw[aria-checked='true'] i{left:23px}
.kev-search{position:relative;flex:0 1 380px;min-width:240px}
.kev-search>svg,.kev-search>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:13px}
.kev-search input{width:100%;height:36px;border:1px solid #d1d5db;border-radius:8px;padding:0 36px;font-size:14px;color:#111827}
.kev-search input::-webkit-search-cancel-button{display:none}
.kev-clr{position:absolute;right:4px;top:4px;width:28px;height:28px;border:0;background:transparent;color:#6b7280;border-radius:6px;cursor:pointer}
.kev-show{margin:0;font-size:13px;font-weight:600;color:#4b5563}
.kev-filters select{height:36px;border:1px solid #d1d5db;border-radius:8px;padding:0 8px;font-size:13px;color:#111827;background:#fff}
.kev-hint{font-size:13px;color:#6b7280}
.kev-ib{width:36px;height:36px;border:1px solid #d1d5db;background:#fff;color:#374151;border-radius:8px;cursor:pointer}
.kev-ib:hover,.kev-clr:hover{background:#e6eeed;color:#005447}
.kev-ex{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:10px;font-size:13px;color:#6b7280}
.kev-ex button{border:1px solid #e5e7eb;background:#f9fafb;border-radius:6px;padding:2px 8px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#1f2937;cursor:pointer}
.kev-ex button:hover{background:#e6eeed;border-color:#bfd4d1;color:#005447}
.kev-ex small{font-size:13px;color:#9ca3af;margin-left:4px}
.kev-search input:focus,.kev-filters select:focus{outline:2px solid #005447;outline-offset:1px;border-color:#005447}
.kev-sbtn:focus-visible,.kev-sw:focus-visible,.kev-ib:focus-visible,.kev-clr:focus-visible,.kev-ex button:focus-visible,.kev-tlink:focus-visible,.kev-srcs button:focus-visible,.kev-btn:focus-visible,.kev-row:focus-visible,.kev-copy:focus-visible,.kev-pg button:focus-visible{outline:2px solid #005447;outline-offset:2px}
.kev-journal{overflow:hidden}
.kev-scroll{overflow-x:auto}
.kev-tbl{width:100%;border-collapse:collapse;font-size:14px}
.kev-tbl th{text-align:left;font-size:13px;font-weight:600;color:#4b5563;padding:12px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;white-space:nowrap}
.kev-tbl td{padding:10px 16px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.kev-row{cursor:pointer}
.kev-row:hover td{background:#fafbfb}
.kev-when{width:1%;white-space:nowrap}
.kev-when b{display:block;font-size:14px;color:#111827}
.kev-when b.unk{color:#6b7280;font-weight:600}
.kev-when span{display:block;font-size:13px;color:#6b7280}
.kev-lv{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:6px;font-size:13px;font-weight:600;white-space:nowrap;border:1px solid transparent}
.kev-lv.error{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.kev-lv.warning{background:#fffbeb;color:#92400e;border-color:#fde68a}
.kev-lv.info{background:#f3f4f6;color:#374151;border-color:#e5e7eb}
.kev-tbl td:nth-child(2){width:1%}
.kev-src{width:200px;min-width:170px;max-width:240px}
.kev-src b{display:block;font-weight:600;color:#111827;overflow:hidden;text-overflow:ellipsis}
.kev-src span{display:block;font-size:13px;color:#6b7280;line-height:1.35}
.kev-what{color:#1f2937;line-height:1.5;word-break:break-word}
.kev-wt{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.kev-row[aria-expanded='true'] .kev-wt{display:block}
.kev-what b{font-weight:600}
.kev-chev{width:28px;color:#9ca3af;text-align:center}
.kev-chev b{display:inline-block;width:8px;height:8px;border-right:2px solid #9ca3af;border-bottom:2px solid #9ca3af;transform:rotate(45deg);margin-top:4px;transition:transform .15s}
.kev-row[aria-expanded='true'] .kev-chev b{transform:rotate(-135deg);margin-top:8px}
.kev-det td{background:#f8fafc;padding:10px 16px 14px 16px;border-top:1px dashed #e2e8f0}
.kev-det[hidden]{display:none}
.kev-det dl{display:flex;flex-wrap:wrap;gap:6px 24px;margin:0 0 8px 0;font-size:13px}
.kev-det dl div{display:flex;gap:6px}
.kev-det dt{color:#6b7280;font-weight:400}
.kev-det dd{margin:0;color:#111827;font-weight:600}
.kev-chipm{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;padding:1px 6px;border-radius:4px;background:#e6eeed;color:#005447}
.kev-raw{display:flex;gap:10px;align-items:flex-start}
.kev-raw pre{flex:1 1 auto;margin:0;white-space:pre-wrap;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#1e293b;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:8px 10px}
.kev-copy{flex:0 0 auto;display:inline-flex;align-items:center;gap:6px;border:1px solid #d1d5db;background:#fff;color:#374151;border-radius:8px;padding:6px 10px;font-size:13px;cursor:pointer}
.kev-copy:hover{background:#e6eeed;color:#005447}
.kev-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;padding:12px 16px;border-top:1px solid #e5e7eb;font-size:13px;color:#4b5563}
.kev-part{display:flex;gap:6px;align-items:center;color:#6b7280;font-style:italic}
.kev-pg{display:flex;gap:6px}
.kev-pg button{min-width:34px;height:32px;padding:0 10px;border:1px solid #e5e7eb;background:#fff;border-radius:8px;font-size:13px;color:#111827;cursor:pointer}
.kev-pg button.on{background:#005447;border-color:#005447;color:#fff;font-weight:600}
.kev-pg button:disabled{color:#9ca3af;cursor:default;background:#f9fafb}
.kev-empty{padding:40px 24px;text-align:center;max-width:620px;margin:0 auto;color:#4b5563}
.kev-empty>svg,.kev-empty>i{font-size:26px;color:#6b7280;display:block;margin:0 auto 10px auto}
.kev-empty.ok>svg,.kev-empty.ok>i{color:#005447}
.kev-empty.err>svg,.kev-empty.err>i{color:#b91c1c}
.kev-empty b{display:block;font-size:16px;color:#111827;margin-bottom:6px;word-break:break-word}
.kev-empty p{margin:0 0 14px 0;font-size:14px;line-height:1.5}
.kev-btn{display:inline-flex;align-items:center;gap:8px;height:36px;border:1px solid #bfd4d1;border-radius:8px;background:#fff;color:#005447;font-weight:600;font-size:14px;padding:0 16px;cursor:pointer;white-space:nowrap}
.kev-btn:hover{background:#e6eeed}
.kev-btn.warn{background:#92400e;border-color:#92400e;color:#fff}
.kev-btn.warn:hover{background:#78350f}
.kev-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
@media (max-width:1200px){.kev-tiles{grid-template-columns:minmax(0,1fr)}.kev-hint{display:none}}
@media (prefers-reduced-motion:reduce){.kev-chev b,.kev-sw i{transition:none}}
</style>";
}

function kev_js(array $p):string{
    $page=CurrentPageName();
    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $st=json_encode(array("level"=>$p["level"],"hide"=>$p["hide"],"limit"=>$p["limit"]),$J);
    $tx=json_encode(array("prev"=>kev_t("kev_prev"),"next"=>kev_t("kev_next"),"page"=>kev_t("kev_page"),"copied"=>kev_t("kev_copied")),$J);
    return "<script>NoSpinner();(function(){var P=".json_encode($page,$J).",T=$tx;
window.KevS=$st;
function el(id){return document.getElementById(id);}
function url(){var q=el('kev-q');return P+'?kev-host=yes&level='+encodeURIComponent(KevS.level)+'&hide_audit='+KevS.hide+'&limit='+KevS.limit+'&q='+encodeURIComponent(q?q.value:'');}
window.KevReload=function(){LoadAjaxSilent('kev-host',url());};
window.KevLevel=function(l){KevS.level=l;var b=document.querySelectorAll('.kev-sbtn');for(var i=0;i<b.length;i++){var on=b[i].getAttribute('data-l')===l;b[i].className='kev-sbtn'+(on?' on':'');b[i].setAttribute('aria-checked',on?'true':'false');}KevReload();};
window.KevHideAudit=function(on){KevS.hide=on?1:0;var s=el('kev-hide');if(s){s.setAttribute('aria-checked',on?'true':'false');}KevReload();};
window.KevLimit=function(v){KevS.limit=parseInt(v,10)||200;KevReload();};
window.KevSearch=function(t){var q=el('kev-q');if(q){q.value=t;}KevReload();};
window.KevClearAll=function(){var q=el('kev-q');if(q){q.value='';}KevS.hide=0;var s=el('kev-hide');if(s){s.setAttribute('aria-checked','false');}KevLevel('');};
// Le haut (bandeau, tuiles, avis) arrive avec le journal : on le place au-dessus des filtres.
window.KevAfter=function(){var src=el('kev-top-src'),dst=el('kev-top');if(src&&dst){dst.innerHTML='';while(src.firstChild){dst.appendChild(src.firstChild);}src.parentNode.removeChild(src);}
 var tb=el('kev-rows');if(!tb){return;}pager(tb,50);
 if(!tb.__kev){tb.__kev=1;
  tb.addEventListener('click',function(e){var c=e.target.closest?e.target.closest('.kev-copy'):null;if(c){copy(c);return;}var tr=e.target.closest?e.target.closest('tr.kev-row'):null;if(tr){toggle(tr);}});
  tb.addEventListener('keydown',function(e){if(e.keyCode!=13&&e.keyCode!=32){return;}var tr=e.target;if(tr&&tr.className.indexOf('kev-row')>=0){e.preventDefault();toggle(tr);}});}};
function toggle(tr){var d=el(tr.getAttribute('aria-controls'));var o=tr.getAttribute('aria-expanded')!=='true';tr.setAttribute('aria-expanded',o?'true':'false');if(d){d.hidden=!o;}}
function copy(b){var pre=b.parentNode.querySelector('pre');if(!pre){return;}var txt=pre.textContent,lab=b.querySelector('span'),old=lab?lab.textContent:'';
 var done=function(){if(lab){lab.textContent=T.copied;setTimeout(function(){lab.textContent=old;},1500);}};
 function fb(){var t=document.createElement('textarea');t.value=txt;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();try{document.execCommand('copy');done();}catch(e){}document.body.removeChild(t);}
 if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(txt).then(done,fb);}else{fb();}}
function pager(tb,size){var rows=[].slice.call(tb.querySelectorAll('tr.kev-row')),n=rows.length,pages=Math.max(1,Math.ceil(n/size)),box=el('kev-pager');
 function draw(cur){for(var i=0;i<n;i++){var vis=(i>=(cur-1)*size&&i<cur*size);rows[i].style.display=vis?'':'none';var d=el(rows[i].getAttribute('aria-controls'));if(d){d.style.display=vis?'':'none';}}
  if(!box){return;}box.innerHTML='';if(pages<2){return;}
  function btn(l,p,dis,on){var x=document.createElement('button');x.type='button';x.textContent=l;if(dis){x.disabled=true;}if(on){x.className='on';x.setAttribute('aria-current','page');}x.onclick=function(){draw(p);};box.appendChild(x);}
  btn(T.prev,cur-1,cur==1,false);var end=Math.min(pages,cur+2),start=Math.max(1,end-4);end=Math.min(pages,start+4);for(var p=start;p<=end;p++){btn(String(p),p,false,p==cur);}btn(T.next,cur+1,cur==pages,false);}
 draw(1);}
var q=el('kev-q');if(q&&!q.__kev){q.__kev=1;q.addEventListener('input',function(){clearTimeout(window.KevDeb);window.KevDeb=setTimeout(KevReload,600);});
 q.addEventListener('keydown',function(e){if(e.keyCode==13){e.preventDefault();clearTimeout(window.KevDeb);KevReload();}});}
KevReload();
})();</script>";
}

// ---------------------------------------------------------------- pages

function build_page(){
    $tpl=new template_admin();
    $p=kev_params();
    $html="<div class='kev'><div id='kev-top'></div>".kev_filters($p)."<div id='kev-host'></div></div>";
    $TINY=array("TITLE"=>"{events}: {system_kernel}","ICO"=>ico_eye,"EXPL"=>"{kev_tiny}","URL"=>"kernel-events","BUTTONS"=>"");
    echo kev_style().$tpl->_ENGINE_parse_body($html).kev_js($p)
        ."<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
}
function kev_host(){
    $tpl=new template_admin();
    $p=kev_params();
    kev_remember($p);
    $m=kev_model($p);
    if($m["error"]!==""){
        echo $tpl->_ENGINE_parse_body("<div id='kev-top-src'></div>".kev_error($m["error"]))."<script>NoSpinner();if(window.KevAfter){KevAfter();}</script>";
        return;
    }
    $top="<div id='kev-top-src'>".kev_banner($m).kev_tiles($m).kev_audit_notice($m)."</div>";
    echo $tpl->_ENGINE_parse_body($top.kev_journal($m))."<script>NoSpinner();if(window.KevAfter){KevAfter();}</script>";
}
