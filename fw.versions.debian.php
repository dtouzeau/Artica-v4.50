<?php
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsSystemAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
page();

// Onglet "Operating system" du centre des paquets : inventaire dpkg en lecture
// seule. Une synthese, une barre de filtres et la table des paquets, filtree et
// paginee dans le navigateur (~750 lignes legeres, sans script par ligne).
function page(){
    $tpl=new template_admin();
    $m=debpkg_model();
    $L=debpkg_labels($tpl);
    $html=array();
    $html[]="<div class='dp-page' id='dp-page'>";
    $html[]=debpkg_summary($m);
    $html[]=debpkg_toolbar($m);
    $html[]="<div class='dp-card'>";
    $chrome=@implode("\n",$html);
    // Les lignes ne passent PAS par le moteur : une description de paquet qui
    // contiendrait des accolades serait prise pour un jeton.
    echo debpkg_style()
        .$tpl->_ENGINE_parse_body($chrome)
        .debpkg_table($m,$L)
        ."</div></div>"
        .debpkg_js($L);
}

function debpkg_model():array{
    $db="/home/artica/SQLITE/dpkg.db";
    $m=array("rows"=>array(),"error"=>"","collected"=>0,"total"=>0,
        "updates"=>0,"security"=>0,"held"=>0,"removed"=>0,"other"=>0,"archs"=>array());

    // CheckPackages() supprime et recree dpkg.db a chaque collecte (toutes les
    // 48 h) : la date du fichier est l'heure du releve.
    if(!is_file($db)){return $m;}
    $m["collected"]=intval(@filemtime($db));

    $q=new lib_sqlite($db);
    $results=$q->QUERY_SQL("SELECT package_name,package_version,package_info,package_status FROM debian_packages ORDER BY package_name");
    if(!$q->ok){
        $m["error"]=(string)$q->mysql_error;
        return $m;
    }
    if(!is_array($results)){$results=array();}

    // Mises a jour en attente : ecrites par PrintUpgrade() dans le meme cycle.
    $apt=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MAIN_APT_GET_JSON"),true);
    $cand=array();
    $orig=array();
    if(is_array($apt)){
        if(isset($apt["candidate_versions"]) && is_array($apt["candidate_versions"])){$cand=$apt["candidate_versions"];}
        if(isset($apt["origins"]) && is_array($apt["origins"])){$orig=$apt["origins"];}
    }

    foreach($results as $ligne){
        $r=debpkg_row_model($ligne,$cand,$orig);
        $m["rows"][]=$r;
        $m["total"]++;
        if($r["candidate"]!==""){$m["updates"]++;}
        if($r["security"]){$m["security"]++;}
        if($r["state"]=="held"){$m["held"]++;}
        if($r["state"]=="removed"){$m["removed"]++;}
        if($r["state"]=="other"){$m["other"]++;}
        if($r["arch"]!==""){$m["archs"][$r["arch"]]=true;}
    }
    ksort($m["archs"]);
    return $m;
}

function debpkg_row_model($ligne,$cand,$orig):array{
    $r=array();
    $r["name"]=trim((string)$ligne["package_name"]);
    $r["version"]=trim((string)$ligne["package_version"]);
    $r["code"]=trim((string)$ligne["package_status"]);

    // La regex du demon n'a que 4 groupes pour les 5 colonnes de "dpkg -l" :
    // package_info porte "amd64        description". On separe les deux.
    $info=trim((string)$ligne["package_info"]);
    $r["arch"]="";
    $r["desc"]=$info;
    if(preg_match('#^([a-z0-9]+)\s+(.*)$#s',$info,$re) && preg_match('#^(all|any|amd64|i386|arm64|armhf|armel|ppc64el|s390x|mips64el|riscv64)$#',$re[1])){
        $r["arch"]=$re[1];
        $r["desc"]=trim($re[2]);
    }

    // ii installe, hi retenu par apt-mark hold, rc desinstalle (configuration
    // gardee) ; tout autre code est montre tel quel.
    $states=array("ii"=>"installed","hi"=>"held","rc"=>"removed");
    $r["state"]=isset($states[$r["code"]])?$states[$r["code"]]:"other";

    // dpkg -l suffixe parfois l'architecture (libc6:amd64) ; apt ne le fait pas.
    $key=$r["name"];
    if(!isset($cand[$key])){$key=preg_replace('#:[a-z0-9]+$#',"",$r["name"]);}
    $r["candidate"]="";
    $r["security"]=false;
    if(isset($cand[$key]) && trim((string)$cand[$key])!=="" && trim((string)$cand[$key])!==$r["version"]){
        $r["candidate"]=trim((string)$cand[$key]);
        // Meme regle que le demon (aptOriginIsSecurity).
        if(isset($orig[$key]) && stripos((string)$orig[$key],"security")!==false){$r["security"]=true;}
    }
    return $r;
}

// Libelles des lignes et du JS, traduits une fois. Le JS les recoit decodes :
// les accents sont des entites dans la base de traduction.
function debpkg_labels($tpl):array{
    $keys=array("installed"=>"debpkg_installed","held"=>"debpkg_held","removed"=>"debpkg_removed",
        "other"=>"debpkg_other","security"=>"debpkg_security","candidate"=>"debpkg_candidate_title",
        "showing"=>"debpkg_showing","of"=>"debpkg_of","packages"=>"debpkg_packages_lc",
        "prev"=>"debpkg_prev","next"=>"debpkg_next","pager"=>"debpkg_pager_label");
    $L=array();
    foreach($keys as $k=>$t){$L[$k]=$tpl->_ENGINE_parse_body("{".$t."}");}
    return $L;
}

function debpkg_summary($m):string{
    $collected="&mdash;";
    if($m["collected"]>0){$collected=date("Y-m-d H:i",$m["collected"]);}
    $h=array();
    $h[]="<section class='dp-sum' aria-label='{debpkg_summary_label}'>";
    $h[]=debpkg_sum_cell("fas fa-boxes-stacked","{debpkg_packages}",debpkg_sum_value($m,$m["total"]),"",
        "<i class='far fa-clock' aria-hidden='true'></i> {debpkg_collected} $collected");
    $h[]=debpkg_sum_updates($m);
    $h[]=debpkg_sum_cell("fas fa-thumbtack","{debpkg_held}",debpkg_sum_value($m,$m["held"]),$m["held"]>0?"dp-v-warn":"","{debpkg_held_note}");
    $h[]=debpkg_sum_cell("fas fa-trash-can","{debpkg_removed}",debpkg_sum_value($m,$m["removed"]),"","{debpkg_removed_note}");
    $h[]="</section>";
    return @implode("\n",$h);
}

// Sans inventaire lisible, un compteur n'a rien mesure : un tiret et non 0.
function debpkg_sum_value($m,$value):string{
    if($m["total"]==0 && ($m["error"]!=="" || $m["collected"]==0)){return "&mdash;";}
    return (string)intval($value);
}

function debpkg_sum_cell($ico,$label,$value,$vcls,$sub):string{
    return "<div class='dp-sum-c'><div class='dp-sum-l'><i class='$ico' aria-hidden='true'></i> $label</div>"
        ."<div class='dp-sum-b'><span class='dp-sum-v $vcls'>$value</span>"
        ."<span class='dp-sum-s'>$sub</span></div></div>";
}

function debpkg_sum_updates($m):string{
    $sub="";
    if($m["security"]>0){
        $sub="<span class='dp-sec-t'><i class='fas fa-shield-halved' aria-hidden='true'></i> {debpkg_security_count} ".intval($m["security"])."</span>";
    }
    if($m["updates"]>0){
        $sub.="<a class='dp-link' href='/os-update'>{debpkg_os_update} <i class='fas fa-arrow-up-right-from-square' aria-hidden='true'></i></a>";
    }
    return "<div class='dp-sum-c'><div class='dp-sum-l'><i class='fas fa-circle-arrow-up' aria-hidden='true'></i> {debpkg_updates_av}</div>"
        ."<div class='dp-sum-b'><span class='dp-sum-v ".($m["updates"]>0?"dp-v-warn":"")."'>".debpkg_sum_value($m,$m["updates"])."</span>"
        ."<span class='dp-sum-s dp-sum-s2'>$sub</span></div></div>";
}

function debpkg_toolbar($m):string{
    $h=array();
    $h[]="<section class='dp-bar'>";
    $h[]="<div class='dp-chips' id='dp-chips' role='radiogroup' aria-label='{debpkg_filter_label}'>";
    $h[]=debpkg_chip("all","{debpkg_all}",$m["total"],true);
    $h[]=debpkg_chip("updates","{debpkg_f_updates}",$m["updates"],false);
    $h[]=debpkg_chip("held","{debpkg_held}",$m["held"],false);
    $h[]=debpkg_chip("removed","{debpkg_removed}",$m["removed"],false);
    $h[]=debpkg_chip("other","{debpkg_other}",$m["other"],false);
    $h[]="</div>";
    $h[]="<div class='dp-bar-r'>";
    $h[]=debpkg_arch_select($m);
    $h[]="<div class='dp-search'><span class='dp-search-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>"
        ."<input type='text' id='dp-q' placeholder='{debpkg_search_ph}' aria-label='{debpkg_search_ph}' autocomplete='off' oninput='DpApply(true)'>"
        ."<button type='button' class='dp-q-x' id='dp-q-x' aria-label='{debpkg_clear_search}' OnClick='DpClearSearch()'>"
        ."<i class='fas fa-xmark' aria-hidden='true'></i></button></div>";
    $h[]="</div></section>";
    return @implode("\n",$h);
}

function debpkg_chip($f,$label,$count,$always):string{
    $dis=(!$always && intval($count)==0)?" aria-disabled='true' disabled":"";
    return "<button type='button' class='dp-chip' role='radio' aria-checked='false' tabindex='-1' data-f='$f'$dis "
        ."OnClick=\"DpFilter('$f')\"><span>$label</span><span class='dp-n".($f=="updates"&&$count>0?" dp-n-warn":"")."'>".intval($count)."</span></button>";
}

function debpkg_arch_select($m):string{
    $o="<option value=''>{debpkg_all_arch}</option>";
    foreach($m["archs"] as $a=>$none){
        $a=htmlspecialchars($a);
        $o.="<option value='$a'>$a</option>";
    }
    return "<select id='dp-arch' class='dp-sel' aria-label='{debpkg_arch}' onchange='DpApply(true)'>$o</select>";
}

function debpkg_table($m,$L):string{
    $tpl=new template_admin();
    $h=array();
    $h[]=$tpl->_ENGINE_parse_body("<table class='dp-tbl'><thead><tr>"
        ."<th scope='col' class='dp-c-name'>{debpkg_package}</th><th scope='col' class='dp-c-ver'>{debpkg_version}</th>"
        ."<th scope='col' class='dp-c-arch'>{debpkg_arch}</th><th scope='col' class='dp-c-desc'>{description}</th>"
        ."<th scope='col' class='dp-c-st'>{debpkg_state}</th></tr></thead>");
    $h[]="<tbody id='dp-rows'>";
    foreach($m["rows"] as $r){$h[]=debpkg_row($r,$L);}
    $h[]="</tbody></table>";
    $h[]=debpkg_empty($m);
    $h[]="<div class='dp-foot'><span id='dp-count'></span><nav class='dp-pg' id='dp-pg' aria-label=\"".htmlspecialchars(html_entity_decode($L["pager"],ENT_QUOTES,"UTF-8"),ENT_QUOTES)."\"></nav></div>";
    return @implode("\n",$h);
}

function debpkg_row($r,$L):string{
    $e=function($s){ return htmlspecialchars((string)$s,ENT_QUOTES); };
    $cls="dp-r dp-s-".$r["state"].($r["candidate"]!==""?" dp-upd":"");
    $ver="<span class='dp-mono'>".$e($r["version"])."</span>";
    if($r["candidate"]!==""){
        $ver.="<div class='dp-cand' title=\"".$e(html_entity_decode($L["candidate"],ENT_QUOTES,"UTF-8"))."\">"
            ."<i class='fas fa-circle-arrow-up' aria-hidden='true'></i> <span class='dp-mono'>".$e($r["candidate"])."</span>";
        if($r["security"]){
            $ver.=" <span class='dp-secpill'><i class='fas fa-shield-halved' aria-hidden='true'></i> {$L["security"]}</span>";
        }
        $ver.="</div>";
    }
    $arch=$r["arch"]!==""?"<span class='dp-arch'>".$e($r["arch"])."</span>":"<span class='dp-muted'>&mdash;</span>";
    $desc=$r["desc"]!==""?"<span class='dp-desc' title=\"".$e($r["desc"])."\">".$e($r["desc"])."</span>":"<span class='dp-muted'>&mdash;</span>";
    return "<tr class='$cls' data-st='{$r["state"]}' data-arch='".$e($r["arch"])."' data-upd='".($r["candidate"]!==""?"1":"0")
        ."' data-sec='".($r["security"]?"1":"0")."'>"
        ."<td class='dp-c-name'><span class='dp-name' title=\"".$e($r["name"])."\">".$e($r["name"])."</span></td>"
        ."<td class='dp-c-ver'>$ver</td>"
        ."<td class='dp-c-arch'>$arch</td>"
        ."<td class='dp-c-desc'>$desc</td>"
        ."<td class='dp-c-st'>".debpkg_state_cell($r,$L)."</td></tr>";
}

function debpkg_state_cell($r,$L):string{
    if($r["state"]=="installed"){
        return "<span class='dp-st-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {$L["installed"]}</span>";
    }
    if($r["state"]=="held"){
        return "<span class='dp-pill dp-pill-warn'><i class='fas fa-thumbtack' aria-hidden='true'></i> {$L["held"]}</span>";
    }
    if($r["state"]=="removed"){
        return "<span class='dp-pill'><i class='fas fa-circle-minus' aria-hidden='true'></i> {$L["removed"]}</span>";
    }
    // Un code inconnu est montre tel quel : le traduire en "installe" mentirait.
    return "<span class='dp-pill dp-pill-out'><i class='fas fa-circle-question' aria-hidden='true'></i> {$L["other"]}: "
        .htmlspecialchars($r["code"])."</span>";
}

// Trois etats vides : inventaire illisible, pas encore collecte, aucun resultat.
function debpkg_empty($m):string{
    $tpl=new template_admin();
    if($m["error"]!==""){
        return $tpl->_ENGINE_parse_body("<div class='dp-empty'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>"
            ."<div>{debpkg_read_error}</div>")."<div class='dp-err'>".htmlspecialchars($m["error"])."</div></div>";
    }
    if($m["total"]==0){
        return $tpl->_ENGINE_parse_body("<div class='dp-empty'><i class='fas fa-hourglass-half' aria-hidden='true'></i>"
            ."<div>{debpkg_not_collected}</div></div>");
    }
    return $tpl->_ENGINE_parse_body("<div class='dp-empty' id='dp-empty' hidden><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<div>{debpkg_nomatch}</div><button type='button' class='dp-btn-text' OnClick='DpClearAll()'>{debpkg_clear_filters}</button></div>");
}

function debpkg_style():string{
    if(isset($GLOBALS["DEBPKG_CSS_DONE"])){return "";}
    $GLOBALS["DEBPKG_CSS_DONE"]=true;
    $accent="#10b981";
    $soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.dp-page{padding:8px 4px 24px;color:#111d23}
.dp-page,.dp-page *{box-sizing:border-box}
.dp-page [hidden]{display:none!important}
.dp-sum{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));background:#fff;border:1px solid #dfe5e8;border-radius:8px;box-shadow:0 1px 2px rgba(16,24,40,.06);margin-bottom:16px}
.dp-sum-c{padding:16px 20px;border-left:1px solid #e8edef;min-width:0}
.dp-sum-c:first-child{border-left:0}
.dp-sum-l{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#3d4947}
.dp-sum-l i,.dp-sum-l svg{margin-right:4px}
.dp-sum-b{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-top:8px;min-width:0}
.dp-sum-v{font-size:24px;font-weight:600;line-height:1;color:#111d23}
.dp-v-warn{color:#865300}
.dp-sum-s{font-size:13px;color:#3d4947;text-align:right;min-width:0}
.dp-sum-s2{display:flex;flex-direction:column;align-items:flex-end;gap:4px}
.dp-sec-t{color:#ba1a1a;font-weight:600;white-space:nowrap}
.dp-link{color:#006a62;font-weight:600;white-space:nowrap}
.dp-link:hover{text-decoration:underline}
.dp-bar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:#fff;border:1px solid #dfe5e8;border-radius:8px;padding:12px 16px;margin-bottom:16px}
.dp-chips{display:flex;gap:8px;flex-wrap:wrap}
.dp-chip{display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 12px;border-radius:6px;border:1px solid #dfe5e8;background:#fff;color:#111d23;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap}
.dp-chip:hover:not([disabled]){background:#f7fafb}
.dp-chip[disabled]{opacity:.45;cursor:default}
.dp-chip.on,.dp-chip.on:hover:not([disabled]){background:$accent;border-color:$accent;color:#fff}
.dp-n{min-width:22px;padding:1px 7px;border-radius:999px;background:#eef2f4;color:#3d4947;font-size:12px;text-align:center}
.dp-n-warn{background:#ffddb9;color:#5a3800}
.dp-chip.on .dp-n{background:rgba(255,255,255,.25);color:#fff}
.dp-bar-r{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.dp-sel{height:36px;border:1px solid #bcc9c6;border-radius:6px;padding:0 10px;font-size:14px;color:#111d23;background:#fff}
.dp-search{position:relative;width:340px;max-width:100%}
.dp-search-ico{position:absolute;left:0;top:0;bottom:0;width:36px;display:flex;align-items:center;justify-content:center;color:#6d7a77;font-size:13px;pointer-events:none}
.dp-search input{width:100%;height:36px;border:1px solid #bcc9c6;border-radius:6px;padding:0 34px;font-size:14px;color:#111d23;background:#fff}
.dp-q-x{position:absolute;right:2px;top:0;bottom:0;width:32px;padding:0;border:0;background:none;color:#6d7a77;cursor:pointer;display:none;align-items:center;justify-content:center}
.dp-q-x.on{display:flex}
.dp-card{background:#fff;border:1px solid #dfe5e8;border-radius:8px;box-shadow:0 1px 2px rgba(16,24,40,.06);overflow:hidden}
.dp-tbl{width:100%;border-collapse:collapse;table-layout:fixed;font-size:14px;color:#111d23}
.dp-tbl th{position:sticky;top:0;z-index:1;background:#f4f7f9;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#3d4947;padding:12px 16px;text-align:left;border-bottom:1px solid #dfe5e8}
.dp-tbl td{padding:9px 16px;border-bottom:1px solid #eef2f4;vertical-align:middle;color:#111d23}
.dp-c-name{width:260px}
.dp-c-ver{width:300px}
.dp-c-arch{width:90px}
.dp-tbl .dp-c-st{width:150px;text-align:right}
.dp-r:hover td{background:#f7fafb}
.dp-upd td:first-child{box-shadow:inset 3px 0 0 #865300}
.dp-name,.dp-desc{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dp-name{font-weight:600}
.dp-s-removed .dp-name{color:#6d7a77;font-weight:400}
.dp-desc{color:#3d4947}
.dp-mono{font-family:monospace;font-size:13px;overflow-wrap:anywhere}
.dp-cand{margin-top:3px;font-size:12px;color:#865300;font-weight:600}
.dp-cand .dp-mono{font-size:12px}
.dp-secpill{display:inline-flex;align-items:center;gap:4px;margin-left:4px;padding:0 7px;border-radius:999px;background:#ffdad6;color:#ba1a1a;font-size:11px;font-weight:700;white-space:nowrap}
.dp-arch{display:inline-block;padding:1px 8px;border-radius:999px;background:#eef2f4;color:#3d4947;font-family:monospace;font-size:12px}
.dp-muted{color:#6d7a77}
.dp-st-ok{color:#3d4947;white-space:nowrap}
.dp-st-ok i,.dp-st-ok svg{color:#26a69a}
.dp-pill{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;background:#eef2f4;color:#3d4947;font-size:12px;font-weight:600;white-space:nowrap}
.dp-pill-warn{background:#fff3e2;color:#865300;border:1px solid #f2d8b0}
.dp-pill-out{background:#fff;border:1px solid #bcc9c6}
.dp-empty{padding:40px 20px;text-align:center;color:#3d4947;font-size:14px}
.dp-empty>i,.dp-empty>svg{display:block;font-size:28px;color:#9aa8b2;margin:0 auto 10px}
.dp-err{margin-top:6px;font-family:monospace;font-size:12px;color:#ba1a1a}
.dp-btn-text{margin-top:10px;border:0;background:none;color:#006a62;font-weight:600;cursor:pointer;padding:4px 8px;border-radius:6px}
.dp-btn-text:hover{background:$soft}
.dp-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-top:1px solid #eef2f4;font-size:13px;color:#3d4947}
.dp-pg{display:flex;gap:4px;align-items:center}
.dp-pg button{min-width:32px;height:30px;padding:0 10px;border:1px solid #dfe5e8;border-radius:6px;background:#fff;color:#111d23;font-size:13px;cursor:pointer}
.dp-pg button[disabled]{opacity:.45;cursor:default}
.dp-pg button.on{background:$accent;border-color:$accent;color:#fff;font-weight:600}
.dp-pg .dp-gap{padding:0 4px;color:#6d7a77}
.dp-page button:focus-visible,.dp-page input:focus-visible,.dp-page select:focus-visible,.dp-page a:focus-visible{outline:2px solid $accent;outline-offset:2px}
@media (prefers-reduced-motion:no-preference){.dp-chip,.dp-pg button{transition:background .15s ease,color .15s ease}}
@media (max-width:1200px){.dp-sum{grid-template-columns:repeat(2,minmax(0,1fr))}.dp-sum-c:nth-child(3){border-left:0}.dp-sum-c:nth-child(n+3){border-top:1px solid #e8edef}.dp-c-desc{display:none}}
</style>";
}

function debpkg_js($L):string{
    $J=array();
    foreach($L as $k=>$v){$J[$k]=html_entity_decode(strip_tags($v),ENT_QUOTES,"UTF-8");}
    return "<script>
(function(){
    var L=".json_encode($J).", PER=50;
    function all(sel,root){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}
    var tb=document.getElementById('dp-rows'); if(!tb){return;}
    var rows=all('tr.dp-r',tb), page=1;
    window.DpF=window.DpF||'all';
    function matches(r,f,arch,q){
        var st=r.getAttribute('data-st');
        if(f==='updates'&&r.getAttribute('data-upd')!=='1'){return false;}
        if((f==='held'||f==='removed'||f==='other')&&st!==f){return false;}
        if(arch!==''&&r.getAttribute('data-arch')!==arch){return false;}
        if(q!==''&&r.textContent.toLowerCase().indexOf(q)<0){return false;}
        return true;
    }
    window.DpApply=function(reset){
        if(reset){page=1;}
        var qi=document.getElementById('dp-q'), ai=document.getElementById('dp-arch');
        var q=qi?qi.value.replace(/^\\s+|\\s+$/g,'').toLowerCase():'', arch=ai?ai.value:'', f=window.DpF;
        var list=rows.filter(function(r){return matches(r,f,arch,q);});
        // Filtre Updates : les mises a jour de securite d'abord.
        if(f==='updates'){list.sort(function(a,b){return (b.getAttribute('data-sec')|0)-(a.getAttribute('data-sec')|0);});}
        var n=list.length, pages=Math.max(1,Math.ceil(n/PER)); if(page>pages){page=pages;}
        var a=(page-1)*PER, b=Math.min(n,a+PER);
        rows.forEach(function(r){r.style.display='none';});
        for(var i=a;i<b;i++){tb.appendChild(list[i]); list[i].style.display='';}
        var c=document.getElementById('dp-count');
        if(c){c.textContent=n?(L.showing+' '+(a+1)+'\u2013'+b+' '+L.of+' '+n+' '+L.packages):'';}
        var e=document.getElementById('dp-empty'); if(e){e.hidden=n>0;}
        var x=document.getElementById('dp-q-x'); if(x){x.classList.toggle('on',q!=='');}
        pager(pages);
    };
    function btn(label,p,on,dis,aria){
        var b=document.createElement('button'); b.type='button'; b.textContent=label;
        if(on){b.className='on'; b.setAttribute('aria-current','page');}
        if(dis){b.disabled=true;}
        if(aria){b.setAttribute('aria-label',aria);}
        b.onclick=function(){page=p; DpApply(false);};
        return b;
    }
    function pager(pages){
        var nav=document.getElementById('dp-pg'); if(!nav){return;}
        nav.innerHTML='';
        if(pages<2){return;}
        nav.appendChild(btn('\u2039 '+L.prev,page-1,false,page===1));
        var last=0;
        for(var p=1;p<=pages;p++){
            if(p===1||p===pages||Math.abs(p-page)<=1){
                if(last&&p-last>1){var s=document.createElement('span'); s.className='dp-gap'; s.textContent='\u2026'; nav.appendChild(s);}
                nav.appendChild(btn(String(p),p,p===page,false)); last=p;
            }
        }
        nav.appendChild(btn(L.next+' \u203a',page+1,false,page===pages));
    }
    window.DpFilter=function(f){
        window.DpF=f;
        all('#dp-chips [role=radio]').forEach(function(b){
            var on=b.getAttribute('data-f')===f;
            b.setAttribute('aria-checked',on?'true':'false'); b.tabIndex=on?0:-1; b.classList.toggle('on',on);
        });
        DpApply(true);
    };
    window.DpClearSearch=function(){var q=document.getElementById('dp-q'); if(q){q.value=''; DpApply(true); q.focus();}};
    window.DpClearAll=function(){
        var q=document.getElementById('dp-q'), a=document.getElementById('dp-arch');
        if(q){q.value='';} if(a){a.value='';} DpFilter('all');
    };
    var rg=document.getElementById('dp-chips');
    if(rg){rg.addEventListener('keydown',function(ev){
        if(ev.key!=='ArrowRight'&&ev.key!=='ArrowLeft'){return;}
        var b=all('[role=radio]:not([disabled])',rg), i=b.indexOf(document.activeElement); if(i<0){return;}
        i=(i+(ev.key==='ArrowRight'?1:b.length-1))%b.length; b[i].focus(); b[i].click(); ev.preventDefault();
    });}
    var cur=document.querySelector('#dp-chips [data-f=\"'+window.DpF+'\"]');
    if(!cur||cur.disabled){window.DpF='all';}
    DpFilter(window.DpF);
})();
</script>";
}
