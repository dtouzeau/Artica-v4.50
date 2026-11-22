<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.icon.top.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}

if(isset($_POST["fs_filemax2"])){fs_filemax_save();exit;}
if(isset($_POST["max_filedesc"])){filedesc_save();exit;}
if(isset($_GET["filedesc-live"])){filedesc_live();exit;}
if(isset($_GET["filedesc-section"])){filedesc_section();exit;}
if(isset($_GET["filedesc-form"])){filedsc_form();exit;}
if(isset($_GET["filedsc-form"])){filedsc_form();exit;}
if(isset($_GET["filedesc-form-js"])){filedesc_form_js();exit;}
if(isset($_GET["filedesc-status"])){filedesc_status();exit;}
if(isset($_GET["fs-filemax-js"])){fs_filemax_js();exit;}
if(isset($_GET["fs-filemax-popup"])){fs_filemax_popup();exit;}
if(isset($_GET["nf-conntrack-max-js"])){nf_conntrack_max_js();exit;}
if(isset($_GET["nf-conntrack-max-popup"])){nf_conntrack_max_popup();exit;}
if(isset($_POST["nf_conntrack_max"])){nf_conntrack_max_save();exit;}
if(isset($_GET["max-filedesc-js"])){max_filedesc_js();exit;}
if(isset($_GET["max-filedesc-popup"])){max_filedesc_popup();exit;}


page();
function fs_filemax_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog1("{file_descriptors} ({system})", "$page?fs-filemax-popup=yes&bypop=yes", 500);
}
function max_filedesc_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog1("{file_descriptors}", "$page?max-filedesc-popup=yes", 500);
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{file_descriptors}",
        "fas fa-file-medical-alt","{file_descriptors_squid_explain}","$page?table=yes","proxy-filedescriptors","progress-squid-fildesc-restart",
        false,"table-loader-proxy-fildescaddr");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{file_descriptors}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function fildesc_button_failed(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsafter="LoadAjaxSilent('filedesc-live','$page?filedesc-live=yes');";
    $restart_div="progress-squid-fildesc-restart";

    $js_restart=$tpl->framework_buildjs("/proxy/restart/single","squid.quick.rprogress",
        "squid.quick.rprogress.log",$restart_div,$jsafter,$jsafter);

        $button_restart=$tpl->button_autnonome("{restart}", $js_restart, "fas fa-sync-alt","AsProxyMonitor","99%","btn-danger");

        return $button_restart;

}



function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    // Un SEUL fragment vivant. L'ancienne page en chargeait trois en cascade
    // (set -> current -> max, chacun declenchant le suivant par LoadAjaxSilent):
    // trois allers-retours pour trois lectures de la MEME reponse API, et la
    // derniere tuile n'apparaissait qu'apres les deux autres.
    $html=array();
    $html[]="<div id='filedesc-live'></div>";
    $html[]="<div id='filedesc-form'></div>";

    // Le script est concatene APRES l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body prend chaque accolade JavaScript pour un {token}.
    $js=$tpl->RefreshInterval_js("filedesc-live",$page,"filedesc-live=yes",30);
    $script="<script>LoadAjaxSilent('filedesc-live','$page?filedesc-live=yes');\n$js\n".
        "LoadAjax('filedesc-form','$page?filedesc-section=yes');</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
}

// Valeur appliquee par le daemon quand max_filedesc n'est pas defini
// (articarest squid/Caches.conf.go, defaultMaxFiledesc). La page affichait 16384.
const FDH_DEFAULT_MAX_FILEDESC=65536;

// Feuille propre a la page (carte d'historique). Emise une fois par requete et
// concatenee APRES l'analyse, comme table_form_css() et svctile_css().
function fdh_css():string{
    if(isset($GLOBALS["FDH_CSS_DONE"])){return "";}
    $GLOBALS["FDH_CSS_DONE"]=true;
    $ac=array("accent"=>"#10b981","accent_soft"=>"rgba(16,185,129,.12)","label"=>"#1e293b");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        $ac["accent"]=$b["accent"];
        $ac["accent_soft"]=$b["accent_soft"];
        $ac["label"]=$b["label"];
    }
    $c=array();
    $c[]="<style type='text/css'>";
    $c[]=".fdh{padding:18px 20px;margin:10px 0 0 5px;";
    $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
    $c[]="-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between}";
    $c[]=".fdh-txt{min-width:0;-webkit-box-flex:0;-ms-flex:0 1 auto;flex:0 1 auto;padding-right:16px}";
    $c[]=".fdh-lbl{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b}";
    $c[]=".fdh-lbl .ak-tag{margin-left:8px;text-transform:none;letter-spacing:0}";
    $c[]=".fdh-val{font-size:30px;font-weight:800;color:{$ac["label"]};line-height:1.1;margin-top:4px}";
    $c[]=".fdh-sub{font-size:12px;color:#94a3b8;margin-top:4px}";
    // La courbe prend la largeur restante mais ne descend jamais sous 160px:
    // en dessous les 288 points se confondent et le trace ne dit plus rien.
    $c[]=".fdh-graph{-webkit-box-flex:1;-ms-flex:1 1 260px;flex:1 1 260px;min-width:160px;max-width:420px;height:56px}";
    $c[]=".fdh-graph svg{display:block;width:100%;height:56px}";
    $c[]=".fdh-empty{font-size:12px;color:#94a3b8;text-align:right;-webkit-box-flex:1;-ms-flex:1 1 260px;flex:1 1 260px}";
    $c[]=".fdh-foot{margin:0 0 0 5px;padding:12px 20px;background:#f8fafc;border:1px solid #e2e8f0;";
    $c[]="border-top:0;border-radius:0 0 12px 12px;font-size:12px;color:#64748b;";
    $c[]="display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-flex-wrap:wrap;flex-wrap:wrap;";
    $c[]="-webkit-box-align:center;-ms-flex-align:center;align-items:center;";
    $c[]="-webkit-box-pack:justify;-ms-flex-pack:justify;justify-content:space-between}";
    $c[]=".fdh-foot .fdh-mono{font-family:monospace;color:#334155}";
    $c[]="</style>";
    return @implode("\n",$c);
}

// L'historique echantillonne par le daemon (squid/FiledescHistory.go). Une
// appliance dont le binaire ne connait pas encore la route repond "Not Found":
// la carte affiche alors son chiffre sans courbe, ce qui est exact.
function filedesc_history_read():array{
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc/history");
    $json=json_decode($raw,true);
    if(!is_array($json)){return array();}
    if(!isset($json["Info"])){return array();}
    if(!is_array($json["Info"])){return array();}
    return $json["Info"];
}

// Un trace, pas un graphique: ni axe, ni interaction, ni bibliotheque. En SVG
// plutot qu'en canvas parce que le fragment est redessine toutes les 30
// secondes -- un objet Chart survivrait a la destruction de son noeud et
// fuirait a chaque rafraichissement.
function filedesc_sparkline(array $points):string{
    $vals=array();
    foreach($points as $p){
        if(!isset($p["in_use"])){continue;}
        $vals[]=intval($p["in_use"]);
    }
    // Deux points sont le minimum d'une ligne. En dessous on le DIT, au lieu de
    // tracer une horizontale qui se lirait comme une mesure stable.
    if(count($vals)<2){
        return "<div class='fdh-empty'>{filedesc_history_pending}</div>";
    }
    $w=300;$h=56;
    $min=min($vals);$max=max($vals);
    // Serie parfaitement plate: sans cela la normalisation divise par zero.
    if($max<=$min){$max=$min+1;}
    $n=count($vals);
    $pts=array();
    for($i=0;$i<$n;$i++){
        $x=($i*$w)/($n-1);
        $y=$h-4-((($vals[$i]-$min)/($max-$min))*($h-10));
        $pts[]=sprintf("%.1f,%.1f",$x,$y);
    }
    $line=@implode(" ",$pts);
    $area="0,$h ".$line." $w,$h";
    $accent="#10b981";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        $accent=$b["accent"];
    }
    $svg=array();
    // preserveAspectRatio=none: la courbe doit s'etirer a la largeur reelle de
    // la carte, pas garder le rapport du viewBox.
    $svg[]="<div class='fdh-graph'><svg viewBox='0 0 $w $h' preserveAspectRatio='none'>";
    $svg[]="<polygon points='$area' fill='$accent' fill-opacity='.10'></polygon>";
    $svg[]="<polyline points='$line' fill='none' stroke='$accent' stroke-width='2' ";
    $svg[]="stroke-linejoin='round' stroke-linecap='round'></polyline>";
    $svg[]="</svg></div>";
    return @implode("",$svg);
}

// La carte "TOP VALEUR": le pic et sa courbe.
function filedesc_history_card(array $filedesc_status):string{
    $points=filedesc_history_read();
    $tpl=new template_admin();
    // Le pic vient de l'historique quand il existe (24 h reelles) et retombe sur
    // largest_file_descriptors, que squid compte depuis SON demarrage. Les deux
    // ne mesurent pas la meme fenetre: on prend le plus large des deux plutot
    // que d'annoncer un pic inferieur a une valeur deja observee.
    $peak=intval(isset($filedesc_status["MAX"])?$filedesc_status["MAX"]:0);
    foreach($points as $p){
        if(!isset($p["in_use"])){continue;}
        if(intval($p["in_use"])>$peak){$peak=intval($p["in_use"]);}
    }
    $h=array();
    $h[]="<div class='ak ak-card fdh'>";
    $h[]="<div class='fdh-txt'>";
    $h[]="<div class='fdh-lbl'>TOP {value}<span class='ak-tag'>{filedesc_history_24h}</span></div>";
    $h[]="<div class='fdh-val'>".$tpl->FormatNumber($peak)."</div>";
    $h[]="<div class='fdh-sub'>{filedesc_peak_recorded}: ".$tpl->FormatNumber($peak)."</div>";
    $h[]="</div>";
    $h[]=filedesc_sparkline($points);
    $h[]="</div>";
    return @implode("\n",$h);
}

// Les deux tuiles de tete et la carte d'historique, en UNE reponse.
function filedesc_live(){
    $tpl=new template_admin();
    $filedesc_status=filedesc_status();

    if(isset($filedesc_status["ERROR"])){
        $html[]="<div style='display:flex;gap:15px;flex-wrap:wrap;align-items:stretch'>";
        $html[]="<div style='flex:1 1 300px;min-width:0'>".
            $tpl->widget_h("red","fas fa-cogs",fildesc_button_failed(),"{error}")."</div>";
        $html[]="<div style='flex:1 1 300px;min-width:0'>".
            $tpl->widget_h("red","fas fa-tachometer-alt",$filedesc_status["ERROR"],"{error}")."</div>";
        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
        return true;
    }

    // Combien de CPU portent reellement un worker: c'est le denominateur de
    // "defini par CPU". Meme lecture que fw.proxy.smp.php -- SquidSMPConfig est
    // indexe par numero de process, sa valeur est le coeur.
    $CPU=0;
    $SquidSMPConfig=@unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    if(!is_array($SquidSMPConfig)){$SquidSMPConfig=array();}
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    for($i=1;$i<$CPU_NUMBER+1;$i++){
        if(!isset($SquidSMPConfig[$i])){$CPUNumber=0;}else{$CPUNumber=intval($SquidSMPConfig[$i]);}
        if($i==1){if($CPUNumber==0){$CPUNumber=1;}}
        if($CPUNumber==0){continue;}
        $CPU++;
    }

    $textreal=null;
    $text=$filedesc_status["SETTINGS"];
    if($CPU>1){
        $text=$tpl->FormatNumber($filedesc_status["SETTINGS"])."/$CPU CPU(s)";
        $textreal=": <strong>".round(intval($filedesc_status["CURRENT_INT"])/$CPU)."/CPU</strong>";
    }

    $CURRENT_PRC=$filedesc_status["CURRENT_PRC"];
    $color="green";
    if($CURRENT_PRC>80){$color="yellow";}
    if($CURRENT_PRC>95){$color="red";}

    $html=array();
    $html[]="<div style='display:flex;gap:15px;flex-wrap:wrap;align-items:stretch'>";
    $html[]="<div style='flex:1 1 300px;min-width:0'>".
        $tpl->widget_h("green","fas fa-cogs",$text,"{defined}$textreal")."</div>";
    $html[]="<div style='flex:1 1 300px;min-width:0'>".
        $tpl->widget_h("$color","fas fa-tachometer-alt",
            "{$filedesc_status["CURRENT"]} ({$CURRENT_PRC}%)","{using}")."</div>";
    $html[]="</div>";
    $html[]=filedesc_history_card($filedesc_status);

    echo fdh_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function filedesc_section():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=True;

    $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
    $file_max=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("fs.file-max"));
    $nf_conntrack_max=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("net.netfilter.nf_conntrack_max"));

    if($max_filedesc==0){$max_filedesc=FDH_DEFAULT_MAX_FILEDESC;}
    $max_filedesc=$tpl->FormatNumber($max_filedesc);
    $file_max=$tpl->FormatNumber($file_max);
    $nf_conntrack_max=$tpl->FormatNumber($nf_conntrack_max);
    $tpl->table_form_section("{file_descriptors_label}","{filedesc_kernel_proxy_limits}");
    $tpl->table_form_field_js("Loadjs('$page?max-filedesc-js=yes')");
    $tpl->table_form_field_text("{file_descriptors_label} (proxy)",$max_filedesc,ico_performance);
    $tpl->table_form_field_js("Loadjs('$page?fs-filemax-js=yes')");
    $tpl->table_form_field_text("{file_descriptors_label} ({system})",$file_max,ico_performance);
    $tpl->table_form_field_js("Loadjs('$page?nf-conntrack-max-js=yes')");
    $tpl->table_form_field_text("{nf_conntrack_max} ({system})",$nf_conntrack_max,ico_performance);
    $html[]=$tpl->table_form_compile();
    // Le pied de la maquette: ou les valeurs atterrissent, et quel noyau les
    // porte. La version vient de php_uname(): la console tourne sur l'appliance,
    // c'est donc le noyau qui applique reellement ces limites.
    $foot=array();
    $foot[]="<div class='fdh-foot'>";
    $foot[]="<span><i class='fas fa-circle-info' style='color:#1c84c6'></i>&nbsp;{filedesc_limits_note}</span>";
    $foot[]="<span>{filedesc_kernel_sync} <span class='fdh-mono'>".
        htmlspecialchars(php_uname("r"))."</span></span>";
    $foot[]="</div>";
    $html[]=@implode("\n",$foot);
    echo fdh_css().$tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function max_filedesc_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=True;
    $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
    if($max_filedesc==0){$max_filedesc=FDH_DEFAULT_MAX_FILEDESC;}
    $security="AsSquidAdministrator";
    $jsafter=jsReload();
    $jsrestart=$jsafter.$tpl->framework_buildjs("/proxy/general/nohup/restart","squid.articarest.nohup",
        "squid.articarest.nohup.log","progress-squid-fildesc-restart");

    $form[]=$tpl->field_multiple_64("max_filedesc","{file_descriptors} (proxy)",$max_filedesc,"");
    $html=$tpl->form_outside("",$form,null,"{apply}",$jsrestart,$security,true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function filedesc_form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{file_descriptors}","$page?filedsc-form=yes&bypop=yes",950);
}

function jsReload():string{
    $page=CurrentPageName();
    $html[]="if( document.getElementById('filedesc-form') ){";
    $html[]="LoadAjax('filedesc-form','$page?filedesc-section=yes');";
    $html[]="}";
    // Changer max_filedesc change le "defini" des tuiles: sans cela le chiffre
    // du haut contredit la valeur qu'on vient d'enregistrer, jusqu'au prochain
    // tick de 30 s. Meme garde que les autres: le fragment peut avoir disparu.
    $html[]="if( document.getElementById('filedesc-live') ){";
    $html[]="LoadAjaxSilent('filedesc-live','$page?filedesc-live=yes');";
    $html[]="}";
    $html[]="if( document.getElementById('applications-squid-status') ){";
    $html[]="LoadAjax('applications-squid-status','fw.proxy.status.php?applications-squid-status=yes');";
    $html[]="}";
    return @implode("", $html);

}

function filedsc_form(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $bypop=false;
    if(isset($_GET["bypop"])){
        $bypop=true;
    }
    $security="AsSquidAdministrator";


    if($bypop){
        $jsafter="Loadjs('$page?filedesc-form-js=yes');".jsReload();
    }

    echo "<div id='popup-filedesc-progress'></div>";
    $jsrestart=$jsafter.$tpl->framework_buildjs("/proxy/general/nohup/restart","squid.articarest.nohup",
        "squid.articarest.nohup.log","popup-filedesc-progress");




    $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
    $ico=new icontop();
    if(method_exists($ico,"filedescriptors_checking")) {
        $ico->filedescriptors_checking(true);
        $Configured = $ico->current_in_conf;
        $OnProxy = $ico->Available;
        if ($Configured > 0 && $OnProxy > 0) {
            if ($Configured > $OnProxy) {
                $text = $tpl->_ENGINE_parse_body("{diff_filedesc}");
                $text = str_replace("%s1", $OnProxy, $text);
                $text = str_replace("%s2", $Configured , $text);
                $html[] = $tpl->div_error("{you_need_to_restart_proxy_service}<br>$text");
                $tpl->form_add_button("{restart}", "Loadjs('$page?restart-js=yes')");
            }
        }
    }


    if($max_filedesc==0){$max_filedesc=FDH_DEFAULT_MAX_FILEDESC;}

    $form[]=$tpl->field_multiple_64("max_filedesc","{file_descriptors} (proxy)",$max_filedesc,"");
    $html[]=$tpl->form_outside("{file_descriptors}", @implode("\n", $form),"{file_descriptors_squid_explain}","{apply}",$jsrestart,$security,true);
    echo $tpl->_ENGINE_parse_body($html);
}
function filedesc_save():bool{
    $tpl=new template_admin();
    $max_filedesc=intval($_POST["max_filedesc"]);
    $fs_filemax=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("fs.file-max"));

    if ($max_filedesc > $fs_filemax) {
        $error = $tpl->javascript_parse_text("{error_fs_max2}");
        $max_filedesc = $max_filedesc + 500;
        $error = str_replace("%s", $max_filedesc, $error);
        echo (new template_admin())->post_error("$error");
        return false;
    }

    // En dessous de 1025 le daemon n'ecrit pas max_filedescriptors: Squid heriterait de la
    // limite systeme (nofile geant, workers tues par l'OOM). On revient a la valeur par defaut.
    // Ce plancher n'etait jamais applique: il modifiait $_POST, pas la valeur enregistree.
    if($max_filedesc<1025){$max_filedesc=FDH_DEFAULT_MAX_FILEDESC;}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("max_filedesc", $max_filedesc);
    return admin_tracks("filedescriptors for the Proxy service defined to $max_filedesc");
}
function filedesc_status():array{

    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc"));


    if(!$json->Status){
        $ARRAY["ERROR"]=$json->Error;
        return $ARRAY;
    }

    // Guard division: the daemon returns max_filedescriptors=0 when the metric is unavailable
    // (e.g. proxy stopped) -> PHP 8 raises DivisionByZeroError. Fall back to 0%.
    $max_fd=intval($json->Info->max_filedescriptors);
    $inuse_fd=intval($json->Info->current_file_descriptors_in_use);
    $percentage=($max_fd>0)?round(($inuse_fd/$max_fd)*100,2):0;

    $ARRAY["CURRENT_INT"]=$json->Info->current_file_descriptors;
    $ARRAY["CURRENT"]=$tpl->FormatNumber($json->Info->current_file_descriptors_in_use);
    $ARRAY["CURRENT_PRC"]=$percentage;
    $ARRAY["MAX"]=$json->Info->largest_file_descriptors;
    $ARRAY["SETTINGS"]=$json->Info->must_file_descriptors;
    return $ARRAY;
}
function nf_conntrack_max_js():bool{
    $tpl = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog1("{nf_conntrack_max} ({system})", "$page?nf-conntrack-max-popup=yes&bypop=yes", 500);
}
function nf_conntrack_max_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $bypop=false;
    if(isset($_GET["bypop"])){$bypop=true;}
    $security="AsSquidAdministrator";
    $jsafter=jsReload();

    if($bypop){
        $jsafter="Loadjs('$page?nf-conntrack-max-js=yes');$jsafter";
    }
    $nf_conntrack_max=intval($GLOBALS["CLASS_SOCKETS"]->KERNEL_GET("net.netfilter.nf_conntrack_max"));

    $form[]=$tpl->field_multiple_64("nf_conntrack_max","{nf_conntrack_max} ({system})",$nf_conntrack_max,"");
    $html[]=$tpl->form_outside("", $form,"","{apply}","dialogInstance1.close();$jsafter",$security,true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nf_conntrack_max_save():bool{
    $nf_conntrack_max=$_POST["nf_conntrack_max"];
    if($nf_conntrack_max<524288){$nf_conntrack_max=524288;}
    admin_tracks("Max Connections tracking for the system defined to $nf_conntrack_max");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("nf_conntrack_max",$nf_conntrack_max);
    $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("net.netfilter.nf_conntrack_max",$nf_conntrack_max);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return admin_tracks("set kernel parameter nf_conntrack_max to $nf_conntrack_max");
}
function fs_filemax_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLUSTER_CLI=true;
    $bypop=false;
    if(isset($_GET["bypop"])){$bypop=true;}
    $security="AsSquidAdministrator";
    $jsafter=jsReload();

    if($bypop){
        $jsafter="Loadjs('$page?fs-filemax-js=yes');$jsafter";
    }

    $file_max=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("fs_filemax"));
    $form[]=$tpl->field_multiple_64("fs_filemax2","{file_descriptors} ({system})",$file_max,"");
    $html[]=$tpl->form_outside("", $form,"","{apply}","dialogInstance1.close();$jsafter",$security,true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function fs_filemax_save():bool{
    $tpl=new template_admin();
    $max_filedesc=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));

    $fs_filemax=intval($_POST["fs_filemax2"]);
    $max_filedesc=$max_filedesc+5000;
    if($SQUIDEnable==1) {
        if ($max_filedesc > $fs_filemax) {
            $error = $tpl->javascript_parse_text("{error_fs_max2}");
            $max_filedesc = $max_filedesc + 500;
            $error = str_replace("%s", $max_filedesc, $error);
            echo $tpl->post_error($error);
            return false;
        }
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("fs_filemax",$fs_filemax);
    $GLOBALS["CLASS_SOCKETS"]->KERNEL_SET("fs.file-max",$fs_filemax);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return admin_tracks("filedescriptors for the system defined to $max_filedesc");
}