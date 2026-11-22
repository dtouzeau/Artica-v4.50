<?php
//SP119,SP206,SP28 HF
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.updates.packages.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
// awc_table_style(): feuille de style partagee, utilisee par la fenetre phpinfo
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["range1"])){dhcp_save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["phpinfo-js"])){phpinfo_js();exit;}
if(isset($_GET["phpinfo-popup"])){phpinfo_popup();exit;}
if(isset($_GET["table-start"])){table_start();exit;}



if(isset($_GET["tabs"])){tabs();exit;}
page();


function phpinfo_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="PHP v".PHP_VERSION;
    return $tpl->js_dialog2($title, "$page?phpinfo-popup=yes");
}

function phpinfo_popup():bool{
    $s=php_ini_path();
    $s=str_replace('<div class="center">', "", $s);
    // La sortie de phpinfo() est un artefact externe: on se contente de la
    // rehabiller avec la meme feuille de style que les autres tableaux.
    // "class=e" est sa colonne de CLES -- elle reste alignee a gauche.
    $s=str_replace('<table>', "<table class='awc-tbl awc-tbl-compact'>", $s);
    // Pas de <strong> ouvrant ici: phpinfo() fournit le </td> fermant, pas le
    // </strong> -- la balise resterait ouverte a chaque ligne.
    $s=str_replace('<td class="e">', "<td class='awc-nowrap' style='font-weight:600'>", $s);
    $s=str_replace('<td class="v">', "<td class='awc-mono'>", $s);
    $s=str_replace('Winstead,', "Winstead,<br>", $s);
    $s=str_replace('Belski,', "Belski,<br>", $s);
    $s=str_replace('Rethans,', "Rethans,<br>", $s);
    $s=str_replace('Zarkos,', "Zarkos,<br>", $s);
    $s=str_replace('auth_plugin_mysql_clear_password,', "auth_plugin_mysql_clear_password,<br>", $s);
    $s=str_replace(":/usr","<br>/usr","$s");
    $s=str_replace(":/var","<br>/var","$s");
    echo awc_table_style()."<div class='awc-wrap'>$s</div>";
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $REVERSE_APPLIANCE=false;
    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        $REVERSE_APPLIANCE=true;
    }

    $SEE_PYTHON=true;

    if($REVERSE_APPLIANCE){
        $SEE_PYTHON=false;
    }
    if(is_file("/etc/artica-postfix/ARTICA_SMTP_APPLIANCE")){
        $SEE_PYTHON=false;
    }


    $array["{APP_ARTICA}"]="$page?table-start=yes";
    $array["{operating_system}"]="fw.versions.debian.php";
    // L'onglet ne depend plus de python-packages.db : la liste est lue par
    // articarest (GET /system/python/packages) a chaque ouverture.
    if($SEE_PYTHON) {
        $array["{python_packages}"] = "fw.versions.python.php";
    }
    echo $tpl->tabs_default($array);
    return true;
}
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{packages_center}: {versions}","fa fa-info", "{fw_artica_versions_explain}","$page?tabs=yes","packages-center","progress-articaupd-restart",false,"table-loader-versions-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function TinyJS():string{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $jsProgress=$tpl->framework_buildjs("/system/softwares/refresh",
    "UpdateReposIndex.progress","UpdateReposIndex.log",
    "progress-articaupd-restart",
    "LoadAjax('update-softwares-index','$page?table=yes');");

    $topbuttons[]=array($jsProgress,
        ico_refresh,"{update_softwares_index}","AsSystemAdministrator");

    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{packages_center}: {versions}";
    $TINY_ARRAY["ICO"]="fa fa-info";
    $TINY_ARRAY["EXPL"]="{fw_artica_versions_explain}";
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}
function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='update-softwares-index'></div>
    <script>LoadAjax('update-softwares-index','$page?table=yes');</script>
        ";
    return true;
}
// Onglet "Artica Core server" : une bande d'identite, une barre de filtres
// et le tableau des logiciels groupe par section. Un seul modele (ver_model)
// lit l'index et les reglages ; chaque widget n'en rend qu'une partie.
function table():bool{
    $tpl=new template_admin();
    $m=ver_model();
    $html=array();
    $html[]="<div class='ver-page' id='ver-page'>";
    $html[]=ver_identity($m);
    if(!$m["index_ok"]){$html[]=ver_index_banner();}
    $html[]=ver_toolbar($m);
    $html[]=ver_table($m);
    $html[]="</div>";
    // CSS et JS concatenes HORS du moteur : ses accolades seraient prises
    // pour des jetons.
    echo ver_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>\n".TinyJS()."\nNoSpinner();\n</script>"
        .ver_js();
    return true;
}

function ver_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $m["artica"]=trim((string)@file_get_contents("VERSION"));
    $m["php"]=PHP_VERSION;
    $m["os"]="";
    $json=json_decode($sock->REST_API("/harmp/status"));
    if(is_object($json) && isset($json->DistributionName)){
        $m["os"]=trim((string)$json->DistributionName);
    }
    $m["kernel_full"]=trim((string)$sock->GET_INFO("LinuxKernelVersion"));
    $m["kernel"]=ver_kernel_short($m["kernel_full"]);

    // unserialize() rend FALSE quand l'index est absent ou corrompu.
    $UPDATES_ARRAY=@unserialize(base64_decode($sock->GET_INFO("v4softsRepo")));
    if(!is_array($UPDATES_ARRAY)){$UPDATES_ARRAY=array();}
    $m["index_ok"]=count($UPDATES_ARRAY)>0;
    if(is_file("/etc/artica-postfix/ARTICA_REVERSE_PROXY_APPLIANCE")){
        $UPDATES_ARRAY["REVERSE_APPLIANCE"]=true;
    }

    $m["groups"]=ver_parse_rows(ver_legacy_rows($UPDATES_ARRAY),$UPDATES_ARRAY);
    $m["total"]=0;
    $m["updates"]=0;
    $m["installed"]=0;
    foreach($m["groups"] as $g){
        foreach($g["rows"] as $r){
            $m["total"]++;
            if($r["kind"]=="update"){$m["updates"]++;}
            if($r["installed"]){$m["installed"]++;}
        }
    }
    $users=new usersMenus();
    $m["admin"]=(bool)$users->AsSystemAdministrator;
    return $m;
}

// "#1 SMP PREEMPT_DYNAMIC Debian 6.12.115-2 (2026-11-01)" -> "6.12.115-2"
function ver_kernel_short($full):string{
    if($full===""){return "";}
    if(preg_match('#Debian\s+([0-9][^\s()]*)#',$full,$re)){return $re[1];}
    if(preg_match('#\b([0-9]+\.[0-9]+[^\s()]*)#',$full,$re)){return $re[1];}
    return $full;
}

// Comparaison de versions : version_compare() et non la concatenation des
// chiffres (10.0 serait plus ancien que 9.9.9). En minuscules : "4.4.3-P1"
// n'est un niveau de correctif pour PHP qu'ecrit "-p1".
function ver_cmp($a,$b):int{
    return version_compare(strtolower(trim($a)),strtolower(trim($b)));
}

// Les ~90 fonctions de ligne restent la source de l'etat installe, de la
// version et du produit de l'index : le modele relit leur sortie (3 cellules
// par ligne, une cellule colspan par section) et la complete avec l'index.
function ver_parse_rows($html,$UPDATES_ARRAY):array{
    $groups=array();
    $cur="";
    if(!preg_match_all('#<tr>(.*?)</tr>#s',$html,$trs)){return array();}
    foreach($trs[1] as $tr){
        if(strpos($tr,"colspan=")!==false){
            $cur=trim(strip_tags($tr));
            if(!isset($groups[$cur])){$groups[$cur]=array("label"=>$cur,"rows"=>array());}
            continue;
        }
        if(!preg_match_all('#<td[^>]*>(.*?)</td>#s',$tr,$td)){continue;}
        if(count($td[1])<3){continue;}
        if($cur===""){
            $cur="{system}";
            $groups[$cur]=array("label"=>$cur,"rows"=>array());
        }
        $groups[$cur]["rows"][]=ver_row_model($td[1],$UPDATES_ARRAY);
    }
    $out=array();
    foreach($groups as $g){
        if(count($g["rows"])>0){$out[]=$g;}
    }
    return $out;
}

function ver_row_model($c,$UPDATES_ARRAY):array{
    $r=array();
    $r["name"]=trim(preg_replace('#:\s*$#',"",trim(strip_tags($c[0]))));
    $r["installed"]=strpos($c[1],"fa-minus")===false;
    $r["version"]=trim(html_entity_decode(strip_tags(str_replace("&nbsp;"," ",$c[1])),ENT_QUOTES,"UTF-8"));
    if(!$r["installed"]){$r["version"]="";}
    $r["product"]="";
    if(preg_match('#product=([A-Za-z0-9_\-]+)#',$c[2],$re)){$r["product"]=$re[1];}

    // Derniere version de l'index pour ce produit, et la taille de son paquet.
    $r["latest"]="";
    $r["size"]=0;
    $p=$r["product"];
    if($p!=="" && isset($UPDATES_ARRAY[$p]) && is_array($UPDATES_ARRAY[$p])){
        foreach($UPDATES_ARRAY[$p] as $e){
            if(!is_array($e) || !isset($e["VERSION"])){continue;}
            $v=trim((string)$e["VERSION"]);
            if($v===""){continue;}
            if($r["latest"]==="" || ver_cmp($v,$r["latest"])>0){
                $r["latest"]=$v;
                $r["size"]=isset($e["SIZE"])?intval($e["SIZE"]):0;
            }
        }
    }

    // update : plus recent dans l'index ; ok : l'index a exactement cette version ;
    // known : installe sans reference comparable (index absent, produit hors
    // index, ou version installee absente de l'index) ;
    // noversion : installe, version non remontee ; avail / absent : non installe.
    if(!$r["installed"]){
        $r["kind"]=$p!==""?"avail":"absent";
    }elseif($r["version"]===""){
        $r["kind"]="noversion";
    }elseif($r["latest"]!=="" && ver_cmp($r["latest"],$r["version"])>0){
        $r["kind"]="update";
    }elseif($r["latest"]!=="" && ver_cmp($r["latest"],$r["version"])==0){
        // "A jour" seulement quand l'index contient exactement cette version :
        // une version installee plus haute que l'index (build par noyau de
        // PF_RING, GlusterFS 12 contre 11.3) n'est pas comparable.
        $r["kind"]="ok";
    }else{
        $r["kind"]="known";
    }
    return $r;
}

function ver_identity($m):string{
    $page=CurrentPageName();
    $php="<button type='button' class='ver-id-v ver-php' title='{information}' "
        ."OnClick=\"Loadjs('$page?phpinfo-js=yes')\">".htmlspecialchars($m["php"])
        ." <i class='fas fa-circle-info' aria-hidden='true'></i></button>";
    $h=array();
    $h[]="<section class='ver-id' aria-label='{ver_identity_label}'>";
    $h[]=ver_identity_cell("fas fa-cube","{APP_ARTICA}",$m["artica"],$m["artica"]);
    $h[]=ver_identity_cell("fab fa-linux","{OS}",$m["os"],$m["os"]);
    $h[]=ver_identity_cell("fas fa-microchip","{kernel_version}",$m["kernel"],$m["kernel_full"]);
    $h[]=ver_identity_cell("fas fa-code","PHP Version","",$m["php"],$php);
    $h[]="</section>";
    return @implode("\n",$h);
}

function ver_identity_cell($ico,$label,$value,$title,$custom=""):string{
    $v=$custom;
    if($v===""){
        if(trim($value)===""){
            $v="<span class='ver-id-v ver-muted'>&mdash;</span>";
        }else{
            $v="<span class='ver-id-v' title=\"".htmlspecialchars($title)."\">".htmlspecialchars($value)."</span>";
        }
    }
    return "<div class='ver-id-c'><span class='ver-id-i'><i class='$ico' aria-hidden='true'></i></span>"
        ."<div class='ver-id-t'><span class='ver-id-l'>$label</span>$v</div></div>";
}

function ver_index_banner():string{
    return "<section class='ver-warn' role='alert'>"
        ."<i class='fas fa-triangle-exclamation' aria-hidden='true'></i>"
        ."<div><div class='ver-warn-t'>{missing_update_index}</div>"
        ."<div class='ver-warn-x'>{missing_update_index_explain}</div></div></section>";
}

function ver_toolbar($m):string{
    $h=array();
    $h[]="<section class='ver-bar'>";
    $h[]="<div class='ver-chips' id='ver-chips' role='radiogroup' aria-label='{ver_filter_label}'>";
    $h[]=ver_chip("all","{ver_all}",$m["total"],"");
    $h[]=ver_chip("updates","{ver_updates}",$m["updates"],$m["updates"]>0?"ver-n-warn":"");
    $h[]=ver_chip("installed","{ver_installed}",$m["installed"],"");
    $h[]="</div>";
    $h[]="<div class='ver-search'><span class='ver-search-ico' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>"
        ."<input type='text' id='ver-q' placeholder='{ver_search_ph}' aria-label='{ver_search_ph}' "
        ."autocomplete='off' oninput='VerApply()'>"
        ."<button type='button' class='ver-q-x' id='ver-q-x' aria-label='{ver_clear_search}' "
        ."OnClick='VerClearSearch()'><i class='fas fa-xmark' aria-hidden='true'></i></button></div>";
    $h[]="</section>";
    return @implode("\n",$h);
}

function ver_chip($f,$label,$count,$ncls):string{
    return "<button type='button' class='ver-chip' role='radio' aria-checked='false' tabindex='-1' "
        ."data-f='$f' OnClick=\"VerFilter('$f')\"><span>$label</span>"
        ."<span class='ver-n $ncls'>".intval($count)."</span></button>";
}

function ver_table($m):string{
    $h=array();
    $h[]="<div class='ver-card'>";
    $h[]="<table class='ver-tbl'>";
    $h[]="<thead><tr><th scope='col'>{software}</th><th scope='col' class='ver-c-ins'>{ver_installed}</th>"
        ."<th scope='col' class='ver-c-av'>{ver_available}</th><th scope='col' class='ver-c-act'>{ver_action}</th></tr></thead>";
    foreach($m["groups"] as $i=>$g){
        $h[]=ver_group($g,$i,$m);
    }
    $h[]="</table>";
    $h[]="<div class='ver-empty' id='ver-empty' hidden>"
        ."<i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<div>{ver_nomatch}</div>"
        ."<button type='button' class='ver-btn ver-btn-text' OnClick='VerClearAll()'>{ver_clear_filters}</button></div>";
    $h[]="<div class='ver-foot'><span>{ver_shown}</span> <strong id='ver-shown'>".intval($m["total"])."</strong></div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

function ver_group($g,$i,$m):string{
    $inst=0;
    foreach($g["rows"] as $r){if($r["installed"]){$inst++;}}
    $h=array();
    $h[]="<tbody class='ver-g' id='ver-g$i'>";
    $h[]="<tr class='ver-gh'><td colspan='4'><button type='button' class='ver-gbtn' aria-expanded='true' "
        ."aria-controls='ver-g$i' OnClick='VerToggle(this)'>"
        ."<i class='fas fa-chevron-down' aria-hidden='true'></i><span class='ver-gname'>{$g["label"]}</span>"
        ."<span class='ver-gcount'>$inst / ".count($g["rows"])." {ver_installed_lc}</span></button></td></tr>";
    foreach($g["rows"] as $r){
        $h[]=ver_row($r,$m);
    }
    $h[]="</tbody>";
    return @implode("\n",$h);
}

function ver_row($r,$m):string{
    $kind=$r["kind"];
    $inst=$r["installed"]?"1":"0";
    // La cle produit (APP_UNBOUND) rend la ligne trouvable par le nom du logiciel
    // quand son libelle est fonctionnel ("DNS Cache service").
    $s=strtolower(str_replace(array("APP_","_")," ",$r["product"]));
    return "<tr class='ver-r ver-k-$kind' data-k='$kind' data-i='$inst' data-s='".htmlspecialchars($s,ENT_QUOTES)."'>"
        ."<td><span class='ver-name'>{$r["name"]}</span></td>"
        ."<td class='ver-c-ins'>".ver_cell_installed($r)."</td>"
        ."<td class='ver-c-av'>".ver_cell_available($r,$m)."</td>"
        ."<td class='ver-c-act'>".ver_cell_action($r,$m)."</td></tr>";
}

function ver_cell_installed($r):string{
    if(!$r["installed"]){
        return "<span class='ver-pill'><i class='fas fa-circle-minus' aria-hidden='true'></i> {ver_not_installed}</span>";
    }
    if($r["version"]===""){
        return "<span class='ver-muted'>{ver_no_version}</span>";
    }
    return "<span class='ver-mono'>".htmlspecialchars($r["version"])."</span>";
}

function ver_cell_available($r,$m):string{
    $latest=htmlspecialchars($r["latest"]);
    if($r["kind"]=="update"){
        $size="";
        if($r["size"]>0){$size=" <span class='ver-size'>".FormatBytes($r["size"]/1024,true)."</span>";}
        return "<span class='ver-new' title='{ver_newer_title}'><i class='fas fa-circle-arrow-up' aria-hidden='true'></i> "
            ."<span class='ver-mono'>$latest</span></span>$size";
    }
    if($r["kind"]=="ok"){
        return "<span class='ver-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {ver_uptodate}</span>";
    }
    // Pas d'installation comparable : la derniere version de l'index, sans verdict.
    if($latest!==""){return "<span class='ver-mono ver-muted'>$latest</span>";}
    return "<span class='ver-muted'>&mdash;</span>";
}

function ver_cell_action($r,$m):string{
    if($r["product"]==="" || !$m["admin"]){return "";}
    $js="Loadjs('fw.system.upgrade-software.php?product={$r["product"]}');";
    $cls="ver-btn-text";
    $ico="fa-rotate";
    if($r["kind"]=="update"){$cls="ver-btn-primary";$ico="fa-download";}
    if($r["kind"]=="avail"){$cls="ver-btn-outline";$ico="fa-plus";}
    return "<button type='button' class='ver-btn $cls' OnClick=\"$js\">"
        ."<i class='fas $ico' aria-hidden='true'></i> {install_upgrade2}</button>";
}

function ver_style():string{
    if(isset($GLOBALS["VER_STYLE"])){return "";}
    $GLOBALS["VER_STYLE"]=true;
    $accent="#10b981";
    $soft="rgba(16,185,129,.12)";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
    }
    return "<style>
.ver-page{padding:8px 4px 24px;color:#111d23}
.ver-page,.ver-page *{box-sizing:border-box}
.ver-page [hidden]{display:none!important}
.ver-id{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));background:#fff;border:1px solid #dfe5e8;border-radius:8px;box-shadow:0 1px 2px rgba(16,24,40,.06);margin-bottom:16px}
.ver-id-c{display:flex;align-items:center;gap:14px;padding:20px;min-width:0;border-left:1px solid #e8edef}
.ver-id-c:first-child{border-left:0}
.ver-id-i{flex:0 0 40px;height:40px;border-radius:8px;background:#f1f5f7;border:1px solid #e3e8ec;display:flex;align-items:center;justify-content:center;color:#3d4947;font-size:18px}
.ver-id-t{min-width:0;display:flex;flex-direction:column;gap:3px}
.ver-id-l{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#3d4947}
.ver-id-v{font-family:monospace;font-size:16px;font-weight:600;color:#111d23;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
.ver-php{background:none;border:0;padding:0;text-align:left;cursor:pointer}
.ver-php:hover{text-decoration:underline}
.ver-php i,.ver-php svg{font-size:13px;color:#3d4947}
.ver-warn{display:flex;gap:14px;align-items:flex-start;background:#fff8ec;border:1px solid #f2d8b0;border-left:4px solid #865300;border-radius:8px;padding:14px 18px;margin-bottom:16px;color:#5a3800;font-size:13px}
.ver-warn>i,.ver-warn>svg{color:#865300;font-size:18px;margin-top:2px}
.ver-warn-t{font-weight:700;margin-bottom:2px}
.ver-bar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:16px}
.ver-chips{display:flex;gap:8px;flex-wrap:wrap}
.ver-chip{display:inline-flex;align-items:center;gap:8px;height:36px;padding:0 14px;border-radius:6px;border:1px solid #dfe5e8;background:#fff;color:#111d23;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap}
.ver-chip:hover{background:#f7fafb}
.ver-chip.on{background:$accent;border-color:$accent;color:#fff}
.ver-n{min-width:24px;padding:1px 7px;border-radius:999px;background:#eef2f4;color:#3d4947;font-size:12px;text-align:center}
.ver-n-warn{background:#ffddb9;color:#5a3800}
.ver-chip.on .ver-n{background:rgba(255,255,255,.25);color:#fff}
.ver-search{position:relative;width:320px;max-width:100%}
.ver-search-ico{position:absolute;left:0;top:0;bottom:0;width:36px;display:flex;align-items:center;justify-content:center;color:#6d7a77;font-size:13px;pointer-events:none}
.ver-search input{width:100%;height:38px;border:1px solid #bcc9c6;border-radius:6px;padding:0 34px;font-size:14px;color:#111d23;background:#fff}
.ver-q-x{position:absolute;right:2px;top:0;bottom:0;width:32px;padding:0;border:0;background:none;color:#6d7a77;cursor:pointer;display:none;align-items:center;justify-content:center}
.ver-q-x.on{display:flex}
.ver-card{background:#fff;border:1px solid #dfe5e8;border-radius:8px;box-shadow:0 1px 2px rgba(16,24,40,.06);overflow:hidden}
.ver-tbl{width:100%;border-collapse:collapse;font-size:14px;color:#111d23;table-layout:fixed}
.ver-tbl th{background:#f4f7f9;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#3d4947;padding:12px 20px;text-align:left;border-bottom:1px solid #dfe5e8}
.ver-tbl td{padding:10px 20px;border-bottom:1px solid #eef2f4;vertical-align:middle;color:#111d23}
.ver-c-ins{width:200px}
.ver-c-av{width:240px}
.ver-tbl .ver-c-act{width:200px;text-align:right}
.ver-r:hover td{background:#f7fafb}
.ver-k-update td:first-child{box-shadow:inset 3px 0 0 #865300}
.ver-k-avail .ver-name,.ver-k-absent .ver-name{color:#3d4947}
.ver-name{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ver-gh td{background:#f4f7f9;padding:0;border-bottom:1px solid #e3e8ec}
.ver-gbtn{display:flex;align-items:center;gap:10px;width:100%;border:0;background:none;padding:9px 20px;font-size:13px;font-weight:600;color:#111d23;cursor:pointer;text-align:left}
.ver-gbtn>i,.ver-gbtn>svg{font-size:11px;color:#3d4947;width:12px}
.ver-gname{display:inline-block}
.ver-gname::first-letter{text-transform:uppercase}
.ver-gcount{margin-left:auto;font-size:12px;font-weight:600;color:#3d4947;background:#fff;border:1px solid #dfe5e8;border-radius:999px;padding:1px 10px;white-space:nowrap}
.ver-collapsed .ver-r{display:none}
.ver-collapsed .ver-gbtn>i,.ver-collapsed .ver-gbtn>svg{transform:rotate(-90deg)}
.ver-hide{display:none}
.ver-mono{font-family:monospace;font-size:13px;overflow-wrap:anywhere}
.ver-muted{color:#6d7a77}
.ver-pill{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;background:#eef2f4;color:#3d4947;font-size:12px;font-weight:600;white-space:nowrap}
.ver-new{color:#865300;font-weight:600;white-space:nowrap}
.ver-size{display:inline-block;margin-left:6px;font-size:12px;color:#5a3800;background:#fff3e2;border:1px solid #f2d8b0;border-radius:4px;padding:0 6px;white-space:nowrap}
.ver-ok{color:#1f7a6f;font-weight:600;white-space:nowrap}
.ver-btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;border:1px solid transparent;background:none}
.ver-btn-primary{background:$accent;border-color:$accent;color:#fff}
.ver-btn-primary:hover{filter:brightness(.93)}
.ver-btn-outline{border-color:#bcc9c6;color:#111d23;background:#fff}
.ver-btn-outline:hover{background:#f7fafb}
.ver-btn-text{color:#3d4947}
.ver-btn-text:hover{background:$soft;color:#111d23}
.ver-empty{padding:40px 20px;text-align:center;color:#3d4947;font-size:14px}
.ver-empty>i,.ver-empty>svg{font-size:28px;color:#9aa8b2;display:block;margin:0 auto 10px}
.ver-foot{padding:12px 20px;font-size:13px;color:#3d4947;border-top:1px solid #eef2f4}
.ver-page button:focus-visible,.ver-page input:focus-visible{outline:2px solid $accent;outline-offset:2px}
@media (prefers-reduced-motion:no-preference){.ver-chip,.ver-btn,.ver-gbtn>i{transition:all .15s ease}}
@media (max-width:1200px){.ver-id{grid-template-columns:repeat(2,minmax(0,1fr))}.ver-id-c:nth-child(3){border-left:0}.ver-id-c:nth-child(n+3){border-top:1px solid #e8edef}.ver-c-av{width:200px}.ver-size{display:block;margin:2px 0 0;width:max-content}}
</style>";
}

function ver_js():string{
    return "<script>
(function(){
    function all(sel,root){return Array.prototype.slice.call((root||document).querySelectorAll(sel));}
    window.VerF=window.VerF||'all';
    window.VerApply=function(){
        var qi=document.getElementById('ver-q'); if(!qi){return;}
        var q=qi.value.replace(/^\\s+|\\s+$/g,'').toLowerCase(), f=window.VerF, shown=0;
        all('#ver-page tbody.ver-g').forEach(function(g){
            var vis=0;
            all('tr.ver-r',g).forEach(function(r){
                var k=r.getAttribute('data-k'), inst=r.getAttribute('data-i')==='1';
                var ok=(f==='all')||(f==='updates'&&k==='update')||(f==='installed'&&inst);
                if(ok&&q!==''){ok=(r.textContent+' '+(r.getAttribute('data-s')||'')).toLowerCase().indexOf(q)>=0;}
                r.classList.toggle('ver-hide',!ok); if(ok){vis++;}
            });
            g.classList.toggle('ver-hide',vis===0); shown+=vis;
        });
        var s=document.getElementById('ver-shown'); if(s){s.textContent=shown;}
        var e=document.getElementById('ver-empty'); if(e){e.hidden=shown>0;}
        var x=document.getElementById('ver-q-x'); if(x){x.classList.toggle('on',q!=='');}
    };
    window.VerFilter=function(f){
        window.VerF=f;
        all('#ver-chips [role=radio]').forEach(function(b){
            var on=b.getAttribute('data-f')===f;
            b.setAttribute('aria-checked',on?'true':'false'); b.tabIndex=on?0:-1; b.classList.toggle('on',on);
        });
        VerApply();
    };
    window.VerToggle=function(btn){
        var g=btn; while(g&&g.tagName!=='TBODY'){g=g.parentNode;} if(!g){return;}
        var c=!g.classList.contains('ver-collapsed');
        g.classList.toggle('ver-collapsed',c); btn.setAttribute('aria-expanded',c?'false':'true');
    };
    window.VerClearSearch=function(){var q=document.getElementById('ver-q'); if(q){q.value=''; VerApply(); q.focus();}};
    window.VerClearAll=function(){var q=document.getElementById('ver-q'); if(q){q.value='';} VerFilter('all');};
    var rg=document.getElementById('ver-chips');
    if(rg){rg.addEventListener('keydown',function(ev){
        if(ev.key!=='ArrowRight'&&ev.key!=='ArrowLeft'){return;}
        var b=all('[role=radio]',rg), i=b.indexOf(document.activeElement); if(i<0){return;}
        i=(i+(ev.key==='ArrowRight'?1:b.length-1))%b.length; b[i].focus(); b[i].click(); ev.preventDefault();
    });}
    VerFilter(window.VerF);
})();
</script>";
}

// Corps historique du tableau : une ligne <tr> par logiciel. ver_model() relit
// cette sortie, qui reste la source de l'etat installe et du produit de l'index.
function ver_legacy_rows($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $html=array();
    $html[]="<tr>";
    $html[]=versions_section_cell("{system}");
    $html[]="</tr>";

    $html[]=APP_NGINX_CONSOLE($UPDATES_ARRAY);
    $html[]=APP_TRIVY($UPDATES_ARRAY);
    $html[]=APP_AIDE($UPDATES_ARRAY);
    $html[]=APP_PLAKAR($UPDATES_ARRAY);
    $html[]=APP_AUTOFS($UPDATES_ARRAY);
    $html[]=APP_GLFS($UPDATES_ARRAY);
    $html[]=APP_FIRMWARES($UPDATES_ARRAY);
    $html[]=PFRING($UPDATES_ARRAY);
    $html[]=APP_PFRING_AVX($UPDATES_ARRAY);
    $html[]=APP_DOCKER($UPDATES_ARRAY);
    $html[]=APP_FIRECRACKER($UPDATES_ARRAY);
    $html[]=APP_IWCONFIG($UPDATES_ARRAY);
    $html[]=APP_JAVA($UPDATES_ARRAY);
    $html[]=APP_VMTOOLS($UPDATES_ARRAY);
    $html[]=APP_IETD($UPDATES_ARRAY);
    $html[]=APP_SNMPD($UPDATES_ARRAY);
    $html[]=APP_FPING($UPDATES_ARRAY);
    $html[]=APP_DBUS($UPDATES_ARRAY);
    $html[]=APP_XAPIAN($UPDATES_ARRAY);
    $html[]=APP_PHP_GEOIP2($UPDATES_ARRAY);
    $html[]=APP_ZABBIX_AGENT($UPDATES_ARRAY);
    $html[]=APP_SYNO_BACKUP($UPDATES_ARRAY);
    $html[]=APP_URBACKUP($UPDATES_ARRAY);
    $html[]=APP_SYSLOGD($UPDATES_ARRAY);
    $html[]=APP_CLAMAV($UPDATES_ARRAY);
    $html[]="<tr>";
    $html[]=versions_section_cell("{databases}");
    $html[]="</tr>";
   // $html[]=APP_LOKI($UPDATES_ARRAY);
    $html[]=APP_MYSQL($UPDATES_ARRAY);
    $html[]=APP_CLICKHOUSE($UPDATES_ARRAY);
    $html[]=APP_MANTICORE($UPDATES_ARRAY);
    $html[]=APP_POSTGRES($UPDATES_ARRAY);
    $html[]=APP_ELASTICSEARCH($UPDATES_ARRAY);
    $html[]=APP_FILEBEAT($UPDATES_ARRAY);

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("openldap_installed"))==1){
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_OPENLDAP}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("openldap_version")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";

    }
    $OPENSSH_VER=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENSSH_VER");
    $html[]=APP_REDIS_SERVER($UPDATES_ARRAY);
    $html[]="<tr>";
    $html[]=versions_section_cell("{network_services}");
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_OPENSSH}:</strong></td>";
    $html[]="<td>$OPENSSH_VER</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    $html[]=APP_SSHPORTAL($UPDATES_ARRAY);
    $html[]=APP_SHELLINABOX($UPDATES_ARRAY);
    $html[]=APP_RUSTDESK($UPDATES_ARRAY);
    $html[]=APP_DHCP($UPDATES_ARRAY);

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPD_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_TFTPD}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("TFTPD_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }
    $html[]=APP_PROFTPD($UPDATES_ARRAY);
    $html[]=APP_MSTPD($UPDATES_ARRAY);


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FREERADIUS_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_FREERADIUS}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FREERADIUS_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    $html[]=APP_SAMBA($UPDATES_ARRAY);

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDInstalled"))==1){
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_NTPD}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDVersion")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    $html[]=APP_MSKTUTIL($UPDATES_ARRAY);
    $html[]=APP_TAILSCALE($UPDATES_ARRAY);
    $html[]=APP_OPENVPN($UPDATES_ARRAY);
    $html[]=APP_PPTP_CLIENT($UPDATES_ARRAY);
    $html[]=APP_STRONGSWAN($UPDATES_ARRAY);
    $html[]=APP_SPLUNK_FORWARDER($UPDATES_ARRAY);
    $html[]=APP_QAT($UPDATES_ARRAY);


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_RSYNC}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CURLFTPFS_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>CurlFtpFS:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CURLFTPFS_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    $MESSAGING=true;
    $DNS = true;
    $PROXY=true;
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){$MESSAGING=false;}
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){$DNS=false;}
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){$PROXY=false;}

    $html[]="<tr>";
    $html[]=versions_section_cell("{web_services}");
    $html[]="</tr>";
    $html[]=APP_NGINX($UPDATES_ARRAY);
    $html[]=WP_CLIENT($UPDATES_ARRAY);
    $html[]=APP_MATTERMOST($UPDATES_ARRAY);
    $html[]=APP_SQUID($UPDATES_ARRAY);

    if($MESSAGING) {
        $html[] = "<tr>";
        $html[] = versions_section_cell("{messaging}");
        $html[] = "</tr>";
        $html[] = APP_POSTFIX($UPDATES_ARRAY);
        $html[] = APP_RSPAMD($UPDATES_ARRAY);
        $html[] = APP_RBLDNSD($UPDATES_ARRAY);
        $html[] = APP_CYRUS($UPDATES_ARRAY);
    }
    if($DNS) {
        $html[] = "<tr>";
        $html[] = versions_section_cell("{DNS_SERVICES}");
        $html[] = "</tr>";
        $html[] = APP_DNSCRYPT_PROXY($UPDATES_ARRAY);
        $html[] = APP_UNBOUND($UPDATES_ARRAY);
        $html[] = APP_DNSDIST($UPDATES_ARRAY);
        $html[] = APP_DNSDIST9($UPDATES_ARRAY);
        $html[] = APP_DSC($UPDATES_ARRAY);
        $html[] = APP_PDNS($UPDATES_ARRAY);
        $html[] = APP_DOH_PROXY($UPDATES_ARRAY);
        $html[] = APP_CLOUDFLARE_DNS($UPDATES_ARRAY);
        $html[] = APP_DDNS_AGENT($UPDATES_ARRAY);
    }

    $html[]="<tr>";
    $html[]=versions_section_cell("{APP_FIREWALL}");
    $html[]="</tr>";
    $IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_VERSION");
    $bton="<span class='label label-default'>{unavailable}</span>";
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_IPTABLES}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$IPTABLES_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";


    $APP_XTABLES_VERSION=$tpl->icon_nothing();
    if(isset($UPDATES_ARRAY["APP_XTABLES"])){
        $APP_XTABLES_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_XTABLES_VERSION");
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_XTABLES');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_XTABLES}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_XTABLES_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    $APP_NDPI_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NDPI_INSTALLED"))==1){
        $APP_NDPI_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NDPI_VERSION");
    }
    if(isset($UPDATES_ARRAY["APP_NDPI"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NDPI');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_NDPI}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_NDPI_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    $warn_ico="";
    $APP_CROWDSEC_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_INSTALLED"))==1){
        $APP_CROWDSEC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_VERSION");
    }
    if(isset($UPDATES_ARRAY["APP_CROWDSEC"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_CROWDSEC');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_CROWDSEC",
                "TOKEN_VER"=>"APP_CROWDSEC_VERSION",
                "TOKEN_ENABLED"=>"EnableCrowdSec")
        );
        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_CROWDSEC}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_CROWDSEC_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    $html[]=APP_FAIL2BAN($UPDATES_ARRAY);


    $SURICATA_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_INSTALLED"))==1) {
        $SURICATA_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SURICATA"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SURICATA');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_SURICATA}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$SURICATA_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    $html[]=APP_NETMONIX($UPDATES_ARRAY);

//--------------------------------------------------------------------------------------------------
    $APP_SAMHAIN_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMHAIN_INSTALLED"))==1) {
        $APP_SAMHAIN_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMHAIN_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SAMHAIN"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SAMHAIN');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_SAMHAIN}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_SAMHAIN_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------------
    $APP_KEEPALIVED_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_INSTALLED"))==1) {
        $APP_KEEPALIVED_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_KEEPALIVED"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_KEEPALIVED');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_KEEPALIVED}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_KEEPALIVED_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";


    $APP_FAILOVER_CHECKER_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KEEPALIVED_INSTALLED"))==1) {
        $APP_FAILOVER_CHECKER_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("go-failover-checker-ver");
    }

    if(isset($UPDATES_ARRAY["APP_FAILOVER_CHECKER"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FAILOVER_CHECKER');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_KEEPALIVED} Checker:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_FAILOVER_CHECKER_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------------
    $FIREQOS_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FIREQOS_INSTALLED"))==1){
        $FIREQOS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FIREQOS_VERSION");
    }
    if(isset($UPDATES_ARRAY["APP_FIREHOL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FIREHOL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_FIREQOS}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$FIREQOS_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]=versions_section_cell("{monitoring}");
    $html[]="</tr>";
    //-------------------------------------------------------------------------------------------
    $warn_ico=null;
    $APP_MONIT_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MONIT_INSTALLED"))==1){
        $APP_MONIT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MONIT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MONIT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MONIT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_MONIT",
                "TOKEN_VER"=>"APP_MONIT_VERSION",
                "TOKEN_ENABLED"=>"MONIT_INSTALLED"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }

    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_MONIT}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_MONIT_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------
    $APP_WAZHU_VERSION=$tpl->icon_nothing();
    $warn_ico=null;
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_INSTALLED"))==1){
        $APP_WAZHU_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_WAZHU_VERSION");
    }



    if(isset($UPDATES_ARRAY["APP_WAZHU"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_WAZHU');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_WAZHU",
                "TOKEN_VER"=>"APP_WAZHU_VERSION",
                "TOKEN_ENABLED"=>"EnableWazhuCLient")
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_WAZHU}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_WAZHU_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------
    $APP_NAGIOS_CLIENT_VERSION=$tpl->icon_nothing();
    $warn_ico=null;
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NAGIOS_CLIENT_INSTALLED"))==1){
        $APP_NAGIOS_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NAGIOS_CLIENT_VERSION");
    }



    if(isset($UPDATES_ARRAY["APP_NAGIOS_CLIENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NAGIOS_CLIENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_NAGIOS_CLIENT",
                "TOKEN_VER"=>"APP_NAGIOS_CLIENT_VERSION",
                "TOKEN_ENABLED"=>"EnableNagiosClient")
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_NAGIOS_CLIENT}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_NAGIOS_CLIENT_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

//--------------------------------------------------------------------------------------------

    $html[]=APP_VNSTAT($UPDATES_ARRAY);


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"))==1){
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_MUNIN}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_VERSION")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    $warn_ico=null;
    $APP_NETDATA_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetDataInstalled"))==1){
        $APP_NETDATA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NETDATA_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_NETDATA"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NETDATA');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_NETDATA",
                "TOKEN_VER"=>"APP_NETDATA_VERSION",
                "TOKEN_ENABLED"=>"NetDataInstalled"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_NETDATA} (NetData):</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_NETDATA_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    $html[]=APP_NTOPNG($UPDATES_ARRAY);


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NMAPInstalled"))==1){
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_NMAP}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NMAPVersion")."</td>";
        $html[]="<td>&nbsp;</td>";
        $html[]="</tr>";
    }

    // Les lignes proxy qui suivent tombaient sous "Monitoring" : elles
    // rejoignent le groupe "Proxy services" ouvert par APP_SQUID().
    if($PROXY){
        $html[]="<tr>";
        $html[]=versions_section_cell("{proxy_services}");
        $html[]="</tr>";
    }
    $html[]=APP_UFDBGUARDD($UPDATES_ARRAY);
    $html[]=APP_HAPROXY($UPDATES_ARRAY);
    $html[]=APP_GREENSQL($UPDATES_ARRAY);
    $html[]=APP_RDPPROXY($UPDATES_ARRAY);
    $html[]=APP_3PROXY($UPDATES_ARRAY);
    $html[]=APP_REDSOCKS($UPDATES_ARRAY);
    $html[]=APP_WANPROXY($UPDATES_ARRAY);
    $html[]=APP_C_ICAP($UPDATES_ARRAY);
    $html[]=APP_GO_SHIELD_SERVER($UPDATES_ARRAY);
    $html[]=APP_GO_SHIELD_CONNECTOR($UPDATES_ARRAY);
    $html[]=APP_GO_AD_GROUP_SEARCH($UPDATES_ARRAY);
    $html[]=APP_GO_HOTSPOT_ENGINE($UPDATES_ARRAY);
    $html[]=APP_GO_PAC_ENGINE($UPDATES_ARRAY);
    $html[]=APP_GO_WEBFITLER_ERROR_PAGE_ENGINE($UPDATES_ARRAY);
    $html[]=APP_ADAGENT_CONNECTOR($UPDATES_ARRAY);
    $html[]=APP_ADAGENT_LBL($UPDATES_ARRAY);
    return @implode("\n",$html);
}


/**
 * Un intitule de section du tableau des versions.
 * Les onze intitules ouvraient un <th> et le fermaient par un </td>. Le
 * navigateur refermait donc le <th> de force et laissait un </td> orphelin, a
 * onze reprises dans un meme tableau.
 */
function versions_section_cell($title):string{
    return "<td colspan='3' style='background:#f4f8f9;padding:10px 20px'>"
        ."<span style='font-size:14px;font-weight:700;letter-spacing:.04em;"
        ."text-transform:uppercase;color:#006a62'>$title</span></td>";
}

function php_ini_path():string{
    ob_start();
    phpinfo();
    $s = ob_get_contents();
    ob_end_clean();
    if(preg_match("#<body>(.*?)</body>#is", $s,$re)){$s=$re[1];}
    return $s;
}
function APP_FIRECRACKER($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $APP_VERSION=$tpl->icon_nothing();
    $warn_ico=null;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_INSTALLED"))==1){
        $APP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRECRACKER_VERSION");
    }


    $bton="";
    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_FIRECRACKER",
            "TOKEN_VER"=>"APP_FIRECRACKER_VERSION",
            "TOKEN_ENABLED"=>"EnableFireCracker"),false);

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }


    if(isset($UPDATES_ARRAY["APP_FIRECRACKER"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FIRECRACKER');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_FIRECRACKER}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_NETMONIX($UPDATES_ARRAY):string
{
    $warn_ico="";
    $tpl = new template_admin();
    $bton = $tpl->icon_nothing();
    $html = array();
    if (isset($UPDATES_ARRAY["APP_NETMONIX"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NETMONIX');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }
    $RESULTS=$tpl->NOTIF_ARRAY(array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_NETMONIX",
            "TOKEN_VER"=>"NDPID_VERSION",
            "TOKEN_ENABLED"=>"EnableNetMonix"),false);

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }



    $NDPID_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NDPID_VERSION");

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_NETMONIX}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$NDPID_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_AD_GROUP_SEARCH($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_AD_GROUP_SEARCH"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_AD_GROUP_SEARCH');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
        $libmem=new lib_memcached();
        $Go_Shield_ADG_Version=trim($libmem->getKey("Go-Squid-Auth-Version"));
        if(trim($Go_Shield_ADG_Version==null)){
            $Go_Shield_ADG_Version="0.0.0";
        }
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_GO_AD_GROUP_SEARCH}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$Go_Shield_ADG_Version."</td>";
        $html[]="<td class='center awc-nowrap'>$bton</td>";
        $html[]="</tr>";
    }
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_AUTOFS($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton="";
    $APP_AUTOFS_VERSION=$tpl->icon_nothing();
    $warn_ico=null;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSInstalled"))==1){
        $APP_AUTOFS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_AUTOFS_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_AUTOFS"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_AUTOFS');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_AUTOFS",
            "TOKEN_VER"=>"APP_AUTOFS_VERSION",
            "TOKEN_ENABLED"=>""),false);


    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning ".ico_emergency."'></i>";
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{automount_center}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_AUTOFS_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);

}

function APP_FIRMWARES($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $APP_FIRMWARES_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRMWARES_INSTALLED"))==1){
        $APP_FIRMWARES_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_FIRMWARES_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_FIRMWARES"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FIRMWARES');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_FIRMWARES}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_FIRMWARES_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);

}
function APP_DOCKER($UPDATES_ARRAY):string{
    $tpl=new template_admin();
        $warn_ico = null;
        $bton = $tpl->icon_nothing();
        $APP_DOCKER_VERSION = $tpl->icon_nothing();
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_INSTALLED")) == 1) {
            $APP_DOCKER_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");
        }

        if (isset($UPDATES_ARRAY["APP_DOCKER"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_DOCKER');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


            $RESULTS = $tpl->NOTIF_ARRAY(
                array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                    "TOKEN_UPDATE_ARRAY" => "APP_DOCKER",
                    "TOKEN_VER" => "APP_DOCKER_VERSION",
                    "TOKEN_ENABLED" => "EnableDockerd")
            );

            if (isset($RESULTS["NEW_VER"])) {
                $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
            }


        }

        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_DOCKER}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>$APP_DOCKER_VERSION$warn_ico</td>";
        $html[]="<td class='center awc-nowrap'>$bton</td>";
        $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_AIDE($UPDATES_ARRAY):string{

    $tpl=new template_admin();

    $warn_ico = null;
    $bton = $tpl->icon_nothing();
    $APP_VERSION = $tpl->icon_nothing();
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_AIDE_INSTALLED")) == 1) {
        $APP_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_AIDE_VERSION");
    }

    if (isset($UPDATES_ARRAY["APP_AIDE"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_AIDE');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


        $RESULTS = $tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY" => "APP_AIDE",
                "TOKEN_VER" => "APP_AIDE_VERSION",
                "TOKEN_ENABLED" => "EnableAIDE")
        );

        if (isset($RESULTS["NEW_VER"])) {
            $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }


    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_AIDE}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_IWCONFIG($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_IWCONFIG=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IWCONFIG_INSTALLED"))==1){$APP_IWCONFIG=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IWCONFIG_VERSION");}


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_IWCONFIG}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_IWCONFIG</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_JAVA($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $JAVA_VERSION=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("JAVA_INSTALLED"))==1){$JAVA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("JAVA_VERSION");}
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_JAVA}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$JAVA_VERSION</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_VMTOOLS($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton="";
    if(isset($UPDATES_ARRAY["APP_VMTOOLS"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_VMTOOLS');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_TOOLS_INSTALLED"))==0){return "";}
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_VMTOOLS}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_TOOLS_VERSION")."$bton</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_IETD($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_CLIENT_INSTALLED"))==0){return "";}
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_IETD}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ISCSI_VERSION")."</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_NGINX_CONSOLE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $warn_ico=null;
    $bton="<span class='label label-default'>{unavailable}</span>";
    $APP_NGINX_CONSOLE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_CONSOLE_VERSION");



    if(isset($UPDATES_ARRAY["APP_NGINX_CONSOLE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NGINX_CONSOLE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_NGINX_CONSOLE",
            "TOKEN_VER"=>"APP_NGINX_CONSOLE_VERSION",
            "TOKEN_ENABLED"=>""),false);

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_NGINX_CONSOLE}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_NGINX_CONSOLE_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    // Un SECOND </tr> se trouvait ici, en double.
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SNMPD($UPDATES_ARRAY):string{
    $tpl=new template_admin();

    $SNMPD_VERSION=$tpl->icon_nothing();
    $SNMPD_BTN=$tpl->icon_nothing();

    if(isset($UPDATES_ARRAY["APP_SNMPD"])) {
        $SNMPD_BTN = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SNMPD');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPD_INSTALLED"))==1){ $SNMPD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPD_VERSION"); }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_SNMPD}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$SNMPD_VERSION</td>";
    $html[]="<td>$SNMPD_BTN</td>";
$html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_FPING($UPDATES_ARRAY):string{
        $tpl=new template_admin();
    $FPING_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FPING_INSTALLED"))==1){
        $FPING_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FPING_VERSION");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_FPING}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$FPING_VERSION</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_DBUS($UPDATES_ARRAY):string{
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_DBUS}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>".$GLOBALS["CLASS_SOCKETS"]->GET_INFO("dbus_daemon_version")."</td>";
    $html[]="<td>&nbsp;</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_XAPIAN($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
$XAPIAN_OMINDEX_VERSION=$tpl->icon_nothing();
if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XAPIAN_PHP_INSTALLED"))==1){
    $XAPIAN_OMINDEX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("XAPIAN_OMINDEX_VERSION");
}
$html[]="<tr>";
$html[]="<td class='awc-nowrap'><strong>{APP_XAPIAN}:</strong></td>";
$html[]="<td class='awc-nowrap awc-mono'>$XAPIAN_OMINDEX_VERSION</td>";
$html[]="<td>&nbsp;</td>";
$html[]="</tr>";if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_PHP_GEOIP2($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $APP_PHP_GEOIP2_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"))==1){
        $APP_PHP_GEOIP2_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GEOIPUPDATE_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_PHP_GEOIP2"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PHP_GEOIP2');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_PHP_GEOIP2}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_PHP_GEOIP2_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_ZABBIX_AGENT($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico=null;
    $bton="<span class='label label-default'>{unavailable}</span>";
    $APP_ZABBIX_AGENT_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ZABBIX_AGENT_INSTALLED"))==1){
        $APP_ZABBIX_AGENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_ZABBIX_AGENT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_ZABBIX_AGENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_ZABBIX_AGENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_ZABBIX_AGENT",
                "TOKEN_VER"=>"APP_ZABBIX_AGENT_VERSION",
                "TOKEN_ENABLED"=>"EnableZabbixAgent")
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }


    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_ZABBIX_AGENT}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_ZABBIX_AGENT_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SYNO_BACKUP($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $APP_SYNO_BACKUP_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYNO_BACKUP_INSTALLED"))==1){
        $APP_SYNO_BACKUP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYNO_BACKUP_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SYNO_BACKUP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SYNO_BACKUP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_SYNO_BACKUP}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_SYNO_BACKUP_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_URBACKUP($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $APP_URBACKUP_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_URBACKUP_INSTALLED"))==1){
        $APP_URBACKUP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_URBACKUP_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_URBACKUP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_URBACKUP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_URBACKUP}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_URBACKUP_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SYSLOGD($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico=null;

    $bton = $tpl->icon_nothing();
    $APP_RSYSLOGD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION");
    if(strlen($APP_RSYSLOGD_VERSION)<3){
        $APP_RSYSLOGD_VERSION=$tpl->icon_nothing();
    }

    if (isset($UPDATES_ARRAY["APP_SYSLOGD"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SYSLOGD');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_SYSLOGD",
                "TOKEN_VER"=>"APP_SYSLOGD_VERSION",
                "TOKEN_ENABLED"=>"ALWAYS")
        );


        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }

    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_RSYSLOG}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_RSYSLOGD_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_CLAMAV($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico=null;
    $bton="<span class='label label-default'>{unavailable}</span>";
    $ClamAVDaemonVersion=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonInstalled"))==1){
        $ClamAVDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonVersion");
    }

    if(isset($UPDATES_ARRAY["APP_CLAMAV"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_CLAMAV');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_CLAMAV",
            "TOKEN_VER"=>"ClamAVDaemonVersion",
            "TOKEN_ENABLED"=>"EnableClamavDaemon")
    );

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_CLAMAV}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$ClamAVDaemonVersion$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_MYSQL($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $warn_ico=null;
    $bton="&nbsp;";
    $APP_MYSQL_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_INSTALLED"))==1){
        $APP_MYSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_VERSION");
    }


    if(isset($UPDATES_ARRAY["APP_MYSQL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MYSQL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_MYSQL",
                "TOKEN_VER"=>"APP_MYSQL_VERSION",
                "TOKEN_ENABLED"=>"EnableMySQL")
        );


        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }


    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_MYSQL}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_MYSQL_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MANTICORE($UPDATES_ARRAY):string{
    $warn_ico="";
    $bton="";
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_MANTICORE_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MANTICORE_INSTALLED"))==1){
        $APP_MANTICORE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MANTICORE_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MANTICORE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MANTICORE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MANTICORE_INSTALLED"))==1) {
            $RESULTS = $tpl->NOTIF_ARRAY(
                array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                    "TOKEN_UPDATE_ARRAY" => "APP_MANTICORE",
                    "TOKEN_VER" => "APP_MANTICORE_VERSION",
                    "TOKEN_ENABLED" => "MantiCoreSearchEnabled")
            );
        }


        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }


    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_MANTICORE}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_MANTICORE_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}






function APP_POSTGRES($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="&nbsp;";
    $APP_POSTGRES_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_POSTGRES_INSTALLED"))==1){
        $APP_POSTGRES_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_POSTGRES_VERSION");
    }


    if(isset($UPDATES_ARRAY["APP_POSTGRES"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_POSTGRES');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_POSTGRES}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_POSTGRES_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_ELASTICSEARCH($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
        $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $ELASTICSEARCH_VERSION=$tpl->icon_nothing();
    $APP_KIBANA_VERSION=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_INSTALLED"))==1){
        $ELASTICSEARCH_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_VERSION");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KIBANA_INSTALLED"))==1){
        $APP_KIBANA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KIBANA_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_ELASTICSEARCH"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_ELASTICSEARCH');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_ELASTICSEARCH}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$ELASTICSEARCH_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_KIBANA}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_KIBANA_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_FILEBEAT($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $FILEBEAT_VERSION=$tpl->icon_nothing();



    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FILEBEAT_INSTALLED"))==1){
        $FILEBEAT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("FILEBEAT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_FILEBEAT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FILEBEAT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_FILEBEAT}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$FILEBEAT_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_REDIS_SERVER($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $APP_REDIS_SERVER_VERSION=$tpl->icon_nothing();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_INSTALLED"))==1){
        $APP_REDIS_SERVER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDIS_SERVER_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_REDIS_SERVER"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_REDIS_SERVER');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_REDIS_SERVER}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_REDIS_SERVER_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SSHPORTAL($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_SSHPORTAL_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SSHPORTAL_INSTALLED"))==1){
        $APP_SSHPORTAL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SSHPORTAL_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SSHPORTAL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SSHPORTAL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_SSHPORTAL}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_SSHPORTAL_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SHELLINABOX($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_SHELLINABOX_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SHELLINABOX_INSTALLED"))==1){
        $APP_SHELLINABOX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SHELLINABOX_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_SHELLINABOX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SHELLINABOX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_SHELLINABOX}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_SHELLINABOX_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_RUSTDESK($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_RUSTDESK_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RUSTDESK_INSTALLED"))==1){
        $APP_RUSTDESK_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RUSTDESK_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_RUSTDESK"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_RUSTDESK');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_RUSTDESK}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_RUSTDESK_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SQUID7($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton = $tpl->icon_nothing();
    $warn_ico = null;
    $SquidRealVersion = $tpl->icon_nothing();
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED")) == 1) {
        $SquidRealVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
    }
    if (!preg_match("#^6\.#", $SquidRealVersion)) {
        $SquidRealVersion = $tpl->icon_nothing();
    }

    if (isset($UPDATES_ARRAY["APP_SQUID6"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SQUID6');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


        $RESULTS = $tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY" => "APP_SQUID6",
                "TOKEN_VER" => "SquidRealVersion",
                "TOKEN_ENABLED" => "SQUIDEnable")
        );


        if (isset($RESULTS["NEW_VER"])) {
            $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }

    }

    $html[] = "<tr>";
    $html[] = "<td class='awc-nowrap'><strong>{APP_SQUID}:</strong></td>";
    $html[] = "<td class='awc-nowrap awc-mono'>$SquidRealVersion$warn_ico</td>";
    $html[] = "<td>$bton</td>";
    $html[] = "</tr>";
    return @implode("\n",$html);
}
function APP_SQUID($UPDATES_ARRAY):string{

    $PROXY=true;
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){$PROXY=false;}
    if(!$PROXY){
        return "";
    }
    $tpl=new template_admin();
    $html[] = "<tr>";
    $html[] = versions_section_cell("{proxy_services}");
    $html[] = "</tr>";

    $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
    if($DEBIAN_VERSION>10){
        $html[] = APP_SQUID7($UPDATES_ARRAY);
        if(!is_array($html)){$html=array();} return @implode("\n",$html);
    }




//------------------------------------------------------------------------------------------------------
        $bton = $tpl->icon_nothing();
        $warn_ico = null;
        $SquidRealVersion = $tpl->icon_nothing();
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED")) == 1) {
            $SquidRealVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
        }
        if (!preg_match("#^6\.#", $SquidRealVersion)) {
            $SquidRealVersion = $tpl->icon_nothing();
        }

        if (isset($UPDATES_ARRAY["APP_SQUID6"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_SQUID6');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


            $RESULTS = $tpl->NOTIF_ARRAY(
                array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                    "TOKEN_UPDATE_ARRAY" => "APP_SQUID6",
                    "TOKEN_VER" => "SquidRealVersion",
                    "TOKEN_ENABLED" => "SQUIDEnable")
            );


            if (isset($RESULTS["NEW_VER"])) {
                $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
            }

        }

        $html[] = "<tr>";
        $html[] = "<td class='awc-nowrap'><strong>{APP_SQUID6}:</strong></td>";
        $html[] = "<td class='awc-nowrap awc-mono'>$SquidRealVersion$warn_ico</td>";
        $html[] = "<td>$bton</td>";
        $html[] = "</tr>";
        $bton = $tpl->icon_nothing();
        $warn_ico = null;
        $SquidRealVersion = $tpl->icon_nothing();
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED")) == 1) {
            $SquidRealVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
        }
        if (!preg_match("#^5\.#", $SquidRealVersion)) {
            $SquidRealVersion = $tpl->icon_nothing();
        }

        if (isset($UPDATES_ARRAY["APP_SQUID"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_SQUID');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


            $RESULTS = $tpl->NOTIF_ARRAY(
                array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                    "TOKEN_UPDATE_ARRAY" => "APP_SQUID",
                    "TOKEN_VER" => "SquidRealVersion",
                    "TOKEN_ENABLED" => "SQUIDEnable")
            );


            if (isset($RESULTS["NEW_VER"])) {
                $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
            }

        }

        $html[] = "<tr>";
        $html[] = "<td class='awc-nowrap'><strong>{APP_SQUID} 5.x:</strong></td>";
        $html[] = "<td class='awc-nowrap awc-mono'>$SquidRealVersion$warn_ico</td>";
        $html[] = "<td>$bton</td>";
        $html[] = "</tr>";
//------------------------------------------------------------------------------------------------------
        $bton = $tpl->icon_nothing();
        $SquidRealVersion = $tpl->icon_nothing();
        if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED")) == 1) {
            $SquidRealVersion = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRealVersion");
        }

        if (preg_match("#^([0-9]+)\.#", $SquidRealVersion, $re)) {
            if (intval($re[1]) > 4) {
                $SquidRealVersion = $tpl->icon_nothing();
            }
        }

        if (isset($UPDATES_ARRAY["APP_SQUID_REV4"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_SQUID_REV4');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
        }

        $html[] = "<tr>";
        $html[] = "<td class='awc-nowrap'><strong>{APP_SQUID_REV4}:</strong></td>";
        $html[] = "<td class='awc-nowrap awc-mono'>$SquidRealVersion</td>";
        $html[] = "<td>$bton</td>";
        $html[] = "</tr>";
//-------------------------------------------------------------------------------------------------------------------
    if(!is_array($html)){$html=array();} return @implode("\n",$html);


}

function APP_DDNS_AGENT($UPDATES_ARRAY):string{

    $tpl=new template_admin();
    $APP_DDNS_AGENT_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DDNS_AGENT_INSTALLED"))==1){
        $APP_DDNS_AGENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DDNS_AGENT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_DDNS_AGENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DDNS_AGENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_DDNS_AGENT}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_DDNS_AGENT_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_PROFTPD($UPDATES_ARRAY):string
{
    $tpl = new template_admin();
    $warn_ico = "";
    $VERSION = $tpl->icon_nothing();
    $bton = $tpl->icon_nothing();
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDInstalled")) == 1) {
        $VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDVersion");
    }

    if (isset($UPDATES_ARRAY["APP_PROFTPD"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PROFTPD');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }


    $RESULTS = $tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY" => $UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY" => "APP_PROFTPD",
            "TOKEN_VER" => "ProFTPDVersion",
            "TOKEN_ENABLED" => "EnableProFTPD"), false
    );

    if (isset($RESULTS["NEW_VER"])) {
        $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }
    $html[] = "<tr>";
    $html[] = "<td class='awc-nowrap'><strong>{APP_PROFTPD}:</strong></td>";
    $html[] = "<td class='awc-nowrap awc-mono'>$VERSION$warn_ico</td>";
    $html[] = "<td class='center awc-nowrap'>$bton</td>";
    $html[] = "</tr>";
    if (!is_array($html)) {
        $html = array();
    }
    return @implode("\n", $html);
}

// mstpd (RSTP/MSTP des commutateurs L2). Ligne affichee quand le paquet Artica
// est installe ou que le depot le propose. La comparaison est celle de
// pkgupd_mstpd_latest() (class.updates.packages.inc) : elle tient compte du
// patchset "-articaN", que NOTIF_ARRAY ignore.
function APP_MSTPD($UPDATES_ARRAY):string
{
    $tpl = new template_admin();
    $installed = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MSTPD_INSTALLED")) == 1;
    $inRepo = isset($UPDATES_ARRAY["APP_MSTPD"]) && is_array($UPDATES_ARRAY["APP_MSTPD"]);
    if (!$installed && !$inRepo) {
        return "";
    }
    $warn_ico = "";
    $VERSION = $tpl->icon_nothing();
    $bton = $tpl->icon_nothing();
    $cur = "";
    if ($installed) {
        $cur = trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MSTPD_VERSION"));
        if ($cur !== "") {
            $VERSION = htmlspecialchars($cur);
        }
    }
    if ($inRepo) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MSTPD');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }
    if ($cur !== "" && pkgupd_mstpd_latest($UPDATES_ARRAY, $cur) > 0) {
        $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }
    $html = array();
    $html[] = "<tr>";
    $html[] = "<td class='awc-nowrap'><strong>{APP_MSTPD}:</strong></td>";
    $html[] = "<td class='awc-nowrap awc-mono'>$VERSION$warn_ico</td>";
    $html[] = "<td class='center awc-nowrap'>$bton</td>";
    $html[] = "</tr>";
    return implode("\n", $html);
}

function APP_PFRING_AVX($UPDATES_ARRAY){
    $tpl=new template_admin();

    $bton="<span class='label label-default'>{unavailable}</span>";


    if(isset($UPDATES_ARRAY["APP_PFRING_AVX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PFRING_AVX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $DHCPD_VERSION=php_uname("r");


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_PFRING_AVX}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$DHCPD_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);



}

function PFRING($UPDATES_ARRAY):string{
    $tpl=new template_admin();

    $bton="<span class='label label-default'>{unavailable}</span>";


    if(isset($UPDATES_ARRAY["PFRING"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=PFRING');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $DHCPD_VERSION=php_uname("r");


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{PFRING}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$DHCPD_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_DHCP($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $DHCPD_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_INSTALLED"))==1){
        $DHCPD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");
    }

    if(isset($UPDATES_ARRAY["APP_DHCP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DHCP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_DHCP}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$DHCPD_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MSKTUTIL($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $MSKTUTIL_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $warn_ico=null;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSKTUTIL_INSTALLED"))==1){
        $MSKTUTIL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSKTUTIL_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MSKTUTIL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MSKTUTIL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_MSKTUTIL",
            "TOKEN_VER"=>"MSKTUTIL_VERSION",
            "TOKEN_ENABLED"=>"MSKTUTIL_INSTALLED"),false
    );

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_MSKTUTIL}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$MSKTUTIL_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}


function APP_TAILSCALE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_TAILSCALE_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_TAILSCALE_INSTALLED"))==1){
        $APP_TAILSCALE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_TAILSCALE_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_TAILSCALE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_TAILSCALE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_TAILSCALE}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_TAILSCALE_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
        if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_SAMBA($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    //if(isset($UPDATES_ARRAY["APP_SAMBA"])){return "";}
    $tpl=new template_admin();
    $APP_SAMBA_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SAMBA_INSTALLED"))==1){
        $APP_SAMBA_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SAMBA_VERSION");
    }
    $warn_ico=null;

    if(isset($UPDATES_ARRAY["APP_SAMBA"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SAMBA');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_SAMBA",
                "TOKEN_VER"=>"APP_SAMBA_VERSION",
                "TOKEN_ENABLED"=>"EnableSamba"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_SAMBA_SMBD}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_SAMBA_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_TRIVY($UPDATES_ARRAY):string
{
    if (isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])) {
        return "";
    }
    $tpl = new template_admin();
    $APP_TRIVY_VERSION = $tpl->icon_nothing();
    $bton = "<span class='label label-default'>{unavailable}</span>";
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_TRIVY_INSTALLED")) == 1) {
        $APP_TRIVY_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_TRIVY_VERSION");
    }

    $warn_ico = null;

    if (isset($UPDATES_ARRAY["APP_TRIVY"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_TRIVY');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


        $RESULTS = $tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY" => "APP_TRIVY",
                "TOKEN_VER" => "APP_TRIVY_VERSION",
            ), false
        );

        if (isset($RESULTS["NEW_VER"])) {
            $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[] = "<tr>";
    $html[] = "<td class='awc-nowrap'><strong>{APP_TRIVY}:</strong></td>";
    $html[] = "<td class='awc-nowrap awc-mono'>$APP_TRIVY_VERSION$warn_ico</td>";
    $html[] = "<td class='center awc-nowrap'>$bton</td>";
    $html[] = "</tr>";

    if (!is_array($html)) {
        $html = array();
    }
    return @implode("\n", $html);
}

function APP_OPENVPN($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_OPENVPN_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OPENVPN_INSTALLED"))==1){
        $APP_OPENVPN_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_OPENVPN_VERSION");
    }

    $warn_ico=null;

    if(isset($UPDATES_ARRAY["APP_OPENVPN"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_OPENVPN');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");


        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_OPENVPN",
                "TOKEN_VER"=>"OpenVPNVersion",
                "TOKEN_ENABLED"=>"EnableOpenVPNServer"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_OPENVPN}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_OPENVPN_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_PPTP_CLIENT($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_PPTP_CLIENT_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PPTP_CLIENT_INSTALLED"))==1){
        $APP_PPTP_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PPTP_CLIENT_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_PPTP_CLIENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PPTP_CLIENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_PPTP_CLIENT}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_PPTP_CLIENT_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_STRONGSWAN($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $IPSEC_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPSEC_INSTALLED"))==1){
        $IPSEC_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPSEC_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_STRONGSWAN"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_STRONGSWAN');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_STRONGSWAN}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$IPSEC_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_SPLUNK_FORWARDER($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $SPLUNK_UF_VERSION = $tpl->icon_nothing();
    $bton = $tpl->icon_nothing();
    if (intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SPLUNK_FORWARDER_INSTALLED")) == 1) {
        $SPLUNK_UF_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SPLUNK_FORWARDER_VERSION");
    }

    if (isset($UPDATES_ARRAY["APP_SPLUNK_FORWARDER"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_SPLUNK_FORWARDER');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }

    $html[] = "<tr>";
    $html[] = "<td class='awc-nowrap'><strong>{APP_SPLUNK_FORWARDER}:</strong></td>";
    $html[] = "<td class='awc-nowrap awc-mono'>$SPLUNK_UF_VERSION</td>";
    $html[] = "<td>$bton</td>";
    $html[] = "</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_NGINX($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $APP_NGINX_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_INSTALLED"))==1){
        $APP_NGINX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_NGINX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NGINX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_NGINX}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_NGINX_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function WP_CLIENT($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $WP_CLIENT_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WP_CLIENT_INSTALLED"))==1){$WP_CLIENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WP_CLIENT_VERSION");}
    $warn_ico=null;
    if(isset($UPDATES_ARRAY["WP_CLIENT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=WP_CLIENT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"WP_CLIENT",
                "TOKEN_VER"=>"WP_CLIENT_VERSION",
                "TOKEN_ENABLED"=>"EnableWordpressManagement"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }

    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{WP_CLIENT}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$WP_CLIENT_VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_POSTFIX($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_POSTFIX_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"))==1){
        $APP_POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_POSTFIX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_POSTFIX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_POSTFIX}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_POSTFIX_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MSMTP($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $MSMTP_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSMTP_INSTALLED"))==1){
        $MSMTP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSMTP_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MSMTP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MSMTP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_MSMTP}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$MSMTP_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MILTER_GREYLIST($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_MILTER_GREYLIST_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_INSTALLED"))==1){
        $APP_MILTER_GREYLIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MILTER_GREYLIST"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MILTER_GREYLIST');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_MILTERGREYLIST}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_MILTER_GREYLIST_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_MILTER_REGEX($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();

    $APP_MILTER_REGEX_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_REGEX_INSTALLED"))==1){
        $APP_MILTER_REGEX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_REGEX_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MILTER_REGEX"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MILTER_REGEX');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_MILTER_REGEX}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_MILTER_REGEX_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_MIMEDEFANG($UPDATES_ARRAY):string{
        if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
        $tpl=new template_admin();
    $APP_MIMEDEFANG_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangInstalled"))==1){
        $APP_MIMEDEFANG_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangVersion");
    }

    if(isset($UPDATES_ARRAY["APP_MIMEDEFANG"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MIMEDEFANG');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_VALVUAD}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_MIMEDEFANG_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_RBLDNSD($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_RBLDNSD_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RBLDNSD_INSTALLED"))==1){
        $APP_RBLDNSD_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RBLDNSD_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_RBLDNSD"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_RBLDNSD');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_RBLDNSD}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_RBLDNSD_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_CYRUS($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $warn_ico="";
    $bton="<span class='label label-default'>{unavailable}</span>";
    $VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CYRUS_INSTALLED"))==1){
        $VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CYRUS_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_CYRUS"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_CYRUS');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_CYRUS",
            "TOKEN_VER"=>"APP_CYRUS_VERSION",
            "TOKEN_ENABLED"=>"EnableCyrusImap"),false
    );


    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_CYRUS}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$warn_ico$VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_DNSCRYPT_PROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_DNSCRYPT_PROXY_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSCRYPT_PROXY_INSTALLED"))==1){$APP_DNSCRYPT_PROXY_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSCRYPT_PROXY_VERSION");}

    if(isset($UPDATES_ARRAY["APP_DNSCRYPT_PROXY"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DNSCRYPT_PROXY');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_DNSCRYPT_PROXY}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_DNSCRYPT_PROXY_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_UNBOUND($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $UnboundVersion=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundInstalled"))==1){$UnboundVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundVersion");}

    if(isset($UPDATES_ARRAY["APP_UNBOUND"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_UNBOUND');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }



    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_UNBOUND}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$UnboundVersion</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}


function APP_DOH_PROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $CurVersion=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $warn_ico="";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOH_PROXY_INSTALLED"))==1){$CurVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOH_PROXY_VERSION");}

    if(isset($UPDATES_ARRAY["APP_DOH_PROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DOH_PROXY');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_DOH_PROXY",
            "TOKEN_VER"=>"APP_DOH_PROXY_VERSION",
            "TOKEN_ENABLED"=>"EnableDohProxy"),false
    );


    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }




    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_DOH_PROXY}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$CurVersion$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_CLOUDFLARE_DNS($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $CurVersion=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $warn_ico="";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLOUDFLARED_INSTALLED"))==1){$CurVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLOUDFLARED_VERSION");}

    if(isset($UPDATES_ARRAY["APP_DOH_PROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_CLOUDFLARE_DNS');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_CLOUDFLARE_DNS",
            "TOKEN_VER"=>"CLOUDFLARED_VERSION",
            "TOKEN_ENABLED"=>"EnableCloudflared"),false
    );


    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }




    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_CLOUDFLARE_DNS}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$CurVersion$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_VNSTAT($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_VNSTAT_VERSION");
    $bton="<span class='label label-default'>{unavailable}</span>";
    if($version==null){ $version=$tpl->icon_nothing(); }
    $warn_ico="";
    if (isset($UPDATES_ARRAY["APP_VNSTAT"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_VNSTAT');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");

        $RESULTS = $tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY" => "APP_VNSTAT",
                "TOKEN_VER" => "APP_VNSTAT_VERSION",
                "TOKEN_ENABLED" => "EnableVnStat")
        );

        if (isset($RESULTS["NEW_VER"])) {
            $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_VNSTAT}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$version$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_DNSDIST($UPDATES_ARRAY):string{
    $warn_ico="";
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $dnsdist_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $bton="<span class='label label-default'>{unavailable}</span>";
    if($dnsdist_version==null){ $dnsdist_version=$tpl->icon_nothing(); }

    if (isset($UPDATES_ARRAY["APP_DNSDIST"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DNSDIST');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


        $RESULTS = $tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY" => "APP_DNSDIST",
                "TOKEN_VER" => "APP_DNSDIST_VERSION",
                "TOKEN_ENABLED" => "EnableDNSDist")
        );

        if (isset($RESULTS["NEW_VER"])) {
            $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_DNSDIST}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$dnsdist_version$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_DNSDIST9($UPDATES_ARRAY):string{
    $warn_ico="";
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $dnsdist_version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $bton="<span class='label label-default'>{unavailable}</span>";
    if($dnsdist_version==null){ $dnsdist_version=$tpl->icon_nothing(); }

    if (isset($UPDATES_ARRAY["APP_DNSDIST9"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DNSDIST9');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");


        $RESULTS = $tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY" => $UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY" => "APP_DNSDIST9",
                "TOKEN_VER" => "APP_DNSDIST_VERSION",
                "TOKEN_ENABLED" => "EnableDNSDist")
        );

        if (isset($RESULTS["NEW_VER"])) {
            $warn_ico = "&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_DNSDIST9}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$dnsdist_version$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}


function APP_DSC($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $DSCVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DSCVersion");
    $bton="<span class='label label-default'>{unavailable}</span>";

    if(isset($UPDATES_ARRAY["APP_DSC"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_DSC');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    if($DSCVersion==null){
        $DSCVersion=$tpl->icon_nothing();
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_DSC}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$DSCVersion</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    // Le </tr> manquait: la ligne ne se fermait que parce qu'APP_PDNS(), emise
    // juste apres, DEBUTAIT par un </tr> orphelin. Les deux se compensaient par
    // hasard -- reordonner ces deux appels cassait la structure du tableau.
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}


function APP_PDNS($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_PDNS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSVersion");
    $APP_PDNS_RECURSOR_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSRecursorVersion");

    $bton="<span class='label label-default'>{unavailable}</span>";
    if(isset($UPDATES_ARRAY["APP_PDNS"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PDNS');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if($APP_PDNS_VERSION==null){
        $APP_PDNS_VERSION=$tpl->icon_nothing();
    }
    if($APP_PDNS_RECURSOR_VERSION==null){
        $APP_PDNS_RECURSOR_VERSION=$tpl->icon_nothing();
    }

    // Un </tr> orphelin ouvrait cette fonction: voir le commentaire d'APP_DSC().
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_PDNS}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_PDNS_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_PDNS_RECURSOR}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_PDNS_RECURSOR_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_FAIL2BAN($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $FAIL2BAN_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"))==1) {
        $FAIL2BAN_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_FAIL2BAN"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_FAIL2BAN');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_FAIL2BAN}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$FAIL2BAN_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_NTOPNG($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $NTOPNG_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtopNGInstalled"))==1) {
        $NTOPNG_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTOPNG_VERSION");

    }
    if(isset($UPDATES_ARRAY["APP_NTOPNG"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_NTOPNG');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_NTOPNG} (NtopNG):</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$NTOPNG_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GREENSQL($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $APP_GREENSQL_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GREENSQL_INSTALLED"))==1){
        $APP_GREENSQL_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GREENSQL_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_GREENSQL"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GREENSQL');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_GREENSQL}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_GREENSQL_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_RDPPROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $APP_RDPPROXY_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_INSTALLED"))==1){
        $APP_RDPPROXY_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_RDPPROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_RDPPROXY');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_RDPPROXY}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_RDPPROXY_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_3PROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $APP_3PROXY_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_INSTALLED"))==1){
        $APP_3PROXY_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_3PROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_3PROXY');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_3PROXY}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_3PROXY_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_REDSOCKS($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $APP_REDSOCKS_VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDSOCKS_INSTALLED"))==1){
        $APP_REDSOCKS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_REDSOCKS_VERSION");
    }


    $bton=$tpl->button_autnonome("{install_upgrade2}",
        "Loadjs('fw.apt-redsocks.php');",
        "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_REDSOCKS}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$APP_REDSOCKS_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_HAPROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_INSTALLED"))==1){$VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HAPROXY_VERSION");}

    if(isset($UPDATES_ARRAY["APP_HAPROXY"])){
    $bton=$tpl->button_autnonome("{install_upgrade2}",
        "Loadjs('fw.system.upgrade-software.php?product=APP_HAPROXY');",
        "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_HAPROXY_SERVICE}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_PLAKAR($UPDATES_ARRAY):string{

    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PLAKAR_INSTALLED"))==1){
        $VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_PLAKAR_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_PLAKAR"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_PLAKAR');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_PLAKAR}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_CLICKHOUSE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLICKHOUSE_INSTALLED"))==1){
        $VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CLICKHOUSE_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_CLICKHOUSE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_CLICKHOUSE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_CLICKHOUSE}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GLFS($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GLFS_INSTALLED"))==1){
        $VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_GLFS_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_GLUSTERFS"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GLUSTERFS');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_GLUSTERFS}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_RSPAMD($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $VERSION=$tpl->icon_nothing();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RSPAMD_INSTALLED"))==1){
        $VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RSPAMD_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_RSPAMD"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_RSPAMD');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{rspamd.title}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}


function APP_MATTERMOST($UPDATES_ARRAY):string{
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $VERSION=$tpl->icon_nothing();
    $warn_ico="";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MATTERMOST_INSTALLED"))==1){
        $VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MATTERMOST_VERSION");
    }

    if(isset($UPDATES_ARRAY["APP_MATTERMOST"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_MATTERMOST');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_MATTERMOST",
            "TOKEN_VER"=>"APP_MATTERMOST_VERSION",
            "TOKEN_ENABLED"=>"EnableMattermost"),false
    );

    $APP_MATTERMOST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MATTERMOST_VERSION");
    if(strlen($APP_MATTERMOST_VERSION)>1) {
        $VERSION = $APP_MATTERMOST_VERSION;
    }

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_MATTERMOST}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$VERSION$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_QAT($UPDATES_ARRAY):string{

    $tpl=new template_admin();
    $warn_ico=null;
    $IntelQATVersion=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IntelQATInstalled"))==1){
        $IntelQATVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("IntelQATVersion");
    }

    if(isset($UPDATES_ARRAY["APP_QAT"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_QAT');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_QAT",
            "TOKEN_VER"=>"IntelQATVersion",
            "TOKEN_ENABLED"=>"EnableIntelQAT"),false
    );


    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>Intel QuickAssist:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$IntelQATVersion$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function APP_UFDBGUARDD($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $warn_ico=null;
    $UFDBDaemonVersion=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_UFDBGUARD_INSTALLED"))==1){
        $UFDBDaemonVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBDaemonVersion");
    }

    if(isset($UPDATES_ARRAY["APP_UFDBGUARDD"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_UFDBGUARDD');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $RESULTS=$tpl->NOTIF_ARRAY(
        array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
            "TOKEN_UPDATE_ARRAY"=>"APP_UFDBGUARDD",
            "TOKEN_VER"=>"UFDBDaemonVersion",
            "TOKEN_ENABLED"=>"EnableUfdbGuard"),false
    );

    if(isset($RESULTS["NEW_VER"])){
        $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_UFDBGUARD}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$UFDBDaemonVersion$warn_ico</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}

function APP_WANPROXY($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $WANPROXYVERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WANPROXY_INSTALLED"))==1){
        $WANPROXYVERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WANPROXY_VERSION");
        if($WANPROXYVERSION==null){$WANPROXYVERSION="2170101";}
    }

    if(isset($UPDATES_ARRAY["APP_WANPROXY"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_WANPROXY');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }


    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_WANPROXY}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$WANPROXYVERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_C_ICAP($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $C_ICAP_VERSION=$tpl->icon_nothing();
    $bton="<span class='label label-default'>{unavailable}</span>";

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("C_ICAP_INSTALLED"))==1){
        $C_ICAP_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapVersion");
    }

    if(isset($UPDATES_ARRAY["APP_C_ICAP"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_C_ICAP');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html[]="<tr>";
    $html[]="<td class='awc-nowrap'><strong>{APP_C_ICAP}:</strong></td>";
    $html[]="<td class='awc-nowrap awc-mono'>$C_ICAP_VERSION</td>";
    $html[]="<td class='center awc-nowrap'>$bton</td>";
    $html[]="</tr>";
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_SHIELD_SERVER($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $warn_ico=null;

    if(isset($UPDATES_ARRAY["APP_GO_SHIELD_SERVER"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_SHIELD_SERVER');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }

    $html=array();

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Enable"))==1){
        $Go_Shield_Server_Addr = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr");
        $Go_Shield_Server_Port = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
        if ($Go_Shield_Server_Addr==null){$Go_Shield_Server_Addr="127.0.0.1";}
        if($Go_Shield_Server_Port==0){$Go_Shield_Server_Port=3333;}
        $cURLConnection = curl_init();

        $RESULTS=$tpl->NOTIF_ARRAY(
            array("UPDATES_ARRAY"=>$UPDATES_ARRAY,
                "TOKEN_UPDATE_ARRAY"=>"APP_GO_SHIELD_SERVER",
                "TOKEN_VER"=>"APP_GO_SHIELD_VERSION",
                "TOKEN_ENABLED"=>"Go_Shield_Server_Enable"),false
        );

        if(isset($RESULTS["NEW_VER"])){
            $warn_ico="&nbsp;<i class='text-warning fa-solid fa-light-emergency-on'></i>";
        }

        if(function_exists("json_decode")) {
            curl_setopt($cURLConnection, CURLOPT_URL, "http://$Go_Shield_Server_Addr:$Go_Shield_Server_Port/get-version");
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
            $resp = curl_exec($cURLConnection);
            if(function_exists("curl_close")) {
                curl_close($cURLConnection);
            }

            $jsonArrayResponse = json_decode($resp, true);
            $Go_Shield_Server_Version = $tpl->icon_nothing();
            if (isset($jsonArrayResponse["version"])) {
                $Go_Shield_Server_Version = $jsonArrayResponse["version"];
            }
            $html[] = "<tr>";
            $html[] = "<td class='awc-nowrap'><strong>{APP_GO_SHIELD_SERVER}:</strong></td>";
            $html[] = "<td class='awc-nowrap awc-mono'>" . $Go_Shield_Server_Version . "$warn_ico</td>";
            $html[] = "<td>$bton</td>";
            $html[] = "</tr>";
        }

        $bton="<span class='label label-default'>{unavailable}</span>";

        if(isset($UPDATES_ARRAY["APP_GO_FS"])){
            $bton=$tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_GO_FS');",
                "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
        }


            $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("go-shield-server-fs-watcher-ver");
            $html[]="<tr>";
            $html[]="<td class='awc-nowrap'><strong>{APP_GO_FS}:</strong></td>";
            $html[]="<td class='awc-nowrap awc-mono'>".$version."</td>";
            $html[]="<td class='center awc-nowrap'>$bton</td>";
            $html[]="</tr>";

    }
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_SHIELD_CONNECTOR($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_SHIELD_CONNECTOR"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_SHIELD_CONNECTOR');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
        $libmem=new lib_memcached();
        $Go_Shield_Connector_Version=trim($libmem->getKey("Go-Shield-Connector-Version"));
        if(trim($Go_Shield_Connector_Version==null)){
            $Go_Shield_Connector_Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GO_SHIELD_CONNECTOR_VERSION");
        }
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_GO_SHIELD_CONNECTOR}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$Go_Shield_Connector_Version."</td>";
        $html[]="<td class='center awc-nowrap'>$bton</td>";
        $html[]="</tr>";
    }

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_HOTSPOT_ENGINE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_HOTSPOT_ENGINE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_HOTSPOT_ENGINE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"))==1){
        $Go_Hotspot_Ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HOTSPOTWEB_VERSION");
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_GO_HOTSPOT_ENGINE}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$Go_Hotspot_Ver."</td>";
        $html[]="<td class='center awc-nowrap'>$bton</td>";
        $html[]="</tr>";
    }

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_PAC_ENGINE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_PAC_ENGINE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_PAC_ENGINE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyPac"))==1){
        $Go_Pac_Ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PROXYPAC_VERSION");
        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_GO_PAC_ENGINE}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$Go_Pac_Ver."</td>";
        $html[]="<td class='center awc-nowrap'>$bton</td>";
        $html[]="</tr>";
    }
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_GO_WEBFITLER_ERROR_PAGE_ENGINE($UPDATES_ARRAY):string{
    if(isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])){return "";}
    $tpl=new template_admin();
    $bton="<span class='label label-default'>{unavailable}</span>";
    $html=array();
    if(isset($UPDATES_ARRAY["APP_GO_WEBFITLER_ERROR_PAGE_ENGINE"])){
        $bton=$tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_GO_WEBFITLER_ERROR_PAGE_ENGINE');",
            "fa-download","AsSystemAdministrator",0,"btn-primary btn-xs");
    }
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUseInternalService"))==1){

        $Go_ErrorPage_Ver=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WEBERRORPAGE_VERSION");

        $html[]="<tr>";
        $html[]="<td class='awc-nowrap'><strong>{APP_GO_WEBFITLER_ERROR_PAGE_ENGINE}:</strong></td>";
        $html[]="<td class='awc-nowrap awc-mono'>".$Go_ErrorPage_Ver."</td>";
        $html[]="<td class='center awc-nowrap'>$bton</td>";
        $html[]="</tr>";
    }

    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}
function APP_ADAGENT_LBL($UPDATES_ARRAY):string
{
    if (isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])) {
        return "";
    }
    $tpl = new template_admin();
    $html = array();
    if (isset($UPDATES_ARRAY["APP_ADAGENT_LBL"])) {
        $bton = $tpl->button_autnonome("{install_upgrade2}",
            "Loadjs('fw.system.upgrade-software.php?product=APP_ADAGENT_LBL');",
            "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
        $Ver = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAGENT_VERSION");
        $html[] = "<tr>";
        $html[] = "<td class='awc-nowrap'><strong>{APP_ADAGENT_LBL}:</strong></td>";
        $html[] = "<td class='awc-nowrap awc-mono'>" . $Ver . "</td>";
        $html[] = "<td>$bton</td>";
        $html[] = "</tr>";
    }
    return @implode("\n", $html);
}
function APP_ADAGENT_CONNECTOR($UPDATES_ARRAY):string{
    if (isset($UPDATES_ARRAY["REVERSE_APPLIANCE"])) {
        return "";
    }
    $tpl = new template_admin();
    $html = array();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"))==1){
        if(isset($UPDATES_ARRAY["APP_ADAGENT_CONNECTOR"])) {
            $bton = $tpl->button_autnonome("{install_upgrade2}",
                "Loadjs('fw.system.upgrade-software.php?product=APP_ADAGENT_CONNECTOR');",
                "fa-download", "AsSystemAdministrator", 0, "btn-primary btn-xs");
            $libmem=new lib_memcached();
            $ad_agent_Ver=trim($libmem->getKey("Go-Squid-AD-Agent-Client-Version"));
            if(trim($ad_agent_Ver==null)){
                $ad_agent_Ver="0.0.0";
            }
            $html[] = "<tr>";
            $html[] = "<td class='awc-nowrap'><strong>{APP_ADAGENT_CONNECTOR}:</strong></td>";
            $html[] = "<td class='awc-nowrap awc-mono'>" . $ad_agent_Ver . "</td>";
            $html[] = "<td>$bton</td>";
            $html[] = "</tr>";
        }
    }
    if(!is_array($html)){$html=array();} return @implode("\n",$html);
}