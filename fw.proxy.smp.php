<?php
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
// Page "instances multiples" du proxy, refondue sur la maquette
// /home/claude/images/stitch_multi_cpu_proxy_redesign.zip : quatre tuiles de
// telemetrie, puis une ligne par coeur physique portant son instance squid.
//
// TROIS sources, jointes ici et nulle part ailleurs :
//   /proxy/monitor/filedesc  -> l'agregat de l'instance (mgr:info)
//   /proxy/smp/instances     -> un processus par worker: PID, RSS, descripteurs
//   mgr:utilization          -> cpu_usage et requetes, PAR kid
//
// Ce qui les relie est SquidSMPConfig, dont la CLE est le numero de process
// squid (son "kid") et la VALEUR le coeur sur lequel il est epingle -- c'est
// ainsi que le daemon le lit (articarest squid/Caches.conf.go, boucle
// cpu_affinity_map). Coeur et instance sont donc deux axes distincts des que la
// selection de coeurs a un trou, et la page les affiche comme tels.
if(isset($_POST["NONE"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");

if(isset($_GET["build-page"])){smp_build_page();exit;}
if(isset($_GET["live"])){smp_live();exit;}
if(isset($_GET["switch-cpu-number"])){switch_cpu_number();exit;}
if(isset($_GET["SquidSMPDisabled"])){switch_SquidSMPDisabled();exit;}
if(isset($_GET["performance-after"])){performance_save_after();exit;}
if(isset($_POST["PerformanceSave"])){PerformanceSave();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{your_proxy} - {multiple_instances}",
        ico_cpu,"{SQUID_SMP_EXPLAIN}<br>{squid_worker_explain}",
        "$page?build-page=yes","proxy-smp","progress-smp-restart",false,"smp-content");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{multiple_instances}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// Le squelette ne porte QUE le conteneur rafraichi et son minuteur. Le minuteur
// vit ici et non dans le fragment: reconduit a chaque tick, il en empilerait un
// nouveau par rafraichissement. "smp-live" est aussi sa sentinelle -- il est
// cree ici, donc il disparait avec le contenu de la page et le minuteur s'arrete
// de lui-meme (ArticaRefresh.tick() detruit l'entree, sans rien journaliser).
function smp_build_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='smp-live'></div>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('smp-live','$page?live=yes');";
    $html[]=$tpl->RefreshInterval_js("smp-live",$page,"live=yes",10);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// ----------------------------------------------------------------------------
// Sources de donnees
// ----------------------------------------------------------------------------

// L'agregat de l'instance. Les noms comptent: cpu_usage (et NON "full_usage",
// qui n'a jamais existe cote Go) et average_http_requests, porte par Info et non
// par la racine -- les deux erreurs affichaient 0 en permanence.
function smp_filedesc(){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc"));
    if(!is_object($json)){return null;}
    if(!property_exists($json,"Info")){return null;}
    if(!is_object($json->Info)){return null;}
    return $json->Info;
}

function smp_num($obj,$key){
    if(!is_object($obj)){return 0;}
    if(!property_exists($obj,$key)){return 0;}
    return floatval($obj->$key);
}

// L'inventaire des processus. Une appliance dont le binaire articarest est plus
// ancien que cette route repond "Not Found" (donc null apres json_decode): la
// page se contente alors des metriques du cache manager et affiche "-" la ou le
// noyau aurait parle. C'est voulu -- aucune de ces colonnes n'est vitale.
function smp_instances():array{
    $out=array("available"=>false,"smp"=>false,"workers"=>0,"ports"=>array(),"bykid"=>array());
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/smp/instances"));
    if(!is_object($json)){return $out;}
    if(!property_exists($json,"Info")){return $out;}
    $info=$json->Info;
    if(!is_object($info)){return $out;}
    $out["available"]=true;
    if(property_exists($info,"smp")){$out["smp"]=($info->smp==true);}
    if(property_exists($info,"workers")){$out["workers"]=intval($info->workers);}
    if(property_exists($info,"ports")){
        if(is_array($info->ports)){
            foreach($info->ports as $p){$out["ports"][]=intval($p);}
        }
    }
    if(property_exists($info,"instances")){
        if(is_array($info->instances)){
            foreach($info->instances as $i){
                if(!is_object($i)){continue;}
                $kid=intval(smp_num($i,"kid"));
                if($kid<1){continue;}
                $out["bykid"][$kid]=array(
                    "pid"=>intval(smp_num($i,"pid")),
                    "rss_kb"=>intval(smp_num($i,"rss_kb")),
                    "fd_open"=>intval(smp_num($i,"fd_open")),
                    "uptime"=>intval(smp_num($i,"uptime_seconds")));
            }
        }
    }
    return $out;
}

// cpu_usage et requetes/sec par kid, lus dans mgr:utilization. Fenetre de 5
// minutes, repli sur les totaux depuis le demarrage tant qu'elle n'est pas
// remplie.
function smp_kid_metrics():array{
    $out=array();
    $GLOBALS["makeQueryForce"]=true;
    $cache_manager=new cache_manager();
    $data=$cache_manager->makeQuery("utilization",true);
    if(!$cache_manager->ok){return $out;}
    if(!is_array($data)){return $out;}

    $MAIN=array();
    $CPU=0;
    $mins=0;
    foreach($data as $line){
        $line=trim($line);
        // "by kid1 {" -- le kid EST le numero de process squid. L'ancien code
        // ajoutait 1 ici, ce qui decalait chaque mesure d'une instance.
        if(preg_match("#by kid([0-9]+)\s+\{#i",$line,$re)){$CPU=intval($re[1]);continue;}
        if(preg_match("#Totals since cache startup#",$line)){$mins=0;continue;}
        if(preg_match("#Last\s+([0-9]+)\s+minutes#i",$line,$re)){$mins=intval($re[1]);continue;}
        if(preg_match("#Last\s+([0-9]+)\s+hour#i",$line,$re)){$mins=intval($re[1])*60;continue;}
        if(preg_match("#Last\s+([0-9]+)\s+day#i",$line,$re)){$mins=intval($re[1])*1440;continue;}
        if(preg_match("#Last\s+hour#i",$line)){$mins=60;continue;}
        if(preg_match("#Last\s+day#i",$line)){$mins=1440;continue;}
        if(strpos($line,"=")===false){continue;}
        $tb=explode("=",$line,2);
        if($CPU<1){continue;}
        $MAIN[$CPU][$mins][trim($tb[0])]=trim($tb[1]);
    }

    foreach($MAIN as $kid=>$windows){
        $cpu_usage=0;
        $requests=0;
        $raw_cpu="";
        $raw_req="";
        if(isset($windows[5]["cpu_usage"])){$raw_cpu=$windows[5]["cpu_usage"];}
        elseif(isset($windows[0]["cpu_usage"])){$raw_cpu=$windows[0]["cpu_usage"];}
        if(isset($windows[5]["server.http.requests"])){$raw_req=$windows[5]["server.http.requests"];}
        elseif(isset($windows[0]["server.http.requests"])){$raw_req=$windows[0]["server.http.requests"];}

        if(preg_match("#([0-9\.]+)#",$raw_cpu,$re)){$cpu_usage=floatval($re[1]);}
        if(preg_match("#([0-9\.]+)\/sec#",$raw_req,$re)){$requests=floatval($re[1]);}
        $out[$kid]=array("cpu"=>$cpu_usage,"requests"=>$requests);
    }
    return $out;
}

// SquidSMPConfig lu comme le daemon le lit: kid => coeur. Une configuration
// heritee ou la cle valait la valeur (coeur => coeur) se relit sans changement.
function smp_core_map():array{
    $out=array();
    $raw=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig");
    if(trim($raw)==""){return $out;}
    $conf=@unserialize($raw);
    if(!is_array($conf)){return $out;}
    foreach($conf as $kid=>$core){
        $kid=intval($kid);
        $core=intval($core);
        if($kid<1){continue;}
        if($core<1){continue;}
        $out[$kid]=$core;
    }
    ksort($out);
    return $out;
}

// ----------------------------------------------------------------------------
// Feuille de style -- maquette stitch_multi_cpu_proxy_redesign
// ----------------------------------------------------------------------------

// Emise une fois par requete, comme table_form_css() et svctile_css(), et
// TOUJOURS concatenee APRES _ENGINE_parse_body(): son regex #{(.+?)}# prend
// chaque corps de regle CSS pour un {token} et declenche un acces memcached par
// regle.
//
// Pourquoi une feuille propre a la page plutot que widget_h(): les tuiles de la
// maquette sont des cartes BLANCHES a pastille coloree, quand widget_h() rend un
// aplat de couleur a texte blanc. Les accents, eux, restent ceux de la marque --
// aucun vert n'est ecrit en dur.
function smp_css():string{
    if(isset($GLOBALS["SMP_CSS_DONE"])){return "";}
    $GLOBALS["SMP_CSS_DONE"]=true;

    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
              "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)",
              "on_fg"=>"#ffffff","label"=>"#1e293b");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }

    $c=array();
    $c[]="<style type='text/css'>";
    // --- les quatre tuiles de telemetrie
    $c[]=".smp-kpis{display:-ms-grid;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));";
    $c[]="grid-gap:16px;gap:16px}";
    $c[]="@media (max-width:1200px){.smp-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}";
    $c[]="@media (max-width:700px){.smp-kpis{grid-template-columns:minmax(0,1fr)}}";
    // min-width:0 sinon un long libelle refuse de retrecir et deborde de sa piste.
    $c[]=".smp-kpi-txt{min-width:0}";
    $c[]=".smp-kpi-lbl{display:block;margin-bottom:5px}";
    $c[]=".smp-kpi-num-on{color:{$ac["accent"]}}";
    $c[]=".smp-kpi-unit{font-size:12px;color:#64748b;margin-left:6px}";
    $c[]=".smp-kpi-sub{margin-top:8px}";
    // La taille de l'icone se pose sur le CONTENEUR: Font Awesome tourne en mode
    // SVG+JS, un selecteur descendant "i" ne matche plus rien apres remplacement.
    $c[]=".smp-kpi-ico{-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto;width:48px;height:48px;";
    $c[]="border-radius:12px;font-size:20px;background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;";
    $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
    $c[]="-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;margin-left:12px}";
    $c[]=".smp-kpi-ico-on{background:{$ac["accent_soft"]};color:{$ac["accent"]};border-color:{$ac["accent_line"]}}";
    $c[]=".smp-bar{display:block;width:112px;max-width:100%;margin-top:9px}";
    // --- la carte des coeurs
    $c[]=".smp-card{margin-top:16px}";
    $c[]=".smp-head{padding:14px 20px;border-bottom:1px solid #e2e8f0;background:#f8fafc;";
    $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center}";
    $c[]=".smp-head-t{font-size:14px;font-weight:700;color:{$ac["label"]};margin-right:12px}";
    $c[]=".smp-row{padding:16px 20px;border-top:1px solid #f1f5f9;";
    $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
    $c[]="-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between}";
    $c[]=".smp-row:first-child{border-top:0}";
    $c[]=".smp-row-off{background:#fbfcfd}";
    $c[]=".smp-id{display:-webkit-box;display:-ms-flexbox;display:flex;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
    $c[]="-webkit-box-flex:1;-ms-flex:1 1 280px;flex:1 1 280px;min-width:0;padding-right:12px}";
    $c[]=".smp-chip{position:relative;-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto;";
    $c[]="width:46px;height:46px;border-radius:12px;font-size:20px;margin-right:14px;";
    $c[]="background:{$ac["accent_soft"]};color:{$ac["accent"]};border:1px solid {$ac["accent_line"]};";
    $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
    $c[]="-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center}";
    $c[]=".smp-chip-off{background:#f1f5f9;color:#94a3b8;border-color:#e2e8f0}";
    $c[]=".smp-dot{position:absolute;top:-4px;right:-4px;width:12px;height:12px;border-radius:50%;";
    $c[]="background:{$ac["accent"]};border:2px solid #fff}";
    $c[]=".smp-title{font-size:13px;font-weight:700;color:#0f172a}";
    $c[]=".smp-title-off{font-weight:600;color:#475569}";
    $c[]=".smp-id .ak-tag{margin-left:8px}";
    $c[]=".smp-meta{font-size:11px;color:#64748b;margin-top:4px}";
    $c[]=".smp-meta b{color:#334155;font-weight:600}";
    $c[]=".smp-sep{color:#cbd5e1;padding:0 7px}";
    $c[]=".smp-metrics{display:-webkit-box;display:-ms-flexbox;display:flex;";
    $c[]="-webkit-flex-wrap:wrap;flex-wrap:wrap;-webkit-box-flex:0;-ms-flex:0 1 auto;flex:0 1 auto}";
    $c[]=".smp-m{padding:0 16px 0 0;min-width:96px}";
    $c[]=".smp-m-lbl{font-size:11px;font-weight:500;color:#94a3b8}";
    $c[]=".smp-m-val{font-size:13px;font-weight:700;color:#1e293b;margin-top:4px;";
    $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center}";
    $c[]=".smp-m-val .smp-bar{margin-top:0;width:72px;margin-right:9px}";
    $c[]=".smp-m-off .smp-m-lbl,.smp-m-off .smp-m-val{color:#94a3b8;font-weight:500}";
    // --- l'interrupteur
    $c[]=".smp-ctl{-webkit-box-flex:0;-ms-flex:0 0 auto;flex:0 0 auto;padding-left:8px}";
    // --- le pied de carte
    $c[]=".smp-foot{padding:14px 20px;border-top:1px solid #e2e8f0;background:#f8fafc;";
    $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
    $c[]="-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between;";
    $c[]="font-size:12px;color:#475569}";
    $c[]=".smp-foot b{color:#1e293b;font-weight:600}";
    $c[]="</style>";
    return @implode("\n",$c);
}

// ----------------------------------------------------------------------------
// Les widgets -- un par fonction
// ----------------------------------------------------------------------------

// Le gabarit commun aux quatre tuiles. Les fonctions smp_kpi_* ne calculent que
// leur contenu, ce qui laisse chaque tuile lisible et reutilisable seule.
function smp_kpi_card($label,$value,$unit,$sub,$icon,$on=false,$valueOn=false):string{
    $icocls="smp-kpi-ico";
    if($on){$icocls.=" smp-kpi-ico-on";}
    $numcls="ak-fig-v";
    if($valueOn){$numcls.=" smp-kpi-num-on";}
    $u="";
    if($unit<>""){$u="<span class='smp-kpi-unit'>$unit</span>";}
    $s="";
    if($sub<>""){$s="<div class='ak-fig-s smp-kpi-sub'>$sub</div>";}
    $h=array();
    $h[]="<div class='ak-fig'>";
    $h[]="<div class='smp-kpi-txt'>";
    $h[]="<span class='ak-fig-l smp-kpi-lbl'>$label</span>";
    $h[]="<div><span class='$numcls'>$value</span>$u</div>";
    $h[]=$s;
    $h[]="</div>";
    $h[]="<div class='$icocls'><i class='$icon'></i></div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

// Une barre de progression dont la couleur bascule aux memes seuils que les
// vignettes de l'ancienne page (70% / 95%).
function smp_progress($percent,$width_class=""):string{
    $percent=floatval($percent);
    if($percent<0){$percent=0;}
    if($percent>100){$percent=100;}
    $cls="ak-bar smp-bar";
    if($percent>95){$cls.=" ak-err";}elseif($percent>70){$cls.=" ak-warn";}
    if($width_class<>""){$cls.=" $width_class";}
    return "<span class='$cls'><span style='width:".sprintf("%.1f",$percent)."%'></span></span>";
}

function smp_kpi_cpu($info,$cpu_number):string{
    $usage=smp_num($info,"cpu_usage");
    $sub="{smp_total_capacity}: $cpu_number {cpu_cores}";
    return smp_kpi_card("{CpuUsage}",round($usage,1)."%","",$sub,"fas fa-microchip",true,false);
}

function smp_kpi_filedesc($info):string{
    $used=intval(smp_num($info,"current_file_descriptors_in_use"));
    $max=intval(smp_num($info,"current_file_descriptors"));
    $percent=0;
    if($max>0){$percent=($used*100)/$max;}
    $unit="";
    if($max>0){$unit="/ ".number_format($max,0,"."," ");}
    // Le pourcentage etait lu dans un champ "percentage" que l'API ne renvoie
    // pas: il valait 0 en toute circonstance. Il se calcule.
    return smp_kpi_card("{smp_file_descriptors}",number_format($used,0,"."," "),$unit,
        smp_progress($percent),"fas fa-sliders-h",false,false);
}

function smp_kpi_requests($info):string{
    $rqs=intval(smp_num($info,"average_http_requests"));
    $total=intval(smp_num($info,"http_requests"));
    if($rqs>0){
        $sub="{requests}: ".number_format($total,0,"."," ");
    }else{
        $sub="{smp_traffic_idle}";
    }
    return smp_kpi_card("{requests} / {minute}",number_format($rqs,0,"."," "),"",
        $sub,"fas fa-cloud",false,false);
}

function smp_kpi_workers($cores,$cpu_number,$smp_disabled):string{
    $active=count($cores);
    if($active<1){$active=1;}
    $sub="{active2}";
    if($smp_disabled){$sub="{SquidSMPDisabled}";}
    return smp_kpi_card("{smp_active_workers}",$active,"/ $cpu_number {cpu_cores}",
        $sub,"fas fa-terminal",true,true);
}

// L'interrupteur d'un coeur. C'est un lien et non un icon_check(): la maquette
// demande un curseur, et icon_check() emet trois fonctions JS par ligne dont
// aucune n'est utile ici.
function smp_switch($core,$on):string{
    $page=CurrentPageName();
    $title="{use} CPU N.$core";
    return "<button type='button' role='switch' class='ak-sw' aria-checked='".($on?"true":"false")."' title=\"$title\" aria-label=\"$title\" ".
        "OnClick=\"Loadjs('$page?switch-cpu-number=$core');\"></button>";
}

// Une ligne = un coeur physique. $kid vaut 0 quand le coeur n'est affecte a
// aucun processus, et c'est la seule chose qui distingue les deux etats: tout le
// reste (PID, memoire, mesures) decoule du kid.
function smp_row($core,$kid,$proc,$metric,$ports,$known=true):string{
    $on=($kid>0);
    $h=array();
    $h[]="<div class='smp-row".($on?"":" smp-row-off")."'>";

    $h[]="<div class='smp-id'>";
    $h[]="<span class='smp-chip".($on?"":" smp-chip-off")."'><i class='fas fa-microchip'></i>".
         ($on?"<span class='smp-dot'></span>":"")."</span>";
    $h[]="<span style='min-width:0'>";
    if($on){
        $h[]="<div class='smp-title'>{instance}&nbsp;$kid &mdash; {smp_cpu_core} #$core".
             "<span class='ak-tag ak-ok'>{active2}</span></div>";
        $meta=array();
        if(isset($proc["pid"]) && $proc["pid"]>0){
            $meta[]="{pid}: <b>".intval($proc["pid"])."</b>";
        }
        if(count($ports)>0){
            $meta[]="{listen_port}: <b>".htmlspecialchars(@implode(", ",$ports))."</b>";
        }
        if(isset($proc["rss_kb"]) && $proc["rss_kb"]>0){
            // FormatBytes() est une FONCTION globale de class.templates.inc et
            // prend des KILO-octets -- exactement l'unite de /proc/<pid>/status.
            $meta[]="{memory}: <b>".FormatBytes($proc["rss_kb"],true)."</b>";
        }
        // Un PID absent a DEUX causes opposees: le worker n'est pas la (une
        // alerte), ou le daemon ne connait pas encore la route (un silence).
        // Les confondre ferait passer un binaire ancien pour une panne.
        if(count($meta)==0){
            if($known){$meta[]="{pid}: {smp_not_allocated}";}
            else{$meta[]="{pid}: &mdash;";}
        }
        $h[]="<div class='smp-meta'>".@implode("<span class='smp-sep'>&bull;</span>",$meta)."</div>";
    }else{
        $h[]="<div class='smp-title smp-title-off'>{smp_cpu_core} #$core".
             "<span class='ak-tag ak-off'>{inactive2}</span></div>";
        $h[]="<div class='smp-meta'>{smp_core_not_assigned}</div>";
    }
    $h[]="</span>";
    $h[]="</div>";

    $h[]="<div class='smp-metrics".($on?"":" smp-m-off")."'>";
    if($on){
        $cpu=0;
        $req=0;
        if(isset($metric["cpu"])){$cpu=floatval($metric["cpu"]);}
        if(isset($metric["requests"])){$req=floatval($metric["requests"]);}
        $fd="&mdash;";
        if(isset($proc["fd_open"]) && $proc["fd_open"]>0){$fd=intval($proc["fd_open"]);}
        // DEUX decimales, comme l'ancienne page: au repos un worker tourne a
        // 0.02%, qu'un arrondi a une decimale affiche "0%" -- soit exactement
        // l'information qu'on vient chercher ici, perdue.
        $h[]="<div class='smp-m'><div class='smp-m-lbl'>{CpuUsage}</div>".
             "<div class='smp-m-val'>".smp_progress($cpu)."".sprintf("%.2f",$cpu)."%</div></div>";
        $h[]="<div class='smp-m'><div class='smp-m-lbl'>{requests} / {smp_per_second}</div>".
             "<div class='smp-m-val'>".sprintf("%.2f",$req)."</div></div>";
        $h[]="<div class='smp-m'><div class='smp-m-lbl'>{smp_file_descriptors}</div>".
             "<div class='smp-m-val'>$fd</div></div>";
    }else{
        $h[]="<div class='smp-m'><div class='smp-m-lbl'>{CpuUsage}</div><div class='smp-m-val'>&mdash;</div></div>";
        $h[]="<div class='smp-m'><div class='smp-m-lbl'>{requests} / {smp_per_second}</div><div class='smp-m-val'>&mdash;</div></div>";
        $h[]="<div class='smp-m'><div class='smp-m-lbl'>{smp_file_descriptors}</div><div class='smp-m-val'>0</div></div>";
    }
    $h[]="</div>";

    $h[]="<div class='smp-ctl'>".smp_switch($core,$on)."</div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

function smp_footer($info):string{
    $memsize=intval(smp_num($info,"storage_memsize"));
    $tpl=new template_admin();
    $mem="&mdash;";
    if($memsize>0){$mem=FormatBytes($memsize,true);}
    $jsrestart=$tpl->framework_buildjs(
        "/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log",
        "progress-smp-restart");
    $h=array();
    $h[]="<div class='smp-foot'>";
    $h[]="<span>{smp_shared_cache_memory}: <b>$mem</b></span>";
    $h[]="<span>".$tpl->button_autnonome("{restart_proxy_service}",$jsrestart,
        "fas fa-sync-alt","AsSquidAdministrator")."</span>";
    $h[]="</div>";
    return @implode("\n",$h);
}

// ----------------------------------------------------------------------------
// Le fragment rafraichi
// ----------------------------------------------------------------------------

function smp_live(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    if($CPU_NUMBER<1){$CPU_NUMBER=1;}
    $SquidSMPDisabled=(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPDisabled"))==1);

    $info=smp_filedesc();
    $cores=smp_core_map();            // kid => coeur
    $procs=smp_instances();           // kid => processus
    $metrics=smp_kid_metrics();       // kid => mesures

    // L'axe d'affichage est le COEUR, l'axe des donnees est le KID: cette table
    // inversee est le seul endroit ou les deux se rencontrent.
    $kid_of_core=array();
    foreach($cores as $kid=>$core){$kid_of_core[$core]=$kid;}

    $html=array();
    $html[]="<div class='ak smpw'>";
    if($SquidSMPDisabled){
        $html[]=$tpl->div_warning("{SquidSMPDisabled}||{SquidSMPDisabled_explain}");
    }
    if($info==null){
        $html[]=$tpl->div_error("{cachemgr}: {did_not_respond}");
    }

    $html[]="<div class='smp-kpis'>";
    $html[]=smp_kpi_cpu($info,$CPU_NUMBER);
    $html[]=smp_kpi_filedesc($info);
    $html[]=smp_kpi_requests($info);
    $html[]=smp_kpi_workers($cores,$CPU_NUMBER,$SquidSMPDisabled);
    $html[]="</div>";

    $html[]="<div class='ak-card smp-card'>";
    $html[]="<div class='smp-head'><span class='smp-head-t'>{cpu_affinity}</span>";
    $html[]="<span class='ak-count'>$CPU_NUMBER {smp_cores_detected}</span></div>";
    for($core=1;$core<=$CPU_NUMBER;$core++){
        $kid=0;
        if(isset($kid_of_core[$core])){$kid=$kid_of_core[$core];}
        $proc=array();
        if($kid>0 && isset($procs["bykid"][$kid])){$proc=$procs["bykid"][$kid];}
        $metric=array();
        if($kid>0 && isset($metrics[$kid])){$metric=$metrics[$kid];}
        $html[]=smp_row($core,$kid,$proc,$metric,$procs["ports"],$procs["available"]);
    }
    $html[]=smp_footer($info);
    $html[]="</div>";
    $html[]="</div>";

    $html[]="<script>NoSpinner();</script>";

    // Le CSS est concatene APRES le parse: _ENGINE_parse_body() lirait chaque
    // regle comme un {token}. Le TINY part en dernier, apres le contenu.
    echo smp_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).smp_tiny($SquidSMPDisabled);
}

// Les boutons d'en-tete. Ils vivent dans le fragment rafraichi parce que l'un
// d'eux change d'etat avec SquidSMPDisabled.
function smp_tiny($SquidSMPDisabled):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs(
        "/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log",
        "progress-smp-restart");

    $topbuttons=array();
    $topbuttons[]=array($jsrestart,ico_refresh,"{restart_proxy_service}");
    if($SquidSMPDisabled){
        $topbuttons[]=array("Loadjs('$page?SquidSMPDisabled=0')",ico_run,"{SquidSMPDisabled_on}");
    }else{
        $topbuttons[]=array("Loadjs('$page?SquidSMPDisabled=1')",ico_stop,"{SquidSMPDisabled_off}");
    }

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{your_proxy} - {multiple_instances}";
    $TINY_ARRAY["ICO"]=ico_cpu;
    $TINY_ARRAY["EXPL"]="{SQUID_SMP_EXPLAIN}<br>{squid_worker_explain}";
    $TINY_ARRAY["URL"]="proxy-smp";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    // Le contenu du TINY voyage en base64: rien a faire parser ici.
    return "<script type=\"text/javascript\">$jstiny</script>";
}

// ----------------------------------------------------------------------------
// Mutations
// ----------------------------------------------------------------------------

// Masquer un bouton n'est pas un controle de privilege: le repartiteur en tete
// de fichier repond a toute session authentifiee, la verification doit donc etre
// ici, cote serveur.
function smp_require_admin():bool{
    $users=new usersMenus();
    if($users->AsSquidAdministrator){return true;}
    $tpl=new template_admin();
    $tpl->js_no_privileges();
    return false;
}

// Bascule un COEUR, puis renumerote les processus.
//
// SquidSMPConfig est indexe par numero de process squid, et le daemon lit
// strictement les cles 1..N (N = len(map)). Une configuration a trou --
// [2=>2, 4=>4] apres avoir coche les coeurs 2 et 4 -- lui fait chercher la cle
// 1, ne rien trouver, et n'emettre qu'UN cpu_affinity_map: le coeur 4 etait
// perdu en silence. On conserve donc l'ensemble des coeurs choisis et on
// reattribue les numeros de process de facon contigue.
function switch_cpu_number():bool{
    header("content-type: application/x-javascript");
    if(!smp_require_admin()){return false;}
    $page=CurrentPageName();
    $CpuToUse=intval($_GET["switch-cpu-number"]);
    if($CpuToUse<1){return false;}

    $before=smp_core_map();
    $before_md5=md5(serialize($before));

    $chosen=array();
    foreach($before as $kid=>$core){$chosen[$core]=true;}
    if(isset($chosen[$CpuToUse])){
        unset($chosen[$CpuToUse]);
    }else{
        $chosen[$CpuToUse]=true;
    }
    $list=array_keys($chosen);
    sort($list,SORT_NUMERIC);

    $SquidSMPConfig=array();
    $kid=1;
    foreach($list as $core){
        $SquidSMPConfig[$kid]=$core;
        $kid++;
    }
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($SquidSMPConfig),"SquidSMPConfig");

    echo "LoadAjaxSilent('smp-live','$page?live=yes');\n";
    echo "Loadjs('$page?performance-after=yes&smp=$before_md5');";
    return admin_tracks("Save the use of proxy CPU Number $CpuToUse");
}

function switch_SquidSMPDisabled():bool{
    header("content-type: application/x-javascript");
    if(!smp_require_admin()){return false;}
    $page=CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidSMPDisabled",intval($_GET["SquidSMPDisabled"]));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/general/emergency/smp");
    echo "LoadAjaxSilent('smp-live','$page?live=yes');";
    return admin_tracks("Set multi-cpu feature to {$_GET["SquidSMPDisabled"]}");
}

// Le proxy ne prend la nouvelle repartition qu'au redemarrage: on ne le propose
// que si la configuration a reellement change depuis l'etat compare.
function performance_save_after(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    // Empreinte de l'etat AVANT la bascule, transmise par l'appelant. Absente,
    // elle ne peut egaler l'empreinte actuelle: on propose le redemarrage, ce
    // qui est le sens sur des deux erreurs possibles.
    $smp="";
    if(isset($_GET["smp"])){$smp=$_GET["smp"];}
    $after_md5=md5(serialize(smp_core_map()));

    $jsrestart=$tpl->framework_buildjs(
        "/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log",
        "progress-smp-restart");

    if($after_md5<>$smp){
        echo $tpl->js_confirm_execute("{need_restart_reconfigure_proxy}",
            "NONE","Restart Proxy service",$jsrestart);
        die();
    }
    echo $jsrestart;
}

function PerformanceSave(){
    if(!smp_require_admin()){return;}
    $sock=new sockets();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $SquidSMPConfig=array();
    $kid=1;
    foreach($_POST as $key=>$val){
        if(preg_match("#SMP([0-9]+)#",$key,$re)){
            if(intval($val)>0){
                $SquidSMPConfig[$kid]=intval($val);
                $kid++;
            }
            unset($_POST[$key]);
        }
    }
    $sock->SaveConfigFile(serialize($SquidSMPConfig),"SquidSMPConfig");
}
