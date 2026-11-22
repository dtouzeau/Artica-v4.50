<?php
$GLOBALS["PEITYCONF"]="{ width:280,height:25,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
$GLOBALS["DYNAMIC_RATE_FEATURE"]=false;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tpl.inc");
include_once(dirname(__FILE__)."/ressources/class.modsecurity.tools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

// -- Ecritures : auto-save des controles .vitr-* ----------------------------
// auto_switch est le SEUL reglage restant. max_latency_ms / max_dirsize_mb /
// vitr_depth ont disparu du daemon : les miroirs sont fabriques par le service
// cloud, la taille et la profondeur du crawl ne se decident plus ici, et la
// bascule ne se lit plus sur une sonde de latence maison mais sur les etats
// du module de supervision des backends.
if(isset($_POST["vitrification"])){vitrification_save();exit;}
if(isset($_POST["auto_switch"])){vitrification_config_save();exit;}

// -- Ouverture des boites de dialogue ---------------------------------------
if(isset($_GET["vitrification-js"])){vitrification_js();exit;}
if(isset($_GET["vitrification-events-js"])){vitrification_events_js();exit;}

// -- Fragments HTML ---------------------------------------------------------
if(isset($_GET["vitrification-popup"])){vitrification_popup();exit;}
if(isset($_GET["vitrification-events"])){vitrification_events();exit;}
if(isset($_GET["vitrification-status"])){vitrification_status();exit;}
if(isset($_GET["vitrification-config"])){vitrification_config();exit;}
if(isset($_GET["vitrification-conditions"])){vitrification_conditions();exit;}

// -- Actions ----------------------------------------------------------------
// Plus de "run"/"stop" : le miroir n'est plus fabrique ici, il est telecharge.
// On n'interrompt pas un telechargement distant, donc il n'y a rien a arreter.
if(isset($_GET["vitrification-fetch"])){vitrification_fetch();exit;}
if(isset($_GET["vitrification-on"])){vitrification_on();exit;}
if(isset($_GET["vitrification-off"])){vitrification_off();exit;}
if(isset($_GET["vitrification-empty-js"])){vitrification_empty_js();exit;}
if(isset($_GET["vitrification-empty"])){vitrification_empty();exit;}
vitrification_js();

/* ===========================================================================
 *  Bibliotheque de composants .vitr-* -- transposition de
 *  /home/claude/images/stitch_site_mirroring_system_redesign
 *  Regle du projet : un widget = une fonction.
 * ======================================================================== */

/**
 * Feuille de style de la modale. Emise UNE SEULE FOIS par requete et
 * TOUJOURS concatenee EN DEHORS de _ENGINE_parse_body() : son motif
 * #{(.+?)}# prendrait chaque corps de regle CSS pour un {token} et
 * declencherait une recherche memcached par regle.
 */
function vitr_style():string{
    if(isset($GLOBALS["VITR_CSS_DONE"])){return "";}
    $GLOBALS["VITR_CSS_DONE"]=true;
    return "<style>
.vitr-modal{color:#334155;font-size:13px;line-height:1.45}
.vitr-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;
box-shadow:0 1px 2px rgba(15,23,42,.04);margin-bottom:16px}
.vitr-strip{display:flex;flex-wrap:wrap;align-items:stretch}
.vitr-kpi{flex:1 1 0;min-width:158px;padding:0 14px;border-left:1px solid #f1f5f9;
display:flex;flex-direction:column;justify-content:center}
.vitr-kpi-f{border-left:0;padding-left:0}
.vitr-kpi-l{padding-right:0}
.vitr-lbl{font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
.vitr-val{font-size:14px;font-weight:700;color:#1e293b;margin-top:2px}
.vitr-sub{font-size:11px;color:#94a3b8;margin-top:1px}
.vitr-chip{width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;
justify-content:center;font-size:18px;flex:0 0 auto}
.vitr-chip-s{width:32px;height:32px;border-radius:8px;font-size:14px}
.vitr-chip-amber{background:#fffbeb;color:#d97706;border:1px solid #fde68a}
.vitr-chip-teal{background:#f0fdfa;color:#0d9488;border:1px solid #ccfbf1}
.vitr-chip-red{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.vitr-row{display:flex;align-items:center;gap:10px}
.vitr-row-b{display:flex;align-items:center;justify-content:space-between;gap:10px}
.vitr-mono{font-family:'Courier New',Courier,monospace}
.vitr-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:34px;
padding:0 13px;border-radius:8px;font-size:12px;font-weight:600;border:1px solid transparent;
cursor:pointer;text-decoration:none}
.vitr-btn-p{background:#0d9488;color:#fff}
.vitr-btn-p:hover{background:#0f766e;color:#fff;text-decoration:none}
.vitr-btn-s{background:#fff;border-color:#e2e8f0;color:#475569}
.vitr-btn-s:hover{background:#f8fafc;color:#334155;text-decoration:none}
.vitr-btn-d{background:#fff;border-color:#fecaca;color:#dc2626}
.vitr-btn-d:hover{background:#fef2f2;color:#b91c1c;text-decoration:none}
.vitr-btn-w{width:100%}
.vitr-hd{display:flex;align-items:flex-start;gap:12px}
.vitr-hd-t{font-size:13px;font-weight:700;color:#0f172a}
.vitr-hd-s{font-size:11px;color:#64748b;margin-top:2px}
.vitr-hd-teal{color:#0f766e;font-weight:600}
.vitr-tag{font-size:11px;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;
border-radius:6px;padding:3px 8px;white-space:nowrap}
.vitr-note{background:#f0fdfa;border:1px solid #ccfbf1;border-radius:8px;padding:10px 12px;
font-size:12px;color:#475569;margin-top:12px}
.vitr-note-g{background:#f8fafc;border-color:#f1f5f9;font-size:11px;color:#64748b}
.vitr-foot{display:flex;justify-content:space-between;align-items:center;gap:10px;font-size:11px;
color:#94a3b8;border-top:1px solid #f1f5f9;margin-top:14px;padding-top:10px}
.vitr-foot-a{color:#0d9488;font-weight:600;cursor:pointer}
.vitr-foot-a:hover{color:#0f766e;text-decoration:underline}
.vitr-grid2{display:flex;gap:16px;flex-wrap:wrap}
.vitr-col{flex:1 1 330px;min-width:296px;display:flex}
.vitr-col>.vitr-card{width:100%;display:flex;flex-direction:column;justify-content:space-between}
.vitr-badge{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.04em;
padding:2px 7px;border-radius:5px;border:1px solid;white-space:nowrap}
.vitr-b-ok{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.vitr-b-teal{background:#f0fdfa;color:#0f766e;border-color:#99f6e4}
.vitr-b-ko{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.vitr-b-warn{background:#fffbeb;color:#b45309;border-color:#fde68a}
.vitr-b-n{background:#f8fafc;color:#64748b;border-color:#e2e8f0}
.vitr-chk{display:flex;align-items:center;justify-content:space-between;gap:10px;background:#f8fafc;
border:1px solid #f1f5f9;border-radius:8px;padding:8px 10px;font-size:12px;margin-top:8px}
.vitr-chk-l{display:flex;align-items:center;gap:8px;color:#334155}
.vitr-chk>.vitr-badge{text-transform:uppercase}
.vitr-ok{color:#059669}
.vitr-ko{color:#dc2626}
.vitr-dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex:0 0 auto}
.vitr-dot-ok{background:#10b981}
.vitr-dot-n{background:#94a3b8}
.vitr-sw{position:relative;display:inline-block;width:42px;height:23px;border-radius:999px;
background:#e2e8f0;cursor:pointer;vertical-align:middle;flex:0 0 auto}
.vitr-sw-k{position:absolute;top:2px;left:2px;width:19px;height:19px;border-radius:50%;
background:#fff;border:1px solid #cbd5e1}
.vitr-sw-on{background:#0d9488}
.vitr-sw-on>.vitr-sw-k{left:21px;border-color:#0d9488}
.vitr-actions{display:flex;justify-content:space-between;align-items:center;gap:10px;
flex-wrap:wrap;border-top:1px solid #e2e8f0;padding-top:14px;margin-top:2px}
.vitr-actions-g{display:flex;gap:8px;flex-wrap:wrap}
.vitr-empty{text-align:center;color:#94a3b8;font-size:12px;padding:28px 10px}
.vitr-dom{border:1px solid #f1f5f9;border-radius:10px;background:#f8fafc;padding:10px 12px;
margin-top:10px}
.vitr-dom-n{font-size:13px;font-weight:700;color:#0f172a;word-break:break-all}
.vitr-dom-m{display:flex;flex-wrap:wrap;gap:6px 16px;margin-top:8px;font-size:11px;color:#64748b}
.vitr-dom-m b{color:#334155;font-weight:600}
.vitr-dom-e{margin-top:8px;font-size:11px;border-radius:6px;padding:6px 8px;word-break:break-word}
.vitr-e-ko{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.vitr-e-warn{background:#fffbeb;color:#b45309;border:1px solid #fde68a}
.vitr-e-n{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}
.vitr-kv{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.vitr-kv-i{flex:1 1 130px;min-width:120px;background:#f8fafc;border:1px solid #f1f5f9;
border-radius:8px;padding:7px 10px}
.vitr-kv-k{font-size:10px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8}
.vitr-kv-v{font-size:13px;font-weight:700;color:#1e293b;margin-top:2px;word-break:break-word}
.vitr-lst{max-height:230px;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;
background:#fff;margin-top:8px}
.vitr-lst-r{display:flex;gap:10px;align-items:flex-start;padding:6px 10px;border-top:1px solid #f8fafc;
font-size:11px}
.vitr-lst-f{border-top:0}
.vitr-lst-u{color:#334155;word-break:break-all;flex:1}
.vitr-lst-t{color:#94a3b8;white-space:nowrap}
</style>";
}

/**
 * Traduit la syntaxe "ID:12|auto_switch" en corps d'objet jQuery.post(),
 * exactement comme BigCircleCheckbox/BigIntegerField : les segments
 * "cle:valeur" partent en constante, le segment nu recoit la valeur saisie.
 * Les handlers d'ecriture de la page restent donc inchanges.
 */
function vitr_post_fields($field):string{
    $f=array();
    if(strpos($field,"|")>0){
        $tb=explode("|",$field);
        foreach($tb as $newf){
            if(strpos($newf,":")>0){
                $ss=explode(":",$newf);
                $f[]="'".$ss[0]."': '".$ss[1]."'";
                continue;
            }
            $f[]="'$newf': value";
        }
    }else{
        $f[]="'$field': value";
    }
    return @implode(",",$f);
}

/**
 * Accumulateur du JavaScript des controles. Il existe pour une raison
 * precise : _ENGINE_parse_body() applique #{(.+?)}#is au fragment, donc
 * un « function(){ ... } » embarque dans le HTML serait lu comme un
 * {token} et declencherait une recherche memcached par bloc -- une
 * correspondance corromprait le script. Le JS est donc concatene APRES
 * l'analyse, comme la feuille de style.
 */
function vitr_js_push($js){
    if(!isset($GLOBALS["VITR_JS"])){$GLOBALS["VITR_JS"]=array();}
    $GLOBALS["VITR_JS"][]=$js;
}

/** Rend puis vide l'accumulateur. A appeler APRES _ENGINE_parse_body(). */
function vitr_js_compile():string{
    if(!isset($GLOBALS["VITR_JS"])){return "";}
    $js=$GLOBALS["VITR_JS"];
    $GLOBALS["VITR_JS"]=array();
    if(count($js)==0){return "";}
    return "<script>".@implode("\n",$js)."</script>";
}

/** Interrupteur compact auto-enregistre (remplace visuellement BigCircleCheckbox). */
function vitr_switch($field,$value,$after=""):string{
    $page=CurrentPageName();
    $uid="vs".md5($field.microtime(true).rand(1000,9999));
    $fftxt=vitr_post_fields($field);
    $on=(intval($value)==1)?" vitr-sw-on":"";
    $js=array();
    $js[]="function VitrSw_$uid(el){";
    $js[]="var on=(el.className.indexOf('vitr-sw-on')<0);var value=on?1:0;";
    $js[]="el.className='vitr-sw'+(on?' vitr-sw-on':'');";
    $js[]="$.post('$page',{ $fftxt }).done(function(){ $after })";
    $js[]=".fail(function(x,t,e){alert('POST failed:'+e);});}";
    vitr_js_push(@implode("",$js));
    return "<span class='vitr-sw$on' id='$uid' onclick=\"VitrSw_$uid(this);\">".
           "<span class='vitr-sw-k'></span></span>";
}

/** Interrupteur pilotant une action REST (pas un champ) : bascule vitrifie/direct. */
function vitr_switch_js($value,$js):string{
    $uid="vj".md5($js.microtime(true).rand(1000,9999));
    $on=(intval($value)==1)?" vitr-sw-on":"";
    return "<span class='vitr-sw$on' id='$uid' onclick=\"$js\"><span class='vitr-sw-k'></span></span>";
}

/** Pastille d'etat. $tone : ok | teal | ko | warn | n */
function vitr_badge($text,$tone="n"):string{
    $map=array("ok"=>"vitr-b-ok","teal"=>"vitr-b-teal","ko"=>"vitr-b-ko",
               "warn"=>"vitr-b-warn","n"=>"vitr-b-n");
    $cls=isset($map[$tone])?$map[$tone]:"vitr-b-n";
    return "<span class='vitr-badge $cls'>$text</span>";
}

/**
 * Duree ecoulee, compactee. Les abreviations restent volontairement non
 * traduites (meme convention que svctile_uptime) pour que la largeur de la
 * ligne reste previsible.
 */
function vitr_since($timestamp):string{
    $timestamp=intval($timestamp);
    if($timestamp<=0){return "";}
    $delta=time()-$timestamp;
    if($delta<0){$delta=0;}
    if($delta<60){return $delta."s";}
    if($delta<3600){return intval($delta/60)."mn";}
    if($delta<86400){return intval($delta/3600)."h";}
    return intval($delta/86400)."d";
}

/**
 * Prochain passage du creneau de telechargement. Le cron du daemon est
 * « *​/30 * * * * » : il tombe a :00 et a :30 de chaque heure, en heure
 * LOCALE -- jamais « trente minutes apres la derniere verification du
 * domaine ». S'ancrer sur cette derniere etait faux par construction : un
 * domaine verifie a :29 annoncait un passage a :59, alors qu'il a lieu a :30.
 *
 * Calcule sur les composantes locales, et non sur un modulo de l'epoque, pour
 * rester exact dans les fuseaux dont le decalage n'est pas un multiple de
 * trente minutes.
 */
function vitr_next_pass_at():int{
    $now=time();
    $minute=intval(date("i",$now));
    $second=intval(date("s",$now));
    $target=($minute<30)?30:60;
    return $now+($target*60-($minute*60+$second));
}

/** Duree restante, compactee. Rend "" pour une echeance deja passee. */
function vitr_until($timestamp):string{
    $timestamp=intval($timestamp);
    if($timestamp<=0){return "";}
    $delta=$timestamp-time();
    if($delta<=0){return "";}
    if($delta<60){return $delta."s";}
    if($delta<3600){return intval($delta/60)."mn";}
    if($delta<86400){return intval($delta/3600)."h";}
    return intval($delta/86400)."d";
}

/**
 * Horodatage absolu. Un instant nul n'est PAS l'epoque Unix : il se dit
 * « jamais », il ne s'affiche pas en 1970 (Pattern #50 -- une absence de
 * mesure ne produit pas une valeur).
 */
function vitr_when($timestamp):string{
    $timestamp=intval($timestamp);
    if($timestamp<=0){return "{never}";}
    return date("Y-m-d H:i",$timestamp);
}

/** En-tete de carte : pastille d'icone, titre, sous-titre, zone de droite. */
function vitr_card_head($ico,$title,$subtitle,$right="",$tone="teal",$small=true):string{
    $sz=$small?" vitr-chip-s":"";
    $h=array();
    $h[]="<div class='vitr-row-b' style='align-items:flex-start'>";
    $h[]="<div class='vitr-hd'>";
    $h[]="<span class='vitr-chip vitr-chip-$tone$sz'><i class='$ico'></i></span>";
    $h[]="<div><div class='vitr-hd-t'>$title</div><div class='vitr-hd-s'>$subtitle</div></div>";
    $h[]="</div>";
    if(strlen($right)>0){$h[]="<div style='text-align:right'>$right</div>";}
    $h[]="</div>";
    return @implode("",$h);
}

/**
 * Tronque un texte issu du daemon sans couper un caractere multi-octets.
 *
 * Rend du TEXTE BRUT, jamais du HTML : elle ajoutait une entite « &hellip; »
 * que ses appelants re-echappaient ensuite, si bien que toute erreur longue
 * se terminait par les neuf caracteres litteraux de l'entite. Le caractere
 * de suspension est donc pose tel quel, et il traverse htmlspecialchars sans
 * dommage.
 *
 * Corollaire pour l'appelant : TOUJOURS htmlspecialchars(vitr_cut($x,$n)),
 * jamais vitr_cut(htmlspecialchars($x),$n) -- couper apres echappement
 * tranche une entite en deux et laisse « &a » a l'ecran.
 */
function vitr_cut($text,$max):string{
    $text=trim(strval($text));
    if($text==""){return "";}
    if(function_exists("mb_strlen")){
        if(mb_strlen($text,"UTF-8")<=$max){return $text;}
        return mb_substr($text,0,$max-1,"UTF-8")."\xe2\x80\xa6";
    }
    if(strlen($text)<=$max){return $text;}
    return substr($text,0,$max-1)."\xe2\x80\xa6";
}

/**
 * Classement d'un LastError du daemon, par sa NATURE (LastErrorKind), qui est
 * un vocabulaire ferme publie par le daemon depuis la Task 14 bis. Deux
 * natures ne sont PAS des pannes du service cloud, et les peindre en rouge
 * enverrait l'exploitant chercher un pare-feu inexistant :
 *  - budget_exhausted : NOTRE limite d'ordonnancement, reprise au tour
 *    suivant ;
 *  - not_ready : l'etat nominal des premieres heures, le cloud n'a pas encore
 *    fini de crawler.
 *
 * Le repli sur le TEXTE ne sert qu'aux appliances dont le daemon est
 * ANTERIEUR au champ de nature -- il rend alors une chaine vide, et sans ce
 * repli tout etat deja sur disque repasserait en rouge. Sur ces versions les
 * deux libelles sont figes, donc la reconnaissance est sure ; sur les
 * versions recentes elle n'est jamais consultee.
 */
function vitr_error_tone($error,$kind=""):string{
    $error=trim(strval($error));
    if($error==""){return "";}
    $kind=strtolower(trim(strval($kind)));
    if($kind!=""){
        if($kind=="budget_exhausted"){return "n";}
        if($kind=="not_ready"){return "warn";}
        return "ko";
    }
    $lower=strtolower($error);
    if(strpos($lower,"budget exhausted")!==false){return "n";}
    if(strpos($lower,"not produced yet")!==false){return "warn";}
    return "ko";
}

/** Bloc d'erreur d'un domaine, colore selon vitr_error_tone(). */
function vitr_error_line($error,$kind=""):string{
    $tone=vitr_error_tone($error,$kind);
    if($tone==""){return "";}
    $map=array("ko"=>"vitr-e-ko","warn"=>"vitr-e-warn","n"=>"vitr-e-n");
    $cls=isset($map[$tone])?$map[$tone]:"vitr-e-n";
    $ico=($tone=="ko")?"fas fa-circle-xmark":(($tone=="warn")?"fas fa-hourglass-half":"fas fa-clock");
    return "<div class='vitr-dom-e $cls'><i class='$ico'></i>&nbsp;".
           htmlspecialchars(vitr_cut($error,220))."</div>";
}

/**
 * Traduction d'un statut de rapport de crawl. L'ensemble est FERME cote
 * daemon (completed | partial | failed, plus mirror_active pose localement
 * quand le telechargement est gele parce que le miroir est en service) :
 * une valeur inconnue s'affiche telle quelle plutot que d'etre repliee sur
 * un statut qu'elle n'a pas.
 */
function vitr_report_badge($status):string{
    $status=strtolower(trim(strval($status)));
    if($status==""){return vitr_badge("{none}","n");}
    if($status=="completed"){return vitr_badge("{vitr_crawl_completed}","ok");}
    if($status=="partial"){return vitr_badge("{vitr_crawl_partial}","warn");}
    if($status=="failed"){return vitr_badge("{vitr_crawl_failed}","ko");}
    if($status=="mirror_active"){return vitr_badge("{vitr_mirror_active_remote}","teal");}
    return vitr_badge(htmlspecialchars(vitr_cut($status,24)),"n");
}

/** Gabarit commun d'un KPI a pastille : icone a gauche, libelle/valeur/sous-titre. */
function vitr_kpi_ico($ico,$tone,$label,$value,$sub):string{
    $h=array();
    $h[]="<div class='vitr-row'>";
    $h[]="<span class='vitr-chip vitr-chip-$tone'><i class='$ico'></i></span>";
    $h[]="<div style='min-width:0'>";
    $h[]="<div class='vitr-lbl'>$label</div>";
    $h[]="<div class='vitr-val'>$value</div>";
    $h[]="<div class='vitr-sub'>$sub</div>";
    $h[]="</div></div>";
    return @implode("",$h);
}

/**
 * KPI 1 -- domaines publies. Le miroir n'est plus fabrique ici : il n'y a
 * plus de tache locale a decrire (« en cours », « pourcentage »), il y a un
 * inventaire de ce qui est effectivement servi. Les deux pannes que le
 * daemon distingue -- synchronisation cloud et lecture de l'etat local --
 * restent distinctes ici aussi : les fondre faisait afficher un chemin de
 * fichier sous l'etiquette « erreur de synchro ».
 */
function vitr_kpi_domains(array $cloud):string{
    $domains=array();
    if(isset($cloud["Domains"]) AND is_array($cloud["Domains"])){$domains=$cloud["Domains"];}
    $total=count($domains);
    $published=0;
    foreach($domains as $d){
        if(!is_array($d)){continue;}
        if(!empty($d["Published"])){$published++;}
    }
    $syncError=htmlspecialchars(vitr_cut(strval(isset($cloud["LastSyncError"])?$cloud["LastSyncError"]:""),40));
    $stateError=htmlspecialchars(vitr_cut(strval(isset($cloud["LastStateError"])?$cloud["LastStateError"]:""),40));
    $breaker=!empty($cloud["BreakerOpen"]);

    // Ordre DELIBERE : ne rien avoir pu lire prime sur tout le reste, sinon
    // on afficherait « 0/0 » -- un decompte -- pour une lecture qui n'a pas
    // eu lieu.
    if(vitr_transport_failed($cloud)){
        return vitr_kpi_ico("fas fa-plug-circle-xmark","red","{vitr_domains_published}","-",
            "{vitr_transport_error}: ".htmlspecialchars(vitr_cut($cloud["TransportError"],60)));
    }
    if($stateError!=""){
        return vitr_kpi_ico("fas fa-triangle-exclamation","red","{vitr_domains_published}","-",
            "{vitr_state_error}: $stateError");
    }
    if($syncError!=""){
        return vitr_kpi_ico("fas fa-triangle-exclamation","red","{vitr_domains_published}",
            "$published/$total","{vitr_sync_error}: $syncError");
    }
    if($breaker){
        return vitr_kpi_ico("fas fa-plug-circle-exclamation","amber","{vitr_domains_published}",
            "$published/$total","{vitr_breaker_open}");
    }
    $sync=intval(isset($cloud["LastSyncAt"])?$cloud["LastSyncAt"]:0);
    $sub=($sync>0)?"{vitr_last_sync} ".vitr_since($sync):"{vitr_never_synced}";
    $tone=($total>0 AND $published==$total)?"teal":"amber";
    if($total==0){$tone="amber";}
    $ico="fas fa-globe";

    // Le creneau de telechargement rend la main sans rien faire au-dela de
    // CPUs+2 : le dire, sinon la « prochaine verification » annonce un tour
    // qui n'a pas lieu.
    //
    // Le temoin est desormais EFFACE par le daemon des qu'un passage va a son
    // terme : sa presence decrit le present. La fenetre ne sert plus qu'aux
    // appliances dont le daemon ne l'efface pas encore, et vaut donc une
    // periode de creneau -- pas deux heures, pendant lesquelles plusieurs
    // passages sains ont pu se succeder. Et il s'AJOUTE a la derniere
    // synchronisation au lieu de la masquer : l'une ne remplace pas l'autre.
    $skipAt=intval(isset($cloud["LastFetchSkipAt"])?$cloud["LastFetchSkipAt"]:0);
    if($skipAt>0 AND (time()-$skipAt)<2100){
        $why=htmlspecialchars(vitr_cut(strval(isset($cloud["LastFetchSkipWhy"])?$cloud["LastFetchSkipWhy"]:""),50));
        $sub=$sub." &middot; {vitr_fetch_skipped}".(($why=="")?"":": $why");
        $tone="amber";
        $ico="fas fa-gauge-high";
    }
    return vitr_kpi_ico($ico,$tone,"{vitr_domains_published}","$published/$total",$sub);
}

/**
 * KPI 2 -- occupation du miroir local. Il n'y a PLUS de taille maximale :
 * le reglage a disparu du daemon et la reponse de /vitrification/storage/status
 * ne porte plus MaxSizeBytes. Une jauge sans plafond n'aurait rien mesure,
 * donc il n'y a plus de jauge -- et un total a -1 (mesure impossible) ne
 * s'affiche jamais comme « 0 octet » (Pattern #50).
 */
function vitr_kpi_storage(array $storage):string{
    $total=floatval(isset($storage["TotalSizeBytes"])?$storage["TotalSizeBytes"]:0);
    $readError=htmlspecialchars(vitr_cut(strval(isset($storage["ReadError"])?$storage["ReadError"]:""),40));
    $count=0;
    if(isset($storage["Domains"]) AND is_array($storage["Domains"])){$count=count($storage["Domains"]);}
    if(vitr_transport_failed($storage)){
        return vitr_kpi_ico("fas fa-plug-circle-xmark","red","{vitr_mirror_storage}","-",
            "{vitr_transport_error}: ".htmlspecialchars(vitr_cut($storage["TransportError"],60)));
    }
    // ReadError est PARTIEL cote daemon : le total additionne les domaines
    // reellement mesures, et le temoin ne concerne que les autres. Quatre
    // domaines mesures sur cinq donnent donc un total REEL et un temoin non
    // vide -- afficher « non mesurable » y perdrait une taille connue a un
    // domaine pres. Non mesurable veut dire : total negatif, ou temoin
    // renseigne ET total nul (aucun domaine n'a pu etre mesure).
    if($total<0 OR ($readError!="" AND $total==0)){
        return vitr_kpi_ico("fas fa-triangle-exclamation","red","{vitr_mirror_storage}","-",
            ($readError!="")?$readError:"{vitr_storage_unreadable}");
    }
    // Total partiel : la taille est vraie, mais incomplete -- le dire.
    $sub=($readError!="")?$readError:"{vitr_domains_on_disk}: $count";
    return vitr_kpi_ico("fas fa-hard-drive","teal","{vitr_mirror_storage}",
        FormatBytes($total/1024,true),$sub);
}

/**
 * KPI 3 -- mode servi actuellement. L'interrupteur n'apparait que si au
 * moins un domaine est PUBLIE : basculer sur un miroir vide servirait du
 * vide. « Publie » se lit sur le disque (lien current + manifeste), pas sur
 * une taille -- un repertoire non mesurable rendrait -1 et le bouton aurait
 * disparu alors que le miroir est bien la.
 *
 * L'etat servi vient de $cloud["Active"], qui est l'UNION du drapeau de
 * reglage et de la configuration nginx reellement deployee : le drapeau seul
 * passe par articarest, et un service de reglages injoignable aurait affiche
 * « direct » pendant que nginx sert le miroir.
 */
function vitr_kpi_mode(array $cloud,array $storage,$ID):string{
    $page=CurrentPageName();
    $ID=intval($ID);
    // Rien n'a pu etre lu : on ne sait PAS quel mode est servi. Affirmer
    // « source directe » et montrer un interrupteur eteint -- juste au-dessus
    // de la ligne qui nomme la panne -- etait le meme defaut que celui des
    // autres indicateurs, sur un chemin qui l'avait manque. Et un
    // interrupteur cliquable sur un etat inconnu invite a une bascule decidee
    // sur rien.
    if(vitr_transport_failed($cloud) AND vitr_transport_failed($storage)){
        $h=array();
        $h[]="<div class='vitr-row-b'>";
        $h[]="<div><div class='vitr-lbl'>{vitr_current_mode}</div>";
        $h[]="<div class='vitr-val'><span class='vitr-dot vitr-dot-n'></span>&nbsp;-</div>";
        $h[]="<div class='vitr-sub'>{vitr_transport_error}</div></div>";
        $h[]=vitr_badge("{vitr_unreadable}","ko");
        $h[]="</div>";
        return @implode("",$h);
    }
    $vitrified=!empty($cloud["Active"]);
    if(!isset($cloud["Active"])){$vitrified=!empty($storage["IsVitrified"]);}
    $published=0;
    if(isset($cloud["Domains"]) AND is_array($cloud["Domains"])){
        foreach($cloud["Domains"] as $d){
            if(is_array($d) AND !empty($d["Published"])){$published++;}
        }
    }
    $dot=$vitrified?"vitr-dot-ok":"vitr-dot-n";
    $value=$vitrified?"{vitrified_mode}":"{vitr_direct_source}";
    // Miroir en service = telechargements GELES (fetchSite rend « frozen »
    // pour chaque domaine) : le dire ici, a cote du mode, sinon l'exploitant
    // attend une mise a jour qui ne viendra pas tant qu'il ne repasse pas
    // en direct.
    $sub=$vitrified?"{vitr_serving_static} &middot; {vitr_updates_paused}":"{vitr_mirror_idle}";

    $right=vitr_badge("{empty}","n");
    if($published>0 OR $vitrified){
        $target=$vitrified?"vitrification-off":"vitrification-on";
        $right=vitr_switch_js($vitrified?1:0,"Loadjs('$page?$target=$ID');");
    }
    $h=array();
    $h[]="<div class='vitr-row-b'>";
    $h[]="<div><div class='vitr-lbl'>{vitr_current_mode}</div>";
    $h[]="<div class='vitr-val'><span class='vitr-dot $dot'></span>&nbsp;$value</div>";
    $h[]="<div class='vitr-sub'>$sub</div></div>";
    $h[]=$right;
    $h[]="</div>";
    return @implode("",$h);
}

/**
 * KPI 4 -- declencheur. Il n'y a plus d'instantane a lancer ni de tache a
 * arreter : le miroir est fabrique par le service cloud, ce bouton demande
 * son TELECHARGEMENT immediat. On n'interrompt pas un telechargement
 * distant, il n'y a donc pas de bouton d'arret.
 */
function vitr_kpi_action(array $cloud,$ID):string{
    $page=CurrentPageName();
    $ID=intval($ID);
    return "<a class='vitr-btn vitr-btn-p vitr-btn-w' ".
           "OnClick=\"Loadjs('$page?vitrification-fetch=$ID');\">".
           "<i class='fas fa-cloud-arrow-down'></i>&nbsp;{vitr_fetch_now}</a>";
}

/** Bandeau superieur : assemble les 4 KPI dans une seule carte. */
function vitr_strip(array $items):string{
    $h=array();
    $h[]="<div class='vitr-card'><div class='vitr-strip'>";
    $last=count($items)-1;
    foreach($items as $index=>$content){
        $cls="vitr-kpi";
        if($index==0){$cls=$cls." vitr-kpi-f";}
        if($index==$last){$cls=$cls." vitr-kpi-l";}
        $h[]="<div class='$cls'>$content</div>";
    }
    $h[]="</div></div>";
    return @implode("",$h);
}

/**
 * Une ligne de domaine : ce qui est publie, ou pourquoi rien ne l'est.
 * Rien n'est invente : un instant nul se dit « jamais » et une taille nulle
 * ne s'affiche pas comme une mesure.
 */
function vitr_domain_row(array $d):string{
    $domain=htmlspecialchars(strval(isset($d["Domain"])?$d["Domain"]:""));
    $published=!empty($d["Published"]);
    $cid=strval(isset($d["ContentID"])?$d["ContentID"]:"");
    $short=($cid=="")?"":htmlspecialchars(substr(str_replace("sha256:","",$cid),0,12));
    $size=floatval(isset($d["PackageBytes"])?$d["PackageBytes"]:0);
    $err=strval(isset($d["LastError"])?$d["LastError"]:"");
    $kind=strval(isset($d["LastErrorKind"])?$d["LastErrorKind"]:"");
    $report=strval(isset($d["ReportStatus"])?$d["ReportStatus"]:"");
    $publishedAt=intval(isset($d["PublishedAt"])?$d["PublishedAt"]:0);
    $downloadedAt=intval(isset($d["LastDownloadAt"])?$d["LastDownloadAt"]:0);
    $lastCheck=intval(isset($d["LastCheckAt"])?$d["LastCheckAt"]:0);

    $badge=$published?vitr_badge("{vitr_published}","ok"):vitr_badge("{vitr_not_published}","ko");
    if(strtolower($report)=="mirror_active"){$badge=vitr_badge("{vitr_mirror_active_remote}","teal");}

    $right=($short=="")?"":"<span class='vitr-mono' style='color:#64748b;font-size:11px'>$short</span>";

    $meta=array();
    // « Publie le » ne s'affiche QUE si le miroir l'est vraiment. La pastille
    // se lit sur le DISQUE (lien current + manifeste), la date vient de
    // l'ETAT : un vidage du miroir laisse l'etat intact, et la ligne
    // affichait donc une date de publication sous une pastille « non
    // publie ». Quand rien n'est publie, c'est la date de TELECHARGEMENT --
    // que le daemon publie -- qui informe, sous son propre nom.
    if($published){
        $meta[]="<span>{vitr_published}:&nbsp;<b>".vitr_when($publishedAt)."</b></span>";
    }elseif($downloadedAt>0){
        $meta[]="<span>{vitr_last_download}:&nbsp;<b>".vitr_when($downloadedAt)."</b></span>";
    }
    if($size>0){$meta[]="<span>{size}:&nbsp;<b>".FormatBytes($size/1024,true)."</b></span>";}
    $meta[]="<span>{vitr_report_status}:&nbsp;".vitr_report_badge($report)."</span>";
    $meta[]="<span>{vitr_last_check}:&nbsp;<b>".vitr_when($lastCheck)."</b></span>";
    // La prochaine verification est celle du CRENEAU, pas « la derniere plus
    // trente minutes » : la minuterie tombe aux bornes absolues de l'heure.
    $rest=vitr_until(vitr_next_pass_at());
    $meta[]="<span>{vitr_next_check}:&nbsp;<b>".(($rest=="")?"{vitr_next_pass}":$rest)."</b></span>";

    $h=array();
    $h[]="<div class='vitr-dom'>";
    $h[]="<div class='vitr-row-b'>";
    $h[]="<span class='vitr-row' style='gap:8px;min-width:0'>";
    $h[]="<i class='fas fa-globe' style='color:#0d9488'></i>";
    $h[]="<span class='vitr-dom-n'>$domain</span></span>";
    $h[]="<span class='vitr-row' style='gap:8px'>$right$badge</span>";
    $h[]="</div>";
    $h[]="<div class='vitr-dom-m'>".@implode("",$meta)."</div>";
    $h[]=vitr_error_line($err,$kind);
    $h[]="</div>";
    return @implode("",$h);
}

/** Chemin du miroir sur disque, tire de la reponse /vitrification/storage/status. */
function vitr_target_path(array $storage):string{
    if(!isset($storage["Domains"]) OR !is_array($storage["Domains"])){return "";}
    $paths=array();
    foreach($storage["Domains"] as $domain=>$entry){
        if(!is_array($entry)){continue;}
        if(empty($entry["Exists"])){continue;}
        $path=strval(isset($entry["Path"])?$entry["Path"]:"");
        if($path==""){continue;}
        $paths[]=$path;
    }
    if(count($paths)==0){return "";}
    $first=htmlspecialchars($paths[0]);
    if(count($paths)==1){return $first;}
    $more=count($paths)-1;
    return "$first <span style='color:#94a3b8'>(+$more)</span>";
}

/** Ligne de pied de la carte d'introduction : etat du miroir + chemin cible. */
function vitr_mirror_line(array $storage):string{
    $total=floatval(isset($storage["TotalSizeBytes"])?$storage["TotalSizeBytes"]:0);
    $readError=trim(strval(isset($storage["ReadError"])?$storage["ReadError"]:""));
    // -1 OU un temoin de lecture renseigne : mesure impossible. Ce n'est ni
    // « synchronise » ni « vide », et un total reste a 0 quand chaque domaine
    // est illisible -- le seul -1 ne suffit donc pas a s'en apercevoir.
    if(vitr_transport_failed($storage)){
        $text="{vitr_transport_error}";
        $dot="vitr-dot-n";
    }elseif($total<0 OR ($readError!="" AND $total==0)){
        $text="{vitr_storage_unreadable}";
        $dot="vitr-dot-n";
    }else{
        $dot=($total>0)?"vitr-dot-ok":"vitr-dot-n";
        $text=($total>0)?"{vitr_mirror_synced}":"{vitr_mirror_empty}";
    }
    $path=vitr_target_path($storage);
    $h=array();
    $h[]="<span class='vitr-row' style='gap:7px'><span class='vitr-dot $dot'></span>$text</span>";
    if($path!=""){
        $h[]="<span style='color:#cbd5e1'>&middot;</span>";
        $h[]="<span class='vitr-mono' style='color:#64748b;font-size:11px'>{target}: $path</span>";
    }
    return "<div class='vitr-foot' style='justify-content:flex-start;gap:12px;color:#475569'>".
           @implode("",$h)."</div>";
}

/**
 * Carte d'introduction. Elle porte l'interrupteur maitre de la fonction :
 * il est volontairement HORS du bandeau auto-rafraichi, pour qu'un tick ne
 * puisse pas re-rendre le controle pendant que l'operateur clique dessus.
 */
function vitr_card_intro($ID,array $ligne,array $storage):string{
    $ID=intval($ID);
    // Ligne du site illisible : l'interrupteur maitre DISPARAIT. Le rendre
    // eteint afficherait un reglage qu'on n'a pas lu, et un clic ecrirait
    // « active » ou « desactive » sur la foi de cette lecture manquee.
    if(!vitr_site_readable($ligne)){
        $h=array();
        $h[]="<div class='vitr-card'>";
        $h[]=vitr_card_head("fas fa-box-archive","{vitrification}","{vitr_intro_subtitle}",
            vitr_badge("{vitr_unreadable}","ko"),"red",false);
        $h[]="<div class='vitr-dom-e vitr-e-ko'><i class='fas fa-plug-circle-xmark'></i>&nbsp;".
             "{vitr_site_unreadable}</div>";
        $h[]="</div>";
        return @implode("",$h);
    }
    $enabled=intval(isset($ligne["vitrification_enabled"])?$ligne["vitrification_enabled"]:0);
    $right="<span class='vitr-row' style='gap:9px'><span class='vitr-lbl'>{enabled}</span>".
           vitr_switch("ID:$ID|vitrification",$enabled,reload($ID))."</span>";
    $h=array();
    $h[]="<div class='vitr-card'>";
    $h[]=vitr_card_head("fas fa-box-archive","{vitrification}","{vitr_intro_subtitle}",$right,"teal",false);
    $h[]="<div class='vitr-note'>{vitrification_explain}</div>";
    $h[]="<div class='vitr-note vitr-note-g'>{vitr_cloud_explain}</div>";
    $h[]=vitr_mirror_line($storage);
    $h[]="</div>";
    return @implode("",$h);
}

/** Une ligne de condition de bascule. */
function vitr_check_row($label,$badgeText,$tone,$ok):string{
    $ico=$ok?"fas fa-check vitr-ok":"fas fa-xmark vitr-ko";
    $h=array();
    $h[]="<div class='vitr-chk'><span class='vitr-chk-l'>";
    $h[]="<i class='$ico'></i><span>$label</span></span>";
    $h[]=vitr_badge($badgeText,$tone);
    $h[]="</div>";
    return @implode("",$h);
}

/** Carte "bascule automatique". Les conditions vivent dans leur propre fragment. */
function vitr_card_autoswitch($ID,array $config):string{
    $ID=intval($ID);
    $auto=intval(isset($config["auto_switch"])?$config["auto_switch"]:0);
    $subtitle=$auto?"{vitr_continuous_monitoring}":"{vitr_monitoring_disabled}";
    $subtitle=$auto?"<span class='vitr-hd-teal'>$subtitle</span>":$subtitle;
    $right=vitr_switch("ID:$ID|auto_switch",$auto,reload($ID));
    $page=CurrentPageName();
    $h=array();
    $h[]="<div class='vitr-card'><div>";
    $h[]=vitr_card_head("fas fa-right-left","{auto_switch}",$subtitle,$right);
    $h[]="<div style='font-size:12px;color:#64748b;margin-top:12px'>{auto_switch_vitrification}</div>";
    $h[]="<div id='vitrification-conditions-$ID' style='margin-top:4px'></div>";
    $h[]="</div>";
    $h[]="<div class='vitr-foot'><span>{vitr_backend_sla_driven}</span>";
    $h[]="<span class='vitr-foot-a' OnClick=\"LoadAjaxSilent('vitrification-conditions-$ID',".
         "'$page?vitrification-conditions=$ID');\">{refresh}</span></div>";
    $h[]="</div>";
    return @implode("",$h);
}

/**
 * Barre d'actions basse de la modale. « Options avancees » a disparu avec
 * les trois reglages qu'elle portait (taille maximale du miroir, profondeur
 * de crawl, seuil de latence) : ils n'ont plus aucun effet, le crawl est
 * decide par le service cloud.
 */
function vitr_footer($ID):string{
    $page=CurrentPageName();
    $ID=intval($ID);
    $h=array();
    $h[]="<div class='vitr-actions'><div class='vitr-actions-g'>";
    $h[]="<a class='vitr-btn vitr-btn-s' OnClick=\"Loadjs('$page?vitrification-events-js=$ID');\">".
         "<i class='fas fa-file-lines'></i>&nbsp;{vitr_crawl_reports}</a>";
    $h[]="<a class='vitr-btn vitr-btn-d' OnClick=\"Loadjs('$page?vitrification-empty-js=$ID');\">".
         "<i class='fas fa-trash'></i>&nbsp;{vitr_empty_mirror}</a>";
    $h[]="</div>";
    $h[]="<a class='vitr-btn vitr-btn-s' OnClick=\"dialogInstance2.close();\">{close}</a>";
    $h[]="</div>";
    return @implode("",$h);
}

/* ===========================================================================
 *  Acces aux donnees -- une reponse REST par requete PHP, memorisee
 * ======================================================================== */

/**
 * La ligne du site, et le fait de savoir si on a pu la lire.
 *
 * socksngix::GetCache() rend un tableau VIDE aussi bien pour « la lecture a
 * echoue » que pour un site inexistant -- exactement le meme defaut que les
 * accesseurs ci-dessous, un etage plus haut. Consequence observee daemon
 * arrete : la page affirmait « la vitrification est desactivee pour ce site »,
 * une affirmation SUR LE REGLAGE alors que rien n'avait pu etre lu.
 *
 * Un site reel porte toujours des cles (ID, servicename, type...) : un
 * tableau vide n'est donc jamais une ligne de site, c'est une lecture
 * manquee. On ne touche pas a la classe partagee, on nomme le cas ici.
 */
function vitr_site_row($ID):array{
    $ID=intval($ID);
    if(isset($GLOBALS["VITR_ROW_$ID"])){return $GLOBALS["VITR_ROW_$ID"];}
    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();
    if(!is_array($ligne)){$ligne=array();}
    $GLOBALS["VITR_ROW_$ID"]=$ligne;
    return $ligne;
}

/** La ligne du site a-t-elle pu etre lue ? */
function vitr_site_readable(array $ligne):bool{
    return count($ligne)>0;
}

/**
 * Le message d'une lecture qui n'a PAS eu lieu. REST_API_NGINX enveloppe
 * indifferemment une erreur curl, un 404 (daemon anterieur a ces routes) et
 * un corps illisible dans un { Status:false, Error }. Jeter cet echec faisait
 * afficher « zero domaine publie, jamais synchronise, aucun domaine
 * declare » : un diagnostic faux et confortable, sur une appliance dont le
 * daemon ne connait simplement pas encore la route.
 *
 * TransportError est le TROISIEME temoin, distinct des deux que le daemon
 * publie (LastSyncError, la synchro ; LastStateError, la lecture de l'etat
 * local) : il dit que rien n'a pu etre lu du tout.
 */
function vitr_transport_error($response):string{
    if(is_array($response)){
        foreach(array("Error","error") as $k){
            $msg=trim(strval(isset($response[$k])?$response[$k]:""));
            if($msg!=""){return $msg;}
        }
    }
    return "{vitr_daemon_unreachable}";
}

/** Le site a-t-il une reponse exploitable, ou rien du tout ? */
function vitr_transport_failed(array $data):bool{
    return isset($data["TransportError"]) AND trim(strval($data["TransportError"]))!="";
}

/**
 * Etat cloud du site : un enregistrement par domaine declare
 * (/vitrification/fetch/status). Remplace l'ancien vitr_task() : il n'y a
 * plus de tache locale de fabrication, il y a un inventaire par domaine.
 */
function vitr_cloud($ID):array{
    $ID=intval($ID);
    if(isset($GLOBALS["VITR_CLOUD_$ID"])){return $GLOBALS["VITR_CLOUD_$ID"];}
    $out=array();
    $response=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/fetch/status/$ID"),true);
    if(is_array($response) AND !empty($response["Status"])){
        if(isset($response["Data"]) AND is_array($response["Data"])){$out=$response["Data"];}
    }else{
        $out["TransportError"]=vitr_transport_error($response);
    }
    $GLOBALS["VITR_CLOUD_$ID"]=$out;
    return $out;
}

/** Occupation disque du miroir (/vitrification/storage/status). */
function vitr_storage($ID):array{
    $ID=intval($ID);
    if(isset($GLOBALS["VITR_STORAGE_$ID"])){return $GLOBALS["VITR_STORAGE_$ID"];}
    $out=array();
    $response=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/storage/status/$ID"),true);
    if(is_array($response) AND !empty($response["Status"])){
        if(isset($response["Data"]) AND is_array($response["Data"])){$out=$response["Data"];}
    }else{
        $out["TransportError"]=vitr_transport_error($response);
    }
    $GLOBALS["VITR_STORAGE_$ID"]=$out;
    return $out;
}

/** Configuration de vitrification du site, toujours sous forme de tableau. */
function vitr_config(array $ligne):array{
    if(!isset($ligne["vitrification_config"])){return array();}
    if(!is_array($ligne["vitrification_config"])){return array();}
    return $ligne["vitrification_config"];
}

/* ===========================================================================
 *  Ouverture des boites de dialogue
 * ======================================================================== */

function vitrification_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval(isset($_GET["js"])?$_GET["js"]:(isset($_GET["vitrification-js"])?$_GET["vitrification-js"]:0));
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    return $tpl->js_dialog2("#$ID - $servicename","$page?vitrification-popup=$ID",900);
}

function vitrification_events_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-events-js"]);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    $title="#$ID - $servicename - ".$tpl->javascript_parse_text("{vitr_crawl_reports}");
    return $tpl->js_dialog5($title,"$page?vitrification-events=$ID",800);
}

/* ===========================================================================
 *  Fragments
 * ======================================================================== */

/**
 * Modale principale. Deux zones seulement sont pilotees par AJAX :
 *  - #vitrification-status-<id> : le bandeau + l'etat par domaine, rafraichi
 *    toutes les 10 s ;
 *  - #vitrification-config-<id> : la carte de reglage, chargee une fois puis
 *    rechargee apres une ecriture. Elle contient un controle : la mettre
 *    sous minuterie le re-rendrait pendant que l'operateur clique dessus.
 */
function vitrification_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-popup"]);
    $ligne=vitr_site_row($ID);
    $storage=vitr_storage($ID);

    $html=array();
    $html[]="<div class='vitr-modal'>";
    $html[]="<div id='vitrification-fetch-result-$ID'></div>";
    $html[]="<div id='vitrification-status-$ID'></div>";
    $html[]=vitr_card_intro($ID,$ligne,$storage);
    $html[]="<div id='vitrification-config-$ID'></div>";
    $html[]=vitr_footer($ID);
    $html[]="</div>";

    $script=array();
    $script[]="<script>";
    $script[]="LoadAjaxSilent('vitrification-status-$ID','$page?vitrification-status=$ID');";
    $script[]="LoadAjaxSilent('vitrification-config-$ID','$page?vitrification-config=$ID');";
    $script[]=$tpl->RefreshInterval_js("vitrification-status-$ID",$page,"vitrification-status=$ID",10);
    $script[]="</script>";

    echo vitr_style().$tpl->_ENGINE_parse_body(@implode("",$html))
        .vitr_js_compile().@implode("\n",$script);
    return true;
}

/**
 * Bandeau superieur (4 KPI) puis l'etat PAR DOMAINE. Aucun champ de saisie
 * ici, ce fragment est sous minuterie.
 */
function vitrification_status():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-status"]);
    $ligne=vitr_site_row($ID);
    // « Je n'ai pas pu lire » vient AVANT « c'est desactive » : l'inverse
    // affirme un reglage qu'on n'a pas lu.
    if(!vitr_site_readable($ligne)){
        echo $tpl->_ENGINE_parse_body("<div class='vitr-card'>".
            "<div class='vitr-dom-e vitr-e-ko' style='margin-top:0'>".
            "<i class='fas fa-plug-circle-xmark'></i>&nbsp;{vitr_site_unreadable}</div></div>");
        return true;
    }
    $enabled=intval(isset($ligne["vitrification_enabled"])?$ligne["vitrification_enabled"]:0);
    if($enabled==0){
        echo $tpl->_ENGINE_parse_body("<div class='vitr-card vitr-empty'>{vitr_disabled_notice}</div>");
        return true;
    }
    $cloud=vitr_cloud($ID);
    $storage=vitr_storage($ID);
    $items=array();
    $items[]=vitr_kpi_domains($cloud);
    $items[]=vitr_kpi_storage($storage);
    $items[]=vitr_kpi_mode($cloud,$storage,$ID);
    $items[]=vitr_kpi_action($cloud,$ID);

    // Trois cas, jamais deux : rien n'a pu etre lu / rien n'est declare /
    // voici les domaines. Replier le premier sur le deuxieme affichait
    // « aucun domaine declare » a un exploitant dont le daemon ne connait pas
    // encore la route.
    if(vitr_transport_failed($cloud)){
        $rows="<div class='vitr-dom-e vitr-e-ko' style='margin-top:0'>".
              "<i class='fas fa-plug-circle-xmark'></i>&nbsp;{vitr_transport_error}: ".
              htmlspecialchars(vitr_cut($cloud["TransportError"],300))."</div>";
    }elseif(isset($cloud["Domains"]) AND is_array($cloud["Domains"]) AND count($cloud["Domains"])>0){
        $rows=@implode("",array_map("vitr_domain_row",$cloud["Domains"]));
    }else{
        $rows="<div class='vitr-empty'>{vitr_no_domain}</div>";
    }
    // Mode vitrification actif : le daemon suspend tout telechargement de
    // miroir (une nouvelle generation remplacerait la copie que nginx sert).
    // L'avertissement precede la liste des domaines, dont chaque ligne
    // porterait sinon un « ignore : miroir en service » sans explication.
    $paused="";
    $vitrified=!empty($cloud["Active"]);
    if(!isset($cloud["Active"])){$vitrified=!empty($storage["IsVitrified"]);}
    if($vitrified AND !vitr_transport_failed($cloud)){
        $paused="<div class='vitr-dom-e vitr-e-warn' style='margin:0 0 8px 0'>".
                "<i class='fas fa-pause-circle'></i>&nbsp;{vitr_updates_paused_notice}</div>";
    }
    $card="<div class='vitr-card'>".
          vitr_card_head("fas fa-list-check","{domains}","{vitr_per_domain_subtitle}").
          $paused.$rows."</div>";

    echo $tpl->_ENGINE_parse_body(vitr_strip($items).$card).vitr_js_compile();
    return true;
}

/**
 * La carte de reglage. Une seule subsiste : l'interrupteur d'automatisme.
 * Les trois autres reglages (taille maximale, profondeur, seuil de latence)
 * n'existent plus cote daemon.
 *
 * Fragment volontairement NON VIDE quand la fonction est desactivee : un
 * fragment a zero octet est indiscernable d'une page morte (Pattern #57).
 */
function vitrification_config():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-config"]);
    $ligne=vitr_site_row($ID);
    if(!vitr_site_readable($ligne)){
        echo "<!-- site row unreadable for service $ID -->";
        return true;
    }
    if(intval(isset($ligne["vitrification_enabled"])?$ligne["vitrification_enabled"]:0)==0){
        echo "<!-- vitrification disabled for service $ID -->";
        return true;
    }
    $config=vitr_config($ligne);
    $html=array();
    $html[]="<div class='vitr-grid2'>";
    $html[]="<div class='vitr-col'>".vitr_card_autoswitch($ID,$config)."</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body(@implode("",$html)).vitr_js_compile()
        ."<script>LoadAjaxSilent('vitrification-conditions-$ID',"
        ."'$page?vitrification-conditions=$ID');</script>";
    return true;
}

/**
 * Le detail par etat. Une bascule se decide sur ces etats : l'exploitant doit
 * pouvoir les lire sans ouvrir une autre page, y compris quand la conclusion
 * est « aucun backend observable » -- c'est justement la que le decompte
 * explique pourquoi.
 */
function vitr_state_counts(array $counts):string{
    if(count($counts)==0){return "";}
    $parts=array();
    foreach($counts as $state=>$number){
        $parts[]=htmlspecialchars($state)."&nbsp;<b>".intval($number)."</b>";
    }
    return "<div class='vitr-dom-m' style='margin-top:8px'><span>".
           @implode("</span><span>",$parts)."</span></div>";
}

/**
 * Les conditions de bascule. Elles ne se lisent PLUS sur une sonde de
 * latence maison (/backends-scanner) mais sur les etats du module de
 * supervision des backends (/backend-sla/backends), qui est exactement ce
 * qui decide la bascule cote daemon : ce que la page montre est ce qui
 * decide.
 *
 * MAINTENANCE / DISABLED / REMOVED sont ecartes : ce sont des backends
 * volontairement hors service, les compter en panne declencherait une
 * bascule pour une intervention planifiee. INITIALIZING / UNKNOWN ne sont
 * pas des pannes non plus : ce sont des ABSENCES de mesure, et une absence
 * de mesure ne produit pas un verdict (Pattern #50).
 */
function vitrification_conditions():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-conditions"]);

    // Le SUCCES d'abord. Ne tester que la presence de la cle faisait lire un
    // daemon injoignable ou une route en erreur comme « aucune sonde de
    // backend » : une affirmation SUR LES BACKENDS alors qu'on n'a rien pu
    // lire (Pattern #54).
    $response=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/backend-sla/backends/$ID"),true);
    if(!is_array($response) OR empty($response["Status"])){
        echo $tpl->_ENGINE_parse_body("<div class='vitr-chk'><span class='vitr-chk-l'>".
            "<i class='fas fa-plug-circle-xmark' style='color:#dc2626'></i>".
            "<span>{vitr_transport_error}: ".
            htmlspecialchars(vitr_cut(vitr_transport_error($response),140)).
            "</span></span>".vitr_badge("{vitr_unreadable}","ko")."</div>");
        return true;
    }
    $entries=array();
    if(isset($response["backends"]) AND is_array($response["backends"])){
        $entries=$response["backends"];
    }

    // La regle est celle de Vitrification.Decide(), a la lettre :
    //  - MAINTENANCE / DISABLED / REMOVED sont ECARTES (hors service voulu) ;
    //  - INITIALIZING, UNKNOWN, mais AUSSI tout etat vide, inconnu ou futur
    //    rendent « pas mesure » et court-circuitent -- le defaut sur est
    //    l'absence de mesure, jamais « observable et en panne » ;
    //  - zero backend observable rend « aucun backend », meme quand la liste
    //    en contenait : tous en maintenance, il n'y a rien a decider.
    // Diverger sur l'un des deux derniers points faisait annoncer une bascule
    // imminente que le daemon ne fera jamais.
    $anyUp=false;$measured=true;$observable=0;$counts=array();
    foreach($entries as $entry){
        if(!is_array($entry)){continue;}
        $state=strtoupper(trim(strval(isset($entry["state"])?$entry["state"]:"")));
        $counts[($state=="")?"?":$state]=(isset($counts[($state=="")?"?":$state])?$counts[($state=="")?"?":$state]:0)+1;
        if($state=="MAINTENANCE" OR $state=="DISABLED" OR $state=="REMOVED"){continue;}
        if($state=="UP"){$anyUp=true;$observable++;continue;}
        if($state=="DOWN" OR $state=="DEGRADED"){$observable++;continue;}
        // INITIALIZING, UNKNOWN, "" et tout etat que le daemon nommerait
        // demain : pas mesure.
        $measured=false;
        $observable++;
    }
    if($observable==0){
        echo $tpl->_ENGINE_parse_body("<div class='vitr-chk'><span class='vitr-chk-l'>".
            "<i class='fas fa-circle-question' style='color:#94a3b8'></i>".
            "<span>{vitr_no_backend_probe}</span></span>".vitr_badge("{none}","n")."</div>".
            vitr_state_counts($counts));
        return true;
    }

    $cloud=vitr_cloud($ID);
    $mirror=false;
    if(isset($cloud["Domains"]) AND is_array($cloud["Domains"])){
        foreach($cloud["Domains"] as $d){
            if(is_array($d) AND !empty($d["Published"])){$mirror=true;break;}
        }
    }

    $html=array();
    $html[]=vitr_check_row("{vitr_backend_reachable}",
        $anyUp?"{valid}":"{vitr_unreachable}",$anyUp?"ok":"ko",$anyUp);
    $html[]=vitr_check_row("{vitr_backends_measured}",
        $measured?"{valid}":"{vitr_not_measured}",$measured?"teal":"n",$measured);
    $html[]=vitr_check_row("{vitr_mirror_available}",
        $mirror?"{vitr_ready}":"{empty}",$mirror?"ok":"ko",$mirror);

    $html[]=vitr_state_counts($counts);
    echo $tpl->_ENGINE_parse_body(@implode("",$html));
    return true;
}

/* ===========================================================================
 *  Rapports de crawl (ex-« historique des instantanes »)
 * ======================================================================== */

/**
 * Une valeur du rapport de crawl. Le rapport est ecrit par le service
 * cloud et recopie TEL QUEL par le daemon : une cle absente s'affiche « - »,
 * jamais 0 -- « zero page enregistree » et « le rapport ne dit rien du
 * nombre de pages » sont deux faits differents (Pattern #50).
 */
function vitr_rep_val(array $report,$key,$bytes=false):string{
    if(!array_key_exists($key,$report)){return "-";}
    $value=$report[$key];
    if($value===null OR is_array($value) OR is_object($value)){return "-";}
    if(is_bool($value)){return $value?"true":"false";}
    if($bytes){return FormatBytes(floatval($value)/1024,true);}
    $text=trim(strval($value));
    if($text==""){return "-";}
    return htmlspecialchars(vitr_cut($text,120));
}

/** Une case du tableau de bord d'un rapport. */
function vitr_rep_kv($label,$value):string{
    return "<div class='vitr-kv-i'><div class='vitr-kv-k'>$label</div>".
           "<div class='vitr-kv-v'>$value</div></div>";
}

/**
 * Le rapport de crawl d'UN domaine. Vingt liens ignores au plus : la liste
 * est un echantillon lisible, pas un export -- le rapport complet reste sur
 * disque.
 */
function vitr_report_card($domain,$report):string{
    $domain=htmlspecialchars(strval($domain));
    if(!is_array($report)){
        return "<div class='vitr-card'>".
               vitr_card_head("fas fa-globe",$domain,"{vitr_report_unreadable}").
               "</div>";
    }
    $status=isset($report["status"])?strval($report["status"]):"";

    $h=array();
    $h[]="<div class='vitr-card'>";
    $h[]=vitr_card_head("fas fa-globe",$domain,"{vitr_report_status}",vitr_report_badge($status));
    $h[]="<div class='vitr-kv'>";
    $h[]=vitr_rep_kv("{status}",($status=="")?"-":htmlspecialchars(vitr_cut($status,24)));
    $h[]=vitr_rep_kv("{vitr_pages_saved}",vitr_rep_val($report,"pages_saved"));
    $h[]=vitr_rep_kv("{vitr_css_saved}",vitr_rep_val($report,"css_saved"));
    $h[]=vitr_rep_kv("{vitr_js_saved}",vitr_rep_val($report,"javascript_saved"));
    $h[]=vitr_rep_kv("{vitr_bytes_stored}",vitr_rep_val($report,"bytes_stored",true));
    $h[]=vitr_rep_kv("{vitr_partial_reason}",vitr_rep_val($report,"partial_reason"));
    $h[]="</div>";

    $ignored=array();
    if(isset($report["ignored_links"]) AND is_array($report["ignored_links"])){
        $ignored=$report["ignored_links"];
    }
    $total=count($ignored);
    $shown=array_slice($ignored,0,20);
    $h[]="<div style='margin-top:14px'><span class='vitr-lbl'>{vitr_ignored_links}</span>";
    $h[]="&nbsp;<span class='vitr-tag'>$total</span></div>";
    if($total==0){
        $h[]="<div class='vitr-lst'><div class='vitr-empty' style='padding:14px'>{none}</div></div>";
    }else{
        $h[]="<div class='vitr-lst'>";
        $first=true;
        foreach($shown as $link){
            if(!is_array($link)){continue;}
            $url=htmlspecialchars(vitr_cut(strval(isset($link["url"])?$link["url"]:""),160));
            $reason=htmlspecialchars(vitr_cut(strval(isset($link["reason"])?$link["reason"]:""),40));
            $occ="";
            if(isset($link["occurrences"]) AND intval($link["occurrences"])>1){
                $occ="&nbsp;&times;".intval($link["occurrences"]);
            }
            if($url==""){$url="-";}
            if($reason==""){$reason="-";}
            $cls=$first?"vitr-lst-r vitr-lst-f":"vitr-lst-r";
            $h[]="<div class='$cls'><span class='vitr-lst-u vitr-mono'>$url</span>".
                 "<span class='vitr-lst-t'>$reason$occ</span></div>";
            $first=false;
        }
        $h[]="</div>";
        if($total>20){
            $more=$total-20;
            $h[]="<div class='vitr-sub' style='margin-top:6px'>+$more</div>";
        }
    }

    $errors=array();
    if(isset($report["errors"]) AND is_array($report["errors"])){$errors=$report["errors"];}
    if(count($errors)>0){
        $h[]="<div style='margin-top:14px'><span class='vitr-lbl'>{errors}</span></div>";
        $h[]="<div class='vitr-lst'>";
        $first=true;
        foreach($errors as $error){
            if(is_array($error)){$error=@json_encode($error);}
            $text=htmlspecialchars(vitr_cut(strval($error),240));
            if($text==""){continue;}
            $cls=$first?"vitr-lst-r vitr-lst-f":"vitr-lst-r";
            $h[]="<div class='$cls'><span class='vitr-lst-u' style='color:#b91c1c'>$text</span></div>";
            $first=false;
        }
        $h[]="</div>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

/**
 * L'historique n'est plus un journal de fetch local : c'est le RAPPORT DE
 * CRAWL rendu par le service cloud, un par domaine.
 * /vitrification/fetch/events rend une CARTE domaine -> report.json et
 * ignore desormais tout parametre de limite -- il n'est donc plus envoye.
 */
function vitrification_events():bool{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-events"]);
    $reports=array();
    $failure="";
    $response=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX(
        "/vitrification/fetch/events/$ID"),true);
    if(is_array($response) AND !empty($response["Status"])){
        if(isset($response["Data"]) AND is_array($response["Data"])){$reports=$response["Data"];}
    }else{
        // Une lecture qui a echoue n'est pas « aucun rapport » (Pattern #54).
        $failure=strval(isset($response["Error"])?$response["Error"]:"");
        if($failure==""){$failure="{vitr_report_unreadable}";}
    }

    $html=array();
    $html[]="<div class='vitr-modal'>";
    if($failure!=""){
        $html[]="<div class='vitr-card'><div class='vitr-dom-e vitr-e-ko' style='margin-top:0'>".
                "<i class='fas fa-circle-xmark'></i>&nbsp;".
                htmlspecialchars(vitr_cut($failure,300))."</div></div>";
    }elseif(count($reports)==0){
        $html[]="<div class='vitr-card'><div class='vitr-empty'>{vitr_no_report}</div></div>";
    }else{
        ksort($reports);
        foreach($reports as $domain=>$report){
            $html[]=vitr_report_card($domain,$report);
        }
    }
    $html[]="</div>";
    echo vitr_style().$tpl->_ENGINE_parse_body(@implode("",$html));
    return true;
}

/* ===========================================================================
 *  Ecritures et actions
 * ======================================================================== */

/** Rafraichissements a jouer apres une ecriture : la ligne du site, puis la modale. */
function reload($ID):string{
    $page=CurrentPageName();
    $ID=intval($ID);
    $f=array();
    $f[]="LoadAjax('www-parameters-$ID','fw.nginx.sites.php?www-parameters2=$ID');";
    $f[]="LoadAjaxSilent('vitrification-config-$ID','$page?vitrification-config=$ID');";
    $f[]="LoadAjaxSilent('vitrification-status-$ID','$page?vitrification-status=$ID');";
    return @implode("",$f);
}

function vitrification_save():bool{
    $ID=intval($_POST["ID"]);
    $vitrification=intval($_POST["vitrification"]);
    $sock=new socksngix($ID);
    $sock->SET_INFO("VitrificationEnabled",$vitrification);
    $servicename=$sock->GetServiceName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/chock/$ID");
    return admin_tracks("Set vitrification feature to $vitrification for $servicename");
}

/**
 * Seul auto_switch subsiste. max_dirsize_mb / vitr_depth / max_latency_ms
 * ne sont plus lus par le daemon : les reecrire ici laisserait dans la base
 * des reglages que plus rien n'honore, et la console les afficherait comme
 * s'ils agissaient encore.
 */
function vitrification_config_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);
    $sock=new socksngix($ID);
    $Conf=json_decode($sock->GET_INFO("VitrificationConfig"),true);
    if(!is_array($Conf)){$Conf=array();}
    $Conf=array("auto_switch"=>isset($Conf["auto_switch"])?intval($Conf["auto_switch"]):0);
    if(isset($_POST["auto_switch"])){$Conf["auto_switch"]=intval($_POST["auto_switch"]);}

    $sock->SET_INFO("VitrificationConfig",json_encode($Conf));
    $hostname=$sock->GetServiceName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/flush-cache");
    return admin_tracks_post("Save Vitrification configuration for site $hostname");
}

/**
 * Emet le compte rendu d'une action dans le bloc dedie de la modale, PUIS
 * rafraichit. json_encode() est la seule facon sure de faire traverser du
 * HTML dans un litteral JavaScript.
 *
 * $html vide efface le bloc : une action qui reussit ne laisse pas a l'ecran
 * le refus de la precedente.
 */
function vitr_action_report($ID,$html):string{
    $page=CurrentPageName();
    $ID=intval($ID);
    $js="(function(){var e=document.getElementById('vitrification-fetch-result-$ID');".
        "if(e){e.innerHTML=".json_encode($html).";}})();";
    return $js."LoadAjaxSilent('vitrification-status-$ID','$page?vitrification-status=$ID');";
}

/**
 * Bandeau de refus d'une action. $tone : ko (refus) ou n (fait, sans drame).
 */
function vitr_action_error($message,$tone="ko"):string{
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $cls=($tone=="ko")?"vitr-e-ko":"vitr-e-n";
    $ico=($tone=="ko")?"fas fa-circle-xmark":"fas fa-circle-info";
    return $tpl->_ENGINE_parse_body("<div class='vitr-card'><div class='$cls vitr-dom-e' ".
        "style='margin-top:0'><i class='$ico'></i>&nbsp;".
        htmlspecialchars(vitr_cut($message,300))."</div></div>");
}

/**
 * Notre client a-t-il RENONCE A ATTENDRE ? Ce n'est jamais un refus : le
 * daemon poursuit son travail, son contexte etant independant de la requete
 * HTTP. Partage par la route de telechargement et par les trois actions.
 */
function vitr_is_timeout($message):bool{
    $message=strval($message);
    return (stripos($message,"timed out")!==false
         OR stripos($message,"timeout")!==false
         OR stripos($message,"operation too slow")!==false
         OR stripos($message,"Operation timed out")!==false);
}

/**
 * L'issue d'une action SYNCHRONE, a trois etats -- et c'est le troisieme qui
 * manquait.
 *
 * Les trois routes (bascule on/off, vidage) refusent avec un motif nomme :
 * « no published snapshot », « mirror directory unreadable », « currently
 * served from its mirror ». Mais le client generaliste de la console coupe a
 * 13 s, et une bascule depasse legitimement ce delai : reconstruction plus
 * attente de confirmation cumulent une vingtaine de secondes, et le verrou de
 * construction peut faire patienter bien davantage quand un autre processus
 * reconstruit en boucle. Traiter cette coupure comme un refus affirmait en
 * rouge l'echec d'une bascule qui avait eu lieu -- et faisait sauter sa trace
 * d'audit.
 *
 *   ok=true                  -> le daemon a repondu, l'action a reussi
 *   ok=false, timeout=true   -> NOUS avons renonce ; l'action se poursuit
 *   ok=false, timeout=false  -> le daemon a REFUSE, avec son motif
 */
function vitr_action_outcome($raw):array{
    $response=json_decode($raw,true);
    if(is_array($response) AND !empty($response["Status"])){
        return array("ok"=>true,"timeout"=>false,"message"=>"");
    }
    $message=vitr_transport_error($response);
    return array("ok"=>false,"timeout"=>vitr_is_timeout($message),"message"=>$message);
}

/**
 * Execute une action REST synchrone en accordant au daemon plus que les 13 s
 * du client generaliste, puis restaure le delai -- la propriete est portee
 * par l'objet partage de la requete.
 */
function vitr_rest_action($endpoint):array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $previous=$sock->REST_API_TIMEOUT;
    $sock->REST_API_TIMEOUT=45;
    $raw=$sock->REST_API_NGINX($endpoint);
    $sock->REST_API_TIMEOUT=$previous;
    return vitr_action_outcome($raw);
}

/**
 * Rend le compte rendu et dit s'il faut journaliser. Une action qui a
 * seulement DEPASSE le delai a bien eu lieu : sa trace d'audit ne doit pas
 * disparaitre avec l'attente de son accuse de reception.
 */
function vitr_action_emit($ID,array $out):bool{
    if($out["timeout"]){
        echo vitr_action_report($ID,vitr_action_error("{vitr_action_still_running}","n"));
        return true;
    }
    if(!$out["ok"]){
        echo vitr_action_report($ID,vitr_action_error($out["message"]));
        return false;
    }
    echo vitr_action_report($ID,"");
    return true;
}

/**
 * SwitchOn/SwitchOff sont SYNCHRONES : ils reconstruisent la configuration,
 * rechargent nginx, ATTENDENT que la conf deployee le confirme
 * (waitNginxVitrified), et ne rendent qu'ensuite -- avec un refus nomme le
 * cas echeant. Il n'y a donc rien a temporiser : la reponse EST le resultat,
 * et l'ancienne attente de trois secondes ne faisait que masquer le fait
 * qu'on ne la lisait pas.
 */
function vitrification_on():bool{
    $ID=intval($_GET["vitrification-on"]);
    $sockngix=new socksngix($ID);
    $hostname=$sockngix->GetServiceName();
    $out=vitr_rest_action("/vitrification/switch/on/$ID");
    header("content-type: application/x-javascript");
    if(!vitr_action_emit($ID,$out)){return true;}
    return admin_tracks("Activate $hostname to use vitrification mode");
}

function vitrification_off():bool{
    $ID=intval($_GET["vitrification-off"]);
    $sockngix=new socksngix($ID);
    $hostname=$sockngix->GetServiceName();
    $out=vitr_rest_action("/vitrification/switch/off/$ID");
    header("content-type: application/x-javascript");
    if(!vitr_action_emit($ID,$out)){return true;}
    return admin_tracks("Back $hostname to use backend mode");
}

/**
 * Libelle d'une issue de telechargement. L'ensemble est FERME cote daemon.
 * Deux d'entre elles ne sont PAS des pannes du service cloud et ne doivent
 * pas se peindre en rouge :
 *  - budget_exhausted : NOTRE budget de temps est epuise, le domaine est
 *    repris au tour suivant ;
 *  - in_progress : un telechargement tourne deja pour ce site.
 * Les afficher en panne enverrait l'exploitant chercher un pare-feu qui
 * n'existe pas.
 */
function vitr_outcome_label($outcome):array{
    $outcome=strtolower(trim(strval($outcome)));
    $map=array(
        "published"       =>array("{vitr_outcome_published}","ok"),
        "unchanged"       =>array("{vitr_outcome_unchanged}","teal"),
        "not_ready"       =>array("{vitr_outcome_not_ready}","warn"),
        "frozen"          =>array("{vitr_mirror_active_remote}","teal"),
        "rejected"        =>array("{vitr_outcome_rejected}","ko"),
        "unreachable"     =>array("{vitr_outcome_unreachable}","ko"),
        "budget_exhausted"=>array("{vitr_outcome_budget}","n"),
        "in_progress"     =>array("{vitr_outcome_running}","n"));
    if(isset($map[$outcome])){return $map[$outcome];}
    return array(htmlspecialchars(vitr_cut($outcome,40)),"n");
}

/**
 * « Fetch now » : telechargement immediat, SYNCHRONE cote daemon (la route
 * est bornee par son propre budget). La reponse porte une issue par domaine,
 * et deux d'entre elles ne se persistent nulle part : sans les afficher,
 * l'operateur cliquerait et ne verrait rien changer.
 *
 * Le compte rendu est injecte par json_encode() : c'est la seule facon sure
 * de faire traverser du HTML dans un litteral JavaScript.
 */
function vitrification_fetch():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-fetch"]);
    $sockngix=new socksngix($ID);
    $hostname=$sockngix->GetServiceName();
    // 70 s, contre 30 par defaut : le budget de CETTE route cote daemon est
    // de 60 s (fetchAPITimeout). Couper avant lui n'annulait rien -- son
    // contexte est independant de la requete -- et affichait donc « la
    // demande a ete refusee » pendant que le miroir se publiait trois
    // secondes plus tard.
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/vitrification/cloud/fetch/$ID",array(),70);
    $response=json_decode($raw,true);

    $rows=array();
    if(!is_array($response) OR empty($response["Status"])){
        $error=vitr_transport_error($response);
        // Un depassement de delai n'est PAS un refus : le daemon poursuit son
        // telechargement, seul notre client a renonce a attendre. Le dire en
        // neutre, et inviter a rafraichir -- l'issue apparaitra dans l'etat
        // par domaine.
        if(vitr_is_timeout($error)){
            $rows[]="<div class='vitr-dom-e vitr-e-n' style='margin-top:0'>".
                    "<i class='fas fa-hourglass-half'></i>&nbsp;{vitr_fetch_still_running}</div>";
        }else{
            if($error==""){$error="{vitr_fetch_failed}";}
            $rows[]="<div class='vitr-dom-e vitr-e-ko' style='margin-top:0'>".
                    "<i class='fas fa-circle-xmark'></i>&nbsp;".
                    htmlspecialchars(vitr_cut($error,300))."</div>";
        }
    }else{
        $results=array();
        if(isset($response["Data"]) AND is_array($response["Data"])){$results=$response["Data"];}
        if(count($results)==0){
            $rows[]="<div class='vitr-dom-e vitr-e-n' style='margin-top:0'>{vitr_no_domain}</div>";
        }
        foreach($results as $result){
            if(!is_array($result)){continue;}
            $domain=htmlspecialchars(vitr_cut(strval(isset($result["domain"])?$result["domain"]:""),80));
            $label=vitr_outcome_label(isset($result["outcome"])?$result["outcome"]:"");
            $detail=htmlspecialchars(vitr_cut(strval(isset($result["detail"])?$result["detail"]:""),160));
            $sub=($detail!="")?"<div class='vitr-sub'>$detail</div>":"";
            $rows[]="<div class='vitr-chk'><span class='vitr-chk-l' style='min-width:0'>".
                    "<span><b>$domain</b>$sub</span></span>".
                    vitr_badge($label[0],$label[1])."</div>";
        }
    }
    $html="<div class='vitr-card'>".
          vitr_card_head("fas fa-cloud-arrow-down","{vitr_fetch_result}","{vitr_fetch_now}").
          @implode("",$rows)."</div>";

    header("content-type: application/x-javascript");
    echo "(function(){var e=document.getElementById('vitrification-fetch-result-$ID');".
         "if(e){e.innerHTML=".json_encode($tpl->_ENGINE_parse_body($html)).";}})();";
    echo "LoadAjaxSilent('vitrification-status-$ID','$page?vitrification-status=$ID');";
    return admin_tracks("Fetch vitrification package for $hostname");
}

function vitrification_empty_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-empty-js"]);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    $title=addslashes($tpl->javascript_parse_text("{vitr_empty_mirror}"));
    $text=addslashes($tpl->javascript_parse_text("{vitr_empty_mirror_confirm}")." ($servicename)");
    $confirm=addslashes($tpl->javascript_parse_text("{delete}"));
    $cancel=addslashes($tpl->javascript_parse_text("{cancel}"));
    header("content-type: application/x-javascript");
    echo "swal({title:'$title',text:'$text',type:'warning',showCancelButton:true,".
         "confirmButtonColor:'#ed5565',confirmButtonText:'$confirm',cancelButtonText:'$cancel'},".
         "function(isConfirm){if(!isConfirm){return;}".
         "Loadjs('$page?vitrification-empty=$ID');});";
    return true;
}

/**
 * Le vidage est SYNCHRONE et refuse tant que nginx SERT le miroir -- sinon le
 * site repondrait en erreur sur toutes ses URL, sans bascule de retour. Ce
 * refus doit se lire : l'exploitant vient de confirmer une boite rouge.
 */
function vitrification_empty():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["vitrification-empty"]);
    $sockngix=new socksngix($ID);
    $hostname=$sockngix->GetServiceName();
    $out=vitr_rest_action("/vitrification/storage/empty/$ID");
    header("content-type: application/x-javascript");
    $logIt=vitr_action_emit($ID,$out);
    echo "LoadAjaxSilent('vitrification-config-$ID','$page?vitrification-config=$ID');";
    if(!$logIt){return true;}
    return admin_tracks("Empty vitrification mirror for $hostname");
}
